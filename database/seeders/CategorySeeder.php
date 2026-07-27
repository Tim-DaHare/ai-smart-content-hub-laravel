<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * The unique, human-readable category names extracted from the source data.
     *
     * @var array<int, string>
     */
    private const CATEGORIES = [
        'Success Stories',
        'Technical Guides',
        'Sales Reports',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::CATEGORIES as $title) {
            Category::query()->firstOrCreate(['title' => $title]);
        }
    }
}
