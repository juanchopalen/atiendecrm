<?php

namespace App\Services\Agent;

use App\Models\KnowledgeDocument;
use Illuminate\Database\Eloquent\Builder;

class KnowledgeBaseSearch
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function buscarFaq(string $pregunta, int $topK = 3): array
    {
        return $this->buscar('faq', null, $pregunta, $topK);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buscarPorCategoria(?string $categoria, string $pregunta, int $topK = 3): array
    {
        return $this->buscar('articulo_kb', $categoria, $pregunta, $topK);
    }

    /**
     * Categorías existentes de artículos de KB, usadas para que el paso de
     * clasificación elija entre categorías reales en vez de adivinar un string.
     *
     * @return array<int, string>
     */
    public function categoriasDisponibles(): array
    {
        return KnowledgeDocument::query()
            ->where('tipo', 'articulo_kb')
            ->whereNotNull('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria')
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buscar(string $tipo, ?string $categoria, string $pregunta, int $topK): array
    {
        $palabras = collect(preg_split('/\s+/', mb_strtolower($pregunta)) ?: [])
            ->filter(fn (string $palabra): bool => mb_strlen($palabra) >= 4)
            ->unique()
            ->values();

        $query = KnowledgeDocument::query()->where('tipo', $tipo);

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
}
