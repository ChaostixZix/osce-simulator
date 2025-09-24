import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';

export default function Login({ providers = {} }) {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(false);
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setErrors({});
        setProcessing(true);

        router.post('/auth/supabase/login', {
            email,
            password,
            remember: remember,
        }, {
            onFinish: () => setProcessing(false),
            onError: (errors) => setErrors(errors),
        });
    };

    const handleOAuthLogin = (provider) => {
        window.location.href = `/auth/supabase/oauth/${provider}`;
    };

    return (
        <div className="min-h-screen bg-background flex items-center justify-center p-4">
            <Head title="Login" />

            <div className="w-full max-w-md space-y-6">
                {/* Header */}
                <div className="text-center space-y-2">
                    <h1 className="text-2xl font-semibold text-foreground">
                        Welcome back
                    </h1>
                    <p className="text-muted-foreground">
                        Sign in to your account
                    </p>
                </div>

                {/* Login Form */}
                <div className="clean-card p-6 space-y-6">
                    <form onSubmit={handleSubmit} className="space-y-4">
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
                                placeholder="Enter your password"
                                required
                            />
                            {errors.password && (
                                <p className="text-sm text-red-600 dark:text-red-400">{errors.password}</p>
                            )}
                        </div>

                        {/* Remember Me & Forgot Password */}
                        <div className="flex items-center justify-between">
                            <div className="flex items-center">
                                <input
                                    id="remember"
                                    type="checkbox"
                                    checked={remember}
                                    onChange={(e) => setRemember(e.target.checked)}
                                    className="h-4 w-4 text-primary border-border rounded focus:ring-ring"
                                />
                                <label htmlFor="remember" className="ml-2 text-sm text-muted-foreground">
                                    Remember me
                                </label>
                            </div>

                            <a
                                href="/auth/supabase/forgot-password"
                                className="text-sm text-primary hover:text-primary/80 transition-colors"
                            >
                                Forgot password?
                            </a>
                        </div>

                        {/* Submit Button */}
                        <button
                            type="submit"
                            disabled={processing}
                            className="clean-button primary w-full py-2 px-4"
                        >
                            {processing ? 'Signing in...' : 'Sign in'}
                        </button>
                    </form>

                    {/* OAuth Divider */}
                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <div className="w-full border-t border-border"></div>
                        </div>
                        <div className="relative flex justify-center text-sm">
                            <span className="px-2 bg-card text-muted-foreground">
                                Or continue with
                            </span>
                        </div>
                    </div>

                    {/* OAuth Providers */}
                    <div className="grid grid-cols-3 gap-3">
                        {providers.google && (
                            <button
                                onClick={() => handleOAuthLogin('google')}
                                className="clean-button p-2 flex items-center justify-center"
                                title="Sign in with Google"
                            >
                                <svg className="w-5 h-5" viewBox="0 0 24 24">
                                    <g transform="matrix(1, 0, 0, 1, 27.009001, -39.238998)">
                                        <path fill="#4285F4" d="M -3.264 51.509 C -3.264 50.039 -3.334 48.609 -3.454 47.229 L -14.754 47.229 L -14.754 55.529 L -8.284 55.529 C -8.764 57.809 -10.114 59.799 -12.134 61.129 L -12.134 66.659 L -6.924 66.659 C -1.464 61.599 -3.264 54.689 -3.264 51.509 Z"/>
                                        <path fill="#34A853" d="M -14.754 69.999 C -8.254 69.999 -2.754 67.609 0.246 63.599 L -5.064 59.069 C -6.924 60.829 -9.354 61.999 -12.204 61.999 C -16.544 61.999 -20.264 58.889 -21.584 54.569 L -26.824 54.569 L -26.824 60.259 C -23.754 66.269 -17.634 69.999 -14.754 69.999 Z"/>
                                        <path fill="#FBBC05" d="M -21.584 54.569 C -21.884 53.499 -22.054 52.359 -22.054 51.199 C -22.054 50.039 -21.884 48.899 -21.164 47.829 L -21.164 42.359 L -26.824 42.359 C -27.944 44.669 -28.554 47.359 -28.554 50.199 C -28.554 53.039 -27.944 55.729 -26.824 58.039 L -21.584 54.569 Z"/>
                                        <path fill="#EA4335" d="M -14.754 32.399 C -11.914 32.399 -9.374 33.409 -7.514 35.369 L -2.424 30.279 C -5.754 26.999 -10.124 25.199 -14.754 25.199 C -17.634 25.199 -23.754 28.929 -26.824 34.939 L -21.164 40.629 C -19.844 36.309 -16.124 33.199 -14.754 33.199 Z"/>
                                    </g>
                                </svg>
                            </button>
                        )}

                        {providers.github && (
                            <button
                                onClick={() => handleOAuthLogin('github')}
                                className="clean-button p-2 flex items-center justify-center"
                                title="Sign in with GitHub"
                            >
                                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path fillRule="evenodd" clipRule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
                                </svg>
                            </button>
                        )}

                        {providers.twitter && (
                            <button
                                onClick={() => handleOAuthLogin('twitter')}
                                className="clean-button p-2 flex items-center justify-center"
                                title="Sign in with Twitter"
                            >
                                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </button>
                        )}
                    </div>
                </div>

                {/* Sign Up Link */}
                <div className="text-center">
                    <p className="text-muted-foreground">
                        Don't have an account?{' '}
                        <a
                            href="/auth/supabase/register"
                            className="text-primary hover:text-primary/80 transition-colors font-medium"
                        >
                            Sign up
                        </a>
                    </p>
                </div>
            </div>
        </div>
    );
}