import React from 'react';
import { useIsMobile } from './responsiveHooks';
import type { BreadcrumbProps } from './types';

export const Breadcrumb: React.FC<BreadcrumbProps> = ({
  items,
  maxItems = 4,
  separator = '/',
  className = '',
}) => {
  const isMobile = useIsMobile();
  const safeMaxItems = Math.max(3, maxItems);
  const visibleItems =
    items.length > safeMaxItems
      ? [
          items[0],
          { label: '…', current: false },
          ...items.slice(-(safeMaxItems - 2)),
        ]
      : items;

  return (
    <nav
      className={`breadcrumb ${className}`.trim()}
      aria-label="Ruta de navegación"
      style={{
        display: 'flex',
        alignItems: 'center',
        flexWrap: isMobile ? 'wrap' : 'nowrap',
        gap: '8px',
        fontSize: '0.875rem',
        color: 'var(--color-text-secondary)',
        overflowX: 'auto',
        paddingBottom: '4px',
      }}
    >
      <ol
        style={{
          display: 'flex',
          flexWrap: isMobile ? 'wrap' : 'nowrap',
          alignItems: 'center',
          gap: '8px',
          listStyle: 'none',
          margin: 0,
          padding: 0,
        }}
      >
        {visibleItems.map((item, index) => (
          <li
            key={`${item.label}-${index}`}
            style={{ display: 'flex', alignItems: 'center', gap: '8px' }}
          >
            {index > 0 && (
              <span
                aria-hidden="true"
                style={{ color: 'var(--color-text-tertiary)' }}
              >
                {separator}
              </span>
            )}
            {item.current ? (
              <span
                aria-current="page"
                style={{
                  fontWeight: 600,
                  color: 'var(--color-text-primary)',
                }}
              >
                {item.label}
              </span>
            ) : item.href ? (
              <a
                href={item.href}
                onClick={(event) => {
                  if (item.onClick) {
                    event.preventDefault();
                    item.onClick();
                  }
                }}
                style={{
                  color: 'var(--color-primary-600)',
                  textDecoration: 'none',
                  fontWeight: 500,
                }}
              >
                {item.label}
              </a>
            ) : item.onClick ? (
              <button
                type="button"
                onClick={item.onClick}
                style={{
                  background: 'none',
                  border: 'none',
                  color: 'var(--color-primary-600)',
                  font: 'inherit',
                  cursor: 'pointer',
                  padding: 0,
                  fontWeight: 500,
                }}
              >
                {item.label}
              </button>
            ) : (
              <span style={{ color: 'var(--color-text-tertiary)' }}>
                {item.label}
              </span>
            )}
          </li>
        ))}
      </ol>
    </nav>
  );
};
