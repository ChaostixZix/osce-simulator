import React, { createContext, useContext, useState, useCallback } from 'react';
import Toast from './Toast';

const ToastContext = createContext();

export function useToast() {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within a ToastProvider');
  }
  return context;
}

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([]);

  const addToast = useCallback((message, type = 'success', duration = 5000) => {
    const id = Date.now() + Math.random();
    const newToast = { id, message, type, duration };
    
    setToasts(prev => [...prev, newToast]);
    
    // Auto remove after duration
    setTimeout(() => {
      setToasts(prev => prev.filter(toast => toast.id !== id));
    }, duration + 500); // Extra time for exit animation
    
    return id;
  }, []);

  const removeToast = useCallback((id) => {
    setToasts(prev => prev.filter(toast => toast.id !== id));
  }, []);

  const toast = useCallback(({ title, type = 'success', duration = 5000 }) => {
    return addToast(title, type, duration);
  }, [addToast]);

  const contextValue = {
    toast,
    addToast,
    removeToast
  };

  return (
    <ToastContext.Provider value={contextValue}>
      {children}
      
      {/* Toast Container - Fixed position at top right */}
      <div className="fixed top-4 right-4 z-50 space-y-2 pointer-events-none">
        {toasts.map((toastItem) => (
          <div key={toastItem.id} className="pointer-events-auto">
            <Toast
              message={toastItem.message}
              type={toastItem.type}
              duration={toastItem.duration}
              onClose={() => removeToast(toastItem.id)}
            />
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}