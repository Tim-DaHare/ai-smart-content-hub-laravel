<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Services\ContentEmbeddingService;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function __construct(private readonly ContentEmbeddingService $embeddings) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Content::paginate();
    }

    /**
     * Search for content by semantic similarity to the given title string.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1'],
        ]);

        return $this->embeddings->search($validated['q']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        return Content::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(Content $content)
    {
        return $content;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Content $content)
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $content->update($validated);

        return $content;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Content $content)
    {
        $content->delete();

        return response()->noContent();
    }
}
