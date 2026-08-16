<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function homepageSlots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HomepageSlot::class);
    }

    public function breakingNews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BreakingNews::class);
    }

    public function viewStats(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ArticleViewStat::class);
    }

    /**
     * Scope a query to only include published articles.
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ArticleStatus::Published)
              ->where('published_at', '<=', now());
    }
}
