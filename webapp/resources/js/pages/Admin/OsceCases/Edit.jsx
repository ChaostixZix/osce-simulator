import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import OsceCaseForm from '@/components/admin/osce/OsceCaseForm';
import { normalizeCaseData } from '@/components/admin/osce/utils';

export default function Edit({ case: osceCase }) {
    const normalizedCase = normalizeCaseData(osceCase);
    const form = useForm({
        ...normalizedCase,
        _method: 'PUT',
    });

    const breadcrumbs = [
        { title: 'dashboard', href: route('dashboard') },
        { title: 'admin', href: route('admin.osce-cases.index') },
        { title: osceCase?.title ?? 'edit case', href: route('admin.osce-cases.edit', osceCase?.id) },
    ];

    const submit = () => {
        form.post(route('admin.osce-cases.update', osceCase.id));
    };

    return (
        <>
            <Head title={`Edit · ${osceCase?.title ?? ''}`} />

            <AppLayout breadcrumbs={breadcrumbs}>
                <OsceCaseForm
                    form={form}
                    title={`Edit OSCE Case · ${osceCase?.title ?? ''}`}
                    description="Revise content, vitals, and scoring guidance for this scenario."
                    submitLabel="Update Case"
                    cancelUrl={route('admin.osce-cases.index')}
                    onSubmit={submit}
                    mode="edit"
                />
            </AppLayout>
        </>
    );
}
