<?php

use App\Models\Content;
use App\Models\Project;
use App\Services\ContentEmbeddingService;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\EmbeddingsResponseFake;
use Prism\Prism\ValueObjects\Embedding;

test('embedTitles returns an empty array without calling Prism for no titles', function () {
    $fake = Prism::fake([]);

    $embeddings = app(ContentEmbeddingService::class)->embedTitles([]);

    expect($embeddings)->toBe([]);
    $fake->assertCallCount(0);
});

test('embedTitles returns a distinct embedding per title even when titles repeat', function () {
    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([
            Embedding::fromArray([1, 0]),
            Embedding::fromArray([0, 1]),
        ]),
    ]);

    $embeddings = app(ContentEmbeddingService::class)->embedTitles(['same', 'same']);

    expect($embeddings)->toBe([[1, 0], [0, 1]]);
});

test('search excludes content without a title embedding', function () {
    Content::factory()->create(['title_embedding' => null]);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([Embedding::fromArray([1, 0])]),
    ]);

    $results = app(ContentEmbeddingService::class)->search('example');

    expect($results)->toBeEmpty();
});

test('search excludes content below the similarity threshold', function () {
    Content::factory()->create(['title_embedding' => [0, 1]]);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([Embedding::fromArray([1, 0])]),
    ]);

    $results = app(ContentEmbeddingService::class)->search('example');

    expect($results)->toBeEmpty();
});

test('search orders results by similarity descending', function () {
    $weakMatch = Content::factory()->create(['title_embedding' => [0.8, 0.6]]);
    $strongMatch = Content::factory()->create(['title_embedding' => [1, 0]]);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([Embedding::fromArray([1, 0])]),
    ]);

    $results = app(ContentEmbeddingService::class)->search('example');

    expect($results->pluck('id')->all())->toBe([$strongMatch->id, $weakMatch->id]);
});

test('search respects the given limit', function () {
    Content::factory()->count(3)->create(['title_embedding' => [1, 0]]);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([Embedding::fromArray([1, 0])]),
    ]);

    $results = app(ContentEmbeddingService::class)->search('example', limit: 2);

    expect($results)->toHaveCount(2);
});

test('search filters by project id when given', function () {
    $matchingProject = Project::factory()->create();
    $otherProject = Project::factory()->create();

    $inProject = Content::factory()->create([
        'title_embedding' => [1, 0],
        'project_id' => $matchingProject->id,
    ]);
    Content::factory()->create([
        'title_embedding' => [1, 0],
        'project_id' => $otherProject->id,
    ]);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([Embedding::fromArray([1, 0])]),
    ]);

    $results = app(ContentEmbeddingService::class)->search('example', projectId: $matchingProject->id);

    expect($results->pluck('id')->all())->toBe([$inProject->id]);
});
