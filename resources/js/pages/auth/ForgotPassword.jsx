import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

export default function ForgotPassword() {
    const [email, setEmail] = useState('');
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);
    const [success, setSuccess] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setErrors({});
        setProcessing(true);

        router.post('/auth/supabase/forgot-password', {
            email,
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
                        Forgot Password
                    </h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Enter your email address and we'll send you a link to reset your password
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
                                Email Sent
                            </h3>
                            <p className="mt-2 text-sm text-muted-foreground">
                                We've sent a password reset link to your email address. Please check your inbox and follow the instructions.
                            </p>
                            <div className="mt-6">
                                <Link href="/login" className="clean-button">
                                    Return to Login
                                </Link>
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

                            <button
                                type="submit"
                                disabled={processing}
                                className="clean-button w-full justify-center py-3"
                            >
                                {processing ? 'Sending Email...' : 'Send Reset Link'}
                            </button>
                        </form>
                    </div>
                )}

                <div className="text-center">
                    <p className="text-sm text-muted-foreground">
                        Remember your password?{' '}
                        <Link href="/login" className="text-primary hover:text-primary/80 font-medium">
                            Sign in
                        </Link>
                    </p>
                </div>

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