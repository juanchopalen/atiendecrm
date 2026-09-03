# Flujo de trabajo de AtiendeCRM

> Documentación generada a partir del código fuente (`app/`, `routes/`, `lang/es/`). AtiendeCRM es un CRM multi-tenant para correduría de seguros, construido en Laravel 13 + Filament 5. Centraliza clientes, pólizas y casos de servicio, y atiende consultas entrantes por WhatsApp con un agente de IA (Gemini) que consulta datos reales del cliente antes de responder.
>
> Stack: Laravel 13, Filament 5, Spatie Permission/Shield, Gemini API.
> Si el negocio cambia, este documento debe regenerarse a partir del código — no está sincronizado automáticamente.

## 0. Panorama general

El flujo central conecta cuatro cosas: una correduría (tenant), sus clientes, las pólizas de esos clientes, y los casos de servicio que se abren sobre esas pólizas — ya sea creados manualmente por un agente humano o disparados por un mensaje de WhatsApp que el agente de IA no pudo resolver solo.

```
Correduría (Tenant) → Cliente → Póliza → Caso (Ticket) → Cierre + encuesta
```

## 1. Multi-tenant y acceso

El panel de administración (`/admin`) es multi-tenant sobre el modelo `Tenant`, identificado por `slug`. Un `TenantScope` global filtra automáticamente clientes, pólizas, casos, pagos, canales de WhatsApp y documentos de conocimiento por el tenant activo — un usuario nunca ve datos de otra correduría.

El rol `super-admin` es la única excepción a este aislamiento: `User::getTenants()` le permite ver y operar sobre todas las corredurías desde el selector del panel. Cualquier otro rol queda fijo a su propio `tenant_id`.

> **Excepción — modo demo:** existe un número de WhatsApp compartido entre corredurías, marcado `solo_demo = true`, usado solo para ventas/demos. Los flujos de notificación en producción lo excluyen explícitamente.

### Configuración por tenant

Cada `Tenant` tiene un campo JSON `features`. Hoy controla cuántos días antes del vencimiento de una póliza se notifica al cliente (`notifications.days_to_pay`), pero es el punto de extensión para configuración específica de cada correduría.

## 2. Modelo de datos

Todas las entidades operativas heredan aislamiento de tenant (vía el trait `BelongsToTenant`) salvo `Tenant` y `User` mismos.

| Entidad | Descripción | Relaciones | Campos rellenables |
|---|---|---|---|
| **Client** | Persona asegurada de una correduría | hasMany Policy, Ticket, Payment | `name`, `national_id`, `phone`, `email`, `address` |
| **Policy** | Contrato de seguro asociado a un cliente | belongsTo Client · hasMany Ticket, Payment | `policy_number`, `line_of_business`, `insurer`, `start_date`/`expiration_date`, `premium`, `payment_frequency` |
| **Ticket** ("Caso") | Solicitud de servicio abierta por o para un cliente | belongsTo Client, Policy, Agent (User) · hasMany Interaction | `type`, `subject`, `description`, `priority`, `status`, `closed_at` |
| **Interaction** | Mensaje individual dentro de un caso (bitácora) | belongsTo Ticket, User | `channel` (whatsapp / email / phone / nota interna / presencial), `message` |
| **Payment** | Registro de pago de prima de una póliza | belongsTo Client, Policy | `monto`, `fecha_pago`, `estado` (pagado / pendiente / vencido), `metodo` |
| **KnowledgeDocument** | Artículo/FAQ que alimenta las respuestas del agente de IA | scoped por tenant | `categoria`, `titulo`, `contenido`, `tipo` (faq / articulo_kb) |
| **Inbox** | Bandeja de WhatsApp asignada a un grupo de agentes | belongsTo WhatsappChannel · belongsToMany User | `nombre_visible` |
| **WhatsappChannel** | Número de WhatsApp Business conectado por la correduría | belongsTo WABA · hasOne Inbox · hasMany Notification | `phone_number_id`, `numero_visible`, `departamento`, `estado`, `calidad`, `solo_demo` |
| **Tenant** | La correduría — raíz del aislamiento multi-tenant | hasMany User, Client, Policy, Ticket | `name`, `slug`, `tax_id`, `is_active`, `es_demo`, `features` (json) |

