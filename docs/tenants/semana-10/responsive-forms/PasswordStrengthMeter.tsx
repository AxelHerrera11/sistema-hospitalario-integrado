import React from 'react';
import type { PasswordStrengthProps } from './formTypes';
export const PasswordStrengthMeter: React.FC<PasswordStrengthProps> = ({
  value,
  className = '',
}) => {
  const checks = {
    length: value.length >= 12,
    uppercase: /[A-Z]/.test(value),
    lowercase: /[a-z]/.test(value),
    number: /\d/.test(value),
    symbol: /[^A-Za-z0-9]/.test(value),
  };
  const strength = Object.values(checks).filter(Boolean).length;
  const getStrengthLabel = () => {
    if (strength <= 1) return { label: 'MUY DÉBIL', color: 'var(--color-danger-600)', bg: 'var(--color-danger-50)' };
    if (strength <= 2) return { label: 'DÉBIL', color: 'var(--color-danger-600)', bg: 'var(--color-danger-50)' };
    if (strength === 3) return { label: 'MEDIA', color: 'var(--color-warning-600)', bg: 'var(--color-warning-50)' };
    if (strength === 4) return { label: 'BUENA', color: 'var(--color-success-600)', bg: 'var(--color-success-50)' };
    return { label: 'FUERTE', color: 'var(--color-success-600)', bg: 'var(--color-success-50)' };
  };
  const strengthInfo = getStrengthLabel();
  return (
    <div className={`password-strength-meter ${className}`} style={{ marginTop: '8px' }}>
      <div style={{
        height: '6px',
        background: 'var(--color-gray-200)',
        borderRadius: '3px',
        overflow: 'hidden',
        marginBottom: '8px',
      }}>
        <div style={{
          width: `${(Object.values({length: value.length >= 12, uppercase: /[A-Z]/.test(value), lowercase: /[a-z]/.test(value), number: /\d/.test(value), symbol: /[^A-Za-z0-9]/.test(value)}).filter(Boolean).length / 5) * 100}%`,
          height: '100%',
          background: strengthInfo.color,
          transition: 'width 0.3s ease, background 0.3s ease',
        }} />
      </div>
      <div style={{
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        fontSize: '0.75rem',
        fontWeight: 500,
        color: strengthInfo.color,
        marginBottom: '8px',
      }}>
        <span>{strengthInfo.label}</span>
        <span style={{ fontSize: '0.75rem' }}>{value.length}/12</span>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(5, 1fr)', gap: '8px', marginTop: '8px' }}>
        {[
          { key: 'length', label: '12+', check: value.length >= 12 },
          { key: 'uppercase', label: 'A-Z', check: /[A-Z]/.test(value) },
          { key: 'lowercase', label: 'a-z', check: /[a-z]/.test(value) },
          { key: 'number', label: '0-9', check: /\d/.test(value) },
          { key: 'symbol', label: '!@#', check: /[^A-Za-z0-9]/.test(value) },
        ].map(({ key, label, check }) => (
          <div
            key={key}
            style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: '4px',
              padding: '8px',
              background: check ? 'var(--color-success-50)' : 'var(--color-gray-100)',
              borderRadius: '8px',
              border: `1px solid ${check ? 'var(--color-success-300)' : 'var(--color-gray-300)'}`,
            }}
          >
            <span style={{
              fontSize: '0.625rem',
              fontWeight: 600,
              color: check ? 'var(--color-success-700)' : 'var(--color-text-tertiary)',
              textTransform: 'uppercase',
            }}>
              {label}
            </span>
            <span style={{
              fontSize: '1rem',
              color: check ? 'var(--color-success-600)' : 'var(--color-gray-400)',
            }}>
              {check ? '✓' : '✗'}
            </span>
          </div>
        ))}
      </div>
      <p style={{
        marginTop: '8px',
        fontSize: '0.75rem',
        color: 'var(--color-text-secondary)',
      }}>
        💡 Use una frase memorable: "MiHospital2024!Seguro"
      </p>
    </div>
  );
};
