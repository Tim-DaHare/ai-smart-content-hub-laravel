<?php

namespace App\Services;

use App\Models\Content;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\ValueObjects\Embedding;
use Throwable;

class ContentEmbeddingService
{
    private const MODEL = 'gemini-embedding-2';

    private const MIN_SIMILARITY = 0.6;

    /** Maximum number of attempts (initial call + retries) before giving up. */
    private const MAX_ATTEMPTS = 5;

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
            ->withClientRetry(
                times: self::MAX_ATTEMPTS,
                sleepMilliseconds: $this->retryDelay(...),
                when: $this->shouldRetry(...),
            )
            ->asEmbeddings();

        return array_map(
            fn (Embedding $embedding): array => $embedding->embedding,
            $response->embeddings,
        );
    }

    /**
     * Only retry transient failures: rate limits, an overloaded provider, or a dropped connection.
     */
    private function shouldRetry(Throwable $exception, PendingRequest $request): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException
            && in_array($exception->response->status(), [429, 503], true);
    }

    /**
     * Exponential backoff with jitter, honouring the provider's Retry-After header when present.
     */
    private function retryDelay(int $attempt, Throwable $exception): int
    {
        if ($exception instanceof RequestException && $retryAfter = $exception->response->header('Retry-After')) {
            return ((int) $retryAfter) * 1000;
        }

        return min(1000 * 2 ** $attempt, 30_000) + random_int(0, 500);
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
