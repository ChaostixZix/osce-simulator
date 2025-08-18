<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionExamination extends Model
{
    protected $fillable = [
        'osce_session_id',
        'examination_category',
        'examination_type',
        'findings',
        'performed_at'
    ];

    protected $casts = [
        'findings' => 'array',
        'performed_at' => 'datetime'
    ];

    public function osceSession(): BelongsTo
    {
        return $this->belongsTo(OsceSession::class);
    }

    public function getFormattedFindings(): string
    {
        if (is_array($this->findings)) {
            return implode(', ', $this->findings);
        }
        return $this->findings ?? '';
    }
}