<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image_path',
        'featured',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where('featured', true);
    }

    public function scopeSearch(Builder $query, ?string $search): void
    {
        $query->when($search, function ($q, $search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%");
        });
    }

    public function scopeWithFilters(Builder $query, array $filters): void
    {
        $query->when($filters['category'] ?? null, function ($q, $category) {
            $q->whereHas('category', fn ($subQuery) => $subQuery->where('slug', $category));
        })
        ->when($filters['tag'] ?? null, function ($q, $tag) {
            $q->whereHas('tags', fn ($subQuery) => $subQuery->where('slug', $tag));
        });
    }
}
