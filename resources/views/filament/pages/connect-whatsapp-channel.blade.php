<x-filament-panels::page>
    @if (! $this->isConfigured())
        <x-filament::section>
            <x-slot name="heading">Embedded Signup no configurado</x-slot>

            <p class="text-sm text-gray-500">
                Faltan <code>WHATSAPP_META_APP_ID</code> y/o <code>WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID</code> en la
                configuración de la aplicación. Configúralos en <code>.env</code> (ver
                <code>especificacion_multi_tenant_whatsapp.md</code> §5.3) y recarga esta página.
            </p>
        </x-filament::section>
    @else
        <div
            x-data="whatsappEmbeddedSignup({
                metaAppId: @js($this->getMetaAppId()),
                configId: @js($this->getConfigId()),
                apiVersion: @js($this->getApiVersion()),
                callbackUrl: @js($this->getCallbackUrl()),
                csrfToken: @js(csrf_token()),
            })"
            x-init="init()"
            class="space-y-6"
        >
            <x-filament::section>
                <x-slot name="heading">Datos del número</x-slot>
                <x-slot name="description">
                    Completa estos datos antes de conectar. El modo (Dedicado / Coexistencia) se detecta
                    automáticamente según lo que informe Meta durante el flujo.
                </x-slot>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="fi-fo-field-wrp-label text-sm font-medium">Número visible (E.164)</label>
                        <input
                            type="text"
                            x-model="form.numeroVisible"
                            placeholder="+58 412 0000000"
                            class="fi-input mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        />
                    </div>
                    <div>
                        <label class="fi-fo-field-wrp-label text-sm font-medium">Departamento</label>
                        <input
                            type="text"
                            x-model="form.departamento"
                            placeholder="Suscripción, Cobranzas, General..."
                            class="fi-input mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        />
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Conectar con Meta</x-slot>

                <div class="space-y-4">
                    <template x-if="status === 'idle'">
                        <p class="text-sm text-gray-500">
                            Al hacer clic se abrirá la ventana de Meta para iniciar sesión con Facebook Business,
                            elegir o crear el portafolio/WABA del corretaje y registrar el número.
                        </p>
                    </template>

                    <template x-if="status === 'awaiting_meta'">
                        <p class="text-sm text-warning-600">Esperando la confirmación de Meta en la ventana emergente...</p>
                    </template>

                    <template x-if="status === 'submitting'">
                        <p class="text-sm text-warning-600">Registrando el canal en AtiendeCRM...</p>
                    </template>

                    <template x-if="status === 'success'">
                        <div class="rounded-lg bg-success-50 dark:bg-success-500/10 p-3 text-sm text-success-700 dark:text-success-400">
                            Número conectado correctamente (canal #<span x-text="result.whatsapp_channel_id"></span>).
                            Puedes verlo en <a href="{{ \App\Filament\Resources\WhatsappChannels\WhatsappChannelResource::getUrl() }}" class="underline">Números de WhatsApp</a>.
                        </div>
                    </template>

                    <template x-if="status === 'error'">
                        <div class="rounded-lg bg-danger-50 dark:bg-danger-500/10 p-3 text-sm text-danger-700 dark:text-danger-400" x-text="errorMessage"></div>
                    </template>

                    <x-filament::button
                        type="button"
                        x-on:click="launchSignup()"
                        :disabled="false"
                        x-bind:disabled="!form.numeroVisible || !form.departamento || status === 'awaiting_meta' || status === 'submitting'"
                    >
                        Conectar número de WhatsApp
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>

        @once
            <script>
                function whatsappEmbeddedSignup(config) {
                    return {
                        form: { numeroVisible: '', departamento: '' },
                        status: 'idle',
                        errorMessage: '',
                        result: {},
                        pendingCode: null,
                        pendingSessionData: null,

                        init() {
                            window.fbAsyncInit = () => {
                                FB.init({
                                    appId: config.metaAppId,
                                    autoLogAppEvents: true,
                                    xfbml: true,
                                    version: config.apiVersion,
                                });
                            };

                            (function (d, s, id) {
                                let js, fjs = d.getElementsByTagName(s)[0];
                                if (d.getElementById(id)) return;
                                js = d.createElement(s);
                                js.id = id;
                                js.src = 'https://connect.facebook.net/en_US/sdk.js';
                                fjs.parentNode.insertBefore(js, fjs);
                            })(document, 'script', 'facebook-jssdk-embedded-signup');

                            window.addEventListener('message', (event) => {
                                if (
                                    event.origin !== 'https://www.facebook.com'
                                    && event.origin !== 'https://web.facebook.com'
                                ) {
                                    return;
                                }

                                let data;
                                try {
                                    data = JSON.parse(event.data);
                                } catch (e) {
                                    return;
                                }

                                if (data.type !== 'WA_EMBEDDED_SIGNUP') return;

                                if (data.event === 'FINISH' || data.event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING') {
                                    this.pendingSessionData = {
                                        waba_id: data.data?.waba_id,
                                        phone_number_id: data.data?.phone_number_id,
                                        // Meta emits a distinct event when the number is migrated
                                        // to coexistence with the mobile WhatsApp Business app.
                                        modo: data.event === 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING'
                                            ? 'coexistence'
                                            : 'dedicated',
                                    };
                                    this.maybeSubmit();
                                } else if (data.event === 'CANCEL' || data.event === 'ERROR') {
                                    this.status = 'error';
                                    this.errorMessage = 'El flujo de Meta fue cancelado o falló antes de completarse.';
                                }
                            });
                        },

                        launchSignup() {
                            this.status = 'awaiting_meta';
                            this.errorMessage = '';
                            this.pendingCode = null;
                            this.pendingSessionData = null;

                            FB.login((response) => {
                                if (response.authResponse && response.authResponse.code) {
                                    this.pendingCode = response.authResponse.code;
                                    this.maybeSubmit();
                                } else {
                                    this.status = 'error';
                                    this.errorMessage = 'No se recibió autorización de Meta. Inténtalo de nuevo.';
                                }
                            }, {
                                config_id: config.configId,
                                response_type: 'code',
                                override_default_response_type: true,
                                extras: { setup: {}, featureType: '', sessionInfoVersion: '3' },
                            });
                        },

                        maybeSubmit() {
                            if (!this.pendingCode || !this.pendingSessionData) return;

                            this.status = 'submitting';

                            fetch(config.callbackUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': config.csrfToken,
                                },
                                body: JSON.stringify({
                                    code: this.pendingCode,
                                    waba_id: this.pendingSessionData.waba_id,
                                    phone_number_id: this.pendingSessionData.phone_number_id,
                                    numero_visible: this.form.numeroVisible,
                                    departamento: this.form.departamento,
                                    modo: this.pendingSessionData.modo,
                                }),
                            })
                                .then(async (res) => {
                                    const body = await res.json();
                                    if (!res.ok) throw new Error(body.message || 'Error al registrar el canal.');
                                    this.result = body;
                                    this.status = 'success';
                                })
                                .catch((err) => {
                                    this.status = 'error';
                                    this.errorMessage = err.message;
                                });
                        },
                    };
                }
            </script>
        @endonce
    @endif
</x-filament-panels::page>
