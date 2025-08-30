import React from 'react';
import { Link } from '@inertiajs/react';

/**
 * Reusable Breadcrumbs component (React)
 * Props:
 * - items: Array<{ title: string, href?: string }>
 * Renders all but the last item as clickable links (if href provided),
 * and the last item as the current page label.
 */
export default function Breadcrumbs({ items = [] }) {
  if (!Array.isArray(items) || items.length === 0) return null;

  return (
    <nav aria-label="Breadcrumb" className="text-sm text-muted-foreground">
      <ol className="flex flex-wrap items-center justify-start gap-1">
        {items.map((item, index) => {
          const isLast = index === items.length - 1;
          return (
            <React.Fragment key={`${item.title}-${index}`}>
              {index > 0 && <li className="px-1 text-muted-foreground/70">/</li>}
              <li>
                {isLast || !item.href ? (
                  <span aria-current={isLast ? 'page' : undefined} className={isLast ? 'font-medium text-neutral-100' : ''}>
                    {item.title}
                  </span>
                ) : (
                  <Link href={item.href} className="hover:text-neutral-100 underline-offset-4 hover:underline">
                    {item.title}
                  </Link>
                )}
              </li>
            </React.Fragment>
          );
        })}
      </ol>
    </nav>
  );
}
