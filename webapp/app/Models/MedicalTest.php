<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalTest extends Model
{
    protected $fillable = [
        'name',
        'category',
        'type',
        'description',
        'indications',
        'contraindications',
        'cost',
        'turnaround_seconds',
        'available_settings',
        'requires_consent',
        'risk_level',
        'is_active',
    ];

    protected $casts = [
        'indications' => 'array',
        'contraindications' => 'array',
        'available_settings' => 'array',
        'requires_consent' => 'boolean',
        'is_active' => 'boolean',
    ];
}


