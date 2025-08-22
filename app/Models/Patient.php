<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'bangsal',
        'nomor_kamar',
        'status',
    ];

    public function soapNotes(): HasMany
    {
        return $this->hasMany(SoapNote::class);
    }
}
