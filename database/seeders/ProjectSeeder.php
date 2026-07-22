<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * The unique, human-readable project names extracted from the source data.
     *
     * @var array<int, string>
     */
    private const array PROJECTS = [
        'Marketing',
        'Marketing Dept',
        'Dev Portal',
        'Sales Ops Main',
        'Global Revenue',
        'Alpha Research',
        'Customer Success',
        'Legacy Docs',
        'Project Phoenix',
        'Data Factory',
        'Cloud Migration',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PROJECTS as $title) {
            Project::query()->firstOrCreate(['title' => $title]);
        }
    }
}
