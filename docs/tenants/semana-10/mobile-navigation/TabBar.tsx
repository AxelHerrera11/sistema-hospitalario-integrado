import React from 'react';
import type { TabBarProps } from './types';

export const TabBar: React.FC<TabBarProps> = ({
  tabs,
  activeTab,
  onChange,
  scrollable = true,
  className = '',
}) => (
  <nav
    className={`tab-bar ${className}`.trim()}
    role="tablist"
    aria-label="Navegación por pestañas"
    style={{
      display: 'flex',
      borderBottom: '2px solid var(--color-border-default)',
      overflowX: scrollable ? 'auto' : 'hidden',
      scrollbarWidth: 'none',
      WebkitOverflowScrolling: 'touch',
    }}
  >
    {tabs.map((tab) => {
      const active = tab.id === activeTab;
      return (
        <button
          key={tab.id}
          type="button"
          role="tab"
          aria-selected={active}
          aria-disabled={tab.disabled || undefined}
          disabled={tab.disabled}
          onClick={() => onChange(tab.id)}
          className={`tab-item ${active ? 'active' : ''} ${
            tab.disabled ? 'disabled' : ''
          }`.trim()}
          style={{
            position: 'relative',
            flex: scrollable ? '0 0 auto' : '1',
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            padding: '12px 20px',
            marginBottom: '-2px',
            fontSize: '0.875rem',
            fontWeight: 500,
            color: active
              ? 'var(--color-primary-600)'
              : 'var(--color-text-secondary)',
            whiteSpace: 'nowrap',
            border: 0,
            borderBottom: active
              ? '2px solid var(--color-primary-600)'
              : '2px solid transparent',
            background: 'none',
            cursor: tab.disabled ? 'not-allowed' : 'pointer',
            transition: 'all var(--transition-base)',
          }}
        >
          {tab.icon && <span aria-hidden="true">{tab.icon}</span>}
          <span>{tab.label}</span>
          {tab.badge !== undefined && tab.badge !== null && tab.badge !== '' && (
            <span
              className="tab-badge"
              style={{
                minWidth: '16px',
                height: '16px',
                display: 'inline-flex',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '0 6px',
                borderRadius: '9999px',
                background: 'var(--color-primary-50)',
                color: 'var(--color-primary-700)',
                fontSize: '0.625rem',
                fontWeight: 600,
              }}
            >
              {tab.badge}
            </span>
          )}
        </button>
      );
    })}
  </nav>
);
