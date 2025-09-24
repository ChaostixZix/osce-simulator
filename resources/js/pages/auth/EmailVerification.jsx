import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Link } from '@inertiajs/react';

export default function EmailVerification() {
    const [resending, setResending] = useState(false);
    const [resent, setResent] = useState(false);
    const [error, setError] = useState(null);

    const handleResend = (e) => {
        e.preventDefault();
        setResending(true);
        setError(null);

        router.post('/auth/supabase/email/verification-notification', {}, {
            onFinish: () => setResending(false),
            onSuccess: () => {
                setResent(true);
                setTimeout(() => setResent(false), 3000);
            },
            onError: (errors) => {
                setError(errors.email || 'Failed to resend verification email.');
            },
        });
    };

    const handleLogout = (e) => {
        e.preventDefault();
        router.post('/auth/supabase/logout', {}, {
            onSuccess: (page) => {
                if (page.props.redirect) {
                    window.location.href = page.props.redirect;
                }
            },
        });
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800">
            <div className="max-w-md w-full space-y-8 p-8">
                <div className="text-center">
                    <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 dark:bg-yellow-900">
                        <svg className="h-8 w-8 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h2 className="mt-6 text-3xl font-bold text-foreground">
                        Verify Your Email
                    </h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                        Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
                    </p>
                </div>

                <div className="clean-card p-8">
                    <div className="text-center space-y-4">
                        <p className="text-sm text-muted-foreground">
                            A verification link has been sent to your email address. Please check your inbox and click the link to verify your account.
                        </p>
                        
                        <button
                            onClick={handleResend}
                            disabled={resending || resent}
                            className="clean-button w-full justify-center py-3"
                        >
                            {resending ? 'Sending...' : resent ? 'Email Sent!' : 'Resend Verification Email'}
                        </button>

                        {error && (
                            <p className="text-sm text-destructive mt-2">{error}</p>
                        )}

                        <div className="pt-4 border-t border-border">
                            <p className="text-sm text-muted-foreground mb-4">
                                If you're having trouble receiving the verification email, please:
                            </p>
                            <ul className="text-sm text-muted-foreground space-y-1 text-left">
                                <li>• Check your spam/junk folder</li>
                                <li>• Make sure you entered the correct email address</li>
                                <li>• Wait a few minutes before requesting another email</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div className="text-center space-y-2">
                    <p className="text-sm text-muted-foreground">
                        Already verified?{' '}
                        <Link href="/login" className="text-primary hover:text-primary/80 font-medium">
                            Sign in
                        </Link>
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Wrong email address?{' '}
                        <Link href="/logout" onClick={handleLogout} className="text-primary hover:text-primary/80 font-medium">
                            Sign out and try again
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    );
}