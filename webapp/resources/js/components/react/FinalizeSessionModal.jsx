import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import Modal from './Modal';

export default function FinalizeSessionModal({ open, onClose, session }) {
  const [diagnosis, setDiagnosis] = useState('');
  const [differentialDiagnosis, setDifferentialDiagnosis] = useState('');
  const [plan, setPlan] = useState('');
  const [errors, setErrors] = useState({});
  const [processing, setProcessing] = useState(false);

  const handleSubmit = (e) => {
    e.preventDefault();
    
    setProcessing(true);
    setErrors({});

    router.post(`/api/osce/sessions/${session.id}/finalize`, {
      diagnosis,
      differential_diagnosis: differentialDiagnosis,
      plan,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        onClose();
        // Reset form
        setDiagnosis('');
        setDifferentialDiagnosis('');
        setPlan('');
        // Reload the page to show updated session data
        router.reload({ only: ['session'] });
      },
      onError: (error) => {
        setErrors(error);
      },
      onFinish: () => {
        setProcessing(false);
      }
    });
  };

  const isFormValid = diagnosis.trim().length >= 10 && 
                     differentialDiagnosis.trim().length >= 10 && 
                     plan.trim().length >= 10;

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
                differential diagnosis
              </label>
              <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
              <div className="text-xs text-muted-foreground font-mono">
                {differentialDiagnosis.length}/10 min
              </div>
            </div>
            
            <div className="cyber-border bg-gradient-to-br from-purple-500/5 to-purple-600/5 border-purple-500/20 p-0 overflow-hidden">
              <textarea
                value={differentialDiagnosis}
                onChange={(e) => setDifferentialDiagnosis(e.target.value)}
                rows={3}
                className="w-full p-4 bg-transparent border-none outline-none resize-none text-sm text-foreground placeholder-muted-foreground"
                placeholder="list alternative diagnoses and reasoning for ruling them in/out..."
                required
                minLength={10}
              />
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