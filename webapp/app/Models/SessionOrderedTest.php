<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionOrderedTest extends Model
{
    protected $fillable = [
        'osce_session_id',
        'test_type',
        'test_name',
        'results',
        'ordered_at'
    ];

    protected $casts = [
        'results' => 'array',
        'ordered_at' => 'datetime'
    ];

    public function osceSession(): BelongsTo
    {
        return $this->belongsTo(OsceSession::class);
    }

    public function isLab(): bool
    {
        return $this->test_type === 'lab';
    }

    public function isProcedure(): bool
    {
        return $this->test_type === 'procedure';
    }
}