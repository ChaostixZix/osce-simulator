import React, { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function OsceChat({ session, user, sessionData = {}, examCatalog = {} }) {
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState('');
  const [sending, setSending] = useState(false);

  const breadcrumbs = [
    { title: 'OSCE', href: route('osce') },
    { title: `Session #${session?.id}`, href: route('osce.chat', session?.id) }
  ];

  useEffect(() => {
    const loadHistory = async () => {
      try {
        const res = await fetch(`/api/osce/chat/history/${session.id}`);
        const data = await res.json();
        if (Array.isArray(data)) setMessages(data);
      } catch (e) {
        console.warn('Failed to load history');
      }
    };
    loadHistory();
  }, [session?.id]);

  const sendMessage = async () => {
    if (!input.trim()) return;
    setSending(true);
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const res = await fetch('/api/osce/chat/message', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf ?? '' },
        credentials: 'same-origin',
        body: JSON.stringify({ session_id: session.id, message: input })
      });
      const data = await res.json();
      if (res.ok && data) {
        setMessages((prev) => [...prev, { role: 'user', content: input }, ...(data.reply ? [{ role: 'assistant', content: data.reply }] : [])]);
        setInput('');
      }
    } catch (e) {
      console.error('Send failed', e);
    } finally {
      setSending(false);
    }
  };

  return (
    <>
      <Head title={`OSCE Chat #${session?.id}`} />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="grid grid-rows-[1fr_auto] h-[70vh] gap-4">
          <div className="overflow-auto border p-3 space-y-2">
            {messages.map((m, idx) => (
              <div key={idx} className={m.role === 'user' ? 'text-right' : 'text-left'}>
                <div className={`inline-block px-3 py-2 rounded ${m.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'}`}>
                  {m.content}
                </div>
              </div>
            ))}
            {messages.length === 0 && (
              <div className="text-sm text-muted-foreground">No messages yet. Say hello to the patient.</div>
            )}
          </div>
          <div className="flex gap-2">
            <input
              type="text"
              className="flex-1 border px-3 py-2 bg-background text-foreground"
              placeholder="Type your message…"
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' ? sendMessage() : undefined}
            />
            <button className="px-4 py-2 border" onClick={sendMessage} disabled={sending}>
              {sending ? 'Sending…' : 'Send'}
            </button>
          </div>
        </div>
      </AppLayout>
    </>
  );
}
