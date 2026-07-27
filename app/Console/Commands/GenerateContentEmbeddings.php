<?php

namespace App\Console\Commands;

use App\Models\Content;
use App\Services\ContentEmbeddingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('app:generate-content-embeddings {--fresh : Regenerate embeddings for content that already has one}')]
#[Description('Generate title embeddings for content records so they can be found via semantic search')]
class GenerateContentEmbeddings extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ContentEmbeddingService $embeddings): int
    {
        $query = Content::query();

        if (! $this->option('fresh')) {
            $query->whereNull('title_embedding');
        }

        $contents = $query->get();

        if ($contents->isEmpty()) {
            $this->info('No content needs embeddings.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($contents->count());
        $bar->start();

        $contents->chunk(100)->each(function (Collection $chunk) use ($embeddings, $bar): void {
            $vectors = $embeddings->embedTitles($chunk->pluck('title')->all());

            $chunk->values()->each(function (Content $content, int $index) use ($vectors, $bar): void {
                $content->title_embedding = $vectors[$index];
                $content->save();

                $bar->advance();
            });
        });

        $bar->finish();
        $this->newLine();
        $this->info("Generated embeddings for {$contents->count()} content record(s).");

        return self::SUCCESS;
    }
}
