<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoapNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'author_id',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'state',
        'finalized_at',
    ];

    protected $casts = [
        'finalized_at' => 'datetime',
        // Store TipTap JSON as arrays; Eloquent serializes to JSON strings in DB
        'subjective' => 'array',
        'objective' => 'array',
        'assessment' => 'array',
        'plan' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SoapAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(SoapComment::class);
    }

    public function scopeLikeSearch(Builder $query, string $term): void
    {
        $query->where(function (Builder $query) use ($term) {
            $query->where('subjective', 'like', "%{$term}%")
                ->orWhere('objective', 'like', "%{$term}%")
                ->orWhere('assessment', 'like', "%{$term}%")
                ->orWhere('plan', 'like', "%{$term}%");
        });
    }
}
