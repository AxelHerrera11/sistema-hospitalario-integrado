import React from 'react';

export interface SkipLinkProps {
  targetId: string;
  label?: string;
  className?: string;
}

export const SkipLink: React.FC<SkipLinkProps> = ({
  targetId,
  label = 'Saltar al contenido principal',
  className = '',
}) => (
  <a
    href={`#${targetId}`}
    className={`skip-link ${className}`.trim()}
    style={{
      position: 'absolute',
      top: '-100%',
      left: '50%',
      transform: 'translateX(-50%)',
      background: 'var(--color-primary-600)',
      color: 'white',
      padding: '12px 24px',
      borderRadius: '8px',
      zIndex: 'var(--z-max)',
      textDecoration: 'none',
      fontWeight: 600,
      transition: 'top 0.2s ease',
    }}
    onFocus={(event) => {
      event.currentTarget.style.top = '12px';
    }}
    onBlur={(event) => {
      event.currentTarget.style.top = '-100%';
    }}
  >
    {label}
  </a>
);
