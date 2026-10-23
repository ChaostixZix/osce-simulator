import StationLayout from '@/components/guest/StationLayout';

export default function PrivacyPolicy() {
    return (
        <StationLayout title="Privacy and your encounter">
            <article className="privacy-page">
                <p className="section-label">Your practice record</p>
                <h1>Privacy &amp; your encounter</h1>
                <p className="page-lead">Use fictional information when practising. Do not enter real patient details.</p>
                <h2>What the guest station stores</h2>
                <p>
                    The application stores your questions, scripted patient replies, requested clinical findings, submitted summary and evidence
                    review. A session cookie connects this browser to your encounter. No account or email address is required.
                </p>
                <h2>Who can access the record</h2>
                <p>
                    Guest encounters are accessible through the browser session that created them. Site operators with database access can also access
                    stored records. Clearing cookies or letting the session expire can remove your access to a review.
                </p>
                <h2>External services</h2>
                <p>
                    The guest patient and evidence review run on this server. They do not send your conversation to an AI provider. Account-based
                    features, when enabled, may use WorkOS for login and configured AI providers for processing.
                </p>
                <h2>Storage and retention</h2>
                <p>
                    Guest encounters older than seven days are removed by a daily cleanup. This practice record
                    is not a medical record or a formal assessment.
                </p>
            </article>
        </StationLayout>
    );
}
