<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    protected $table = 'osce_sessions';
    
    protected $fillable = [
        'case_id',
        'case_title',
        'status',
        'started_at',
        'ended_at',
        'performance_data',
        'total_messages',
        'duration_seconds',
        'score'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'performance_data' => 'array',
        'score' => 'decimal:2'
    ];

    /**
     * Get the chat messages for this session
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * Get the duration in a human-readable format
     */
    public function getDurationAttribute()
    {
        if ($this->ended_at && $this->started_at) {
            return $this->started_at->diffForHumans($this->ended_at, true);
        }
        
        if ($this->started_at) {
            return $this->started_at->diffForHumans(now(), true);
        }
        
        return null;
    }
}

