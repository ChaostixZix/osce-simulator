import React, { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function OsceChat({ session, user, sessionData = {}, examCatalog = {} }) {
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState('');
  const [sending, setSending] = useState(false);
  const [timerData, setTimerData] = useState(null);
  const [showOrderModal, setShowOrderModal] = useState(false);
  const [testSearchQuery, setTestSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [selectedTests, setSelectedTests] = useState([]);
  const [selectedExams, setSelectedExams] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const breadcrumbs = [
    { title: 'OSCE', href: route('osce') },
    { title: `Session #${session?.id}`, href: route('osce.chat', session?.id) }
  ];

  useEffect(() => {
    const loadHistory = async () => {
      try {
        const res = await fetch(route('osce.chat.history', session.id));
        const data = await res.json();
        if (Array.isArray(data)) setMessages(data);
      } catch (e) {
        console.warn('Failed to load history');
      }
    };
    loadHistory();

    // Load timer data
    const loadTimer = async () => {
      try {
        const res = await fetch(`/api/osce/sessions/${session.id}/timer`);
        const data = await res.json();
        setTimerData(data);
      } catch (e) {
        console.warn('Failed to load timer');
      }
    };
    loadTimer();

    // Set up timer polling
    const timerInterval = setInterval(loadTimer, 30000);
    return () => clearInterval(timerInterval);
  }, [session?.id]);

  const sendMessage = async () => {
    if (!input.trim()) return;
    setSending(true);
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const res = await fetch(route('osce.chat.message'), {
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

  const searchMedicalTests = async () => {
    if (testSearchQuery.length < 2) {
      setSearchResults([]);
      return;
    }
    try {
      const res = await fetch(`/api/medical-tests/search?q=${encodeURIComponent(testSearchQuery)}`);
      if (res.ok) {
        const data = await res.json();
        setSearchResults(data);
      }
    } catch (e) {
      console.error('Search error', e);
    }
  };

  const selectTest = (test) => {
    if (!selectedTests.find(t => t.id === test.id)) {
      setSelectedTests([...selectedTests, { ...test, clinicalReasoning: '', priority: '' }]);
    }
  };

  const removeTest = (id) => {
    setSelectedTests(selectedTests.filter(t => t.id !== id));
  };

  const submitOrders = async () => {
    setIsSubmitting(true);
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const orders = selectedTests.map(test => ({
        medical_test_id: test.id,
        clinical_reasoning: test.clinicalReasoning,
        priority: test.priority
      }));

      const res = await fetch('/api/osce/order-tests', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf ?? '' },
        credentials: 'same-origin',
        body: JSON.stringify({ session_id: session.id, orders })
      });

      if (res.ok) {
        setSelectedTests([]);
        setTestSearchQuery('');
        setSearchResults([]);
        setShowOrderModal(false);
        // Refresh session data
        router.reload({ only: ['sessionData'] });
      }
    } catch (e) {
      console.error('Order failed', e);
    } finally {
      setIsSubmitting(false);
    }
  };

  const performExaminations = async () => {
    if (selectedExams.length === 0) return;
    
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const examinations = selectedExams.map(exam => ({ category: exam.category, type: exam.type }));

      const res = await fetch('/api/osce/examinations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf ?? '' },
        credentials: 'same-origin',
        body: JSON.stringify({ session_id: session.id, examinations })
      });

      if (res.ok) {
        setSelectedExams([]);
        // Refresh session data
        router.reload({ only: ['sessionData'] });
      }
    } catch (e) {
      console.error('Exam failed', e);
    }
  };

  const toggleExamSelection = (category, type) => {
    const examKey = `${category}-${type}`;
    const isSelected = selectedExams.find(e => `${e.category}-${e.type}` === examKey);
    
    if (isSelected) {
      setSelectedExams(selectedExams.filter(e => `${e.category}-${e.type}` !== examKey));
    } else {
      setSelectedExams([...selectedExams, { category, type }]);
    }
  };

  const isSessionActive = session?.status === 'in_progress' && timerData?.time_status === 'active';
  const canSubmitOrders = selectedTests.length > 0 && selectedTests.every(t => t.clinicalReasoning?.length >= 20 && t.priority);
  const totalCost = selectedTests.reduce((sum, t) => sum + (t.cost || 0), 0);
  const maxTurnaround = selectedTests.reduce((max, t) => Math.max(max, t.turnaround_minutes || 0), 0);

  return (
    <>
      <Head title={`OSCE Chat #${session?.id}`} />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="grid grid-cols-1 lg:grid-cols-4 gap-4 h-[80vh]">
          {/* Left Sidebar */}
          <div className="lg:col-span-1 space-y-4">
            {/* Case Overview */}
            <div className="border rounded-lg p-4 space-y-3">
              <h3 className="font-semibold text-lg">Case Overview</h3>
              <div className="space-y-2 text-sm">
                <div><strong>Scenario:</strong> {session?.osce_case?.title}</div>
                <div><strong>Setting:</strong> {session?.osce_case?.clinical_setting}</div>
                {timerData && (
                  <div><strong>Time:</strong> {timerData.formatted_time_remaining}</div>
                )}
              </div>
            </div>

            {/* Session Widgets */}
            <div className="border rounded-lg p-4 space-y-3">
              <h3 className="font-semibold">Session Status</h3>
              <div className="space-y-2 text-sm">
                <div>Total Cost: ${session?.total_test_cost || 0}</div>
                <div>Tests Ordered: {session?.ordered_tests?.length || 0}</div>
                <div>Exams Done: {sessionData?.examination_findings?.length || 0}</div>
              </div>
            </div>

            {/* Physical Exam Selector */}
            <div className="border rounded-lg p-4 space-y-3">
              <h3 className="font-semibold">Physical Exam</h3>
              <div className="space-y-2">
                {Object.entries(examCatalog).map(([category, types]) => (
                  <div key={category} className="space-y-1">
                    <div className="font-medium text-sm capitalize">{category}</div>
                    <div className="grid grid-cols-1 gap-1">
                      {types.map(type => (
                        <label key={type} className="flex items-center space-x-2 text-xs">
                          <input
                            type="checkbox"
                            checked={selectedExams.some(e => e.category === category && e.type === type)}
                            onChange={() => toggleExamSelection(category, type)}
                            disabled={!isSessionActive}
                            className="rounded"
                          />
                          <span className="capitalize">{type.replace('_', ' ')}</span>
                        </label>
                      ))}
                    </div>
                  </div>
                ))}
                <button
                  onClick={performExaminations}
                  disabled={selectedExams.length === 0 || !isSessionActive}
                  className="w-full px-3 py-2 text-sm border rounded disabled:opacity-50"
                >
                  Perform Selected Exams
                </button>
              </div>
            </div>

            {/* Order Tests Button */}
            <button
              onClick={() => setShowOrderModal(true)}
              disabled={!isSessionActive}
              className="w-full px-4 py-2 border rounded disabled:opacity-50"
            >
              Order Tests
            </button>
          </div>

          {/* Main Chat Area */}
          <div className="lg:col-span-3 grid grid-rows-[1fr_auto] gap-4">
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
                placeholder={isSessionActive ? "Type your message…" : "Session inactive"}
                value={input}
                onChange={(e) => setInput(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' ? sendMessage() : undefined}
                disabled={!isSessionActive}
              />
              <button className="px-4 py-2 border" onClick={sendMessage} disabled={sending || !isSessionActive}>
                {sending ? 'Sending…' : 'Send'}
              </button>
            </div>
          </div>
        </div>

        {/* Order Tests Modal */}
        {showOrderModal && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-3xl max-h-[80vh] overflow-y-auto">
              <h2 className="text-xl font-semibold mb-4">Order Medical Tests</h2>
              
              {/* Search Section */}
              <div className="mb-4">
                <input
                  type="text"
                  placeholder="Search tests... (e.g. 'troponin', 'ecg', 'chest x-ray')"
                  value={testSearchQuery}
                  onChange={(e) => { setTestSearchQuery(e.target.value); searchMedicalTests(); }}
                  className="w-full border px-3 py-2 rounded"
                />
                {searchResults.length > 0 && (
                  <div className="mt-2 space-y-1 max-h-40 overflow-y-auto">
                    {searchResults.map(test => (
                      <div key={test.id} className="p-2 border rounded flex items-center justify-between">
                        <div>
                          <div className="font-medium">{test.name}</div>
                          <div className="text-xs text-gray-500">{test.category} • {test.type}</div>
                          <div className="text-xs text-gray-500">${test.cost}</div>
                        </div>
                        <button
                          onClick={() => selectTest(test)}
                          disabled={selectedTests.some(t => t.id === test.id)}
                          className="px-3 py-1 text-sm border rounded disabled:opacity-50"
                        >
                          {selectedTests.some(t => t.id === test.id) ? 'Selected' : 'Select'}
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {/* Selected Tests */}
              {selectedTests.length > 0 && (
                <div className="space-y-3">
                  <h4 className="font-medium">Selected Tests ({selectedTests.length})</h4>
                  <div className="space-y-3 max-h-60 overflow-y-auto">
                    {selectedTests.map(test => (
                      <div key={test.id} className="p-3 rounded border space-y-2">
                        <div className="flex items-center justify-between">
                          <div className="font-medium">{test.name}</div>
                          <button
                            onClick={() => removeTest(test.id)}
                            className="px-2 py-1 text-sm border rounded"
                          >
                            Remove
                          </button>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                          <textarea
                            value={test.clinicalReasoning}
                            onChange={(e) => {
                              const updated = selectedTests.map(t => 
                                t.id === test.id ? { ...t, clinicalReasoning: e.target.value } : t
                              );
                              setSelectedTests(updated);
                            }}
                            placeholder="Clinical reasoning (min 20 chars)"
                            rows="3"
                            className="border px-2 py-1 rounded"
                          />
                          <select
                            value={test.priority}
                            onChange={(e) => {
                              const updated = selectedTests.map(t => 
                                t.id === test.id ? { ...t, priority: e.target.value } : t
                              );
                              setSelectedTests(updated);
                            }}
                            className="border px-2 py-1 rounded"
                          >
                            <option value="">Select Priority</option>
                            <option value="immediate">Immediate</option>
                            <option value="urgent">Urgent</option>
                            <option value="routine">Routine</option>
                          </select>
                        </div>
                        <div className="flex justify-between text-xs text-gray-500">
                          <span>Cost: ${test.cost}</span>
                          <span>Turnaround: {test.turnaround_minutes} min</span>
                        </div>
                      </div>
                    ))}
                  </div>

                  {/* Summary */}
                  <div className="p-3 bg-gray-50 dark:bg-gray-800 rounded space-y-2">
                    <div className="flex justify-between text-sm">
                      <span>Total Cost:</span>
                      <span className="font-medium">${totalCost}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                      <span>Max Turnaround:</span>
                      <span className="font-medium">{maxTurnaround} minutes</span>
                    </div>
                  </div>
                </div>
              )}

              {/* Action Buttons */}
              <div className="flex justify-between mt-4">
                <button
                  onClick={() => setShowOrderModal(false)}
                  className="px-4 py-2 border rounded"
                >
                  Cancel
                </button>
                <button
                  onClick={submitOrders}
                  disabled={!canSubmitOrders || isSubmitting}
                  className="px-4 py-2 bg-primary text-primary-foreground rounded disabled:opacity-50"
                >
                  {isSubmitting ? 'Ordering...' : `Order ${selectedTests.length} Test${selectedTests.length > 1 ? 's' : ''}`}
                </button>
              </div>
            </div>
          </div>
        )}
      </AppLayout>
    </>
  );
}