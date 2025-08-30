import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import Modal from '@/components/react/Modal.jsx';

export default function OsceChat({ session, user, sessionData = {}, examCatalog = {} }) {
  const [messages, setMessages] = useState([]);
  const [input, setInput] = useState('');
  const [sending, setSending] = useState(false);
  const [aiTyping, setAiTyping] = useState(false);
  const [timerData, setTimerData] = useState(null);
  const [showOrderModal, setShowOrderModal] = useState(false);
  const [showExamModal, setShowExamModal] = useState(false);
  const [testSearchQuery, setTestSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [selectedTests, setSelectedTests] = useState([]);
  const [selectedExams, setSelectedExams] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [showResultsModal, setShowResultsModal] = useState(false);
  const [resultsLoading, setResultsLoading] = useState(false);
  const [orderedTestsView, setOrderedTestsView] = useState(() => (session?.ordered_tests || session?.orderedTests || []));
  const [nowTick, setNowTick] = useState(Date.now());
  const refreshGuardRef = useRef(false);
  const messagesRef = useRef(null);

  // Order Tests: tabs, categories, available tests
  const [activeType, setActiveType] = useState('lab'); // 'lab' | 'imaging' | 'procedure' | 'physical_exam'
  const [categoriesByType, setCategoriesByType] = useState({});
  const [selectedCategory, setSelectedCategory] = useState('');
  const [availableTests, setAvailableTests] = useState([]);
  const [loadingCategories, setLoadingCategories] = useState(false);
  const [loadingAvailable, setLoadingAvailable] = useState(false);

  const breadcrumbs = [
    { title: 'OSCE', href: route('osce') },
    { title: `Session #${session?.id}`, href: route('osce.chat', session?.id) }
  ];

  useEffect(() => {
    const loadHistory = async () => {
      try {
        const res = await fetch(route('osce.chat.history', session.id));
        const data = await res.json();
        if (!res.ok) throw new Error(data?.error || 'Failed to load history');
        const mapped = (data?.messages || []).map((m) => ({
          role: m.sender_type === 'user' ? 'user' : (m.sender_type === 'ai_patient' ? 'assistant' : 'system'),
          content: m.message,
          at: m.sent_at,
        }));
        setMessages(mapped);
      } catch (e) {
        console.warn('Failed to load history');
        setError('Unable to load chat history. Please retry.');
      }
    };
    loadHistory();

    // Load timer data
    const loadTimer = async () => {
      try {
        const res = await fetch(`/api/osce/sessions/${session.id}/timer`);
        const data = await res.json();
        if (!res.ok) throw new Error();
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
    setError('');
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const userText = input.trim();
      setInput('');
      // Optimistically add the user message
      setMessages((prev) => [...prev, { role: 'user', content: userText }]);
      setAiTyping(true);
      const res = await fetch(route('osce.chat.message'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf ?? '' },
        credentials: 'same-origin',
        body: JSON.stringify({ session_id: session.id, message: userText })
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data?.error || 'Failed to send message');
      const reply = data?.ai_response?.message || '';
      setMessages((prev) => [...prev, ...(reply ? [{ role: 'assistant', content: reply }] : [])]);
    } catch (e) {
      console.error('Send failed', e);
      setError('AI patient is unavailable or an error occurred. Please try again.');
    } finally {
      setSending(false);
      setAiTyping(false);
    }
  };

  // Debounced search for medical tests with abort guard
  const searchAbortRef = useRef(null);
  useEffect(() => {
    const q = testSearchQuery.trim();
    if (q.length < 2) {
      setSearchResults([]);
      return;
    }
    const controller = new AbortController();
    if (searchAbortRef.current) {
      try { searchAbortRef.current.abort(); } catch {}
    }
    searchAbortRef.current = controller;
    const id = setTimeout(async () => {
      try {
        const params = new URLSearchParams();
        params.set('q', q);
        if (activeType) params.set('type', activeType);
        if (selectedCategory) params.set('category', selectedCategory);
        const res = await fetch(`/api/medical-tests/search?${params.toString()}` , { signal: controller.signal });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data?.error || 'Search failed');
        setSearchResults(Array.isArray(data) ? data : []);
      } catch (e) {
        if (e?.name === 'AbortError') return;
        console.error('Search error', e);
        setError('Failed to search tests. Please retry.');
      }
    }, 300);
    return () => {
      clearTimeout(id);
      try { controller.abort(); } catch {}
    };
  }, [testSearchQuery, activeType, selectedCategory]);

  // Load categories when opening Order Tests modal
  useEffect(() => {
    const loadCategories = async () => {
      setLoadingCategories(true);
      try {
        const res = await fetch('/api/medical-tests/categories');
        const data = await res.json();
        if (res.ok) {
          setCategoriesByType(data || {});
          const list = (data?.[activeType]) || [];
          setSelectedCategory(list.length ? list[0]?.category : '');
        }
      } catch (e) {
        console.warn('Failed to load categories');
      } finally {
        setLoadingCategories(false);
      }
    };
    if (showOrderModal) {
      loadCategories();
    } else {
      // reset ephemeral when closing
      setTestSearchQuery('');
      setSearchResults([]);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [showOrderModal]);

  const loadAvailableTests = async (type, category) => {
    setLoadingAvailable(true);
    try {
      const params = new URLSearchParams();
      if (type) params.set('type', type);
      if (category) params.set('category', category);
      const res = await fetch(`/api/medical-tests/search?${params.toString()}`);
      const data = await res.json();
      if (res.ok) setAvailableTests(Array.isArray(data) ? data : []);
    } catch (e) {
      console.warn('Failed to load available tests');
    } finally {
      setLoadingAvailable(false);
    }
  };

  // React to tab change
  useEffect(() => {
    const list = (categoriesByType?.[activeType]) || [];
    const first = list.length ? list[0]?.category : '';
    setSelectedCategory(first);
    if (showOrderModal) loadAvailableTests(activeType, first);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeType]);

  // React to category change
  useEffect(() => {
    if (showOrderModal) loadAvailableTests(activeType, selectedCategory);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [selectedCategory]);

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
    setError('');
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
        // Refresh session + sessionData for updated counts and findings
        router.reload({ only: ['session', 'sessionData'], preserveScroll: true });
      }
    } catch (e) {
      console.error('Order failed', e);
      setError('Failed to place orders. Please check inputs and retry.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const performExaminations = async () => {
    if (selectedExams.length === 0) return;
    setError('');
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
        setShowExamModal(false);
        // Refresh session + sessionData for updated counts
        router.reload({ only: ['session', 'sessionData'], preserveScroll: true });
      }
    } catch (e) {
      console.error('Exam failed', e);
      setError('Failed to perform examinations. Please retry.');
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

  const aiTypingBubble = useMemo(() => (
    aiTyping ? (
      <div className="text-left">
        <div className="inline-block px-3 py-2 rounded bg-muted text-foreground">
          <div className="flex gap-1 items-center">
            <span className="w-2 h-2 rounded-full bg-gray-400 animate-bounce" />
            <span className="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style={{ animationDelay: '0.1s' }} />
            <span className="w-2 h-2 rounded-full bg-gray-400 animate-bounce" style={{ animationDelay: '0.2s' }} />
          </div>
        </div>
      </div>
    ) : null
  ), [aiTyping]);

  const formatDate = (value) => {
    try {
      const d = new Date(value);
      const pad = (n) => String(n).padStart(2, '0');
      const dd = pad(d.getDate());
      const mm = pad(d.getMonth() + 1);
      const yyyy = d.getFullYear();
      const hh = pad(d.getHours());
      const min = pad(d.getMinutes());
      return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
    } catch {
      return '-';
    }
  };

  const openResults = () => {
    setOrderedTestsView(session?.ordered_tests || session?.orderedTests || []);
    setShowResultsModal(true);
  };

  const refreshResults = async () => {
    try {
      setResultsLoading(true);
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const res = await fetch(`/api/osce/refresh-results/${session.id}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf ?? '' },
        credentials: 'same-origin'
      });
      const data = await res.json();
      if (res.ok) {
        // Prefer ordered_tests from response; fall back to session.orderedTests
        setOrderedTestsView(data?.ordered_tests || data?.session?.ordered_tests || data?.session?.orderedTests || []);
      }
    } catch (e) {
      console.warn('Failed to refresh results');
    } finally {
      setResultsLoading(false);
    }
  };

  const etaText = (t) => {
    const ra = t.results_available_at || t.resultsAvailableAt;
    if (!ra) return null;
    try {
      const now = nowTick; // use ticking state to force rerender
      const at = new Date(ra).getTime();
      const diffMs = Math.max(0, at - now);
      const s = Math.floor(diffMs / 1000);
      const mm = String(Math.floor(s / 60)).padStart(2, '0');
      const ss = String(s % 60).padStart(2, '0');
      return s <= 0 ? 'ready any moment' : `${mm}:${ss}`;
    } catch {
      return null;
    }
  };

  // Keep modal data in sync when session updates while modal is open
  useEffect(() => {
    if (showResultsModal) {
      setOrderedTestsView(session?.ordered_tests || session?.orderedTests || []);
    }
  }, [showResultsModal, session?.ordered_tests, session?.orderedTests]);

  // While results modal is open, tick every second to update countdowns.
  useEffect(() => {
    if (!showResultsModal) return;
    const id = setInterval(() => setNowTick(Date.now()), 1000);
    return () => clearInterval(id);
  }, [showResultsModal]);

  // Auto-refresh once when any countdown crosses to zero
  useEffect(() => {
    if (!showResultsModal) return;
    try {
      const anyPending = (orderedTestsView || []).some(t => {
        const ra = t.results_available_at || t.resultsAvailableAt;
        if (!ra || t?.results?.status) return false;
        const at = new Date(ra).getTime();
        return (at - nowTick) <= 0;
      });
      if (anyPending && !refreshGuardRef.current) {
        refreshGuardRef.current = true;
        refreshResults().finally(() => {
          // allow another auto-refresh after a small delay to avoid spam
          setTimeout(() => { refreshGuardRef.current = false; }, 3000);
        });
      }
    } catch {}
  }, [nowTick, showResultsModal, orderedTestsView]);

  // Auto-scroll to bottom on new messages or when AI starts/stops typing
  useEffect(() => {
    try {
      const el = messagesRef.current;
      if (el) el.scrollTop = el.scrollHeight;
    } catch {}
  }, [messages, aiTyping]);

  return (
    <>
      <Head title={`OSCE Chat #${session?.id}`} />
      <AppLayout breadcrumbs={breadcrumbs}>
        {/* Error banner */}
        {error && (
          <div className="mb-3 border border-red-300 bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-200 rounded px-3 py-2 text-sm">
            {error}
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-4 gap-4 h-[calc(100vh-200px)] min-h-0">
          {/* Left Sidebar */}
          <div className="lg:col-span-1 flex flex-col h-full min-h-0">
            <div className="flex-1 overflow-y-auto space-y-4 pr-2">
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
                  <div>Total Cost: ${session?.total_test_cost ?? 0}</div>
                  <div>Tests Ordered: {session?.ordered_tests?.length ?? 0}</div>
                  <div>Exams Done: {session?.examinations?.length ?? 0}</div>
                </div>
              </div>

              {/* View Results (Ordered Tests by date) */}
              <div className="border rounded-lg p-4 space-y-3">
                <h3 className="font-semibold">View Results</h3>
                <div className="space-y-2 text-sm">
                  {(session?.ordered_tests || session?.orderedTests || []).length === 0 ? (
                    <div className="text-slate-500">Belum ada test diorder.</div>
                  ) : (
                    <div className="max-h-48 overflow-y-auto">
                      <ul className="divide-y">
                        {(session?.ordered_tests || session?.orderedTests || []).map((t, idx) => (
                          <li key={idx} className="py-2">
                            <div className="flex flex-col">
                              <span className="text-xs text-slate-500">
                                {(() => {
                                  try {
                                    const d = new Date(t.ordered_at || t.orderedAt || t.created_at || t.createdAt);
                                    const pad = (n) => String(n).padStart(2, '0');
                                    const dd = pad(d.getDate());
                                    const mm = pad(d.getMonth() + 1);
                                    const yyyy = d.getFullYear();
                                    const hh = pad(d.getHours());
                                    const min = pad(d.getMinutes());
                                    return `${dd}/${mm}/${yyyy} ${hh}:${min}`;
                                  } catch {
                                    return '-';
                                  }
                                })()}
                              </span>
                              <span className="font-medium">{t.test_name || t.testName}</span>
                            </div>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                  <div className="pt-2">
                    <button onClick={openResults} className="text-sm text-emerald-600 hover:text-emerald-500">
                      Buka hasil lengkap
                    </button>
                  </div>
                </div>
              </div>

              {/* Actions */}
              <div className="space-y-2">
                <button
                  onClick={() => setShowExamModal(true)}
                  disabled={!isSessionActive}
                  className="w-full px-4 py-3 bg-gradient-to-r from-emerald-500/10 to-cyan-500/10 hover:from-emerald-500/20 hover:to-cyan-500/20 border-2 border-emerald-400/30 hover:border-emerald-400/50 disabled:from-slate-300/10 disabled:to-slate-400/10 disabled:border-slate-400/30 text-slate-700 dark:text-slate-300 disabled:text-slate-500 font-mono text-sm tracking-wide transition-all duration-200 disabled:cursor-not-allowed"
                  style={{
                    clipPath: 'polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px))'
                  }}
                >
                  PHYSICAL EXAM
                </button>
                <button
                  onClick={() => setShowOrderModal(true)}
                  disabled={!isSessionActive}
                  className="w-full px-4 py-3 bg-gradient-to-r from-blue-500/10 to-purple-500/10 hover:from-blue-500/20 hover:to-purple-500/20 border-2 border-blue-400/30 hover:border-blue-400/50 disabled:from-slate-300/10 disabled:to-slate-400/10 disabled:border-slate-400/30 text-slate-700 dark:text-slate-300 disabled:text-slate-500 font-mono text-sm tracking-wide transition-all duration-200 disabled:cursor-not-allowed"
                  style={{
                    clipPath: 'polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px))'
                  }}
                >
                  ORDER TESTS
                </button>
              </div>
            </div>
          </div>

          {/* Main Chat Area */}
          <div className="lg:col-span-3 flex flex-col h-full min-h-0">
            <div ref={messagesRef} className="flex-1 overflow-y-auto border p-3 space-y-2 min-h-0">
              {messages.map((m, idx) => (
                <div key={idx} className={m.role === 'user' ? 'text-right' : 'text-left'}>
                  <div className={`inline-block px-3 py-2 rounded ${m.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'}`}>
                    {m.content}
                  </div>
                </div>
              ))}
              {aiTypingBubble}
              {messages.length === 0 && (
                <div className="text-sm text-muted-foreground">No messages yet. Say hello to the patient.</div>
              )}
            </div>
            <div className="flex gap-2 mt-4">
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

        {/* Physical Exam Modal (shared) */}
        <Modal
          open={showExamModal}
          onClose={() => setShowExamModal(false)}
          title="Physical Examination"
          size="xl"
          footer={(
            <>
              <button 
                onClick={() => setShowExamModal(false)} 
                className="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono text-sm tracking-wide transition-all duration-200 border-l-4 border-slate-400 dark:border-slate-500"
              >
                CANCEL
              </button>
              <div className="flex-1" />
              <button
                onClick={performExaminations}
                disabled={selectedExams.length === 0 || !isSessionActive}
                className="px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-600 hover:to-cyan-600 disabled:from-slate-400 disabled:to-slate-500 text-white font-mono text-sm tracking-wide transition-all duration-200 border-l-4 border-emerald-400 disabled:border-slate-400 shadow-lg shadow-emerald-500/25 disabled:shadow-none"
              >
                PERFORM [{selectedExams.length}] EXAM{selectedExams.length !== 1 ? 'S' : ''}
              </button>
            </>
          )}
        >
          <div className="space-y-6">
            {Object.entries(examCatalog).map(([category, types]) => (
              <div key={category} className="space-y-3">
                <div className="flex items-center gap-3 pb-2 border-b border-slate-200/30 dark:border-slate-700/50">
                  <div className="w-1 h-4 bg-gradient-to-b from-cyan-400 to-emerald-400" />
                  <div className="font-mono font-semibold text-slate-900 dark:text-slate-100 uppercase tracking-widest text-sm">
                    {category}
                  </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  {types.map((type) => {
                    const checked = selectedExams.some(e => e.category === category && e.type === type);
                    return (
                      <label 
                        key={`${category}-${type}`} 
                        className={`group relative flex items-center gap-3 p-4 cursor-pointer transition-all duration-200 border-l-2 ${
                          checked 
                            ? 'bg-gradient-to-r from-emerald-50 to-cyan-50 dark:from-emerald-950/30 dark:to-cyan-950/30 border-emerald-400 shadow-md shadow-emerald-500/10' 
                            : 'bg-slate-50/50 hover:bg-slate-100/50 dark:bg-slate-900/30 dark:hover:bg-slate-800/50 border-slate-300 dark:border-slate-600 hover:border-slate-400 dark:hover:border-slate-500'
                        }`}
                        style={{
                          clipPath: 'polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 0 100%)'
                        }}
                      >
                        <input
                          type="checkbox"
                          checked={checked}
                          onChange={() => toggleExamSelection(category, type)}
                          className="hidden"
                        />
                        <div className={`relative w-4 h-4 border-2 transition-all duration-200 ${
                          checked 
                            ? 'bg-emerald-400 border-emerald-400' 
                            : 'border-slate-400 dark:border-slate-500 group-hover:border-slate-500 dark:group-hover:border-slate-400'
                        }`} style={{ clipPath: 'polygon(0 0, calc(100% - 2px) 0, 100% 2px, 100% 100%, 2px 100%, 0 calc(100% - 2px))' }}>
                          {checked && (
                            <div className="absolute inset-0 flex items-center justify-center">
                              <svg className="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                              </svg>
                            </div>
                          )}
                        </div>
                        <span className="font-mono text-sm text-slate-700 dark:text-slate-300 uppercase tracking-wide">
                          {type.replace('_', ' ')}
                        </span>
                        {checked && (
                          <div className="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <div className="w-2 h-2 bg-emerald-400 rounded-full shadow-lg shadow-emerald-400/50 animate-pulse" />
                          </div>
                        )}
                      </label>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>
        </Modal>
        
        {/* Ordered Tests / Results Modal */}
        <Modal
          open={showResultsModal}
          onClose={() => setShowResultsModal(false)}
          title="Ordered Tests"
          size="xl"
          footer={(
            <>
              <button 
                onClick={() => setShowResultsModal(false)} 
                className="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono text-sm tracking-wide transition-all duration-200 border-l-4 border-slate-400 dark:border-slate-500"
              >
                CLOSE
              </button>
              <div className="flex-1" />
              <button
                onClick={refreshResults}
                disabled={resultsLoading}
                className="px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-600 hover:to-cyan-600 disabled:from-slate-400 disabled:to-slate-500 text-white font-mono text-sm tracking-wide transition-all duration-200 border-l-4 border-emerald-400 disabled:border-slate-400 shadow-lg shadow-emerald-500/25 disabled:shadow-none"
              >
                {resultsLoading ? 'REFRESHING…' : 'REFRESH RESULTS'}
              </button>
            </>
          )}
        >
          <div className="max-h-[60vh] overflow-y-auto space-y-4">
            {orderedTestsView.length === 0 ? (
              <div className="text-sm text-slate-500">Belum ada test diorder.</div>
            ) : (
              orderedTestsView.map((t, idx) => (
                <div key={idx} className="p-4 border rounded-lg">
                  <div className="flex items-center justify-between mb-1">
                    <div className="font-mono font-semibold text-sm">{t.test_name || t.testName}</div>
                    <div className="text-xs text-slate-500">{formatDate(t.ordered_at || t.orderedAt || t.created_at || t.createdAt)}</div>
                  </div>
                  <div className="text-xs text-slate-600">Type: {t.test_type || t.testType || '-'}</div>
                  <div className="mt-2 text-sm flex items-center gap-2">
                    <span className="font-medium">Status:</span>
                    {!t.results?.status ? (
                      <span className="inline-flex items-center gap-2 text-slate-600">
                        <span className="relative inline-flex">
                          <span className="animate-ping inline-flex h-2 w-2 rounded-full bg-emerald-400 opacity-75"></span>
                          <span className="absolute inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                        pending
                        {etaText(t) && (
                          <span className="ml-2 text-xs text-slate-500">{etaText(t)}</span>
                        )}
                      </span>
                    ) : (
                      <span>{t.results?.status}</span>
                    )}
                  </div>
                  {t.results?.message && (
                    <div className="mt-1 text-xs text-slate-600">{t.results.message}</div>
                  )}
                  {Array.isArray(t.results?.values) && t.results.values.length > 0 && (
                    <div className="mt-2">
                      <div className="text-xs font-medium mb-1">Values:</div>
                      <ul className="pl-4 list-disc text-xs text-slate-700">
                        {t.results.values.map((v, i) => (
                          <li key={i}>{typeof v === 'string' ? v : JSON.stringify(v)}</li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>
              ))
            )}
          </div>
        </Modal>

        {/* Order Tests Modal */}
        <Modal
          open={showOrderModal}
          onClose={() => setShowOrderModal(false)}
          title="Order Medical Tests"
          size="xl"
          footer={(
            <>
              <button 
                onClick={() => setShowOrderModal(false)} 
                className="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-mono text-sm tracking-wide transition-all duration-200 border-l-4 border-slate-400 dark:border-slate-500"
              >
                CANCEL
              </button>
              <div className="flex-1" />
              <button
                onClick={submitOrders}
                disabled={!canSubmitOrders || isSubmitting}
                className="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-purple-500 hover:from-blue-600 hover:to-purple-600 disabled:from-slate-400 disabled:to-slate-500 text-white font-mono text-sm tracking-wide transition-all duration-200 border-l-4 border-blue-400 disabled:border-slate-400 shadow-lg shadow-blue-500/25 disabled:shadow-none"
              >
                {isSubmitting ? 'ORDERING...' : `ORDER [${selectedTests.length}] TEST${selectedTests.length !== 1 ? 'S' : ''}`}
              </button>
            </>
          )}
        >
          {/* Type Tabs + Categories */}
          <div className="mb-4 space-y-3">
            <div className="flex gap-2 flex-wrap">
              {['lab','imaging','procedure','physical_exam'].map((t) => (
                <button
                  key={t}
                  onClick={() => setActiveType(t)}
                  className={`px-3 py-1.5 rounded-md border text-xs font-mono uppercase tracking-widest transition-colors ${
                    activeType === t ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800'
                  }`}
                >
                  {t === 'lab' ? 'LAB' : t === 'imaging' ? 'IMAGING' : t === 'procedure' ? 'PROCEDURE' : 'PHYSICAL EXAM'}
                </button>
              ))}
            </div>

            <div className="flex gap-2 overflow-x-auto pb-1">
              {(categoriesByType?.[activeType] || []).map((c) => (
                <button
                  key={c.category}
                  onClick={() => setSelectedCategory(c.category)}
                  className={`px-3 py-1.5 rounded-full border text-xs font-mono tracking-wide whitespace-nowrap transition-colors ${
                    selectedCategory === c.category
                      ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border-blue-300 dark:border-blue-700'
                      : 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                  }`}
                >
                  {c.category}
                </button>
              ))}
            </div>
          </div>

          {/* Search Section */}
          <div className="mb-6">
            <div className="relative">
              <div className="absolute left-4 top-1/2 transform -translate-y-1/2">
                <div className="w-2 h-2 bg-blue-400 rounded-full shadow-lg shadow-blue-400/50" />
              </div>
              <input
                type="text"
                placeholder="SEARCH TESTS // e.g. 'troponin', 'ecg', 'chest x-ray'"
                value={testSearchQuery}
                onChange={(e) => setTestSearchQuery(e.target.value)}
                className="w-full bg-slate-50 dark:bg-slate-900/50 border-2 border-slate-200 dark:border-slate-700 focus:border-blue-400 dark:focus:border-blue-400 pl-10 pr-4 py-3 font-mono text-sm text-slate-900 dark:text-slate-100 placeholder-slate-500 dark:placeholder-slate-400 transition-all duration-200"
                style={{
                  clipPath: 'polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px))'
                }}
              />
            </div>
            {searchResults.length > 0 && (
              <div className="mt-4 space-y-2 max-h-48 overflow-y-auto">
                {searchResults.map(test => (
                  <div 
                    key={test.id} 
                    className="group relative p-4 bg-slate-50/50 hover:bg-slate-100/50 dark:bg-slate-900/30 dark:hover:bg-slate-800/50 border-l-2 border-slate-300 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-400 transition-all duration-200"
                    style={{
                      clipPath: 'polygon(0 0, calc(100% - 6px) 0, 100% 6px, 100% 100%, 0 100%)'
                    }}
                  >
                    <div className="flex items-center justify-between">
                      <div className="space-y-1">
                        <div className="font-mono font-semibold text-slate-900 dark:text-slate-100 text-sm uppercase tracking-wide">{test.name}</div>
                        <div className="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 font-mono">
                          <span className="px-2 py-0.5 bg-slate-200 dark:bg-slate-700 rounded text-xs">{test.category}</span>
                          <span>•</span>
                          <span>{test.type}</span>
                        </div>
                        <div className="text-xs font-mono text-blue-600 dark:text-blue-400 font-semibold">${test.cost}</div>
                      </div>
                      <button
                        onClick={() => selectTest(test)}
                        disabled={selectedTests.some(t => t.id === test.id)}
                        className={`px-4 py-2 font-mono text-xs tracking-wide transition-all duration-200 ${
                          selectedTests.some(t => t.id === test.id)
                            ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-l-2 border-emerald-400'
                            : 'bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-800/50 text-blue-700 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 border-l-2 border-blue-400 hover:border-blue-500'
                        }`}
                        style={{
                          clipPath: 'polygon(0 0, calc(100% - 4px) 0, 100% 4px, 100% 100%, 0 100%)'
                        }}
                      >
                        {selectedTests.some(t => t.id === test.id) ? 'SELECTED' : 'SELECT'}
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
            {/* Available tests by category when not searching */}
            {searchResults.length === 0 && (
              <div className="mt-4 space-y-2 max-h-48 overflow-y-auto">
                {loadingAvailable && (
                  <div className="text-xs font-mono text-slate-500 dark:text-slate-400">Loading available tests…</div>
                )}
                {!loadingAvailable && availableTests.length === 0 && (
                  <div className="text-xs font-mono text-slate-500 dark:text-slate-400">No tests found in this category.</div>
                )}
                {!loadingAvailable && availableTests.map((test) => (
                  <div 
                    key={test.id}
                    className="group relative p-4 bg-slate-50/50 hover:bg-slate-100/50 dark:bg-slate-900/30 dark:hover:bg-slate-800/50 border-l-2 border-slate-300 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-400 transition-all duration-200"
                    style={{ clipPath: 'polygon(0 0, calc(100% - 6px) 0, 100% 6px, 100% 100%, 0 100%)' }}
                  >
                    <div className="flex items-center justify-between">
                      <div className="space-y-1">
                        <div className="font-mono font-semibold text-slate-900 dark:text-slate-100 text-sm uppercase tracking-wide">{test.name}</div>
                        <div className="flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 font-mono">
                          <span className="px-2 py-0.5 bg-slate-200 dark:bg-slate-700 rounded text-xs">{test.category}</span>
                          <span>•</span>
                          <span>{test.type}</span>
                        </div>
                        {test.cost ? (
                          <div className="text-xs font-mono text-blue-600 dark:text-blue-400 font-semibold">${test.cost}</div>
                        ) : null}
                      </div>
                      <button
                        onClick={() => selectTest(test)}
                        disabled={selectedTests.some(t => t.id === test.id) || !isSessionActive}
                        className={`px-4 py-2 font-mono text-xs tracking-wide transition-all duration-200 ${
                          selectedTests.some(t => t.id === test.id)
                            ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-l-2 border-emerald-400'
                            : 'bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-800/50 text-blue-700 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 border-l-2 border-blue-400 hover:border-blue-500'
                        }`}
                        style={{ clipPath: 'polygon(0 0, calc(100% - 4px) 0, 100% 4px, 100% 100%, 0 100%)' }}
                      >
                        {selectedTests.some(t => t.id === test.id) ? 'SELECTED' : 'SELECT'}
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Selected Tests */}
          {selectedTests.length > 0 && (
            <div className="space-y-4">
              <div className="flex items-center gap-3 pb-2 border-b border-slate-200/30 dark:border-slate-700/50">
                <div className="w-1 h-4 bg-gradient-to-b from-blue-400 to-purple-400" />
                <h4 className="font-mono font-semibold text-slate-900 dark:text-slate-100 uppercase tracking-widest text-sm">
                  Selected Tests [{selectedTests.length}]
                </h4>
              </div>
              <div className="space-y-4 max-h-72 overflow-y-auto">
                {selectedTests.map(test => (
                  <div 
                    key={test.id} 
                    className="relative p-4 bg-gradient-to-r from-slate-50/80 to-blue-50/30 dark:from-slate-900/50 dark:to-blue-950/30 border-l-2 border-blue-400 shadow-md shadow-blue-500/10"
                    style={{
                      clipPath: 'polygon(0 0, calc(100% - 12px) 0, 100% 12px, 100% 100%, 0 100%)'
                    }}
                  >
                    <div className="flex items-center justify-between mb-3">
                      <div className="font-mono font-semibold text-slate-900 dark:text-slate-100 uppercase tracking-wide text-sm">{test.name}</div>
                      <button
                        onClick={() => removeTest(test.id)}
                        className="px-3 py-1.5 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-800/50 text-red-700 dark:text-red-400 font-mono text-xs tracking-wide transition-all duration-200 border-l-2 border-red-400"
                        style={{
                          clipPath: 'polygon(0 0, calc(100% - 4px) 0, 100% 4px, 100% 100%, 0 100%)'
                        }}
                      >
                        REMOVE
                      </button>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <label className="block text-xs font-mono font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-widest">
                          Clinical Reasoning
                        </label>
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
                          className="w-full bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 focus:border-blue-400 dark:focus:border-blue-400 px-3 py-2 font-mono text-sm text-slate-900 dark:text-slate-100 placeholder-slate-500 dark:placeholder-slate-400 resize-none transition-all duration-200"
                          style={{
                            clipPath: 'polygon(0 0, calc(100% - 6px) 0, 100% 6px, 100% 100%, 6px 100%, 0 calc(100% - 6px))'
                          }}
                        />
                      </div>
                      <div className="space-y-2">
                        <label className="block text-xs font-mono font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-widest">
                          Priority Level
                        </label>
                        <select
                          value={test.priority}
                          onChange={(e) => {
                            const updated = selectedTests.map(t => 
                              t.id === test.id ? { ...t, priority: e.target.value } : t
                            );
                            setSelectedTests(updated);
                          }}
                          className="w-full bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 focus:border-blue-400 dark:focus:border-blue-400 px-3 py-2 font-mono text-sm text-slate-900 dark:text-slate-100 transition-all duration-200"
                          style={{
                            clipPath: 'polygon(0 0, calc(100% - 6px) 0, 100% 6px, 100% 100%, 6px 100%, 0 calc(100% - 6px))'
                          }}
                        >
                          <option value="">SELECT PRIORITY</option>
                          <option value="immediate">IMMEDIATE</option>
                          <option value="urgent">URGENT</option>
                          <option value="routine">ROUTINE</option>
                        </select>
                      </div>
                    </div>
                    <div className="flex justify-between mt-3 pt-2 border-t border-slate-200/30 dark:border-slate-700/50">
                      <span className="text-xs font-mono text-slate-600 dark:text-slate-400">COST: <span className="text-blue-600 dark:text-blue-400 font-semibold">${test.cost}</span></span>
                      <span className="text-xs font-mono text-slate-600 dark:text-slate-400">ETA: <span className="text-purple-600 dark:text-purple-400 font-semibold">{test.turnaround_minutes}m</span></span>
                    </div>
                    <div className="absolute top-4 right-16">
                      <div className="w-2 h-2 bg-blue-400 rounded-full shadow-lg shadow-blue-400/50 animate-pulse" />
                    </div>
                  </div>
                ))}
              </div>

              {/* Summary */}
              <div className="relative p-4 bg-gradient-to-r from-slate-900 to-blue-900 dark:from-slate-950 dark:to-blue-950 border-2 border-blue-400/30"
                style={{
                  clipPath: 'polygon(0 0, calc(100% - 16px) 0, 100% 16px, 100% 100%, 16px 100%, 0 calc(100% - 16px))'
                }}
              >
                <div className="grid grid-cols-2 gap-4 text-sm">
                  <div className="flex justify-between">
                    <span className="font-mono text-slate-300 uppercase tracking-widest">Total Cost:</span>
                    <span className="font-mono font-bold text-blue-400">${totalCost}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="font-mono text-slate-300 uppercase tracking-widest">Max ETA:</span>
                    <span className="font-mono font-bold text-purple-400">{maxTurnaround}m</span>
                  </div>
                </div>
                <div className="absolute top-0 right-0 w-20 h-full bg-gradient-to-l from-blue-500/20 to-transparent" />
              </div>
            </div>
          )}
        </Modal>
      </AppLayout>
    </>
  );
}
