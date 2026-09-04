<?php

namespace App\Services\Agent;

use App\Models\KnowledgeDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class KnowledgeBaseSearch
{
    /**
     * Busca en toda la base de conocimiento (FAQs y artículos por igual).
     * No separa por `tipo`: si Gemini clasifica la pregunta como "kb_categoria"
     * cuando en realidad la respuesta vive en una FAQ (o viceversa), separar
     * los grupos de búsqueda por tipo dejaría la respuesta correcta fuera solo
     * por una adivinanza de clasificación equivocada. `categoria`, si viene,
     * se usa como filtro; el tipo de documento no.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buscar(?string $categoria, string $pregunta, int $topK = 3): array
    {
        // Se separa por cualquier carácter que no sea letra/número (no solo
        // espacios): de lo contrario "accidente?" queda como un solo token y
        // el LIKE nunca matchea "accidente" seguido de cualquier otra cosa.
        $palabras = collect(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($pregunta)) ?: [])
            ->filter(fn (string $palabra): bool => mb_strlen($palabra) >= 4)
            ->unique()
            ->values();

        $resultados = $this->ejecutarBusqueda($categoria, $palabras, $topK);

        if ($resultados === [] && filled($categoria)) {
            // La categoría la adivina la clasificación y no siempre acierta;
            // sin este reintento, una categoría mal adivinada deja fuera una
            // pregunta cuya respuesta sí existe en la KB.
            $resultados = $this->ejecutarBusqueda(null, $palabras, $topK);
        }

        return $resultados;
    }

    /**
     * @param  Collection<int, string>  $palabras
     * @return array<int, array<string, mixed>>
     */
    protected function ejecutarBusqueda(?string $categoria, Collection $palabras, int $topK): array
    {
        $query = KnowledgeDocument::query();

        if (filled($categoria)) {
            $query->where('categoria', $categoria);
        }

        if ($palabras->isNotEmpty()) {
            $query->where(function (Builder $q) use ($palabras): void {
                foreach ($palabras as $palabra) {
                    $q->orWhere('titulo', 'like', "%{$palabra}%")
                        ->orWhere('contenido', 'like', "%{$palabra}%");
                }
            });
        }

        return $query->limit($topK)->get()
            ->map(fn (KnowledgeDocument $doc): array => [
                'doc_id' => $doc->id,
                'categoria' => $doc->categoria,
                'titulo' => $doc->titulo,
                'contenido' => $doc->contenido,
            ])
            ->all();
    }

    /**
     * Categorías existentes en toda la base de conocimiento, usadas para que
     * el paso de clasificación elija entre categorías reales en vez de
     * adivinar un string.
     *
     * @return array<int, string>
     */
    public function categoriasDisponibles(): array
    {
        return KnowledgeDocument::query()
            ->whereNotNull('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria')
            ->all();
    }
}
