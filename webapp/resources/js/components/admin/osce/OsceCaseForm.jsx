import React from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import ListField from './ListField';
import KeyValueField from './KeyValueField';
import SectionNav from './SectionNav';
import CaseSummarySidebar from './CaseSummarySidebar';
import { normalizeCaseData } from './utils';

const urgencyLevels = [
    { label: '1 · Low', value: 1 },
    { label: '2', value: 2 },
    { label: '3 · Moderate', value: 3 },
    { label: '4', value: 4 },
    { label: '5 · Critical', value: 5 },
];

const fieldClass = 'w-full rounded-md border border-border bg-background px-3 py-2 text-sm text-foreground';

export default function OsceCaseForm({
    form,
    title,
    description,
    submitLabel,
    cancelUrl,
    onSubmit,
    mode = 'create',
}) {
    const { data, setData, processing, errors } = form;
    const { flash, errors: sharedErrors } = usePage().props;
    const generatedCase = flash?.generated_case;
    const generatorError = sharedErrors?.generator;

    const generatorForm = useForm({
        instructions: '',
        sources: [],
    });

    const [generatorNotice, setGeneratorNotice] = React.useState(null);
    const fileInputRef = React.useRef(null);
    const hasAppliedFlash = React.useRef(false);

    const clearGeneratorForm = React.useCallback(() => {
        generatorForm.setData('sources', []);
        generatorForm.setData('instructions', '');
        generatorForm.clearErrors();
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }, [generatorForm]);

    const handleGeneratedPayload = React.useCallback(
        (payload) => {
            if (!payload) {
                return;
            }

            const normalized = normalizeCaseData(payload);
            setData((previous) => ({
                ...previous,
                ...normalized,
            }));
            setGeneratorNotice('AI suggestions applied. Review and adjust as needed.');
        },
        [setData]
    );

    React.useEffect(() => {
        if (generatedCase && !hasAppliedFlash.current) {
            handleGeneratedPayload(generatedCase);
            clearGeneratorForm();
            hasAppliedFlash.current = true;
        } else if (!generatedCase) {
            hasAppliedFlash.current = false;
        }
    }, [generatedCase, handleGeneratedPayload, clearGeneratorForm]);

    const handleSubmit = (event) => {
        event.preventDefault();
        if (onSubmit) {
            onSubmit();
        }
    };

    const handleGeneratorSubmit = (event) => {
        event.preventDefault();
        setGeneratorNotice(null);
        generatorForm.clearErrors();

        generatorForm.transform(() => {
            const payload = new FormData();
            generatorForm.data.sources.forEach((file) => {
                payload.append('sources[]', file);
            });

            if (generatorForm.data.instructions?.trim()) {
                payload.append('instructions', generatorForm.data.instructions.trim());
            }

            return payload;
        });

        generatorForm.post(route('admin.osce-cases.generate'), {
            forceFormData: true,
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                const payload = page?.props?.flash?.generated_case;

                if (payload) {
                    hasAppliedFlash.current = true;
                    handleGeneratedPayload(payload);
                    clearGeneratorForm();
                } else {
                    setGeneratorNotice(
                        'No generated content was returned. Please review your uploads and try again.'
                    );
                }
            },
            onError: () => {
                setGeneratorNotice(null);
                generatorForm.setData('sources', []);
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
            onFinish: () => {
                generatorForm.transform((data) => data);
            },
        });
    };

    const handleToggle = (event) => {
        setData('is_active', event.target.checked);
    };

    const handleNumberChange = (key, event, options = {}) => {
        const raw = event.target.value;
        if (raw === '') {
            setData(key, options.allowNull ? null : 0);
            return;
        }
        setData(key, Number(raw));
    };

    const sanitizeAndSetArray = (key, values) => {
        setData(key, values);
    };

    const sections = [
        {
            id: 'overview',
            label: 'Case Overview',
            description: 'Metadata, duration, and objectives.',
        },
        {
            id: 'stations',
            label: 'Stations & Checklist',
            description: 'Define the expected flow.',
        },
        {
            id: 'persona',
            label: 'Patient Persona',
            description: 'Patient profile, symptoms, vitals.',
        },
        {
            id: 'reasoning',
            label: 'Clinical Reasoning',
            description: 'Anamnesis, red flags, tests.',
        },
        {
            id: 'setting',
            label: 'Setting & Results',
            description: 'Environment constraints and test results.',
        },
    ];

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="text-center space-y-2 mb-8">
                <h1 className="text-2xl font-semibold text-foreground">{title}</h1>
                <p className="text-muted-foreground">{description}</p>
            </div>

            <SectionNav sections={sections} />

            <div className="grid gap-6 xl:grid-cols-[minmax(0,2fr),minmax(0,1fr)]">
                <div className="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <section id="overview" className="clean-card bg-card p-6 space-y-4 xl:col-span-2">
                        <header className="border-b border-border pb-3">
                            <h2 className="text-lg font-medium text-foreground">Case Overview</h2>
                            <p className="text-sm text-muted-foreground">
                                Core metadata trainees see before entering the station.
                            </p>
                        </header>

                        <div className="grid gap-3 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-title">
                                    Title
                                </label>
                                <input
                                    id="osce-title"
                                    type="text"
                                    value={data.title}
                                    onChange={(event) => setData('title', event.target.value)}
                                    required
                                    className={fieldClass}
                                />
                                {errors.title && <p className="text-sm text-red-500">{errors.title}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-difficulty">
                                    Difficulty
                                </label>
                                <select
                                    id="osce-difficulty"
                                    value={data.difficulty}
                                    onChange={(event) => setData('difficulty', event.target.value)}
                                    className={fieldClass}
                                >
                                    <option value="easy">Easy</option>
                                    <option value="medium">Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                                {errors.difficulty && <p className="text-sm text-red-500">{errors.difficulty}</p>}
                            </div>
                        </div>

                        <div className="grid gap-3 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-duration">
                                    Duration (minutes)
                                </label>
                                <input
                                    id="osce-duration"
                                    type="number"
                                    min={1}
                                    max={480}
                                    value={data.duration_minutes}
                                    onChange={(event) => handleNumberChange('duration_minutes', event)}
                                    className={fieldClass}
                                />
                                {errors.duration_minutes && (
                                    <p className="text-sm text-red-500">{errors.duration_minutes}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-is-active">
                                    Visibility
                                </label>
                                <div className="clean-card bg-background p-3 flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">
                                        Make this case available to learners
                                    </span>
                                    <input
                                        id="osce-is-active"
                                        type="checkbox"
                                        checked={!!data.is_active}
                                        onChange={handleToggle}
                                        className="h-4 w-4"
                                    />
                                </div>
                                {errors.is_active && <p className="text-sm text-red-500">{errors.is_active}</p>}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <label className="text-sm font-medium text-foreground" htmlFor="osce-description">
                                Description
                            </label>
                                <textarea
                                    id="osce-description"
                                    value={data.description}
                                    onChange={(event) => setData('description', event.target.value)}
                                    rows={3}
                                    className={fieldClass}
                                />
                            {errors.description && <p className="text-sm text-red-500">{errors.description}</p>}
                        </div>

                        <div className="grid gap-3 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-scenario">
                                    Scenario Overview
                                </label>
                                <textarea
                                    id="osce-scenario"
                                    value={data.scenario}
                                    onChange={(event) => setData('scenario', event.target.value)}
                                    rows={4}
                                    className={fieldClass}
                                />
                                {errors.scenario && <p className="text-sm text-red-500">{errors.scenario}</p>}
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-objectives">
                                    Learning Objectives
                                </label>
                                <textarea
                                    id="osce-objectives"
                                    value={data.objectives}
                                    onChange={(event) => setData('objectives', event.target.value)}
                                    rows={4}
                                    className={fieldClass}
                                />
                                {errors.objectives && <p className="text-sm text-red-500">{errors.objectives}</p>}
                            </div>
                        </div>
                    </section>

                    <section id="stations" className="clean-card bg-card p-6 space-y-4 xl:col-span-1">
                        <header className="border-b border-border pb-3">
                            <h2 className="text-lg font-medium text-foreground">Stations & Checklist</h2>
                            <p className="text-sm text-muted-foreground">
                                Define the journey learners should follow during the encounter.
                            </p>
                        </header>

                        <div className="grid gap-6 md:grid-cols-2">
                            <ListField
                                id="osce-stations"
                                label="Stations"
                                helper="Break the case into logical stages."
                                values={data.stations}
                                onChange={(values) => sanitizeAndSetArray('stations', values)}
                                placeholder="e.g. Focused history"
                            />

                            <ListField
                                id="osce-checklist"
                                label="Checklist"
                                helper="Essential actions assessed during the case."
                                values={data.checklist}
                                onChange={(values) => sanitizeAndSetArray('checklist', values)}
                                placeholder="e.g. Confirms patient identity"
                            />
                        </div>
                    </section>

                    <section id="persona" className="clean-card bg-card p-6 space-y-4 xl:col-span-1">
                        <header className="border-b border-border pb-3">
                            <h2 className="text-lg font-medium text-foreground">Patient Persona</h2>
                            <p className="text-sm text-muted-foreground">Craft the character and clinical presentation for the AI patient.</p>
                        </header>

                        <div className="space-y-3">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-profile">
                                    Patient Profile
                                </label>
                                <textarea
                                    id="osce-profile"
                                    value={data.ai_patient_profile}
                                    onChange={(event) => setData('ai_patient_profile', event.target.value)}
                                    rows={3}
                                    className={fieldClass}
                                />
                                {errors.ai_patient_profile && (
                                    <p className="text-sm text-red-500">{errors.ai_patient_profile}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-instructions">
                                    Patient Behaviour Instructions
                                </label>
                                <textarea
                                    id="osce-instructions"
                                    value={data.ai_patient_instructions}
                                    onChange={(event) => setData('ai_patient_instructions', event.target.value)}
                                    rows={3}
                                    className={fieldClass}
                                />
                                {errors.ai_patient_instructions && (
                                    <p className="text-sm text-red-500">{errors.ai_patient_instructions}</p>
                                )}
                            </div>
                        </div>

                        <ListField
                            id="osce-symptoms"
                            label="Symptoms"
                            helper="Primary symptoms communicated by the patient."
                            values={data.ai_patient_symptoms}
                            onChange={(values) => sanitizeAndSetArray('ai_patient_symptoms', values)}
                            placeholder="e.g. Crushing chest pain"
                        />

                        <KeyValueField
                            id="osce-vitals"
                            label="Vital Signs"
                            helper="Key vitals displayed on request."
                            value={data.ai_patient_vitals}
                            onChange={(entries) => setData('ai_patient_vitals', entries)}
                            keyPlaceholder="Vital"
                            valuePlaceholder="Value"
                        />

                        <KeyValueField
                            id="osce-responses"
                            label="Patient Responses"
                            helper="Pre-scripted replies for common questions."
                            value={data.ai_patient_responses}
                            onChange={(entries) => setData('ai_patient_responses', entries)}
                            keyPlaceholder="Prompt key"
                            valuePlaceholder="Patient reply"
                        />
                    </section>

                    <section id="reasoning" className="clean-card bg-card p-6 space-y-4 xl:col-span-2">
                        <header className="border-b border-border pb-3">
                            <h2 className="text-lg font-medium text-foreground">Clinical Reasoning</h2>
                            <p className="text-sm text-muted-foreground">
                                Clarify the expected diagnostic thinking and supporting data.
                            </p>
                        </header>

                        <div className="grid gap-6 md:grid-cols-2">
                            <ListField
                                id="osce-anamnesis"
                                label="Expected Anamnesis Questions"
                                helper="Questions the learner should ask to score full points."
                                values={data.expected_anamnesis_questions}
                                onChange={(values) => sanitizeAndSetArray('expected_anamnesis_questions', values)}
                                placeholder="e.g. Onset of chest pain"
                            />

                            <ListField
                                id="osce-red-flags"
                                label="Red Flags"
                                helper="Critical findings that must be identified or addressed."
                                values={data.red_flags}
                                onChange={(values) => sanitizeAndSetArray('red_flags', values)}
                                placeholder="e.g. Hypotension"
                            />
                        </div>

                        <ListField
                            id="osce-differentials"
                            label="Differential Diagnoses"
                            helper="Likely differentials the learner should consider."
                            values={data.common_differentials}
                            onChange={(values) => sanitizeAndSetArray('common_differentials', values)}
                            placeholder="e.g. STEMI"
                        />

                        <div className="grid gap-6 lg:grid-cols-2">
                            <ListField
                                id="osce-high-tests"
                                label="Highly Appropriate Tests"
                                helper="Gold-standard investigations for this presentation."
                                values={data.highly_appropriate_tests}
                                onChange={(values) => sanitizeAndSetArray('highly_appropriate_tests', values)}
                            />
                            <ListField
                                id="osce-appropriate-tests"
                                label="Appropriate Tests"
                                helper="Useful but not mandatory investigations."
                                values={data.appropriate_tests}
                                onChange={(values) => sanitizeAndSetArray('appropriate_tests', values)}
                            />
                            <ListField
                                id="osce-acceptable-tests"
                                label="Acceptable Tests"
                                helper="Optional additions that still provide value."
                                values={data.acceptable_tests}
                                onChange={(values) => sanitizeAndSetArray('acceptable_tests', values)}
                            />
                            <ListField
                                id="osce-inappropriate-tests"
                                label="Inappropriate Tests"
                                helper="Investigations that should be avoided."
                                values={data.inappropriate_tests}
                                onChange={(values) => sanitizeAndSetArray('inappropriate_tests', values)}
                            />
                            <ListField
                                id="osce-contra-tests"
                                label="Contraindicated Tests"
                                helper="Tests that would harm the patient."
                                values={data.contraindicated_tests}
                                onChange={(values) => sanitizeAndSetArray('contraindicated_tests', values)}
                            />
                            <ListField
                                id="osce-required-tests"
                                label="Required Tests"
                                helper="Must-order investigations for completion."
                                values={data.required_tests}
                                onChange={(values) => sanitizeAndSetArray('required_tests', values)}
                            />
                        </div>
                    </section>

                    <section id="setting" className="clean-card bg-card p-6 space-y-4 xl:col-span-2">
                        <header className="border-b border-border pb-3">
                            <h2 className="text-lg font-medium text-foreground">Setting & Results</h2>
                            <p className="text-sm text-muted-foreground">
                                Configure environmental constraints and test result templates.
                            </p>
                        </header>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-setting">
                                    Clinical Setting
                                </label>
                                <input
                                    id="osce-setting"
                                    type="text"
                                    value={data.clinical_setting}
                                    onChange={(event) => setData('clinical_setting', event.target.value)}
                                    className={fieldClass}
                                    placeholder="e.g. Emergency department"
                                />
                                {errors.clinical_setting && (
                                    <p className="text-sm text-red-500">{errors.clinical_setting}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-urgency">
                                    Urgency Level
                                </label>
                                <select
                                    id="osce-urgency"
                                    value={data.urgency_level ?? 3}
                                    onChange={(event) => handleNumberChange('urgency_level', event)}
                                    className={fieldClass}
                                >
                                    {urgencyLevels.map((item) => (
                                        <option key={item.value} value={item.value}>
                                            {item.label}
                                        </option>
                                    ))}
                                </select>
                                {errors.urgency_level && (
                                    <p className="text-sm text-red-500">{errors.urgency_level}</p>
                                )}
                            </div>
                        </div>

                        <KeyValueField
                            id="osce-limitations"
                            label="Setting Limitations"
                            helper="Resources or constraints available during the encounter."
                            value={data.setting_limitations}
                            onChange={(entries) => setData('setting_limitations', entries)}
                            keyPlaceholder="Resource"
                            valuePlaceholder="Availability"
                        />

                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-budget">
                                    Case Budget (optional)
                                </label>
                                <input
                                    id="osce-budget"
                                    type="number"
                                    min={0}
                                    step="0.01"
                                    value={data.case_budget ?? ''}
                                    onChange={(event) => {
                                        const raw = event.target.value;
                                        if (raw === '') {
                                            setData('case_budget', null);
                                            return;
                                        }
                                        setData('case_budget', Number(raw));
                                    }}
                                    className={fieldClass}
                                />
                                {errors.case_budget && <p className="text-sm text-red-500">{errors.case_budget}</p>}
                            </div>
                        </div>

                        <KeyValueField
                            id="osce-test-templates"
                            label="Test Result Templates"
                            helper="Default output returned when learners order specific tests."
                            value={data.test_results_templates}
                            onChange={(entries) => setData('test_results_templates', entries)}
                            keyPlaceholder="Test name"
                            valuePlaceholder="Result summary"
                        />
                    </section>
                </div>

                <aside className="space-y-6">
                    <CaseSummarySidebar data={data} />

                    <section className="clean-card bg-card p-6 space-y-4 lg:sticky lg:top-6">
                        <header className="border-b border-border pb-3">
                            <h2 className="text-lg font-medium text-foreground">OSCE Case Generator</h2>
                            <p className="text-sm text-muted-foreground">
                                Upload reference material to draft a complete case automatically.
                            </p>
                        </header>

                        {generatorNotice && (
                            <div className="rounded-md border border-green-500/40 bg-green-500/10 px-3 py-2 text-sm text-green-500">
                                {generatorNotice}
                            </div>
                        )}

                        {generatorError && (
                            <div className="rounded-md border border-red-500/40 bg-red-500/10 px-3 py-2 text-sm text-red-500">
                                {generatorError}
                            </div>
                        )}

                        <div className="space-y-3">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-generator-files">
                                    Reference Files
                                </label>
                                <input
                                    id="osce-generator-files"
                                    type="file"
                                    multiple
                                    ref={fileInputRef}
                                    onChange={(event) => {
                                        const files = Array.from(event.target.files ?? []);
                                        generatorForm.setData('sources', files);
                                    }}
                                    className="w-full text-sm text-muted-foreground"
                                />
                                {generatorForm.errors.sources && (
                                    <p className="text-sm text-red-500">{generatorForm.errors.sources}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground" htmlFor="osce-generator-instructions">
                                    Additional Instructions
                                </label>
                                <textarea
                                    id="osce-generator-instructions"
                                    rows={3}
                                    value={generatorForm.data.instructions}
                                    onChange={(event) => generatorForm.setData('instructions', event.target.value)}
                                    className={fieldClass}
                                    placeholder="Highlight specific learning goals, patient persona, or scoring focus."
                                />
                                {generatorForm.errors.instructions && (
                                    <p className="text-sm text-red-500">{generatorForm.errors.instructions}</p>
                                )}
                            </div>

                            <button
                                type="button"
                                onClick={handleGeneratorSubmit}
                                className="clean-button primary w-full px-4 py-2"
                                disabled={generatorForm.processing || generatorForm.data.sources.length === 0}
                            >
                                {generatorForm.processing ? 'Generating…' : 'Generate from Uploads'}
                            </button>

                            <p className="text-xs text-muted-foreground">
                                Supports TXT, Markdown, JSON, and PDF. Up to 5 files, 10 MB each.
                            </p>
                        </div>
                    </section>
                </aside>
            </div>

            <div className="flex items-center justify-between">
                <Link href={cancelUrl} className="clean-button px-4 py-2">
                    {mode === 'create' ? 'Cancel' : 'Back'}
                </Link>
                <button type="submit" className="clean-button primary px-4 py-2" disabled={processing}>
                    {processing ? 'Saving…' : submitLabel}
                </button>
            </div>
        </form>
    );
}