## 3. Ciclo de vida del cliente y la póliza

Estados de la póliza a lo largo de su vigencia:

- `active` — Activa
- `expired` — Vencida
- `cancelled` — Cancelada

| Evento | Disparado por | Efecto |
|---|---|---|
| Alta de cliente | Agente crea el registro en el panel | Se envía notificación de bienvenida (`ClientWelcome`) por el canal disponible del cliente |
| Registro de póliza | Agente crea la póliza, ligada a un cliente | Queda visible en el historial del cliente y disponible para abrir casos |
| Vencimiento próximo | Comando diario `policies:send-expiration-notifications` | Si faltan N días (configurables por tenant) para el vencimiento, se notifica al cliente por email una sola vez (`expiration_notified_at` evita duplicados) |
| Alerta interna | Widget del dashboard | Pólizas activas que vencen en los próximos 30 días aparecen en "Por vencer" y en las estadísticas del panel |

## 4. Casos (Tickets)

Un caso agrupa la interacción de servicio sobre un cliente y, opcionalmente, una póliza específica. Se puede originar manualmente en el panel o automáticamente desde un mensaje de WhatsApp.

**Tipos de caso:** `siniestro`, `consulta`, `reclamo`, `renovación`

**Prioridad:** `baja`, `media`, `alta`, `urgente`

**Estados y transición:**

```
1. Abierto           → estado inicial al crear el caso
2. En progreso        → un agente lo está atendiendo
3. Esperando cliente   → se necesita información o acción del cliente
4. Cerrado             → se registra closed_at y se envía la encuesta de satisfacción
```

> **Automatización clave:** el `TicketObserver` detecta cuando `status` cambia a `closed` y dispara automáticamente la notificación `TicketSatisfactionSurvey` al cliente — no requiere acción manual del agente.

**Bitácora del caso:** cada mensaje relevante (respuesta del agente, mensaje entrante de WhatsApp) queda registrado como un `Interaction` dentro del caso más reciente del cliente, formando el historial de la conversación.

## 5. Agente de IA por WhatsApp

Cuando llega un mensaje de WhatsApp, el sistema intenta resolverlo automáticamente con un agente basado en Gemini antes de involucrar a un humano. El mismo motor (`AgentOrchestrator`) atiende tanto los mensajes reales como el harness de pruebas del panel — el canal nunca cambia la lógica.

1. **Meta envía el webhook** — `WhatsAppWebhookController@receive` valida la firma HMAC, identifica el canal por `phone_number_id` y guarda el evento crudo como `WhatsappWebhookEvent`.
2. **Se encola el procesamiento** — `ProcessWhatsAppWebhookEvent` distingue actualizaciones de estado de mensaje (`queued → sent → delivered → read → failed`) de mensajes entrantes nuevos.
3. **Se ubica al cliente y al caso** — el número de origen se normaliza y se busca contra `Client.phone` dentro del tenant del canal; el mensaje se guarda como `Interaction` en el caso más reciente del cliente.
4. **Clasificación de intención (Gemini)** — el mensaje se clasifica en `faq`, `kb_categoria`, `consulta_cliente` o `fuera_de_alcance`, y se marca si requiere datos privados del cliente.
5. **Resolución según intención** — FAQ / categoría de KB → se busca en `KnowledgeDocument`. Consulta de cliente → se verifica identidad y se consultan sus pólizas y estado de pago con herramientas dedicadas.
6. **Respuesta generada y enviada** — Gemini redacta la respuesta final solo con base en el contexto recuperado (sin inventar datos) y se reenvía al cliente por el mismo canal de WhatsApp.
7. **Auditoría** — cada interacción del agente (clasificación, llamadas a herramientas y respuesta) se registra en `AgentAuditLog` para trazabilidad.

