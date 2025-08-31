import React, { useState, useEffect } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FinalizeSessionModal from '@/components/react/FinalizeSessionModal';

export default function OsceResults({ session, user }) {
  const [showFinalizeModal, setShowFinalizeModal] = useState(false);
  
  const breadcrumbs = [
    { title: 'OSCE', href: route('osce') },
    { title: 'Results', href: '#' },
  ];

  // Check if session needs finalization
  const needsFinalization = session?.status === 'completed' && !session?.finalized_at;

  // Auto-show finalize modal if needed
  useEffect(() => {
    if (needsFinalization) {
      setShowFinalizeModal(true);
    }
  }, [needsFinalization]);

  return (
    <>
      <Head title="OSCE Results" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-4">
          {/* Enhanced Welcome Header */}
          <div className="text-center space-y-4 relative">
            <div className="absolute -top-2 left-1/2 transform -translate-x-1/2 w-20 h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent"></div>
            
            <div className="flex items-center justify-center gap-3 mb-2">
              <div className="w-8 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
              <span className="text-xs text-emerald-500 font-mono uppercase tracking-wider">session results</span>
              <div className="w-8 h-0.5 bg-gradient-to-l from-emerald-400 to-cyan-400"></div>
            </div>
            
            <h2 className="text-2xl font-medium lowercase glow-text text-foreground">{session?.osceCase?.title}</h2>
          </div>

          {/* Session Status Card */}
          <div className="cyber-border bg-gradient-to-br from-blue-500/10 to-blue-600/5 border-blue-500/30 p-4 relative group">
            <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-blue-400 to-cyan-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>
            
            <div className="flex items-center gap-3 mb-2">
              <div className="w-1 h-4 bg-gradient-to-b from-blue-400 to-cyan-400"></div>
              <span className="text-xs text-blue-400 font-mono uppercase tracking-wider">session status</span>
            </div>
            
            <div className="space-y-2 text-sm">
              <div className="flex items-center justify-between">
                <span className="text-muted-foreground lowercase">completed at:</span>
                <span className="text-foreground font-mono">
                  {session?.completed_at ? new Date(session.completed_at).toLocaleString('id-ID') : '-'}
                </span>
              </div>
              
              <div className="flex items-center justify-between">
                <span className="text-muted-foreground lowercase">finalization status:</span>
                <div className="flex items-center gap-2">
                  <div className={`w-2 h-2 rounded-full ${session?.finalized_at ? 'bg-emerald-500' : 'bg-amber-500 animate-pulse'}`}></div>
                  <span className={`text-xs font-mono uppercase tracking-wider ${session?.finalized_at ? 'text-emerald-400' : 'text-amber-400'}`}>
                    {session?.finalized_at ? 'finalized' : 'pending'}
                  </span>
                </div>
              </div>
              
              {session?.finalized_at && (
                <div className="flex items-center justify-between">
                  <span className="text-muted-foreground lowercase">finalized at:</span>
                  <span className="text-foreground font-mono">
                    {new Date(session.finalized_at).toLocaleString('id-ID')}
                  </span>
                </div>
              )}
            </div>
          </div>

          {/* Finalization Button (if not finalized) */}
          {needsFinalization && (
            <div className="cyber-border bg-gradient-to-br from-amber-500/10 to-amber-600/5 border-amber-500/30 p-4 relative group">
              <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-amber-400 to-orange-400 opacity-60 group-hover:opacity-100 transition-opacity"></div>
              
              <div className="flex items-center justify-between">
                <div>
                  <div className="flex items-center gap-3 mb-2">
                    <div className="w-1 h-4 bg-gradient-to-b from-amber-400 to-orange-400"></div>
                    <span className="text-xs text-amber-400 font-mono uppercase tracking-wider">action required</span>
                  </div>
                  <div className="text-sm text-foreground lowercase">session must be finalized to complete the workflow</div>
                  <div className="text-xs text-muted-foreground mt-1">provide diagnosis, differential, and management plan</div>
                </div>
                
                <button
                  onClick={() => setShowFinalizeModal(true)}
                  className="cyber-button px-4 py-2 text-amber-600 dark:text-amber-300 font-mono uppercase tracking-wide text-xs"
                >
                  finalize now
                </button>
              </div>
            </div>
          )}

          {/* Finalized Content Display */}
          {session?.finalized_at && (
            <div className="space-y-4">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-1 h-6 bg-gradient-to-b from-emerald-400 to-cyan-400"></div>
                <h3 className="text-lg font-medium lowercase text-foreground font-mono">finalized assessment</h3>
                <div className="flex-1 h-px bg-gradient-to-r from-border to-transparent"></div>
                <div className="flex items-center gap-2 text-xs text-muted-foreground font-mono">
                  <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                  <span>locked</span>
                </div>
              </div>
              
              <div className="grid gap-4">
                <div className="cyber-border bg-gradient-to-br from-emerald-500/5 to-emerald-600/5 border-emerald-500/20 p-4">
                  <div className="text-xs text-emerald-400 font-mono uppercase tracking-wider mb-2">primary diagnosis</div>
                  <div className="text-sm text-foreground whitespace-pre-wrap">{session.diagnosis}</div>
                </div>
                
                <div className="cyber-border bg-gradient-to-br from-blue-500/5 to-blue-600/5 border-blue-500/20 p-4">
                  <div className="text-xs text-blue-400 font-mono uppercase tracking-wider mb-2">differential diagnosis</div>
                  <div className="text-sm text-foreground whitespace-pre-wrap">{session.differential_diagnosis}</div>
                </div>
                
                <div className="cyber-border bg-gradient-to-br from-purple-500/5 to-purple-600/5 border-purple-500/20 p-4">
                  <div className="text-xs text-purple-400 font-mono uppercase tracking-wider mb-2">management plan</div>
                  <div className="text-sm text-foreground whitespace-pre-wrap">{session.plan}</div>
                </div>
              </div>
            </div>
          )}

          <div className="grid md:grid-cols-2 gap-3">
            <div className="border p-3">
              <div className="font-medium mb-1">Ordered Tests</div>
              <ul className="text-sm list-disc ml-4">
                {(session?.ordered_tests || session?.orderedTests || []).map((t, idx) => (
                  <li key={idx}>{t.test_name || t.testName} — {t.results?.status || 'pending'}</li>
                ))}
              </ul>
            </div>
            <div className="border p-3">
              <div className="font-medium mb-1">Examinations</div>
              <ul className="text-sm list-disc ml-4">
                {(session?.examinations || []).map((e, idx) => (
                  <li key={idx}>{e.category || e.type}: {e.finding || '-'}</li>
                ))}
              </ul>
            </div>
          </div>

          <div className="flex gap-2">
            <button
              className="px-4 py-2 border"
              onClick={() => router.post(route('osce.assess.trigger', session.id), { force: true }, { preserveScroll: true })}
            >
              Re/Assess Session
            </button>
            <button
              className="px-4 py-2 border"
              onClick={async () => {
                const res = await fetch(route('osce.status', session.id));
                const data = await res.json();
                alert(`Status: ${data.status} (${data.progress ?? 0}%)`);
              }}
            >
              Check Status
            </button>
          </div>

          <div className="flex items-center justify-center">
            <Link 
              href={route('osce')} 
              className="cyber-button px-4 py-2 text-emerald-600 dark:text-emerald-300 font-mono uppercase tracking-wide text-xs"
            >
              back to osce
            </Link>
          </div>
        </div>
        
        {/* Finalize Modal */}
        <FinalizeSessionModal 
          open={showFinalizeModal} 
          onClose={() => setShowFinalizeModal(false)}
          session={session}
        />
      </AppLayout>
    </>
  );
}
