import React, { useEffect, useState } from 'react';

export default function Toast({ message, type = 'success', duration = 5000, onClose }) {
  const [isVisible, setIsVisible] = useState(false);
  const [isExiting, setIsExiting] = useState(false);

  useEffect(() => {
    // Entry animation
    const timer = setTimeout(() => setIsVisible(true), 100);
    
    // Auto dismiss
    const dismissTimer = setTimeout(() => {
      handleClose();
    }, duration);

    return () => {
      clearTimeout(timer);
      clearTimeout(dismissTimer);
    };
  }, [duration]);

  const handleClose = () => {
    setIsExiting(true);
    setTimeout(() => {
      setIsVisible(false);
      onClose?.();
    }, 300);
  };

  const bgColor = {
    success: 'from-emerald-500/10 to-emerald-600/5 border-emerald-500/30',
    error: 'from-red-500/10 to-red-600/5 border-red-500/30',
    info: 'from-blue-500/10 to-blue-600/5 border-blue-500/30'
  }[type];

  const textColor = {
    success: 'text-emerald-400',
    error: 'text-red-400', 
    info: 'text-blue-400'
  }[type];

  return (
    <div
      className={`cyber-border bg-gradient-to-br ${bgColor} p-4 shadow-lg transition-all duration-300 transform max-w-md ${
        isVisible && !isExiting
          ? 'translate-x-0 opacity-100'
          : 'translate-x-full opacity-0'
      }`}
      style={{
        clipPath: 'polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px))'
      }}
    >
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className={`w-2 h-2 ${type === 'success' ? 'bg-emerald-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'} rounded-full animate-pulse`}></div>
          <span className={`${textColor} font-mono text-sm lowercase`}>
            {message}
          </span>
        </div>
        <button
          onClick={handleClose}
          className={`${textColor} hover:opacity-80 ml-4`}
          aria-label="Close notification"
        >
          <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
          </svg>
        </button>
      </div>
      
      {/* Decorative corner elements */}
      <div className="absolute top-2 right-2 w-2 h-2 bg-gradient-to-br from-emerald-400 to-cyan-400 opacity-60"></div>
    </div>
  );
}