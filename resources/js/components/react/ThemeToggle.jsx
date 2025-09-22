import React from 'react';
import { useTheme } from '@/contexts/ThemeContext';

export default function ThemeToggle() {
    const { theme, resolvedTheme, toggleTheme } = useTheme();

    return (
        <button
            onClick={toggleTheme}
            className="group relative bg-gradient-to-r from-neutral-800 to-neutral-700 dark:from-neutral-700 dark:to-neutral-600 border border-neutral-600 dark:border-neutral-500 text-neutral-200 dark:text-neutral-100 px-3 py-2 text-xs font-mono tracking-wide uppercase transition-all duration-200 hover:from-emerald-900 hover:to-emerald-800 hover:border-emerald-500 hover:shadow-lg hover:shadow-emerald-500/20 transform hover:scale-[1.02] active:scale-[0.98]"
            style={{
                clipPath: 'polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px))'
            }}
            title={`Switch to ${resolvedTheme === 'dark' ? 'light' : 'dark'} mode`}
        >
            <div className="flex items-center gap-2">
                <div className="relative w-4 h-4">
                    {/* Sun icon for light mode */}
                    <div className={`absolute inset-0 transition-all duration-300 ${resolvedTheme === 'light' ? 'opacity-100 rotate-0 scale-100' : 'opacity-0 rotate-180 scale-50'}`}>
                        <svg viewBox="0 0 16 16" fill="currentColor" className="w-full h-full">
                            <circle cx="8" cy="8" r="2.5" />
                            <path d="M8 0v2M8 14v2M0 8h2M14 8h2M2.343 2.343l1.414 1.414M12.243 12.243l1.414 1.414M2.343 13.657l1.414-1.414M12.243 3.757l1.414-1.414" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                        </svg>
                    </div>
                    
                    {/* Moon icon for dark mode */}
                    <div className={`absolute inset-0 transition-all duration-300 ${resolvedTheme === 'dark' ? 'opacity-100 rotate-0 scale-100' : 'opacity-0 -rotate-180 scale-50'}`}>
                        <svg viewBox="0 0 16 16" fill="currentColor" className="w-full h-full">
                            <path d="M6 0.5a6.5 6.5 0 0 0 0 13 7 7 0 0 1 0-13z"/>
                        </svg>
                    </div>
                </div>
                
                <span className="text-[10px] leading-none">
                    {theme === 'system' ? 'AUTO' : (resolvedTheme === 'dark' ? 'DARK' : 'LIGHT')}
                </span>
                
                {/* Hover indicator */}
                <div className="w-1 h-1 bg-emerald-400 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200" />
            </div>
            
            {/* Glitch effect overlay */}
            <div className="absolute inset-0 bg-gradient-to-r from-emerald-500/10 to-cyan-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-200" 
                 style={{
                     clipPath: 'polygon(0 0, calc(100% - 8px) 0, 100% 8px, 100% 100%, 8px 100%, 0 calc(100% - 8px))'
                 }} 
            />
        </button>
    );
}