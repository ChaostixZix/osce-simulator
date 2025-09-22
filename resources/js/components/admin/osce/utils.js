export const normalizeCaseData = (input = {}) => {
    const value = input || {};

    return {
        title: value.title ?? '',
        description: value.description ?? '',
        difficulty: value.difficulty ?? 'medium',
        duration_minutes: toNumber(value.duration_minutes, 15),
        scenario: value.scenario ?? '',
        objectives: value.objectives ?? '',
        is_active: Boolean(value.is_active ?? true),
        stations: toArrayOfStrings(value.stations),
        checklist: toArrayOfStrings(value.checklist),
        ai_patient_profile: value.ai_patient_profile ?? '',
        ai_patient_instructions: value.ai_patient_instructions ?? '',
        ai_patient_symptoms: toArrayOfStrings(value.ai_patient_symptoms),
        ai_patient_vitals: toObjectOfStrings(value.ai_patient_vitals),
        ai_patient_responses: toObjectOfStrings(value.ai_patient_responses),
        expected_anamnesis_questions: toArrayOfStrings(value.expected_anamnesis_questions),
        red_flags: toArrayOfStrings(value.red_flags),
        common_differentials: toArrayOfStrings(value.common_differentials),
        highly_appropriate_tests: toArrayOfStrings(value.highly_appropriate_tests),
        appropriate_tests: toArrayOfStrings(value.appropriate_tests),
        acceptable_tests: toArrayOfStrings(value.acceptable_tests),
        inappropriate_tests: toArrayOfStrings(value.inappropriate_tests),
        contraindicated_tests: toArrayOfStrings(value.contraindicated_tests),
        required_tests: toArrayOfStrings(value.required_tests),
        clinical_setting: value.clinical_setting ?? '',
        urgency_level: toNumber(value.urgency_level, 3),
        setting_limitations: toObjectOfStrings(value.setting_limitations),
        case_budget: value.case_budget === null || value.case_budget === undefined || value.case_budget === ''
            ? null
            : Number(value.case_budget),
        test_results_templates: toObjectOfStrings(value.test_results_templates),
    };
};

export const toArrayOfStrings = (value) => {
    if (!Array.isArray(value)) {
        if (typeof value === 'string' && value.trim() !== '') {
            return [value.trim()];
        }
        return [];
    }

    return value
        .map((item) => (typeof item === 'string' ? item.trim() : ''))
        .filter((item) => item !== '');
};

export const toObjectOfStrings = (value) => {
    if (!isPlainObject(value)) {
        return {};
    }

    return Object.keys(value).reduce((acc, key) => {
        const trimmedKey = String(key ?? '').trim();
        if (trimmedKey === '') {
            return acc;
        }

        const entry = value[key];
        if (entry === null || entry === undefined) {
            return acc;
        }

        if (typeof entry === 'string' || typeof entry === 'number') {
            const strValue = String(entry).trim();
            if (strValue !== '') {
                acc[trimmedKey] = strValue;
            }
            return acc;
        }

        if (Array.isArray(entry) || isPlainObject(entry)) {
            acc[trimmedKey] = entry;
        }

        return acc;
    }, {});
};

export const toNumber = (value, fallback = 0) => {
    const number = Number(value);
    return Number.isFinite(number) && !Number.isNaN(number) ? number : fallback;
};

export const isPlainObject = (value) => {
    return Object.prototype.toString.call(value) === '[object Object]';
};
