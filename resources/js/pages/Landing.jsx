import StationLayout from '@/components/guest/StationLayout';
import { Link } from '@inertiajs/react';

export default function Landing({ demoAvailable = true }) {
    return (
        <StationLayout title="Practise a clinical encounter">
            <section className="landing-opening">
                <div className="landing-copy">
                    <p className="section-label">Clinical skills, one encounter at a time</p>
                    <h1>
                        Practise the conversation.
                        <br />
                        <em>Review the evidence.</em>
                    </h1>
                    <p className="landing-intro">
                        An OSCE station you can work through on your own. Take a history from a virtual patient, record your clinical decisions, then
                        look back at what you covered.
                    </p>
                    {demoAvailable ? (
                        <Link href="/demo" className="clean-button station-button">
                            Begin a guest station
                        </Link>
                    ) : (
                        <p role="status">The practice station is being prepared. Please return shortly.</p>
                    )}
                    <p className="entry-note">No account required. Scripted patient. No clinical grade.</p>
                </div>
                <div className="station-preview clean-card">
                    <div className="preview-heading">
                        <span>Inside the practice room</span>
                        <span>01 / Encounter</span>
                    </div>
                    <p className="preview-type">Emergency setting</p>
                    <h2>
                        Chest Pain
                        <br />
                        Assessment
                    </h2>
                    <p>A 62-year-old male presents to the emergency department with acute onset chest pain that started 2 hours ago.</p>
                    <dl className="preview-facts">
                        <div>
                            <dt>Time available</dt>
                            <dd>25 minutes</dd>
                        </div>
                        <div>
                            <dt>Your role</dt>
                            <dd>Take the history</dd>
                        </div>
                    </dl>
                    <div className="preview-end">
                        <span className="record-symbol" aria-hidden="true">
                            +
                        </span>
                        <p>
                            The case is ready.
                            <br />
                            <strong>The next question is yours.</strong>
                        </p>
                    </div>
                </div>
            </section>
            <section className="landing-review" id="review">
                <div>
                    <p className="section-label">A useful debrief</p>
                    <h2>
                        See what made it
                        <br />
                        into the encounter.
                    </h2>
                </div>
                <div className="review-explanation">
                    <p>
                        Your review connects the questions you asked with the patient&apos;s recorded replies. It keeps your investigation results and
                        clinical summary together, alongside the original station checklist.
                    </p>
                    <div className="explanation-row">
                        <span>Recorded</span>
                        <p>Discussion topics, requested findings and your submitted decisions.</p>
                    </div>
                    <div className="explanation-row">
                        <span>For an examiner</span>
                        <p>Diagnostic accuracy, examination technique and treatment safety. This tool does not grade those skills.</p>
                    </div>
                </div>
            </section>
            <section className="landing-note">
                <span>Before you begin</span>
                <p>
                    This is an educational simulation with a limited, authored patient history. If a detail is not in the case, the patient will say
                    so. Use an examiner to assess your clinical performance.
                </p>
            </section>
        </StationLayout>
    );
}
