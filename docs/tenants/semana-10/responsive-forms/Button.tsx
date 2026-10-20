import React from 'react';
import type { ButtonProps } from './formTypes';
import { useIsMobile } from './responsiveHooks';
export const Button: React.FC<ButtonProps> = ({
  children,
  variant = 'primary',
  size = 'md',
  fullWidth = false,
  disabled = false,
  loading = false,
  onClick,
  type = 'button',
  className = '',
  leftIcon,
  rightIcon,
  fullWidthMobile = true,
}) => {
  const isMobile = useIsMobile();

  const baseStyles: React.CSSProperties = {
    display: 'inline-flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: '8px',
    fontFamily: 'inherit',
    fontWeight: 600,
    border: 'none',
    borderRadius: '8px',
    cursor: disabled ? 'not-allowed' : 'pointer',
    textDecoration: 'none',
    whiteSpace: 'nowrap',
    transition: 'all 0.2s ease',
    opacity: disabled ? 0.6 : 1,
    pointerEvents: disabled ? 'none' : 'auto',
  };

  const sizeStyles: Record<string, React.CSSProperties> = {
    sm: { height: '40px', padding: '0 16px', fontSize: '0.875rem' },
    md: { height: '48px', padding: '0 24px', fontSize: '1rem' },
    lg: { height: '56px', padding: '0 32px', fontSize: '1.125rem' },
  };

  const variantStyles: Record<string, React.CSSProperties> = {
    primary: {
      backgroundColor: 'var(--color-primary-600)',
      color: 'white',
    },
    secondary: {
      backgroundColor: 'var(--color-gray-0)',
      color: 'var(--color-gray-700)',
      border: '1px solid var(--color-gray-400)',
    },
    danger: {
      backgroundColor: 'var(--color-danger-600)',
      color: 'white',
    },
    ghost: {
      backgroundColor: 'transparent',
      color: 'var(--color-primary-600)',
    },
    outline: {
      backgroundColor: 'transparent',
      color: 'var(--color-primary-600)',
      border: '2px solid var(--color-primary-600)',
    },
  };

  const isFullWidth = fullWidth || (fullWidthMobile && isMobile);

  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      className={`btn btn-${variant} btn-${size} ${isFullWidth ? 'btn-full-width' : ''}`}
      style={{
        ...baseStyles,
        ...sizeStyles[size],
        ...variantStyles[variant],
        width: isFullWidth ? '100%' : 'auto',
        minHeight: sizeStyles[size].height,
      }}
      aria-busy={loading}
      aria-disabled={disabled}
    >
      {loading && (
        <span className="spinner" style={{
          width: '16px',
          height: '16px',
          border: '2px solid currentColor',
          borderTopColor: 'transparent',
          borderRadius: '50%',
          animation: 'spin 0.8s linear infinite',
        }} aria-hidden="true" />
      )}
      {!loading && leftIcon && <span aria-hidden="true">{leftIcon}</span>}
      <span className="btn-text">{children}</span>
      {!loading && rightIcon && <span aria-hidden="true">{rightIcon}</span>}
      <style>{`
        @keyframes spin {
          to { transform: rotate(360deg); }
        }
      `}</style>
    </button>
  );
};
