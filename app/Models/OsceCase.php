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
        'highly_appropriate_tests',
        'appropriate_tests',
        'acceptable_tests',
        'inappropriate_tests',
        'contraindicated_tests',
        'required_tests',
        'clinical_setting',
        'urgency_level',
        'setting_limitations',
        'case_budget',
        'test_results_templates'
    ];

    protected $casts = [
        'stations' => 'array',
        'checklist' => 'array',
        'is_active' => 'boolean',
        'ai_patient_vitals' => 'array',
        'ai_patient_symptoms' => 'array',
        'ai_patient_responses' => 'array',
        'highly_appropriate_tests' => 'array',
        'appropriate_tests' => 'array',
        'acceptable_tests' => 'array',
        'inappropriate_tests' => 'array',
        'contraindicated_tests' => 'array',
        'required_tests' => 'array',
        'setting_limitations' => 'array',
        'test_results_templates' => 'array'
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
