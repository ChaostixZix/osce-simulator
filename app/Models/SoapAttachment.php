<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoapAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'soap_note_id',
        'disk',
        'path',
        'original_name',
        'size',
        'mime',
    ];

    public function soapNote(): BelongsTo
    {
        return $this->belongsTo(SoapNote::class);
    }
}
