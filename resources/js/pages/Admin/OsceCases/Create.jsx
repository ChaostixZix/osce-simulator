import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import OsceCaseForm from '@/components/admin/osce/OsceCaseForm';
import { normalizeCaseData } from '@/components/admin/osce/utils';

export default function Create({ defaults }) {
    const initialData = normalizeCaseData(defaults);
    const form = useForm(initialData);

    const breadcrumbs = [
        { title: 'dashboard', href: route('dashboard') },
        { title: 'admin', href: route('admin.osce-cases.index') },
        { title: 'new case', href: route('admin.osce-cases.create') },
    ];

    const submit = () => {
        form.post(route('admin.osce-cases.store'));
    };

    return (
        <>
            <Head title="Create OSCE Case" />

            <AppLayout breadcrumbs={breadcrumbs}>
                <OsceCaseForm
                    form={form}
                    title="Create OSCE Case"
                    description="Capture every detail required for a realistic OSCE encounter."
                    submitLabel="Save Case"
                    cancelUrl={route('admin.osce-cases.index')}
                    onSubmit={submit}
                    mode="create"
                />
            </AppLayout>
        </>
    );
}
