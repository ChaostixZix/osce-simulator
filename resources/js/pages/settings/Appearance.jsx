import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { useTheme } from '@/contexts/ThemeContext';

export default function Appearance() {
  const { theme, setTheme, isDark } = useTheme();
  
  const breadcrumbs = [
    { title: 'Settings', href: route('profile.edit') },
    { title: 'Appearance', href: route('appearance') },
  ];

  const themeOptions = [
    { value: 'light', label: 'Light Mode', description: 'Clean and bright interface with high readability' },
    { value: 'dark', label: 'Dark Mode', description: 'Gaming-inspired dark theme with neon accents' },
    { value: 'system', label: 'System', description: 'Follows your system preference automatically' }
  ];

  return (
    <>
      <Head title="Appearance Settings" />
      <AppLayout breadcrumbs={breadcrumbs}>
        <div className="space-y-6">
          {/* Header */}
          <div>
            <h1 className="text-2xl font-bold glow-text mb-2">Interface Configuration</h1>
            <p className="text-sm text-muted-foreground font-mono">
              Customize your terminal experience • Active: {theme.toUpperCase()}
            </p>
          </div>

          {/* Theme Selection */}
          <div className="space-y-4">
            <div className="flex items-center gap-3">
              <div className="w-1 h-6 bg-primary"></div>
              <h2 className="text-lg font-semibold font-mono uppercase tracking-wider">Display Mode</h2>
            </div>
            
            <div className="grid gap-3">
              {themeOptions.map((option) => (
                <button
                  key={option.value}
                  onClick={() => setTheme(option.value)}
                  className={`cyber-button p-4 text-left transition-all duration-200 group ${
                    theme === option.value 
                      ? 'bg-primary/10 border-primary text-primary' 
                      : 'hover:bg-secondary/50'
                  }`}
                >
                  <div className="flex items-start justify-between">
                    <div className="space-y-1">
                      <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2">
                          {/* Theme Icons */}
                          {option.value === 'light' && (
                            <svg className="w-5 h-5" viewBox="0 0 16 16" fill="currentColor">
                              <circle cx="8" cy="8" r="2.5" />
                              <path d="M8 0v2M8 14v2M0 8h2M14 8h2M2.343 2.343l1.414 1.414M12.243 12.243l1.414 1.414M2.343 13.657l1.414-1.414M12.243 3.757l1.414-1.414" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                            </svg>
                          )}
                          {option.value === 'dark' && (
                            <svg className="w-5 h-5" viewBox="0 0 16 16" fill="currentColor">
                              <path d="M6 0.5a6.5 6.5 0 0 0 0 13 7 7 0 0 1 0-13z"/>
                            </svg>
                          )}
                          {option.value === 'system' && (
                            <svg className="w-5 h-5" viewBox="0 0 16 16" fill="currentColor">
                              <path d="M1 2.5A2.5 2.5 0 0 1 3.5 0h9A2.5 2.5 0 0 1 15 2.5v10a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 1 12.5v-10zm2.5-.5a.5.5 0 0 0-.5.5v10a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5v-10a.5.5 0 0 0-.5-.5h-9zM2 4h12v1H2V4z"/>
                            </svg>
                          )}
                          <span className="font-semibold font-mono">{option.label}</span>
                        </div>
                        
                        {/* Active Indicator */}
                        {theme === option.value && (
                          <div className="flex items-center gap-1">
                            <div className="w-2 h-2 bg-primary rounded-full animate-pulse" />
                            <span className="text-xs text-primary font-mono">ACTIVE</span>
                          </div>
                        )}
                      </div>
                      
                      <p className="text-sm text-muted-foreground font-mono">
                        {option.description}
                      </p>
                    </div>
                    
                    {/* Selection indicator */}
                    <div className={`w-4 h-4 border-2 transition-all duration-200 ${
                      theme === option.value
                        ? 'border-primary bg-primary' 
                        : 'border-muted-foreground group-hover:border-primary'
                    }`} style={{
                      clipPath: 'polygon(0 0, calc(100% - 4px) 0, 100% 4px, 100% 100%, 4px 100%, 0 calc(100% - 4px))'
                    }}>
                      {theme === option.value && (
                        <div className="w-full h-full flex items-center justify-center">
                          <div className="w-1 h-1 bg-primary-foreground"></div>
                        </div>
                      )}
                    </div>
                  </div>
                </button>
              ))}
            </div>
          </div>

          {/* Current Theme Preview */}
          <div className="space-y-4">
            <div className="flex items-center gap-3">
              <div className="w-1 h-6 bg-primary"></div>
              <h2 className="text-lg font-semibold font-mono uppercase tracking-wider">Live Preview</h2>
            </div>
            
            <div className="cyber-border p-4 bg-card">
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <span className="font-mono text-sm">Current Configuration:</span>
                  <span className="text-primary font-mono font-bold">{isDark ? 'DARK_MODE' : 'LIGHT_MODE'}</span>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs font-mono">
                  <div>
                    <div className="text-muted-foreground">Background:</div>
                    <div className="w-8 h-4 bg-background border border-border"></div>
                  </div>
                  <div>
                    <div className="text-muted-foreground">Primary:</div>
                    <div className="w-8 h-4 bg-primary border border-border"></div>
                  </div>
                  <div>
                    <div className="text-muted-foreground">Accent:</div>
                    <div className="w-8 h-4 bg-accent border border-border"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Gaming Features Info */}
          <div className="space-y-4">
            <div className="flex items-center gap-3">
              <div className="w-1 h-6 bg-primary"></div>
              <h2 className="text-lg font-semibold font-mono uppercase tracking-wider">Gaming Features</h2>
            </div>
            
            <div className="grid gap-3">
              <div className="cyber-border p-3 bg-card/50">
                <div className="flex items-center gap-2 mb-2">
                  <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" />
                  <span className="font-mono text-sm font-semibold">Cyber Aesthetics</span>
                </div>
                <p className="text-xs text-muted-foreground font-mono">
                  Steep angle cuts, glow effects, and animated elements for an immersive experience
                </p>
              </div>
              
              <div className="cyber-border p-3 bg-card/50">
                <div className="flex items-center gap-2 mb-2">
                  <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" />
                  <span className="font-mono text-sm font-semibold">Smooth Transitions</span>
                </div>
                <p className="text-xs text-muted-foreground font-mono">
                  All theme changes are animated with 300ms smooth transitions
                </p>
              </div>
              
              <div className="cyber-border p-3 bg-card/50">
                <div className="flex items-center gap-2 mb-2">
                  <div className="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" />
                  <span className="font-mono text-sm font-semibold">Local Storage</span>
                </div>
                <p className="text-xs text-muted-foreground font-mono">
                  Your theme preference is automatically saved and persists between sessions
                </p>
              </div>
            </div>
          </div>
        </div>
      </AppLayout>
    </>
  );
}
