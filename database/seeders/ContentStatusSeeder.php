<?php

namespace Database\Seeders;

use App\Models\ContentStatus;
use Illuminate\Database\Seeder;

class ContentStatusSeeder extends Seeder
{
    /**
     * The unique, human-readable status names extracted from the source data.
     *
     * @var array<int, string>
     */
    private const array STATUSES = [
        'Draft',
        'Published',
        'Archived',
        'Deleted',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::STATUSES as $title) {
            ContentStatus::query()->firstOrCreate(['title' => $title]);
        }
    }
}
