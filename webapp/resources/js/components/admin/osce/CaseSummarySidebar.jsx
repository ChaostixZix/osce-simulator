import React from 'react';
import { toArrayOfStrings } from './utils';

const SummaryStat = ({ label, value }) => (
    <div className="clean-card bg-background p-3">
        <p className="text-xs text-muted-foreground uppercase tracking-wide">{label}</p>
        <p className="text-lg font-semibold text-foreground">{value}</p>
    </div>
);

const truncate = (value, max = 120) => {
    if (!value) {
        return '—';
    }
    return value.length > max ? `${value.slice(0, max)}…` : value;
};

export default function CaseSummarySidebar({ data }) {
    const stations = toArrayOfStrings(data?.stations);
    const checklist = toArrayOfStrings(data?.checklist);
    const anamnesis = toArrayOfStrings(data?.expected_anamnesis_questions);
    const tests = [
        ...toArrayOfStrings(data?.highly_appropriate_tests),
        ...toArrayOfStrings(data?.appropriate_tests),
        ...toArrayOfStrings(data?.acceptable_tests),
        ...toArrayOfStrings(data?.required_tests),
    ];

    const summary = [
        { label: 'Stations', value: stations.length },
        { label: 'Checklist Items', value: checklist.length },
        { label: 'Key Questions', value: anamnesis.length },
        { label: 'Recommended Tests', value: tests.length },
    ];

    return (
        <div className="space-y-4">
            <section className="clean-card bg-card p-6 space-y-3">
                <header className="space-y-1">
                    <h2 className="text-lg font-medium text-foreground">Case Summary</h2>
                    <p className="text-sm text-muted-foreground">
                        Quick snapshot of what learners will see.
                    </p>
                </header>

                <div className="grid gap-3 sm:grid-cols-2">
                    {summary.map((item) => (
                        <SummaryStat key={item.label} label={item.label} value={item.value} />
                    ))}
                </div>

                <div className="space-y-3">
                    <div>
                        <h3 className="text-sm font-medium text-foreground">Scenario Intro</h3>
                        <p className="text-sm text-muted-foreground">
                            {truncate(data?.scenario ?? '')}
                        </p>
                    </div>
                    <div>
                        <h3 className="text-sm font-medium text-foreground">Patient Persona</h3>
                        <p className="text-sm text-muted-foreground">
                            {truncate(data?.ai_patient_profile ?? '')}
                        </p>
                    </div>
                </div>
            </section>
        </div>
    );
}
