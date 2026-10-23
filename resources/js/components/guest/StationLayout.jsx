import { useTheme } from '@/contexts/ThemeContext';
import { Head, Link } from '@inertiajs/react';
import '../../../css/guest.css';

export default function StationLayout({ title, children }) {
    const { isDark, setTheme } = useTheme();
    return (
        <div className="station-app">
            <Head title={title}>
                <meta
                    name="description"
                    content="Practise an OSCE station with a scripted virtual patient. Take a history, record your decisions and review the evidence from your encounter."
                />
            </Head>
            <a className="station-skip" href="#main">
                Skip to content
            </a>
            <header className="station-header">
                <Link href="/" className="station-brand" aria-label="OSCE Simulator home">
                    <span className="brand-mark" aria-hidden="true">
                        o.
                    </span>
                    <span>
                        OSCE Simulator<small>The practice room</small>
                    </span>
                </Link>
                <nav aria-label="Main navigation">
                    <Link href="/demo" className="station-nav-link">
                        Practice a station
                    </Link>
                    <button
                        type="button"
                        className="theme-control"
                        onClick={() => setTheme(isDark ? 'light' : 'dark')}
                        aria-label={isDark ? 'Use light theme' : 'Use dark theme'}
                    >
                        {isDark ? 'Light' : 'Dark'} <span aria-hidden="true">◐</span>
                    </button>
                </nav>
            </header>
            <main id="main">{children}</main>
            <footer className="station-footer">
                <span>
                    OSCE Simulator <span className="footer-divider">/</span> A place to practise.
                </span>
                <Link href="/privacy-policy">Privacy &amp; your encounter</Link>
            </footer>
        </div>
    );
}

export function StationButton({ children, secondary = false, className = '', ...props }) {
    return (
        <button className={`clean-button station-button ${secondary ? 'secondary' : ''} ${className}`} {...props}>
            {children}
        </button>
    );
}

export function Findings({ findings }) {
    if (findings === null || findings === undefined) return null;
    if (typeof findings !== 'object') return <span>{String(findings)}</span>;
    return (
        <dl className="findings">
            {Object.entries(findings)
                .filter(([key]) => key !== 'status')
                .map(([key, value]) => (
                    <div key={key}>
                        <dt>{key.replaceAll('_', ' ')}</dt>
                        <dd>
                            <Findings findings={value} />
                        </dd>
                    </div>
                ))}
        </dl>
    );
}
