<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OsceSession extends Model
{
    protected $fillable = [
        'user_id',
        'osce_case_id',
        'status',
        'started_at',
        'completed_at',
        'score',
        'max_score',
        'responses',
        'feedback'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'responses' => 'array',
        'feedback' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function osceCase(): BelongsTo
    {
        return $this->belongsTo(OsceCase::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(OsceChatMessage::class)->orderBy('sent_at', 'asc');
    }

    public function getLatestChatMessage(): ?OsceChatMessage
    {
        return $this->chatMessages()->latest('sent_at')->first();
    }
}
