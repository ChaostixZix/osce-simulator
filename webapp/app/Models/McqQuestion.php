<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class McqQuestion extends Model
{
    protected $fillable = [
        'mcq_test_id',
        'question',
        'order',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(McqTest::class, 'mcq_test_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(McqOption::class)->orderBy('order');
    }
}
