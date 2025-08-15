<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'session_id',
        'type',
        'content',
        'action_type',
        'metadata',
        'timestamp'
    ];

    protected $casts = [
        'metadata' => 'array',
        'timestamp' => 'datetime'
    ];

    /**
     * Get the session that this message belongs to
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }
}
