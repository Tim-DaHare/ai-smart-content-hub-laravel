<?php

namespace App\Services;

use App\Models\Content;
use Illuminate\Support\Collection;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Embedding;

class ContentEmbeddingService
{
    private const MODEL = 'gemini-embedding-2';

    private const MIN_SIMILARITY = 0.6;

    /**
     * Generate an embedding for each given title, in the same order.
     *
     * @param  array<int, string>  $titles
     * @return array<int, array<int, float>>
     */
    public function embedTitles(array $titles): array
    {
        if ($titles === []) {
            return [];
        }

        $response = Prism::embeddings()
            ->using(Provider::Gemini, self::MODEL)
            ->fromArray($titles)
            ->asEmbeddings();

        return array_map(
            fn (Embedding $embedding): array => $embedding->embedding,
            $response->embeddings,
        );
    }

    /**
     * Find content whose title is semantically similar to the given search string.
     *
     * @return Collection<int, Content>
     */
    public function search(string $query, int $limit = 10, ?int $projectId = null): Collection
    {
        $queryEmbedding = $this->embedTitles([$query])[0];

        return Content::query()
            ->whereNotNull('title_embedding')
            ->forProject($projectId)
            ->with(['project', 'category', 'contentStatus'])
            ->get()
            ->map(fn (Content $content) => [
                'content' => $content,
                'similarity' => self::cosineSimilarity($queryEmbedding, $content->title_embedding),
            ])
            ->filter(fn (array $result) => $result['similarity'] >= self::MIN_SIMILARITY)
            ->sortByDesc('similarity')
            ->take($limit)
            ->pluck('content')
            ->values();
    }

    /**
     * @param  array<int, float>  $a
     * @param  array<int, float>  $b
     */
    private static function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $dotProduct += $value * $b[$i];
            $normA += $value ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
