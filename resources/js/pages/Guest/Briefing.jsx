import StationLayout, { StationButton } from '@/components/guest/StationLayout';
import { useForm } from '@inertiajs/react';

export default function Briefing({ station }) {
    const form = useForm({});
    return (
        <StationLayout title="Station briefing">
            <section className="briefing-page">
                <p className="section-label">Your station briefing</p>
                <h1>{station.title}</h1>
                <p className="page-lead">Read the scenario. Start when you are ready.</p>
                <div className="briefing-grid">
                    <article className="clean-card station-card">
                        <span className="record-label">The scenario</span>
                        <p className="scenario-text">{station.scenario}</p>
                        <h2>Your objectives</h2>
                        <p>{station.objectives}</p>
                        <dl className="brief-facts">
                            <div>
                                <dt>Setting</dt>
                                <dd>{station.clinical_setting}</dd>
                            </div>
                            <div>
                                <dt>Duration</dt>
                                <dd>{station.duration_minutes} minutes</dd>
                            </div>
                        </dl>
                    </article>
                    <aside className="briefing-aside">
                        <h2>What to expect</h2>
                        <p>Ask the patient questions in English. You can review vital signs and request the investigations available in this case.</p>
                        <p>
                            To finish, record a working diagnosis, differential diagnoses and plan. Your evidence review will be saved in this browser
                            session.
                        </p>
                        <p className="quiet-note">
                            The timer starts when you enter. Refreshing does not restart it. You can still submit your summary after time runs out.
                        </p>
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.post('/demo/sessions');
                            }}
                        >
                            <StationButton disabled={form.processing}>
                                {form.processing ? 'Opening your station...' : 'Start the encounter'}
                            </StationButton>
                            {form.errors.message && (
                                <p role="alert" className="form-error">
                                    {form.errors.message}
                                </p>
                            )}
                        </form>
                    </aside>
                </div>
            </section>
        </StationLayout>
    );
}
