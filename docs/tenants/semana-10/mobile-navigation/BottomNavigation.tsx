import React from 'react';
import { useIsMobile, useSafeArea } from './responsiveHooks';
import type { BottomNavItem } from './types';

export interface BottomNavigationProps {
  items: BottomNavItem[];
  activeItem: string;
  onChange: (itemId: string) => void;
  className?: string;
}

export const BottomNavigation: React.FC<BottomNavigationProps> = ({
  items,
  activeItem,
  onChange,
  className = '',
}) => {
  const isMobile = useIsMobile();
  const insets = useSafeArea();

  if (!isMobile) return null;

  return (
    <nav
      className={`bottom-nav ${className}`.trim()}
      aria-label="Navegación principal"
      style={{ paddingBottom: `calc(8px + ${insets.bottom}px)` }}
    >
      <div
        className="bottom-nav-inner"
        style={{
          display: 'flex',
          justifyContent: 'space-around',
          alignItems: 'center',
          width: '100%',
        }}
      >
        {items.map((item) => {
          const isActive = item.id === activeItem;
          return (
            <button
              key={item.id}
              type="button"
              aria-current={isActive ? 'page' : undefined}
              aria-label={item.label}
              aria-disabled={item.disabled || undefined}
              disabled={item.disabled}
              onClick={() => onChange(item.id)}
              className={`bottom-nav-item ${isActive ? 'active' : ''} ${
                item.disabled ? 'disabled' : ''
              }`.trim()}
              style={{
                position: 'relative',
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                gap: '4px',
                padding: '8px 12px',
                minWidth: '60px',
                minHeight: '44px',
                color: isActive
                  ? 'var(--color-primary-600)'
                  : 'var(--color-text-secondary)',
                fontSize: '0.75rem',
                fontWeight: 500,
                background: 'none',
                border: 'none',
                cursor: item.disabled ? 'not-allowed' : 'pointer',
                transition: 'color var(--transition-fast)',
              }}
            >
              <span
                className="bottom-nav-icon"
                style={{ fontSize: '1.25rem', lineHeight: 1 }}
                aria-hidden="true"
              >
                {item.icon}
              </span>
              <span className="bottom-nav-label">{item.label}</span>
              {item.badge !== undefined && item.badge !== null && item.badge !== '' && (
                <span
                  className="bottom-nav-badge"
                  aria-label={`Avisos: ${item.badge}`}
                  style={{
                    position: 'absolute',
                    top: '2px',
                    right: '2px',
                    minWidth: '16px',
                    height: '16px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: '0 4px',
                    borderRadius: '9999px',
                    background: 'var(--color-danger-600)',
                    color: 'white',
                    fontSize: '0.625rem',
                    fontWeight: 600,
                  }}
                >
                  {item.badge}
                </span>
              )}
            </button>
          );
        })}
      </div>
    </nav>
  );
};
