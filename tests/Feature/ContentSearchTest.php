<?php

use App\Models\Category;
use App\Models\Content;
use App\Models\Project;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\EmbeddingsResponseFake;
use Prism\Prism\ValueObjects\Embedding;

test('search returns all content when no query string is given', function () {
    Content::factory()->create(['title_embedding' => [1, 0]]);
    Content::factory()->create(['title_embedding' => null]);

    $fake = Prism::fake([]);

    $this->getJson('/api/content/search')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $fake->assertCallCount(0);
});

test('search returns all content when q is an empty string', function () {
    Content::factory()->count(2)->create();

    $fake = Prism::fake([]);

    $this->getJson('/api/content/search?q=')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data');

    $fake->assertCallCount(0);
});

test('search without a query filters by project_id', function () {
    $matchingProject = Project::factory()->create();
    $otherProject = Project::factory()->create();

    $inProject = Content::factory()->create(['project_id' => $matchingProject->id]);
    Content::factory()->create(['project_id' => $otherProject->id]);

    $this->getJson("/api/content/search?project_id={$matchingProject->id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inProject->id);
});

test('search returns content ordered by similarity above the threshold', function () {
    $strongMatch = Content::factory()->create(['title_embedding' => [1, 0]]);
    $weakMatch = Content::factory()->create(['title_embedding' => [0.8, 0.6]]);
    Content::factory()->create(['title_embedding' => [0, 1]]);
    Content::factory()->create(['title_embedding' => null]);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([Embedding::fromArray([1, 0])]),
    ]);

    $response = $this->getJson('/api/content/search?q=example')
        ->assertSuccessful();

    $response->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $strongMatch->id)
        ->assertJsonPath('data.1.id', $weakMatch->id);
});

test('search hides the title_embedding attribute', function () {
    Content::factory()->create(['title_embedding' => [1, 0]]);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([Embedding::fromArray([1, 0])]),
    ]);

    $this->getJson('/api/content/search?q=example')
        ->assertSuccessful()
        ->assertJsonMissingPath('data.0.title_embedding');
});

test('search includes the related project and category', function () {
    $project = Project::factory()->create(['title' => 'Acme Launch']);
    $category = Category::factory()->create(['title' => 'Announcements']);

    Content::factory()->create([
        'title_embedding' => [1, 0],
        'project_id' => $project->id,
        'category_id' => $category->id,
    ]);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([Embedding::fromArray([1, 0])]),
    ]);

    $this->getJson('/api/content/search?q=example')
        ->assertSuccessful()
        ->assertJsonPath('data.0.project.id', $project->id)
        ->assertJsonPath('data.0.project.title', 'Acme Launch')
        ->assertJsonPath('data.0.category.id', $category->id)
        ->assertJsonPath('data.0.category.title', 'Announcements');
});

test('search returns null project and category when content has none', function () {
    Content::factory()->create([
        'title_embedding' => [1, 0],
        'project_id' => null,
        'category_id' => null,
    ]);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([Embedding::fromArray([1, 0])]),
    ]);

    $this->getJson('/api/content/search?q=example')
        ->assertSuccessful()
        ->assertJsonPath('data.0.project', null)
        ->assertJsonPath('data.0.category', null);
});

test('search rejects a project_id that does not exist', function () {
    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([Embedding::fromArray([1, 0])]),
    ]);

    $this->getJson('/api/content/search?q=example&project_id=999')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('project_id');
});

test('search filters results down to the given project', function () {
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
    Content::factory()->create([
        'title_embedding' => [1, 0],
        'project_id' => null,
    ]);

    Prism::fake([
        EmbeddingsResponseFake::make()->withEmbeddings([Embedding::fromArray([1, 0])]),
    ]);

    $this->getJson("/api/content/search?q=example&project_id={$matchingProject->id}")
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inProject->id);
});
