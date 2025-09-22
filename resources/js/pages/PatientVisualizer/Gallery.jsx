import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@vibe-kanban/ui-kit';

export default function PatientVisualizerGallery({ osceCase, commonPrompts, recentVisualizations }) {
  const [customPrompt, setCustomPrompt] = useState('');
  const [selectedStyle, setSelectedStyle] = useState('medical-illustration');
  const [selectedSetting, setSelectedSetting] = useState('clinical');
  const [generating, setGenerating] = useState(false);
  const [generatedImages, setGeneratedImages] = useState([]);
  const [activeTab, setActiveTab] = useState('common');
  const [galleryImages, setGalleryImages] = useState(recentVisualizations || []);
  const [loadingGallery, setLoadingGallery] = useState(false);
  const [error, setError] = useState('');

  const breadcrumbs = [
    { title: 'OSCE', href: route('osce') },
    { title: 'Patient Visualizer', href: '#' }
  ];

  const styles = [
    { key: 'medical-illustration', label: 'Medical Illustration', description: 'Clean, educational diagrams' },
    { key: 'realistic', label: 'Realistic', description: 'Professional medical photography' },
    { key: 'stylized', label: 'Stylized', description: 'Artistic medical portraits' },
    { key: 'diagram', label: 'Diagram', description: 'Clear medical schematics' }
  ];

  const settings = [
    { key: 'clinical', label: 'Clinical', description: 'Modern clinical setting' },
    { key: 'emergency', label: 'Emergency', description: 'Emergency department' },
    { key: 'ward', label: 'Ward', description: 'Hospital ward setting' },
    { key: 'consultation', label: 'Consultation', description: 'Consultation room' }
  ];

  const generateFromCommon = async (promptKey) => {
    setGenerating(true);
    setError('');

    try {
      const response = await fetch(route('visualizer.generate-common', promptKey), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          style: selectedStyle,
          setting: selectedSetting,
          osce_case_id: osceCase?.id
        })
      });

      const data = await response.json();

      if (data.success) {
        setGeneratedImages(prev => [data.visualization, ...prev]);
      } else {
        setError(data.error || 'Failed to generate visualization');
      }
    } catch (err) {
      console.error('Generation error:', err);
      setError('Network error occurred');
    } finally {
      setGenerating(false);
    }
  };

  const generateCustom = async () => {
    if (!customPrompt.trim()) return;

    setGenerating(true);
    setError('');

    try {
      const response = await fetch(route('visualizer.generate'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          prompt: customPrompt,
          style: selectedStyle,
          setting: selectedSetting,
          osce_case_id: osceCase?.id
        })
      });

      const data = await response.json();

      if (data.success) {
        setGeneratedImages(prev => [data.visualization, ...prev]);
        setCustomPrompt('');
      } else {
        setError(data.error || 'Failed to generate visualization');
      }
    } catch (err) {
      console.error('Generation error:', err);
      setError('Network error occurred');
    } finally {
      setGenerating(false);
    }
  };

  const loadGallery = async () => {
    setLoadingGallery(true);
    try {
      const response = await fetch(route('visualizer.gallery'));
      const data = await response.json();
      setGalleryImages(data.data || []);
    } catch (err) {
      console.error('Failed to load gallery:', err);
    } finally {
      setLoadingGallery(false);
    }
  };

  useEffect(() => {
    if (activeTab === 'gallery') {
      loadGallery();
    }
  }, [activeTab]);

  const renderLoadingSkeleton = () => (
    <div className="clean-card p-6 animate-pulse">
      <div className="aspect-square bg-muted rounded mb-4"></div>
      <div className="h-4 bg-muted rounded mb-2"></div>
      <div className="h-3 bg-muted rounded w-2/3"></div>
    </div>
  );

  const renderImageCard = (image, index) => (
    <div key={index} className="clean-card p-4 group hover:shadow-sm transition-all duration-200">
      <div className="aspect-square rounded overflow-hidden mb-4 bg-muted relative">
        <img
          src={image.image_url}
          alt={image.prompt || image.description}
          className="w-full h-full object-cover"
          loading="lazy"
        />
        <div className="absolute top-2 right-2 bg-black/50 text-white text-xs px-2 py-1 rounded">
          {image.cached ? '💾 Cached' : '✨ Fresh'}
        </div>
        {image.watermarked && (
          <div className="absolute bottom-2 left-2 bg-black/50 text-white text-xs px-2 py-1 rounded">
            AI Generated
          </div>
        )}
      </div>
      <div className="space-y-2">
        <h3 className="font-medium text-foreground text-sm line-clamp-2">
          {image.description || image.prompt}
        </h3>
        {image.category && (
          <span className="inline-block bg-muted text-muted-foreground text-xs px-2 py-1 rounded">
            {image.category}
          </span>
        )}
        <div className="text-xs text-muted-foreground">
          Generated {new Date(image.generated_at).toLocaleDateString()}
        </div>
      </div>
    </div>
  );

  return (
    <>
      <Head title="Patient Visualizer - Nano Banana" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="max-w-6xl mx-auto space-y-6">
          {/* Header */}
          <div className="text-center space-y-4">
            <div className="flex items-center justify-center gap-3 mb-2">
              <div className="w-8 h-0.5 bg-gradient-to-r from-purple-400 to-pink-400"></div>
              <span className="text-xs text-purple-500 font-mono uppercase tracking-wider">Nano Banana</span>
              <div className="w-8 h-0.5 bg-gradient-to-l from-purple-400 to-pink-400"></div>
            </div>
            <h1 className="text-2xl font-semibold text-foreground">Patient Visualizer</h1>
            <p className="text-muted-foreground">Generate stylized patient portraits and clinical vignettes for training</p>

            {osceCase && (
              <div className="clean-card p-4 bg-gradient-to-br from-blue-500/10 to-purple-500/10">
                <div className="font-medium text-foreground">Case Context: {osceCase.title}</div>
                <div className="text-sm text-muted-foreground">{osceCase.chief_complaint}</div>
              </div>
            )}
          </div>

          {/* Error Display */}
          {error && (
            <div className="clean-card p-4 bg-gradient-to-br from-red-500/10 to-red-600/5 border-red-500/20">
              <div className="text-red-600 text-sm">{error}</div>
            </div>
          )}

          {/* Style & Setting Controls */}
          <div className="clean-card p-6">
            <h2 className="font-medium text-foreground mb-4">Generation Settings</h2>
            <div className="grid md:grid-cols-2 gap-6">
              <div>
                <label className="block text-sm font-medium text-foreground mb-3">Style</label>
                <div className="grid grid-cols-2 gap-2">
                  {styles.map((style) => (
                    <button
                      key={style.key}
                      onClick={() => setSelectedStyle(style.key)}
                      className={`clean-button text-left p-3 transition-all ${
                        selectedStyle === style.key
                          ? 'bg-gradient-to-br from-purple-500/20 to-pink-500/20 border-purple-500/50'
                          : 'hover:bg-muted/50'
                      }`}
                    >
                      <div className="font-medium text-sm">{style.label}</div>
                      <div className="text-xs text-muted-foreground">{style.description}</div>
                    </button>
                  ))}
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-foreground mb-3">Setting</label>
                <div className="grid grid-cols-2 gap-2">
                  {settings.map((setting) => (
                    <button
                      key={setting.key}
                      onClick={() => setSelectedSetting(setting.key)}
                      className={`clean-button text-left p-3 transition-all ${
                        selectedSetting === setting.key
                          ? 'bg-gradient-to-br from-blue-500/20 to-cyan-500/20 border-blue-500/50'
                          : 'hover:bg-muted/50'
                      }`}
                    >
                      <div className="font-medium text-sm">{setting.label}</div>
                      <div className="text-xs text-muted-foreground">{setting.description}</div>
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>

          {/* Tab Navigation */}
          <div className="flex gap-2">
            {['common', 'custom', 'generated', 'gallery'].map((tab) => (
              <button
                key={tab}
                onClick={() => setActiveTab(tab)}
                className={`clean-button px-4 py-2 text-sm capitalize transition-all ${
                  activeTab === tab
                    ? 'bg-gradient-to-r from-emerald-500/20 to-cyan-500/20 border-emerald-500/50'
                    : 'hover:bg-muted/50'
                }`}
              >
                {tab === 'common' && '🎯 Common Scenarios'}
                {tab === 'custom' && '✏️ Custom Prompt'}
                {tab === 'generated' && '🖼️ Generated'}
                {tab === 'gallery' && '📁 My Gallery'}
              </button>
            ))}
          </div>

          {/* Tab Content */}
          <div className="min-h-[400px]">
            {activeTab === 'common' && (
              <div className="space-y-6">
                <h2 className="text-lg font-medium text-foreground">Common Medical Scenarios</h2>
                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {Object.entries(commonPrompts).map(([key, prompt]) => (
                    <div key={key} className="clean-card p-4 group hover:shadow-sm transition-all duration-200">
                      <div className="flex items-start justify-between mb-3">
                        <div>
                          <h3 className="font-medium text-foreground text-sm">{prompt.description}</h3>
                          <div className="text-xs text-muted-foreground capitalize">{prompt.category}</div>
                        </div>
                        <span className="text-lg">🩺</span>
                      </div>
                      <p className="text-xs text-muted-foreground mb-4 line-clamp-3">{prompt.prompt}</p>
                      <Button
                        onClick={() => generateFromCommon(key)}
                        disabled={generating}
                        size="sm"
                        className="w-full"
                      >
                        {generating ? '⚡ Generating...' : '🎨 Generate'}
                      </Button>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {activeTab === 'custom' && (
              <div className="space-y-6">
                <h2 className="text-lg font-medium text-foreground">Custom Patient Visualization</h2>
                <div className="clean-card p-6">
                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm font-medium text-foreground mb-2">
                        Describe the patient scenario
                      </label>
                      <textarea
                        value={customPrompt}
                        onChange={(e) => setCustomPrompt(e.target.value)}
                        placeholder="e.g. elderly patient with chronic cough, using oxygen mask, seated upright in bed"
                        rows={4}
                        className="w-full border rounded-lg p-3 bg-background text-foreground resize-none"
                        maxLength={500}
                      />
                      <div className="text-xs text-muted-foreground mt-1">
                        {customPrompt.length}/500 characters
                      </div>
                    </div>

                    <div className="clean-card p-4 bg-gradient-to-r from-amber-500/10 to-orange-500/10">
                      <h4 className="font-medium text-foreground mb-2">💡 Tips for Better Results</h4>
                      <ul className="text-sm text-muted-foreground space-y-1">
                        <li>• Include specific symptoms or clinical presentations</li>
                        <li>• Mention patient age, posture, or emotional state</li>
                        <li>• Add relevant medical equipment or setting details</li>
                        <li>• Avoid requesting graphic or inappropriate content</li>
                      </ul>
                    </div>

                    <Button
                      onClick={generateCustom}
                      disabled={generating || !customPrompt.trim()}
                      className="w-full"
                    >
                      {generating ? '⚡ Generating Visualization...' : '🎨 Generate Custom Visualization'}
                    </Button>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'generated' && (
              <div className="space-y-6">
                <h2 className="text-lg font-medium text-foreground">Generated This Session</h2>
                {generatedImages.length === 0 ? (
                  <div className="clean-card p-8 text-center">
                    <div className="text-4xl mb-4">🖼️</div>
                    <div className="text-muted-foreground">No images generated yet</div>
                    <div className="text-sm text-muted-foreground mt-2">
                      Use the Common Scenarios or Custom Prompt tabs to create visualizations
                    </div>
                  </div>
                ) : (
                  <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {generatedImages.map((image, index) => renderImageCard(image, index))}
                  </div>
                )}
              </div>
            )}

            {activeTab === 'gallery' && (
              <div className="space-y-6">
                <div className="flex items-center justify-between">
                  <h2 className="text-lg font-medium text-foreground">My Gallery</h2>
                  <Button
                    onClick={loadGallery}
                    disabled={loadingGallery}
                    variant="outline"
                    size="sm"
                  >
                    {loadingGallery ? '🔄 Loading...' : '🔄 Refresh'}
                  </Button>
                </div>

                {loadingGallery ? (
                  <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {Array.from({ length: 6 }).map((_, i) => (
                      <div key={i}>{renderLoadingSkeleton()}</div>
                    ))}
                  </div>
                ) : galleryImages.length === 0 ? (
                  <div className="clean-card p-8 text-center">
                    <div className="text-4xl mb-4">📁</div>
                    <div className="text-muted-foreground">No saved visualizations</div>
                    <div className="text-sm text-muted-foreground mt-2">
                      Generated images will be saved here for future reference
                    </div>
                  </div>
                ) : (
                  <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {galleryImages.map((image, index) => renderImageCard(image, index))}
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Watermark Notice */}
          <div className="clean-card p-4 bg-gradient-to-r from-gray-500/10 to-gray-600/5 border-gray-500/20">
            <div className="text-xs text-muted-foreground text-center">
              ⚠️ All generated images include invisible SynthID watermarks and are clearly marked as AI-generated training material.
              These visualizations are for educational purposes only and should not be used for actual medical diagnosis.
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}