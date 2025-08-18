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
        'clinical_reasoning_score',
        'total_test_cost',
        'evaluation_feedback',
        'responses',
        'feedback'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'responses' => 'array',
        'feedback' => 'array',
        'evaluation_feedback' => 'array'
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

    public function orderedTests(): HasMany
    {
        return $this->hasMany(SessionOrderedTest::class, 'osce_session_id')->orderBy('ordered_at', 'desc');
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(SessionExamination::class, 'osce_session_id')->orderBy('performed_at', 'desc');
    }

    public function getLabResults(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->orderedTests()->where('test_type', 'lab')->get();
    }

    public function getProcedureResults(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->orderedTests()->where('test_type', 'procedure')->get();
    }

    public function getPhysicalExamFindings(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->examinations()->get();
    }
}
