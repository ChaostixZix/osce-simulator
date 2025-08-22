<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoapComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'soap_note_id',
        'author_id',
        'body',
    ];

    public function soapNote(): BelongsTo
    {
        return $this->belongsTo(SoapNote::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
