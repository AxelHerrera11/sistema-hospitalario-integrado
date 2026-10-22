import React, { useEffect, useRef } from 'react';
import { useFocusTrap, useIsMobile, useIsTablet } from './responsiveHooks';
import type { NavItem, SidebarProps } from './types';

export const Sidebar: React.FC<SidebarProps> = ({
  isOpen,
  onClose,
  items,
  user,
  onLogout,
  className = '',
}) => {
  const containerRef = useRef<HTMLElement>(null);
  const previousActiveElement = useRef<HTMLElement | null>(null);
  const isMobile = useIsMobile();
  const isTablet = useIsTablet();
  const isSmallScreen = isMobile || isTablet;

  useFocusTrap(isOpen && isSmallScreen, containerRef);

  useEffect(() => {
    if (isOpen && isSmallScreen) {
      previousActiveElement.current =
        document.activeElement instanceof HTMLElement
          ? document.activeElement
          : null;
      const previousOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';

      return () => {
        document.body.style.overflow = previousOverflow;
      };
    }

    if (!isOpen) previousActiveElement.current?.focus();
    return undefined;
  }, [isOpen, isSmallScreen]);

  if (!isOpen && !isSmallScreen) return null;

  const handleItemClick = (item: NavItem, event?: React.MouseEvent) => {
    if (item.onClick) {
      event?.preventDefault();
      item.onClick();
    }
    if (isSmallScreen) onClose();
  };

  const renderItems = (navItems: NavItem[], level = 0): React.ReactNode => (
    <ul
      role="list"
      style={{
        paddingLeft: level > 0 ? '16px' : 0,
        margin: 0,
        listStyle: 'none',
      }}
    >
      {navItems.map((item) => (
        <li key={item.id} style={{ marginBottom: '4px' }}>
          {item.children?.length ? (
            <details>
              <summary className="nav-item">
                <span className="nav-icon" aria-hidden="true">
                  {item.icon}
                </span>
                <span style={{ flex: 1 }}>{item.label}</span>
                <span aria-hidden="true">▾</span>
              </summary>
              {renderItems(item.children, level + 1)}
            </details>
          ) : item.href ? (
            <a
              href={item.href}
              className="nav-item"
              aria-label={item.label}
              onClick={(event) => handleItemClick(item, event)}
              style={{
                color: item.danger
                  ? 'var(--color-danger-600)'
                  : 'var(--color-text-secondary)',
                textDecoration: 'none',
              }}
            >
              <span className="nav-icon" aria-hidden="true">
                {item.icon}
              </span>
              <span style={{ flex: 1 }}>{item.label}</span>
              {item.badge !== undefined && item.badge !== null && (
                <span
                  className="sidebar-badge"
                  style={{
                    marginLeft: 'auto',
                    padding: '2px 8px',
                    borderRadius: '9999px',
                    background: 'var(--color-primary-50)',
                    color: 'var(--color-primary-700)',
                    fontSize: '0.75rem',
                    fontWeight: 600,
                  }}
                >
                  {item.badge}
                </span>
              )}
            </a>
          ) : (
            <button
              type="button"
              className="nav-item"
              disabled={!item.onClick}
              onClick={(event) => handleItemClick(item, event)}
              style={{
                color: item.danger
                  ? 'var(--color-danger-600)'
                  : 'var(--color-text-secondary)',
                width: '100%',
                textAlign: 'left',
                background: 'none',
                border: 0,
                font: 'inherit',
                cursor: item.onClick ? 'pointer' : 'default',
              }}
            >
              <span className="nav-icon" aria-hidden="true">
                {item.icon}
              </span>
              <span style={{ flex: 1 }}>{item.label}</span>
              {item.badge !== undefined && item.badge !== null && (
                <span
                  className="sidebar-badge"
                  style={{
                    marginLeft: 'auto',
                    padding: '2px 8px',
                    borderRadius: '9999px',
                    background: 'var(--color-primary-50)',
                    color: 'var(--color-primary-700)',
                    fontSize: '0.75rem',
                    fontWeight: 600,
                  }}
                >
                  {item.badge}
                </span>
              )}
            </button>
          )}
        </li>
      ))}
    </ul>
  );

  return (
    <>
      {isSmallScreen && isOpen && (
        <div
          className="sidebar-overlay visible"
          onClick={onClose}
          aria-hidden="true"
          style={{
            position: 'fixed',
            inset: 0,
            background: 'rgba(0, 0, 0, 0.5)',
            zIndex: 'calc(var(--z-modal) - 1)',
          }}
        />
      )}
      <aside
        id="mobile-navigation"
        ref={containerRef}
        tabIndex={-1}
        className={`sidebar ${isOpen ? 'open' : ''} ${className}`.trim()}
        role="navigation"
        aria-label="Navegación principal"
        aria-hidden={isSmallScreen && !isOpen}
        style={{
          position: isSmallScreen ? 'fixed' : 'relative',
          left: 0,
          top: 0,
          bottom: 0,
          width: '280px',
          zIndex: isSmallScreen ? 'var(--z-modal)' : 'var(--z-sticky)',
          transform: isSmallScreen
            ? isOpen
              ? 'translateX(0)'
              : 'translateX(-100%)'
            : 'none',
          transition: isSmallScreen ? 'transform 0.3s ease' : 'none',
          visibility: isSmallScreen && !isOpen ? 'hidden' : 'visible',
          display: 'flex',
          flexDirection: 'column',
          height: isSmallScreen ? '100dvh' : '100%',
          background: 'var(--color-bg-primary)',
          borderRight: '1px solid var(--color-border-default)',
          boxShadow: isSmallScreen ? 'var(--shadow-xl)' : 'none',
          overflowY: 'auto',
        }}
      >
        <div
          className="sidebar-header"
          style={{
            position: 'relative',
            padding: '20px 16px',
            borderBottom: '1px solid var(--color-border-default)',
          }}
        >
          {user && (
            <div style={{ paddingRight: '44px' }}>
              {user.avatar && (
                <img
                  src={user.avatar}
                  alt=""
                  width={40}
                  height={40}
                  style={{ borderRadius: '50%', objectFit: 'cover' }}
                />
              )}
              <div
                style={{
                  fontWeight: 600,
                  fontSize: '0.875rem',
                  color: 'var(--color-text-primary)',
                }}
              >
                {user.name}
              </div>
              <div
                style={{
                  fontSize: '0.75rem',
                  color: 'var(--color-text-secondary)',
                  marginTop: '4px',
                }}
              >
                {user.role} · {user.tenant}
              </div>
            </div>
          )}
          <button
            type="button"
            onClick={onClose}
            className="hamburger-close"
            aria-label="Cerrar menú"
            style={{
              position: 'absolute',
              top: '12px',
              right: '12px',
              width: '44px',
              height: '44px',
              background: 'none',
              border: 'none',
              borderRadius: '8px',
              cursor: 'pointer',
              color: 'var(--color-text-secondary)',
            }}
          >
            ✕
          </button>
        </div>

        <nav
          className="sidebar-nav"
          style={{ flex: 1, padding: '16px', overflowY: 'auto' }}
        >
          {renderItems(items)}
        </nav>

        {onLogout && (
          <div
            className="sidebar-footer"
            style={{
              padding: '16px',
              borderTop: '1px solid var(--color-border-default)',
            }}
          >
            <button
              type="button"
              onClick={() => {
                onLogout();
                if (isSmallScreen) onClose();
              }}
              style={{
                width: '100%',
                display: 'flex',
                alignItems: 'center',
                gap: '12px',
                padding: '12px',
                borderRadius: '8px',
                background: 'var(--color-danger-50)',
                color: 'var(--color-danger-700)',
                fontWeight: 500,
                border: '1px solid var(--color-danger-200)',
                cursor: 'pointer',
                fontSize: '0.875rem',
              }}
            >
              🚪 Cerrar sesión
            </button>
          </div>
        )}
      </aside>
    </>
  );
};
