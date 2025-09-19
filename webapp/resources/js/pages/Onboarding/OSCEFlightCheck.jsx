import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@vibe-kanban/ui-kit';
import CasePrimer from '@/components/CasePrimer';

export default function OSCEFlightCheck({ osceCase, user, skipAvailable = false }) {
  const [startTime] = useState(Date.now());
  const [patientImage, setPatientImage] = useState(null);
  const [imageLoading, setImageLoading] = useState(true);

  const breadcrumbs = [
    { title: 'OSCE', href: route('osce') },
    { title: 'Flight Check', href: '#' }
  ];

  // Auto-generate patient visualization on component mount
  useEffect(() => {
    const generatePatientVisualization = async () => {
      try {
        const response = await fetch(route('visualizer.generate'), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
          },
          body: JSON.stringify({
            case_id: osceCase.id,
            prompt_type: 'case_specific',
            custom_prompt: `${osceCase.patient_age || 35} tahun ${osceCase.patient_gender || 'laki-laki'} datang ke ${osceCase.clinical_setting} dengan keluhan ${osceCase.chief_complaint}. ${osceCase.patient_demographics || ''}`
          })
        });

        const data = await response.json();

        if (response.ok && data.image_url) {
          setPatientImage(data.image_url);
        }
      } catch (error) {
        console.error('Failed to generate patient visualization:', error);
      } finally {
        setImageLoading(false);
      }
    };

    generatePatientVisualization();
  }, [osceCase.id]);

  const completeOnboarding = async () => {
    const timeSpent = Math.floor((Date.now() - startTime) / 1000);

    try {
      await fetch(route('onboarding.complete', osceCase.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          step: 1,
          timeSpent
        })
      });
    } catch (error) {
      console.error('Failed to record completion:', error);
    }

    // Start the actual OSCE session
    router.post(route('osce.sessions.start'), { osce_case_id: osceCase.id });
  };

  const skipOnboarding = async () => {
    try {
      await fetch(route('onboarding.skip', osceCase.id), {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        }
      });
    } catch (error) {
      console.error('Failed to record skip:', error);
    }

    router.post(route('osce.sessions.start'), { osce_case_id: osceCase.id });
  };

  return (
    <>
      <Head title="OSCE Flight Check" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="max-w-6xl mx-auto space-y-8">
          {/* Header */}
          <div className="text-center space-y-4">
            <div className="flex items-center justify-center gap-3 mb-2">
              <div className="w-8 h-0.5 bg-gradient-to-r from-emerald-400 to-cyan-400"></div>
              <span className="text-xs text-emerald-500 font-mono uppercase tracking-wider">OSCE Flight Check</span>
              <div className="w-8 h-0.5 bg-gradient-to-l from-emerald-400 to-cyan-400"></div>
            </div>
            <h1 className="text-2xl font-semibold text-foreground">Pre-Session Briefing</h1>
            <p className="text-muted-foreground">Get ready for your clinical assessment</p>
          </div>

          <div className="grid lg:grid-cols-2 gap-8">
            {/* Left Column - Case Information */}
            <div className="space-y-6">
              {/* Case Overview */}
              <div className="clean-card p-6">
                <div className="flex items-center gap-3 mb-4">
                  <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                  <h3 className="text-lg font-medium text-foreground">Case Overview</h3>
                </div>

                <div className="space-y-4">
                  <div>
                    <h4 className="font-medium text-foreground mb-2">{osceCase.title}</h4>
                    <div className="space-y-2">
                      <div className="bg-gradient-to-r from-emerald-500/10 to-blue-500/10 p-3 rounded border border-emerald-500/20">
                        <div className="text-sm font-medium text-foreground mb-1">Data Pasien:</div>
                        <div className="text-sm text-muted-foreground">
                          {osceCase.patient_gender || 'Laki-laki'}, {osceCase.patient_age || 35} tahun,
                          datang ke {osceCase.clinical_setting} dengan keluhan <strong>{osceCase.chief_complaint}</strong>
                        </div>
                      </div>

                      {osceCase.patient_history && (
                        <div className="text-sm text-muted-foreground">
                          <span className="font-medium">Riwayat:</span> {osceCase.patient_history}
                        </div>
                      )}
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div className="flex items-center gap-2">
                      <span className="text-emerald-500">⏱️</span>
                      <span className="text-muted-foreground">Durasi: {osceCase.duration_minutes} menit</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className="text-blue-500">🏥</span>
                      <span className="text-muted-foreground">Lokasi: {osceCase.clinical_setting}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className="text-purple-500">👤</span>
                      <span className="text-muted-foreground">Umur: {osceCase.patient_age || 35} tahun</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <span className="text-pink-500">⚧️</span>
                      <span className="text-muted-foreground">Gender: {osceCase.patient_gender || 'Laki-laki'}</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Quick Tips */}
              <div className="clean-card p-6 bg-gradient-to-br from-blue-500/10 to-purple-500/10">
                <h3 className="text-lg font-medium text-foreground mb-4">Quick Tips</h3>
                <div className="space-y-3">
                  <div className="flex items-start gap-3">
                    <span className="text-emerald-500 mt-1">✓</span>
                    <span className="text-sm text-muted-foreground">Start with open-ended questions to gather history</span>
                  </div>
                  <div className="flex items-start gap-3">
                    <span className="text-emerald-500 mt-1">✓</span>
                    <span className="text-sm text-muted-foreground">Perform focused physical examination based on complaints</span>
                  </div>
                  <div className="flex items-start gap-3">
                    <span className="text-emerald-500 mt-1">✓</span>
                    <span className="text-sm text-muted-foreground">Order tests with clear clinical reasoning</span>
                  </div>
                  <div className="flex items-start gap-3">
                    <span className="text-emerald-500 mt-1">✓</span>
                    <span className="text-sm text-muted-foreground">Manage your time - check the timer regularly</span>
                  </div>
                </div>
              </div>

              {/* Case Primer */}
              <div className="clean-card p-6">
                <h3 className="text-lg font-medium text-foreground mb-4">Clinical Context</h3>
                <CasePrimer caseId={osceCase.id} mode="quick" />
              </div>
            </div>

            {/* Right Column - Patient Visualization */}
            <div className="space-y-6">
              {/* Patient Visualization */}
              <div className="clean-card p-6">
                <div className="flex items-center gap-3 mb-4">
                  <div className="w-2 h-2 bg-purple-500 rounded-full animate-pulse"></div>
                  <h3 className="text-lg font-medium text-foreground">Patient Visualization</h3>
                  <div className="text-xs text-purple-400 font-mono uppercase tracking-wider">Nano Banana AI</div>
                </div>

                <div className="aspect-square bg-gradient-to-br from-purple-500/10 to-pink-500/10 rounded-lg overflow-hidden">
                  {imageLoading ? (
                    <div className="w-full h-full flex items-center justify-center">
                      <div className="space-y-4 text-center">
                        <div className="w-16 h-16 border-4 border-purple-400 border-t-transparent rounded-full animate-spin mx-auto"></div>
                        <div className="text-sm text-muted-foreground font-mono">
                          Generating patient visualization...
                        </div>
                      </div>
                    </div>
                  ) : patientImage ? (
                    <img
                      src={patientImage}
                      alt="AI Generated Patient"
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center">
                      <div className="text-center space-y-2">
                        <div className="text-4xl">👤</div>
                        <div className="text-sm text-muted-foreground">
                          Unable to generate patient image
                        </div>
                      </div>
                    </div>
                  )}
                </div>

                <div className="mt-4 p-3 bg-gradient-to-r from-purple-500/10 to-pink-500/10 rounded border border-purple-500/20">
                  <p className="text-xs text-muted-foreground">
                    🎨 AI-generated patient visualization based on case demographics and presentation
                  </p>
                </div>
              </div>

              {/* Interface Guide */}
              <div className="clean-card p-6">
                <h3 className="text-lg font-medium text-foreground mb-4">Interface Guide</h3>
                <div className="space-y-3">
                  <div className="flex items-center gap-3">
                    <div className="w-3 h-3 bg-emerald-500 rounded-full"></div>
                    <span className="text-sm text-muted-foreground">Chat with patient for history</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <div className="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span className="text-sm text-muted-foreground">Perform physical examinations</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <div className="w-3 h-3 bg-purple-500 rounded-full"></div>
                    <span className="text-sm text-muted-foreground">Order diagnostic tests</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <div className="w-3 h-3 bg-orange-500 rounded-full"></div>
                    <span className="text-sm text-muted-foreground">Monitor session timer</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Action Buttons */}
          <div className="flex items-center justify-between pt-6 border-t border-border/50">
            <div className="flex gap-2">
              {skipAvailable && (
                <Button
                  variant="outline"
                  onClick={skipOnboarding}
                  className="text-muted-foreground"
                >
                  Skip Briefing
                </Button>
              )}
            </div>

            <div className="flex gap-4">
              <Button
                onClick={completeOnboarding}
                className="bg-emerald-600 hover:bg-emerald-700 px-8 py-3"
                size="lg"
              >
                🚀 Start OSCE Session
              </Button>
            </div>
          </div>

          {/* Performance Notes */}
          <div className="clean-card p-4 bg-gradient-to-r from-amber-500/10 to-orange-500/10 border-dashed border-amber-500/20">
            <div className="text-center space-y-2">
              <div className="text-sm text-muted-foreground font-mono">
                💡 Your performance will be evaluated on clinical reasoning, time management, and diagnostic accuracy
              </div>
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}