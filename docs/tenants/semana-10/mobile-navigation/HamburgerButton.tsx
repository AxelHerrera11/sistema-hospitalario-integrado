import React from 'react';

export interface HamburgerButtonProps {
  isOpen: boolean;
  onClick: () => void;
  ariaLabel?: string;
  className?: string;
}

export const HamburgerButton: React.FC<HamburgerButtonProps> = ({
  isOpen,
  onClick,
  ariaLabel,
  className = '',
}) => (
  <button
    type="button"
    onClick={onClick}
    aria-expanded={isOpen}
    aria-label={ariaLabel ?? (isOpen ? 'Cerrar menú' : 'Abrir menú')}
    aria-controls="mobile-navigation"
    className={`hamburger-btn ${className}`.trim()}
    style={{
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      width: '44px',
      height: '44px',
      background: 'none',
      border: 'none',
      cursor: 'pointer',
      padding: 0,
      color: 'var(--color-text-primary)',
      borderRadius: '8px',
    }}
  >
    <span
      className="hamburger-icon"
      style={{
        width: '24px',
        height: '2px',
        background: 'currentColor',
        position: 'relative',
        transition: 'background 0.2s ease',
      }}
      aria-hidden="true"
    />
  </button>
);
