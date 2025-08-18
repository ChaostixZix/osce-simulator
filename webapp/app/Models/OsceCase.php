<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OsceCase extends Model
{
    protected $fillable = [
        'title',
        'description',
        'difficulty',
        'duration_minutes',
        'stations',
        'scenario',
        'objectives',
        'checklist',
        'is_active',
        'ai_patient_profile',
        'ai_patient_vitals',
        'ai_patient_symptoms',
        'ai_patient_instructions',
        'ai_patient_responses',
        'available_labs',
        'available_procedures',
        'available_examinations',
        'lab_results_templates',
        'procedure_results_templates',
        'physical_exam_findings'
    ];

    protected $casts = [
        'stations' => 'array',
        'checklist' => 'array',
        'is_active' => 'boolean',
        'ai_patient_vitals' => 'array',
        'ai_patient_symptoms' => 'array',
        'ai_patient_responses' => 'array',
        'available_labs' => 'array',
        'available_procedures' => 'array',
        'available_examinations' => 'array',
        'lab_results_templates' => 'array',
        'procedure_results_templates' => 'array',
        'physical_exam_findings' => 'array'
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(OsceSession::class);
    }

    public function getAiPatientContext(): array
    {
        return [
            'profile' => $this->ai_patient_profile,
            'vitals' => $this->ai_patient_vitals,
            'symptoms' => $this->ai_patient_symptoms,
            'instructions' => $this->ai_patient_instructions,
            'responses' => $this->ai_patient_responses
        ];
    }
}
