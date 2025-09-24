import { useState } from 'react';
import { router } from '@inertiajs/react';

export default function ResetPassword() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);
    const [success, setSuccess] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setErrors({});
        setProcessing(true);

        router.post('/auth/supabase/reset-password', {
            email,
            password,
            password_confirmation: passwordConfirmation,
        }, {
            onFinish: () => setProcessing(false),
            onError: (errors) => setErrors(errors),
            onSuccess: (page) => {
                if (page.props.success) {
                    setSuccess(true);
                }
            },
        });
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
            <div className="max-w-md w-full space-y-8 p-8">
                <div className="text-center">
                    <h2 className="text-3xl font-bold text-foreground">
                        Reset Password
                    </h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Create a new password for your account
                    </p>
                </div>

                {success ? (
                    <div className="clean-card p-8">
                        <div className="text-center">
                            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900">
                                <svg className="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 className="mt-4 text-lg font-medium text-foreground">
                                Password Reset Successful
                            </h3>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Your password has been reset successfully. You can now sign in with your new password.
                            </p>
                            <div className="mt-6">
                                <button
                                    onClick={() => router.visit('/login')}
                                    className="clean-button"
                                >
                                    Return to Login
                                </button>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="clean-card p-8">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <label htmlFor="email" className="block text-sm font-medium text-foreground mb-2">
                                    Email Address
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-background text-foreground"
                                    placeholder="Enter your email"
                                    required
                                />
                                {errors.email && (
                                    <p className="mt-1 text-sm text-destructive">{errors.email}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="password" className="block text-sm font-medium text-foreground mb-2">
                                    New Password
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-background text-foreground"
                                    placeholder="Create a new password"
                                    required
                                />
                                {errors.password && (
                                    <p className="mt-1 text-sm text-destructive">{errors.password}</p>
                                )}
                            </div>

                            <div>
                                <label htmlFor="password_confirmation" className="block text-sm font-medium text-foreground mb-2">
                                    Confirm New Password
                                </label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    value={passwordConfirmation}
                                    onChange={(e) => setPasswordConfirmation(e.target.value)}
                                    className="w-full px-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-background text-foreground"
                                    placeholder="Confirm your new password"
                                    required
                                />
                                {errors.password_confirmation && (
                                    <p className="mt-1 text-sm text-destructive">{errors.password_confirmation}</p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="clean-button w-full justify-center py-3"
                            >
                                {processing ? 'Resetting Password...' : 'Reset Password'}
                            </button>
                        </form>
                    </div>
                )}

                {!success && Object.keys(errors).length > 0 && (
                    <div className="clean-card p-4 border-destructive/20 bg-destructive/5">
                        <h3 className="text-sm font-medium text-destructive mb-2">
                            There was a problem
                        </h3>
                        <ul className="text-sm text-destructive space-y-1">
                            {Object.entries(errors).map(([key, value]) => (
                                <li key={key}>{value}</li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </div>
    );
}