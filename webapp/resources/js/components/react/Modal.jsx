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
      className="fixed inset-0 z-50 flex items-center justify-center"
      onClick={onClose}
      role="dialog"
      aria-modal="true"
    >
      <div className="absolute inset-0 bg-black/50" />
      <div
        className={`relative w-full ${sizeClass} max-h-[80vh] overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-xl`}
        onClick={stop}
      >
        {title ? (
          <div className="mb-4">
            <h2 className="text-xl font-semibold leading-tight text-gray-900 dark:text-gray-100">{title}</h2>
          </div>
        ) : null}

        <div className="modal-body">{children}</div>

        {footer ? (
          <div className="mt-6 pt-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between gap-2">
            {footer}
          </div>
        ) : null}
      </div>
    </div>
  );
}

