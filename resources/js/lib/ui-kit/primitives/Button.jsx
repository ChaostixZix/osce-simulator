import React from 'react';

export default function Button({ children, onClick, type = 'button', variant = 'default', className = '', disabled = false }) {
  const base = 'inline-flex items-center justify-center px-4 py-2 text-sm font-medium border focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors';
  const variants = {
    default: 'bg-primary text-primary-foreground border-transparent hover:opacity-90',
    outline: 'bg-transparent text-foreground border-border hover:bg-muted',
    subtle: 'bg-muted text-foreground border-transparent hover:bg-muted/80',
  };
  const cn = `${base} ${variants[variant] || variants.default} ${className}`;

  return (
    <button type={type} onClick={onClick} className={cn} disabled={disabled}>
      {children}
    </button>
  );
}

