import StationLayout, { Findings, StationButton } from '@/components/guest/StationLayout';
import { useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function messageId() {
    if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID();
    const bytes = new Uint8Array(16);
    globalThis.crypto.getRandomValues(bytes);
    bytes[6] = (bytes[6] & 15) | 64;
    bytes[8] = (bytes[8] & 63) | 128;
    const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');
    return [hex.slice(0, 8), hex.slice(8, 12), hex.slice(12, 16), hex.slice(16, 20), hex.slice(20)].join('-');
}

export default function Encounter({ station, encounter, messages, investigations }) {
    const chat = useForm({ message: '', client_message_id: messageId() });
    const action = useForm({ action: '' });
    const summary = useForm({ diagnosis: '', differential_diagnosis: '', plan: '' });
    const [remaining, setRemaining] = useState(encounter.remaining_seconds);
    const [showSummary, setShowSummary] = useState(false);
    const transcript = useRef(null);
    const summaryHeading = useRef(null);
    const input = useRef(null);
    const expired = remaining <= 0;
    const base = `/demo/sessions/${encounter.id}`;

    useEffect(() => {
        const deadline = Date.now() + encounter.remaining_seconds * 1000;
        setRemaining(encounter.remaining_seconds);
        const tick = () => setRemaining(Math.max(0, Math.ceil((deadline - Date.now()) / 1000)));
        const timer = setInterval(tick, 1000);
        document.addEventListener('visibilitychange', tick);
        return () => {
            clearInterval(timer);
            document.removeEventListener('visibilitychange', tick);
        };
    }, [encounter.remaining_seconds]);

    useEffect(() => {
        if (transcript.current) transcript.current.scrollTop = transcript.current.scrollHeight;
    }, [messages.length, chat.processing]);

    useEffect(() => {
        if (showSummary) summaryHeading.current?.focus();
    }, [showSummary]);

    function send(event) {
        event.preventDefault();
        if (chat.processing || expired || !chat.data.message.trim()) return;
        chat.post(`${base}/messages`, {
            preserveScroll: true,
            onSuccess: () => {
                chat.setData({ message: '', client_message_id: messageId() });
                input.current?.focus();
            },
        });
    }

    function record(key) {
        action.transform(() => ({ action: key }));
        action.post(`${base}/actions`, { preserveScroll: true });
    }

    function submit(event) {
        event.preventDefault();
        summary.post(`${base}/submit`);
    }

    const clock = `${Math.floor(remaining / 60)
        .toString()
        .padStart(2, '0')}:${(remaining % 60).toString().padStart(2, '0')}`;
    return (
        <StationLayout title="Patient encounter">
            <section className="encounter-page">
                <div className="encounter-heading">
                    <div>
                        <p className="section-label">The encounter</p>
                        <h1>{station.title}</h1>
                    </div>
                    <div className="station-clock">
                        <span>{expired ? 'Time has ended' : 'Time remaining'}</span>
                        <strong aria-label={`${Math.floor(remaining / 60)} minutes ${remaining % 60} seconds remaining`}>{clock}</strong>
                    </div>
                </div>
                {expired && (
                    <p className="notice" role="status">
                        Time has ended. Your conversation is saved. Complete your summary to receive the review.
                    </p>
                )}
                <div className="encounter-grid">
                    <div className="conversation-column">
                        <section className="conversation clean-card">
                            <header className="conversation-header">
                                <div>
                                    <h2>Patient conversation</h2>
                                    <p>Replies come from the authored case.</p>
                                </div>
                                <span className="mode-label">Scripted patient</span>
                            </header>
                            <div
                                className="transcript"
                                ref={transcript}
                                role="log"
                                aria-label="Patient conversation"
                                aria-live="polite"
                                aria-relevant="additions"
                            >
                                {messages.length === 0 && (
                                    <div className="conversation-empty">
                                        <span className="record-label">The patient is waiting</span>
                                        <h3>Start with your first question.</h3>
                                        <p>Introduce yourself, then ask about the presenting complaint. Your conversation will appear here.</p>
                                    </div>
                                )}
                                {messages.map((message) => (
                                    <article
                                        className={`chat-message ${message.sender_type === 'user' ? 'from-you' : 'from-patient'}`}
                                        key={message.id}
                                    >
                                        <span>{message.sender_type === 'user' ? 'You' : 'Patient'}</span>
                                        <p>{message.message}</p>
                                        {message.source_topic === 'unrecorded' && <small>Information unavailable in this case</small>}
                                    </article>
                                ))}
                                {chat.processing && (
                                    <p role="status" className="reply-status">
                                        Recording your question...
                                    </p>
                                )}
                            </div>
                            <form className="chat-composer" onSubmit={send}>
                                <label htmlFor="patient-question">Your question</label>
                                <div className="composer-controls">
                                    <input
                                        ref={input}
                                        id="patient-question"
                                        value={chat.data.message}
                                        onChange={(event) => chat.setData('message', event.target.value)}
                                        placeholder={expired ? 'Conversation ended' : 'Ask the patient a question...'}
                                        maxLength={1000}
                                        autoComplete="off"
                                        disabled={expired || chat.processing}
                                    />
                                    <StationButton type="submit" disabled={expired || chat.processing || !chat.data.message.trim()}>
                                        {chat.processing ? 'Sending...' : 'Send'}
                                    </StationButton>
                                </div>
                                {chat.errors.message && (
                                    <p className="form-error" role="alert">
                                        {chat.errors.message}
                                    </p>
                                )}
                            </form>
                        </section>
                        {!showSummary && (
                            <div className="finish-row">
                                <p>Ready to bring your findings together?</p>
                                <StationButton onClick={() => setShowSummary(true)}>Finish &amp; write summary</StationButton>
                            </div>
                        )}
                        {showSummary && (
                            <section className="station-card clean-card summary-section">
                                <h2 ref={summaryHeading} tabIndex={-1}>
                                    Your clinical summary
                                </h2>
                                <p>Record your reasoning before reviewing the encounter. Each field needs at least 10 characters.</p>
                                <form onSubmit={submit} className="summary-form">
                                    {[
                                        ['diagnosis', 'Working diagnosis'],
                                        ['differential_diagnosis', 'Differential diagnoses'],
                                        ['plan', 'Management plan'],
                                    ].map(([key, label]) => (
                                        <div key={key}>
                                            <label htmlFor={key}>{label}</label>
                                            <textarea
                                                id={key}
                                                value={summary.data[key]}
                                                onChange={(event) => summary.setData(key, event.target.value)}
                                                rows={3}
                                                minLength={10}
                                                maxLength={key === 'plan' ? 5000 : 3000}
                                                required
                                                aria-invalid={Boolean(summary.errors[key])}
                                                aria-describedby={summary.errors[key] ? `${key}-error` : undefined}
                                            />
                                            {summary.errors[key] && (
                                                <p id={`${key}-error`} role="alert" className="form-error">
                                                    {summary.errors[key]}
                                                </p>
                                            )}
                                        </div>
                                    ))}
                                    <p className="quiet-note">
                                        Submitting closes the encounter. Your review describes the recorded evidence; it does not validate this
                                        diagnosis or plan.
                                    </p>
                                    <StationButton type="submit" disabled={summary.processing}>
                                        {summary.processing ? 'Saving your review...' : 'Submit & view evidence review'}
                                    </StationButton>
                                </form>
                            </section>
                        )}
                    </div>
                    <aside className="encounter-sidebar">
                        <details className="station-card clean-card" open>
                            <summary>Station brief</summary>
                            <p>{station.scenario}</p>
                            <p className="quiet-note">{station.objectives}</p>
                        </details>
                        <section className="station-card clean-card">
                            <h2>Clinical findings</h2>
                            <p className="quiet-note">Request the findings you need. Each action is recorded in your review.</p>
                            <div className="action-list">
                                <StationButton
                                    secondary
                                    onClick={() => record('vitals')}
                                    disabled={expired || action.processing || encounter.actions.some((item) => item.key === 'vitals')}
                                >
                                    Review vital signs
                                </StationButton>
                                {investigations.map((test) => (
                                    <StationButton
                                        key={test.key}
                                        secondary
                                        onClick={() => record(test.key)}
                                        disabled={expired || action.processing || encounter.actions.some((item) => item.key === test.key)}
                                    >
                                        {test.name}
                                    </StationButton>
                                ))}
                            </div>
                            {action.processing && <p role="status">Recording the request...</p>}
                            {action.errors.action && (
                                <p className="form-error" role="alert">
                                    {action.errors.action}
                                </p>
                            )}
                            {encounter.actions.length === 0 && <p className="empty-findings">No findings requested yet.</p>}
                            {encounter.actions.map((item) => (
                                <details className="finding-detail" key={item.key}>
                                    <summary>
                                        {item.name}
                                        <span>Recorded</span>
                                    </summary>
                                    <Findings findings={item.findings} />
                                </details>
                            ))}
                        </section>
                    </aside>
                </div>
            </section>
        </StationLayout>
    );
}
