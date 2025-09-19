<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'osce_case_id',
        'completed_at',
        'steps_completed',
        'time_spent_seconds',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'steps_completed' => 'integer',
        'time_spent_seconds' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function osceCase(): BelongsTo
    {
        return $this->belongsTo(OsceCase::class);
    }
}