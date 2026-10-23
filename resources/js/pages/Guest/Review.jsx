import StationLayout, { Findings, StationButton } from '@/components/guest/StationLayout';
import { useForm } from '@inertiajs/react';

export default function Review({ station, encounterId, review, messages }) {
    const restart = useForm({});
    const recorded = review.observations.filter((item) => item.recorded);
    const unrecorded = review.observations.filter((item) => !item.recorded);
    return (
        <StationLayout title="Your evidence review">
            <section className="review-page">
                <div className="review-heading">
                    <div>
                        <p className="section-label">Encounter complete / {encounterId}</p>
                        <h1>Your evidence review</h1>
                        <p className="page-lead">{station.title}</p>
                    </div>
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            restart.post('/demo/sessions');
                        }}
                    >
                        <StationButton secondary disabled={restart.processing}>
                            {restart.processing ? 'Opening station...' : 'Practise this station again'}
                        </StationButton>
                    </form>
                </div>
                <div className="review-summary">
                    <span className="review-check" aria-hidden="true">
                        ✓
                    </span>
                    <div>
                        <h2>Your encounter is saved.</h2>
                        <p>
                            {review.message_count} questions recorded. {review.actions.length} clinical findings requested. Your summary is included
                            below.
                        </p>
                    </div>
                </div>
                <p className="review-limitation">{review.limitations}</p>
                <div className="review-grid">
                    <section className="station-card clean-card evidence-section">
                        <p className="record-label">01 / The conversation</p>
                        <h2>Topics in your transcript</h2>
                        <p className="quiet-note">
                            These are discussion records, not marks. Read the actual question and answer to judge their quality.
                        </p>
                        {recorded.length === 0 && (
                            <p>
                                No supported history topic was recorded. Use the patient conversation to gather the history before your next
                                submission.
                            </p>
                        )}
                        {recorded.map((item) => (
                            <div className="evidence-item" key={item.topic}>
                                <h3>
                                    {item.label}
                                    <span>Recorded</span>
                                </h3>
                                <blockquote>
                                    <span>You asked</span>
                                    {item.question}
                                </blockquote>
                                <p>
                                    <span>Patient replied</span>
                                    {item.response}
                                </p>
                            </div>
                        ))}
                        {unrecorded.length > 0 && (
                            <div className="not-recorded">
                                <h3>No recorded reply for</h3>
                                <p>{unrecorded.map((item) => item.label).join(', ')}.</p>
                                <p className="quiet-note">Revisit these topics in another attempt. A missing record is not a clinical score.</p>
                            </div>
                        )}
                        {review.unknown_count > 0 && (
                            <p className="quiet-note">{review.unknown_count} questions concerned details not available in the authored case.</p>
                        )}
                    </section>
                    <aside className="station-card clean-card checklist-section">
                        <p className="record-label">02 / Examiner reference</p>
                        <h2>The original checklist</h2>
                        <p className="quiet-note">
                            Use this unchanged station checklist with an examiner. The app does not automatically mark these skills as achieved.
                        </p>
                        <ol className="clinical-checklist">
                            {review.checklist.map((item) => (
                                <li key={item}>{item}</li>
                            ))}
                        </ol>
                    </aside>
                    <section className="station-card clean-card">
                        <p className="record-label">03 / Your decisions</p>
                        <h2>Submitted summary</h2>
                        <dl className="submitted-summary">
                            {[
                                ['diagnosis', 'Working diagnosis'],
                                ['differential_diagnosis', 'Differential diagnoses'],
                                ['plan', 'Management plan'],
                            ].map(([key, label]) => (
                                <div key={key}>
                                    <dt>{label}</dt>
                                    <dd>{review.summary[key]}</dd>
                                </div>
                            ))}
                        </dl>
                    </section>
                    <section className="station-card clean-card">
                        <p className="record-label">04 / Requested findings</p>
                        <h2>Clinical record</h2>
                        {review.actions.length === 0 ? (
                            <p>No clinical findings were requested.</p>
                        ) : (
                            review.actions.map((item) => (
                                <details className="finding-detail" key={item.key}>
                                    <summary>{item.name}</summary>
                                    <Findings findings={item.findings} />
                                </details>
                            ))
                        )}
                    </section>
                </div>
                <details className="full-transcript station-card clean-card">
                    <summary>Read the complete conversation ({messages.length} messages)</summary>
                    {messages.length === 0 && <p>No conversation was recorded.</p>}
                    {messages.map((message) => (
                        <div className="transcript-line" key={message.id}>
                            <strong>{message.sender_type === 'user' ? 'You' : 'Patient'}</strong>
                            <p>{message.message}</p>
                        </div>
                    ))}
                </details>
            </section>
        </StationLayout>
    );
}
