<?php

namespace App\Http\Controllers;

use App\Http\Resources\ContentResource;
use App\Models\Content;
use App\Services\ContentEmbeddingService;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function __construct(private readonly ContentEmbeddingService $embeddings) {}

    /**
     * Search for content by semantic similarity to the given title string.
     * When no query string is given, all content is returned instead.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string'],
            'project_id' => ['sometimes', 'integer', 'exists:projects,id'],
        ]);

        $projectId = $validated['project_id'] ?? null;

        if (blank($validated['q'] ?? null)) {
            return ContentResource::collection(
                Content::query()->forProject($projectId)->with(['project', 'category', 'contentStatus'])->get()
            );
        }

        return ContentResource::collection($this->embeddings->search(
            query: $validated['q'],
            projectId: $projectId,
        ));
    }
}
