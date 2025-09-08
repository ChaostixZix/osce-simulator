import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import Modal from './Modal';

export default function FinalizeSessionModal({ open, onClose, session, onFinalized }) {
  const [diagnosis, setDiagnosis] = useState('');
  // Support multiple differential rows
  const [differentials, setDifferentials] = useState(['']);
  const [plan, setPlan] = useState('');
  const [errors, setErrors] = useState({});
  const [processing, setProcessing] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();

    setProcessing(true);
    setErrors({});

    // Build a readable differential list as a single string for backend
    const differentialText = differentials
      .map(v => (v || '').trim())
      .filter(Boolean)
      .map((v, i) => `${i + 1}. ${v}`)
      .join('\n');

    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const res = await fetch(`/api/osce/sessions/${session.id}/finalize`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf || ''
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          diagnosis,
          differential_diagnosis: differentialText,
          plan,
        })
      });

      const data = await res.json().catch(() => ({}));

      if (!res.ok) {
        // Attempt to surface validation errors
        if (data && typeof data === 'object') {
          setErrors(data.errors || data);
        } else {
          setErrors({ general: 'Failed to finalize session' });
        }
        return;
      }

      onClose?.();
      // Reset form
      setDiagnosis('');
      setDifferentials(['']);
      setPlan('');
      // Reload the page to show updated session data
      router.reload({ only: ['session'] });
      // Notify parent to continue flow (e.g., go to rationalization)
      if (typeof onFinalized === 'function') {
        onFinalized();
      }
    } catch (err) {
      setErrors({ general: 'Network error while finalizing session' });
    } finally {
      setProcessing(false);
    }
  };

  const hasAtLeastOneDifferential = differentials.some(v => (v || '').trim().length > 0);
  const totalDifferentialChars = differentials.reduce((sum, v) => sum + ((v || '').trim().length), 0);
  const isFormValid = diagnosis.trim().length >= 10 && 
                     hasAtLeastOneDifferential && totalDifferentialChars >= 10 && 
                     plan.trim().length >= 10;

  const addDifferential = () => setDifferentials(prev => [...prev, '']);
  const removeDifferential = (idx) => setDifferentials(prev => prev.filter((_, i) => i !== idx));
  const updateDifferential = (idx, value) => setDifferentials(prev => prev.map((v, i) => i === idx ? value : v));

  const footer = (
    <div className="flex items-center justify-between w-full">
      <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
        <div className="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></div>
        <span>finalization required</span>
      </div>
      
      <div className="flex items-center gap-3">
        <button
          type="button"
          onClick={onClose}
          disabled={processing}
          className="cyber-button px-4 py-2 text-muted-foreground hover:text-foreground font-mono uppercase tracking-wide text-xs"
        >
          cancel
        </button>
        <button
          type="submit"
          form="finalize-form"
          disabled={!isFormValid || processing}
          className="cyber-button px-6 py-2 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wide text-xs disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {processing ? (
            <div className="flex items-center gap-2">
              <div className="w-3 h-3 border border-emerald-400 border-t-transparent rounded-full animate-spin"></div>
              <span>finalizing...</span>
            </div>
          ) : (
            'finalize session'
          )}
        </button>
      </div>
    </div>
  );

  return (
    <Modal 
      open={open} 
      onClose={onClose} 
      title="finalize osce session"
      size="lg"
      footer={footer}
    >
      <div className="space-y-6">
        {/* Session Info Header */}
        <div className="cyber-border bg-gradient-to-br from-emerald-500/10 to-emerald-600/5 border-emerald-500/30 p-4 relative group">
          <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>
          
          <div className="flex items-center gap-3 mb-2">
            <div className="w-1 h-4 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
            <span className="text-xs text-emerald-400 font-mono uppercase tracking-wider">session #{session.id}</span>
          </div>
          
          <div className="text-sm font-medium lowercase text-foreground">
            {session.osce_case?.title || session.osceCase?.title || 'OSCE Case'}
          </div>
          <div className="text-xs text-muted-foreground lowercase">
            completed {new Date(session.completed_at).toLocaleDateString()}
          </div>
        </div>

        <form id="finalize-form" onSubmit={handleSubmit} className="space-y-6">
          {/* Diagnosis Field */}
          <div className="space-y-3">
            <div className="flex items-center gap-3">
              <div className="w-1 h-5 bg-gradient-to-b from-blue-400 to-cyan-400"></div>
              <label className="text-sm font-medium lowercase text-foreground font-mono">
                primary diagnosis
              </label>
              <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
              <div className="text-xs text-muted-foreground font-mono">
                {diagnosis.length}/10 min
              </div>
            </div>
            
            <div className="cyber-border bg-gradient-to-br from-blue-500/5 to-blue-600/5 border-blue-500/20 p-0 overflow-hidden">
              <textarea
                value={diagnosis}
                onChange={(e) => setDiagnosis(e.target.value)}
                rows={3}
                className="w-full p-4 bg-transparent border-none outline-none resize-none text-sm text-foreground placeholder-muted-foreground"
                placeholder="enter your primary diagnosis with supporting rationale..."
                required
                minLength={10}
              />
            </div>
            
            {errors.diagnosis && (
              <div className="text-xs text-red-400 font-mono bg-red-500/10 border border-red-500/20 p-2 cyber-border">
                {errors.diagnosis}
              </div>
            )}
          </div>

          {/* Differential Diagnosis Field */}
          <div className="space-y-3">
            <div className="flex items-center gap-3">
              <div className="w-1 h-5 bg-gradient-to-b from-purple-400 to-blue-400"></div>
              <label className="text-sm font-medium lowercase text-foreground font-mono">
                differential diagnoses
              </label>
              <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
              <div className="text-xs text-muted-foreground font-mono">
                {differentials.filter(v => (v || '').trim()).length} rows • {totalDifferentialChars}/10 min
              </div>
            </div>

            <div className="space-y-2">
              {differentials.map((val, idx) => (
                <div key={idx} className="flex items-start gap-2">
                  <div className="cyber-border flex-1 bg-gradient-to-br from-purple-500/5 to-purple-600/5 border-purple-500/20 p-0 overflow-hidden">
                    <textarea
                      value={val}
                      onChange={(e) => updateDifferential(idx, e.target.value)}
                      rows={2}
                      className="w-full p-3 bg-transparent border-none outline-none resize-none text-sm text-foreground placeholder-muted-foreground"
                      placeholder={`differential #${idx + 1} — diagnosis and brief reasoning...`}
                    />
                  </div>
                  {differentials.length > 1 && (
                    <button
                      type="button"
                      onClick={() => removeDifferential(idx)}
                      className="px-2 py-1 text-xs text-red-400 border border-red-400/40 hover:bg-red-400/10 rounded"
                    >
                      remove
                    </button>
                  )}
                </div>
              ))}

              <div>
                <button
                  type="button"
                  onClick={addDifferential}
                  className="cyber-button px-3 py-1 text-purple-600 dark:text-purple-300 font-mono uppercase tracking-wide text-xs"
                >
                  + add differential
                </button>
              </div>
            </div>

            {errors.differential_diagnosis && (
              <div className="text-xs text-red-400 font-mono bg-red-500/10 border border-red-500/20 p-2 cyber-border">
                {errors.differential_diagnosis}
              </div>
            )}
          </div>

          {/* Plan Field */}
          <div className="space-y-3">
            <div className="flex items-center gap-3">
              <div className="w-1 h-5 bg-gradient-to-b from-emerald-400 to-green-400"></div>
              <label className="text-sm font-medium lowercase text-foreground font-mono">
                management plan
              </label>
              <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
              <div className="text-xs text-muted-foreground font-mono">
                {plan.length}/10 min
              </div>
            </div>
            
            <div className="cyber-border bg-gradient-to-br from-emerald-500/5 to-emerald-600/5 border-emerald-500/20 p-0 overflow-hidden">
              <textarea
                value={plan}
                onChange={(e) => setPlan(e.target.value)}
                rows={4}
                className="w-full p-4 bg-transparent border-none outline-none resize-none text-sm text-foreground placeholder-muted-foreground"
                placeholder="describe your complete management plan including investigations, treatments, and follow-up..."
                required
                minLength={10}
              />
            </div>
            
            {errors.plan && (
              <div className="text-xs text-red-400 font-mono bg-red-500/10 border border-red-500/20 p-2 cyber-border">
                {errors.plan}
              </div>
            )}
          </div>
        </form>

        {/* Requirements notice */}
        <div className="cyber-border bg-gradient-to-br from-amber-500/10 to-amber-600/5 border-amber-500/30 p-4 relative">
          <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-amber-400 to-orange-400 opacity-60"></div>
          
          <div className="flex items-center gap-3 mb-2">
            <div className="w-1 h-4 bg-gradient-to-b from-amber-400 to-orange-400"></div>
            <span className="text-xs text-amber-400 font-mono uppercase tracking-wider">requirements</span>
          </div>
          
          <div className="text-xs text-muted-foreground space-y-1">
            <div>• all fields must contain at least 10 characters</div>
            <div>• session will be locked after finalization</div>
            <div>• finalization cannot be undone</div>
          </div>
        </div>
      </div>
    </Modal>
  );
}
