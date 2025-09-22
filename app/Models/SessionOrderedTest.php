<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionOrderedTest extends Model
{
    protected $fillable = [
        'osce_session_id',
        'medical_test_id',
        'test_type',
        'test_name',
        'clinical_reasoning',
        'priority',
        'cost',
        'results',
        'ordered_at',
        'results_available_at',
        'completed_at',
    ];

    protected $casts = [
        'results' => 'array',
        'ordered_at' => 'datetime',
        'results_available_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function medicalTest(): BelongsTo
    {
        return $this->belongsTo(MedicalTest::class, 'medical_test_id');
    }
}
