<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OsceCase extends Model
{
    protected $fillable = [
        'title',
        'description',
        'difficulty',
        'duration_minutes',
        'stations',
        'scenario',
        'objectives',
        'checklist',
        'is_active'
    ];

    protected $casts = [
        'stations' => 'array',
        'checklist' => 'array',
        'is_active' => 'boolean'
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(OsceSession::class);
    }
}
