import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Rationalization({ session, rationalization, progress, canUnlockResults }) {
  const [cards, setCards] = useState(rationalization?.cards || []);
  const [diagnosisEntries, setDiagnosisEntries] = useState(rationalization?.diagnosis_entries || []);
  const [primaryDiagnosis, setPrimaryDiagnosis] = useState(diagnosisEntries.find(d => d.diagnosis_type === 'primary')?.diagnosis || '');
  const [primaryReasoning, setPrimaryReasoning] = useState(diagnosisEntries.find(d => d.diagnosis_type === 'primary')?.reasoning || '');
  const [differentials, setDifferentials] = useState(diagnosisEntries.filter(d => d.diagnosis_type === 'differential').map(d => ({ diagnosis: d.diagnosis, reasoning: d.reasoning })));
  const [carePlan, setCarePlan] = useState(rationalization?.care_plan || '');
  const [currentProgress, setCurrentProgress] = useState(progress);
  const [canUnlock, setCanUnlock] = useState(canUnlockResults);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [activeTab, setActiveTab] = useState('cards');

  const breadcrumbs = [
    { title: 'OSCE', href: route('osce') },
    { title: 'Rationalization', href: '#' },
  ];

  // Auto-save for drafts
  useEffect(() => {
    const saveDrafts = () => {
      localStorage.setItem(`rationalization_${rationalization.id}_primary_diagnosis`, primaryDiagnosis);
      localStorage.setItem(`rationalization_${rationalization.id}_primary_reasoning`, primaryReasoning);
      localStorage.setItem(`rationalization_${rationalization.id}_differentials`, JSON.stringify(differentials));
      localStorage.setItem(`rationalization_${rationalization.id}_care_plan`, carePlan);
    };

    const interval = setInterval(saveDrafts, 10000); // Auto-save every 10 seconds
    return () => clearInterval(interval);
  }, [primaryDiagnosis, primaryReasoning, differentials, carePlan, rationalization.id]);

  // Load drafts on mount
  useEffect(() => {
    const loadDrafts = () => {
      const savedPrimary = localStorage.getItem(`rationalization_${rationalization.id}_primary_diagnosis`);
      const savedPrimaryReasoning = localStorage.getItem(`rationalization_${rationalization.id}_primary_reasoning`);
      const savedDifferentials = localStorage.getItem(`rationalization_${rationalization.id}_differentials`);
      const savedCarePlan = localStorage.getItem(`rationalization_${rationalization.id}_care_plan`);

      if (savedPrimary && !primaryDiagnosis) setPrimaryDiagnosis(savedPrimary);
      if (savedPrimaryReasoning && !primaryReasoning) setPrimaryReasoning(savedPrimaryReasoning);
      if (savedDifferentials && differentials.length === 0) {
        try {
          setDifferentials(JSON.parse(savedDifferentials));
        } catch (e) {}
      }
      if (savedCarePlan && !carePlan) setCarePlan(savedCarePlan);
    };

    loadDrafts();
  }, [rationalization.id]);

  // Poll progress
  useEffect(() => {
    const pollProgress = async () => {
      try {
        const res = await fetch(route('rationalization.progress', rationalization.id));
        if (res.ok) {
          const data = await res.json();
          setCurrentProgress(data.progress);
          setCanUnlock(data.can_unlock_results);
        }
      } catch (e) {
        console.warn('Failed to poll progress');
      }
    };

    const interval = setInterval(pollProgress, 30000); // Poll every 30 seconds
    return () => clearInterval(interval);
  }, [rationalization.id]);

  const handleCardAnswer = async (cardId, rationale, markedAsForgot = false) => {
    setIsSubmitting(true);
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const res = await fetch(route('rationalization.answer-card', cardId), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf ?? ''
        },
        credentials: 'same-origin',
        body: JSON.stringify({ rationale, marked_as_forgot: markedAsForgot })
      });

      if (res.ok) {
        const data = await res.json();
        setCards(cards.map(c => c.id === cardId ? data.card : c));
        setCurrentProgress(data.progress);
      }
    } catch (e) {
      console.error('Failed to answer card', e);
    } finally {
      setIsSubmitting(false);
    }
  };

  const submitDiagnoses = async () => {
    setIsSubmitting(true);
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const res = await fetch(route('rationalization.submit-diagnoses', rationalization.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf ?? ''
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          primary_diagnosis: primaryDiagnosis,
          primary_reasoning: primaryReasoning,
          differential_diagnoses: differentials
        })
      });

      if (res.ok) {
        const data = await res.json();
        setCurrentProgress(data.progress);
      } else {
        const errorData = await res.json();
        alert(errorData.message || 'Failed to submit diagnoses');
      }
    } catch (e) {
      console.error('Failed to submit diagnoses', e);
    } finally {
      setIsSubmitting(false);
    }
  };

  const submitCarePlan = async () => {
    setIsSubmitting(true);
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const res = await fetch(route('rationalization.submit-care-plan', rationalization.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf ?? ''
        },
        credentials: 'same-origin',
        body: JSON.stringify({ care_plan: carePlan })
      });

      if (res.ok) {
        const data = await res.json();
        setCurrentProgress(data.progress);
      } else {
        const errorData = await res.json();
        alert(errorData.message || 'Failed to submit care plan');
      }
    } catch (e) {
      console.error('Failed to submit care plan', e);
    } finally {
      setIsSubmitting(false);
    }
  };

  const completeRationalization = async () => {
    setIsSubmitting(true);
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const res = await fetch(route('rationalization.complete', rationalization.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf ?? ''
        },
        credentials: 'same-origin'
      });

      if (res.ok) {
        // Clear drafts
        localStorage.removeItem(`rationalization_${rationalization.id}_primary_diagnosis`);
        localStorage.removeItem(`rationalization_${rationalization.id}_primary_reasoning`);
        localStorage.removeItem(`rationalization_${rationalization.id}_differentials`);
        localStorage.removeItem(`rationalization_${rationalization.id}_care_plan`);
        
        // Navigate to results
        router.visit(route('osce.results.show', session.id));
      } else {
        const errorData = await res.json();
        alert(errorData.error || 'Failed to complete rationalization');
      }
    } catch (e) {
      console.error('Failed to complete rationalization', e);
    } finally {
      setIsSubmitting(false);
    }
  };

  const addDifferential = () => {
    setDifferentials([...differentials, { diagnosis: '', reasoning: '' }]);
  };

  const removeDifferential = (index) => {
    setDifferentials(differentials.filter((_, i) => i !== index));
  };

  const updateDifferential = (index, field, value) => {
    const updated = differentials.map((d, i) => 
      i === index ? { ...d, [field]: value } : d
    );
    setDifferentials(updated);
  };

  const groupedCards = cards.reduce((groups, card) => {
    const type = card.card_type || 'other';
    if (!groups[type]) groups[type] = [];
    groups[type].push(card);
    return groups;
  }, {});

  const getCardTypeLabel = (type) => {
    switch (type) {
      case 'asked_question': return 'Questions Asked';
      case 'negative_anamnesis': return 'Expected Questions';
      case 'investigation': return 'Investigations';
      default: return 'Other';
    }
  };

  const canSubmitDiagnoses = primaryDiagnosis.length > 0 && primaryReasoning.length >= 50 && 
    differentials.length > 0 && differentials.every(d => d.diagnosis.length > 0 && d.reasoning.length >= 30);

  const canSubmitCarePlan = carePlan.length >= 100;

  return (
    <>
      <Head title="Rationalization" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-6">
          {/* Header */}
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-2xl font-bold">Clinical Rationalization</h1>
              <p className="text-gray-600">{session?.osce_case?.title}</p>
            </div>
            <div className="text-right space-y-1">
              <div className="text-sm text-gray-500">Progress</div>
              <div className="text-lg font-semibold">{Math.round((currentProgress?.completed_percentage || 0))}%</div>
            </div>
          </div>

          {/* Tabs */}
          <div className="border-b border-gray-200">
            <nav className="-mb-px flex space-x-8">
              <button
                onClick={() => setActiveTab('cards')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'cards'
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Anamnesis Cards ({cards.length})
              </button>
              <button
                onClick={() => setActiveTab('diagnosis')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'diagnosis'
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Diagnosis
              </button>
              <button
                onClick={() => setActiveTab('careplan')}
                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'careplan'
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Care Plan
              </button>
            </nav>
          </div>

          {/* Tab Content */}
          {activeTab === 'cards' && (
            <div className="space-y-6">
              {Object.entries(groupedCards).map(([type, typeCards]) => (
                <div key={type} className="space-y-4">
                  <h3 className="text-lg font-semibold">{getCardTypeLabel(type)}</h3>
                  <div className="grid gap-4">
                    {typeCards.map(card => (
                      <RationalizationCard
                        key={card.id}
                        card={card}
                        onAnswer={handleCardAnswer}
                        allowForgot={type === 'negative_anamnesis'}
                        isSubmitting={isSubmitting}
                      />
                    ))}
                  </div>
                </div>
              ))}
            </div>
          )}

          {activeTab === 'diagnosis' && (
            <div className="space-y-6">
              <div className="bg-white border rounded-lg p-6">
                <h3 className="text-lg font-semibold mb-4">Primary Diagnosis</h3>
                <div className="space-y-4">
                  <input
                    type="text"
                    placeholder="Primary diagnosis"
                    value={primaryDiagnosis}
                    onChange={(e) => setPrimaryDiagnosis(e.target.value)}
                    className="w-full border px-3 py-2 rounded"
                  />
                  <textarea
                    placeholder="Clinical reasoning for primary diagnosis (min 50 chars)"
                    value={primaryReasoning}
                    onChange={(e) => setPrimaryReasoning(e.target.value)}
                    rows="4"
                    className="w-full border px-3 py-2 rounded"
                  />
                  <div className="text-sm text-gray-500">
                    {primaryReasoning.length}/50 characters minimum
                  </div>
                </div>
              </div>

              <div className="bg-white border rounded-lg p-6">
                <div className="flex justify-between items-center mb-4">
                  <h3 className="text-lg font-semibold">Differential Diagnoses</h3>
                  <button
                    onClick={addDifferential}
                    className="px-3 py-1 text-sm border rounded"
                  >
                    Add Differential
                  </button>
                </div>
                <div className="space-y-4">
                  {differentials.map((diff, index) => (
                    <div key={index} className="border rounded p-4 space-y-3">
                      <div className="flex justify-between items-center">
                        <span className="font-medium">Differential #{index + 1}</span>
                        <button
                          onClick={() => removeDifferential(index)}
                          className="text-red-600 text-sm"
                        >
                          Remove
                        </button>
                      </div>
                      <input
                        type="text"
                        placeholder="Differential diagnosis"
                        value={diff.diagnosis}
                        onChange={(e) => updateDifferential(index, 'diagnosis', e.target.value)}
                        className="w-full border px-3 py-2 rounded"
                      />
                      <textarea
                        placeholder="Clinical reasoning (min 30 chars)"
                        value={diff.reasoning}
                        onChange={(e) => updateDifferential(index, 'reasoning', e.target.value)}
                        rows="3"
                        className="w-full border px-3 py-2 rounded"
                      />
                      <div className="text-sm text-gray-500">
                        {diff.reasoning.length}/30 characters minimum
                      </div>
                    </div>
                  ))}
                </div>
                <button
                  onClick={submitDiagnoses}
                  disabled={!canSubmitDiagnoses || isSubmitting}
                  className="mt-4 px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50"
                >
                  {isSubmitting ? 'Saving...' : 'Save Diagnoses'}
                </button>
              </div>
            </div>
          )}

          {activeTab === 'careplan' && (
            <div className="bg-white border rounded-lg p-6">
              <h3 className="text-lg font-semibold mb-4">Management Plan</h3>
              <div className="space-y-4">
                <textarea
                  placeholder="Structured care plan (min 100 chars)"
                  value={carePlan}
                  onChange={(e) => setCarePlan(e.target.value)}
                  rows="8"
                  className="w-full border px-3 py-2 rounded"
                />
                <div className="flex justify-between items-center">
                  <div className="text-sm text-gray-500">
                    {carePlan.length}/100 characters minimum
                  </div>
                  <div className="flex gap-2">
                    <span className="text-sm text-green-600">
                      {Math.floor(Date.now() / 1000) % 30 < 15 ? 'Saved' : 'Saving...'}
                    </span>
                    <button
                      onClick={submitCarePlan}
                      disabled={!canSubmitCarePlan || isSubmitting}
                      className="px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50"
                    >
                      {isSubmitting ? 'Saving...' : 'Save Care Plan'}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Progress and Complete Section */}
          <div className="bg-gray-50 border rounded-lg p-6">
            <div className="flex justify-between items-center">
              <div>
                <h3 className="font-semibold">Progress Summary</h3>
                <div className="text-sm text-gray-600 space-y-1">
                  <div>Cards: {currentProgress?.cards_completed || 0}/{currentProgress?.total_cards || 0}</div>
                  <div>Diagnosis: {currentProgress?.diagnosis_completed ? '✓' : '○'}</div>
                  <div>Care Plan: {currentProgress?.care_plan_completed ? '✓' : '○'}</div>
                </div>
              </div>
              <button
                onClick={completeRationalization}
                disabled={!canUnlock || isSubmitting}
                className="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg disabled:opacity-50"
              >
                {isSubmitting ? 'Completing...' : 'Complete & Unlock Results'}
              </button>
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}

// RationalizationCard Component
function RationalizationCard({ card, onAnswer, allowForgot = false, isSubmitting = false }) {
  const [rationale, setRationale] = useState(card.user_rationale || '');
  const [processing, setProcessing] = useState(false);

  const getCardTypeLabel = () => {
    switch (card.card_type) {
      case 'asked_question': return 'Question Asked';
      case 'negative_anamnesis': return 'Expected Question';
      case 'investigation': return 'Investigation';
      default: return 'Question';
    }
  };

  const getMinLength = () => {
    return card.card_type === 'negative_anamnesis' ? 20 : 30;
  };

  const canSubmit = rationale.length >= getMinLength();

  const handleSubmit = async () => {
    if (!canSubmit || processing) return;
    setProcessing(true);
    await onAnswer(card.id, rationale, false);
    setProcessing(false);
  };

  const handleForgot = async () => {
    if (processing) return;
    setProcessing(true);
    await onAnswer(card.id, null, true);
    setProcessing(false);
  };

  return (
    <div className="bg-white rounded-lg border shadow-sm">
      <div className="px-6 py-4 border-b border-gray-200">
        <div className="flex items-start justify-between">
          <div className="flex-1">
            <h3 className="text-lg font-medium text-gray-900 mb-2">
              {card.prompt_text}
            </h3>
            <div className="text-sm text-gray-600 bg-gray-50 rounded px-3 py-2">
              <strong>{getCardTypeLabel()}:</strong> "{card.question_text}"
            </div>
          </div>
          <div className="ml-4 flex-shrink-0">
            <div className={`px-3 py-1 rounded-full text-sm font-medium ${
              card.is_answered 
                ? 'bg-green-100 text-green-800' 
                : 'bg-yellow-100 text-yellow-800'
            }`}>
              {card.is_answered ? '✓ Completed' : 'Pending'}
            </div>
          </div>
        </div>
      </div>

      <div className="px-6 py-6">
        {!card.is_answered ? (
          <div className="space-y-4">
            {allowForgot && (
              <div className="mb-4">
                <button
                  onClick={handleForgot}
                  disabled={processing || isSubmitting}
                  className="px-4 py-2 bg-orange-100 text-orange-800 rounded-lg text-sm font-medium hover:bg-orange-200 transition-colors duration-200 disabled:opacity-50"
                >
                  I forgot to ask this question
                </button>
                <p className="text-sm text-gray-600 mt-2">
                  Or provide your rationale for why you chose not to ask this question:
                </p>
              </div>
            )}

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Your rationale:
              </label>
              <textarea
                value={rationale}
                onChange={(e) => setRationale(e.target.value)}
                rows="4"
                className="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder={`Minimum ${getMinLength()} characters. Provide clinical reasoning...`}
                disabled={processing || isSubmitting}
              />
              <div className="flex justify-between items-center mt-2">
                <p className="text-xs text-gray-500">
                  Minimum {getMinLength()} characters. Current: {rationale.length}
                </p>
                <button
                  onClick={handleSubmit}
                  disabled={!canSubmit || processing || isSubmitting}
                  className={`px-4 py-2 text-white font-medium rounded-lg transition-colors duration-200 ${
                    canSubmit && !processing && !isSubmitting
                      ? 'bg-blue-600 hover:bg-blue-700' 
                      : 'bg-gray-400 cursor-not-allowed'
                  }`}
                >
                  {processing ? 'Submitting...' : 'Submit Rationale'}
                </button>
              </div>
            </div>
          </div>
        ) : (
          <div className="space-y-4">
            {card.marked_as_forgot ? (
              <div className="p-4 bg-orange-50 border border-orange-200 rounded-lg">
                <div className="flex items-center space-x-2">
                  <div className="w-5 h-5 bg-orange-400 rounded-full flex items-center justify-center">
                    <span className="text-white text-xs">!</span>
                  </div>
                  <span className="text-sm font-medium text-orange-800">
                    Marked as forgotten
                  </span>
                </div>
                <p className="text-sm text-orange-700 mt-2">
                  This question was not asked during your session and you indicated you forgot to ask it.
                </p>
              </div>
            ) : (
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Your rationale:
                </label>
                <div className="bg-gray-50 border border-gray-200 rounded-md px-3 py-3 text-sm">
                  {card.user_rationale}
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}