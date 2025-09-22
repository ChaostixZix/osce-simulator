import React, { useEffect } from 'react';

// Shared Modal component (React) — consistent with app theme
// Props:
// - open: boolean
// - onClose: () => void
// - title?: string | ReactNode
// - size?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl'
// - children: ReactNode
// - footer?: ReactNode

const sizeClassMap = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-3xl',
  '2xl': 'max-w-4xl',
  '3xl': 'max-w-5xl',
};

export default function Modal({ open, onClose, title, size = 'xl', children, footer }) {
  useEffect(() => {
    if (!open) return;
    const onKey = (e) => {
      if (e.key === 'Escape') onClose?.();
    };
    document.addEventListener('keydown', onKey);
    const prev = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.removeEventListener('keydown', onKey);
      document.body.style.overflow = prev;
    };
  }, [open, onClose]);

  if (!open) return null;

  const sizeClass = sizeClassMap[size] || sizeClassMap.xl;

  const stop = (e) => e.stopPropagation();

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm"
      onClick={onClose}
      role="dialog"
      aria-modal="true"
    >
      <div className="absolute inset-0 bg-slate-950/80 dark:bg-black/90" />
      <div
        className={`relative w-full ${sizeClass} max-h-[85vh] overflow-hidden bg-white dark:bg-slate-950 border border-slate-200/20 dark:border-slate-800/50 shadow-2xl`}
        onClick={stop}
        style={{
          clipPath: 'polygon(0 0, calc(100% - 20px) 0, 100% 20px, 100% 100%, 20px 100%, 0 calc(100% - 20px))'
        }}
      >
        {/* Subtle tech grid pattern overlay */}
        <div className="absolute inset-0 opacity-5 dark:opacity-10">
          <div 
            className="w-full h-full"
            style={{
              backgroundImage: 'linear-gradient(rgba(148, 163, 184, 0.3) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, 0.3) 1px, transparent 1px)',
              backgroundSize: '20px 20px'
            }}
          />
        </div>

        {/* Header with tech styling */}
        {title ? (
          <div className="relative px-6 py-4 border-b border-slate-200/30 dark:border-slate-700/50 bg-gradient-to-r from-slate-50/50 to-transparent dark:from-slate-900/50">
            <div className="flex items-center gap-3">
              <div className="w-2 h-2 bg-emerald-400 rounded-full shadow-lg shadow-emerald-400/50 animate-pulse" />
              <h2 className="text-lg font-mono font-semibold text-slate-900 dark:text-slate-100 tracking-wide uppercase text-sm">{title}</h2>
            </div>
            <div className="absolute top-0 right-0 w-20 h-full bg-gradient-to-l from-cyan-500/10 to-transparent dark:from-cyan-400/10" />
          </div>
        ) : null}

        <div className="relative overflow-y-auto max-h-[calc(85vh-140px)] p-6">
          {children}
        </div>

        {footer ? (
          <div className="relative px-6 py-4 border-t border-slate-200/30 dark:border-slate-700/50 bg-gradient-to-r from-transparent to-slate-50/50 dark:to-slate-900/50 flex items-center justify-between gap-3">
            {footer}
          </div>
        ) : null}
      </div>
    </div>
  );
}

