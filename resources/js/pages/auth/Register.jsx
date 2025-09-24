import React, { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';

export default function Register() {
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setErrors({});
        setProcessing(true);

        router.post('/auth/supabase/register', {
            name,
            email,
            password,
            password_confirmation: passwordConfirmation,
        }, {
            onFinish: () => setProcessing(false),
            onError: (errors) => setErrors(errors),
            onSuccess: (page) => {
                if (page.props.redirect) {
                    window.location.href = page.props.redirect;
                }
            },
        });
    };

    return (
        <div className="min-h-screen bg-background flex items-center justify-center p-4">
            <Head title="Register" />

            <div className="w-full max-w-md space-y-6">
                {/* Header */}
                <div className="text-center space-y-2">
                    <h1 className="text-2xl font-semibold text-foreground">
                        Create your account
                    </h1>
                    <p className="text-muted-foreground">
                        Sign up to get started
                    </p>
                </div>

                {/* Register Form */}
                <div className="clean-card p-6 space-y-6">
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {/* Name Field */}
                        <div className="space-y-2">
                            <label htmlFor="name" className="text-sm font-medium text-foreground">
                                Full Name
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                className="w-full px-3 py-2 border border-border rounded bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                                placeholder="Enter your full name"
                                required
                            />
                            {errors.name && (
                                <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>
                            )}
                        </div>

                        {/* Email Field */}
                        <div className="space-y-2">
                            <label htmlFor="email" className="text-sm font-medium text-foreground">
                                Email address
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                className="w-full px-3 py-2 border border-border rounded bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                                placeholder="Enter your email"
                                required
                            />
                            {errors.email && (
                                <p className="text-sm text-red-600 dark:text-red-400">{errors.email}</p>
                            )}
                        </div>

                        {/* Password Field */}
                        <div className="space-y-2">
                            <label htmlFor="password" className="text-sm font-medium text-foreground">
                                Password
                            </label>
                            <input
                                id="password"
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                className="w-full px-3 py-2 border border-border rounded bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                                placeholder="Create a password"
                                required
                            />
                            {errors.password && (
                                <p className="text-sm text-red-600 dark:text-red-400">{errors.password}</p>
                            )}
                        </div>

                        {/* Password Confirmation Field */}
                        <div className="space-y-2">
                            <label htmlFor="password_confirmation" className="text-sm font-medium text-foreground">
                                Confirm Password
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                value={passwordConfirmation}
                                onChange={(e) => setPasswordConfirmation(e.target.value)}
                                className="w-full px-3 py-2 border border-border rounded bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                                placeholder="Confirm your password"
                                required
                            />
                            {errors.password_confirmation && (
                                <p className="text-sm text-red-600 dark:text-red-400">{errors.password_confirmation}</p>
                            )}
                        </div>

                        {/* Submit Button */}
                        <button
                            type="submit"
                            disabled={processing}
                            className="clean-button primary w-full py-2 px-4"
                        >
                            {processing ? 'Creating account...' : 'Create Account'}
                        </button>
                    </form>
                </div>

                {/* Sign In Link */}
                <div className="text-center">
                    <p className="text-muted-foreground">
                        Already have an account?{' '}
                        <Link
                            href="/auth/supabase/login"
                            className="text-primary hover:text-primary/80 transition-colors font-medium"
                        >
                            Sign in
                        </Link>
                    </p>
                </div>

            </div>
        </div>
    );
}