**Casos que escalan a humano:** fuera de alcance, cliente no registrado, o sin resultados en la base de conocimiento → la respuesta marca `requiere_seguimiento_humano: true` para que un agente lo revise.

**Harness de pruebas:** la página "Agente (prueba)" en el panel permite a un agente simular conversaciones con clientes reales o un número no registrado, viendo el log de clasificación y llamadas a herramientas.

## 6. Automatizaciones y trabajos programados

| Disparador | Componente | Qué hace | Canal |
|---|---|---|---|
| `created` | `ClientObserver` | Envía `ClientWelcome` al crear un cliente | WhatsApp (`client_welcome`) |
| `status → closed` | `TicketObserver` | Envía `TicketSatisfactionSurvey` al cliente | WhatsApp (`ticket_satisfaction_survey`) |
| Diario | `policies:send-expiration-notifications` | Notifica pólizas activas que vencen en N días (por tenant) vía `PolicyExpiringSoon` | Email + WhatsApp (`policy_expiration_reminder`) |
| Cola | `ExpirationReminderJob` | Registra en log el recordatorio de vencimiento de una póliza activa de un tenant específico | — |
| Webhook | `ProcessWhatsAppWebhookEvent` | Aplica actualizaciones de estado de entrega (`queued → sent → delivered → read → failed`) y enruta mensajes entrantes al agente de IA | WhatsApp |

El scheduler (`routes/console.php`) ejecuta `policies:send-expiration-notifications` una vez al día. Los envíos por WhatsApp que fallan de forma transitoria se reintentan automáticamente (`RetriesTransientFailures`).

## 7. Panel de administración

Construido con Filament 5 en `/admin`, con selector de tenant (correduría) integrado. Recursos disponibles para cada correduría:

| Recurso | Uso |
|---|---|
| Clientes | Alta y gestión de clientes; pólizas relacionadas visibles desde el propio registro |
| Pólizas | Alta y gestión de pólizas con adjuntos (documentos del contrato) |
| Casos | Gestión de tickets de servicio, con bitácora de interacciones y adjuntos |
| Bandejas (Inboxes) | Asignación de agentes a números de WhatsApp |
| Documentos de conocimiento | Contenido que consume el agente de IA para responder FAQs |
| Canales / Cuentas de WhatsApp | Conexión y monitoreo de números de WhatsApp Business (calidad, estado) |
| Usuarios | Gestión de cuentas y roles del equipo de la correduría |
| Correduría (Tenants) | Configuración de la organización, solo accesible a roles administrativos |

### Panel principal (dashboard)

Widgets que resumen la operación del día a día:

- **Casos abiertos por prioridad** — gráfico de barras de tickets no cerrados, agrupados por prioridad.
- **Estadísticas de casos** — casos abiertos, urgentes abiertos, pólizas por vencer y tiempo promedio de primera respuesta.
- **Pólizas por vencer** — tabla de pólizas activas que vencen en los próximos 30 días.
- **Calidad de canales WhatsApp** — números en riesgo por calidad baja o deshabilitados, excluyendo el canal de demo.

## 8. Roles y permisos

Gestionados con Spatie Permission + Filament Shield. Los permisos base cubren gestión de correduría, usuarios, clientes, pólizas y casos.

| Rol | Alcance |
|---|---|
| `super-admin` | Acceso total, cruza correduría. Gestiona el sistema completo. |
| `admin` | Administra la correduría: usuarios, configuración y todos los recursos del tenant. |
| `supervisor` | Supervisión de casos y equipo dentro de la correduría. |
| `agente` | Rol operativo: atiende clientes, pólizas y casos asignados. |

Permisos base: `manage tenants`, `manage users`, `manage clients`, `manage policies`, `manage tickets`. Cada recurso del panel tiene además su propia *Policy* de autorización en `app/Policies`.
