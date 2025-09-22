<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsceChatMessage extends Model
{
    protected $fillable = [
        'osce_session_id',
        'sender_type',
        'message',
        'metadata',
        'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
    ];

    public function osceSession(): BelongsTo
    {
        return $this->belongsTo(OsceSession::class);
    }

    public function isFromUser(): bool
    {
        return $this->sender_type === 'user';
    }

    public function isFromAiPatient(): bool
    {
        return $this->sender_type === 'ai_patient';
    }

    public function isFromSystem(): bool
    {
        return $this->sender_type === 'system';
    }
}
