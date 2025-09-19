import React from 'react';

export default function SectionNav({ sections }) {
    if (!sections?.length) {
        return null;
    }

    return (
        <nav className="clean-card bg-card p-4 space-y-3">
            <div className="border-b border-border pb-2">
                <h2 className="text-sm font-medium text-foreground">Quick Sections</h2>
                <p className="text-xs text-muted-foreground">Jump to any area you want to edit.</p>
            </div>

            <div className="grid gap-2">
                {sections.map((section) => (
                    <a
                        key={section.id}
                        href={`#${section.id}`}
                        className="clean-button px-3 py-2 text-sm text-left"
                    >
                        <span className="block font-medium text-foreground">{section.label}</span>
                        {section.description && (
                            <span className="text-xs text-muted-foreground">{section.description}</span>
                        )}
                    </a>
                ))}
            </div>
        </nav>
    );
}
