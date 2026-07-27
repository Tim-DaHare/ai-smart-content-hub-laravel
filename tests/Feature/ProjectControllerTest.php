<?php

use App\Models\Project;

test('index returns all projects', function () {
    $projects = Project::factory()->count(20)->create();

    $this->getJson('/api/projects')
        ->assertSuccessful()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('data.0.id', $projects->first()->id)
        ->assertJsonPath('data.0.title', $projects->first()->title);
});
