<?php

namespace App\Models;

use Database\Factories\ContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $project_id
 * @property int|null $category_id
 * @property int|null $content_status_id
 * @property string $title
 * @property array<int, float>|null $title_embedding
 * @property string|null $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['project_id', 'category_id', 'content_status_id', 'title', 'body'])]
class Content extends Model
{
    /** @use HasFactory<ContentFactory> */
    use HasFactory;

    protected $hidden = ['title_embedding'];

    protected function casts(): array
    {
        return [
            'title_embedding' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function contentStatus(): BelongsTo
    {
        return $this->belongsTo(ContentStatus::class);
    }

    public function scopeForProject(Builder $query, ?int $projectId): Builder
    {
        return $query->when($projectId, fn (Builder $builder) => $builder->where('project_id', $projectId));
    }
}
