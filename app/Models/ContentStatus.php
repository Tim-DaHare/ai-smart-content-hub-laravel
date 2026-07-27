<?php

namespace App\Models;

use Database\Factories\ContentStatusFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title'])]
class ContentStatus extends Model
{
    /** @use HasFactory<ContentStatusFactory> */
    use HasFactory;

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }
}
