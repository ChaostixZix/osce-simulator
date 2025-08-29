import React from 'react';
import { Head, Link } from '@inertiajs/react';

function Landing({ auth }) {
    return (
        <div>
            <Head title="Welcome" />
            <h1>Welcome to React Landing Page</h1>
            <p>This is a simplified React landing page.</p>
            {auth && auth.user ? (
                <Link href="/dashboard">Go to Dashboard</Link>
            ) : (
                <Link href="/login">Login</Link>
            )}
        </div>
    );
}

export default Landing;