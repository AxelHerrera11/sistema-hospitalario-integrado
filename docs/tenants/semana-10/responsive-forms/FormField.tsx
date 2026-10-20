import React, { useRef, useState } from 'react';
import type { FormFieldProps } from './formTypes';
export const FormField: React.FC<FormFieldProps> = ({
  label,
  name,
  type = 'text',
  required = false,
  disabled = false,
  error,
  hint,
  value,
  onChange,
  onBlur,
  autocomplete,
  inputMode,
  leftIcon,
  rightIcon,
  onRightIconClick,
  rightIconAriaLabel,
  className = '',
}) => {
  const [focused, setFocused] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const inputRef = useRef<HTMLInputElement>(null);

  const handleRightIconClick = () => {
    if (onRightIconClick) {
      onRightIconClick();
    }
  };
  const inputType = type === 'password' && !showPassword ? 'password' : type;
  return (
    <div className={`form-field ${error ? 'has-error' : ''} ${focused ? 'focused' : ''} ${className}`}>
      <label htmlFor={name} className="form-label" style={{
        display: 'block',
        marginBottom: '6px',
        fontSize: '0.875rem',
        fontWeight: 500,
        color: 'var(--color-text-primary)',
      }}>
        {label}
        {required && <span className="required-indicator" aria-hidden="true" style={{ color: 'var(--color-danger-600)', marginLeft: '4px' }}>*</span>}
      </label>

      <div className="form-input-wrapper" style={{ position: 'relative', display: 'flex', alignItems: 'center' }}>
        {leftIcon && (
          <span className="input-left-icon" style={{
            position: 'absolute',
            left: '16px',
            top: '50%',
            transform: 'translateY(-50%)',
            color: 'var(--color-text-tertiary)',
            pointerEvents: 'none',
          }} aria-hidden="true">
            {leftIcon}
          </span>
        )}

        <input
          ref={inputRef}
          id={name}
          name={name}
          type={type === 'password' ? (showPassword ? 'text' : 'password') : type}
          required={required}
          disabled={disabled}
          value={value}
          onChange={(e) => onChange(name, e.target.value)}
          autoComplete={autocomplete}
          inputMode={inputMode}
          aria-invalid={!!error}
          aria-describedby={error ? `${name}-error` : hint ? `${name}-hint` : undefined}
          aria-required={required}
          style={{
            width: '100%',
            height: '48px',
            padding: '12px 16px',
            fontSize: '16px', // Prevents zoom on iOS
            fontFamily: 'inherit',
            color: 'var(--color-text-primary)',
            backgroundColor: disabled ? 'var(--color-gray-100)' : 'var(--color-bg-primary)',
            border: '1px solid var(--color-border-default)',
            borderRadius: '8px',
            paddingLeft: '16px',
            paddingRight: rightIcon ? '48px' : '16px',
            outline: 'none',
            transition: 'border-color 0.2s ease, box-shadow 0.2s ease',
            boxSizing: 'border-box',
          } as React.CSSProperties}
          className={`form-input ${error ? 'has-error' : ''} ${focused ? 'focused' : ''}`}
          onFocus={(e) => { setFocused(true); }}
          onBlur={(e) => { setFocused(false); onBlur?.(name, e.target.value); }}
        />

        {rightIcon && (
          <button
            type="button"
            onClick={() => {
              if (onRightIconClick) onRightIconClick();
              else if (type === 'password') setShowPassword(value => !value);
            }}
            disabled={disabled}
            aria-label={rightIconAriaLabel || (type === 'password' ? 'Cambiar visibilidad de contraseña' : 'Acción del campo')}
            aria-pressed={type === 'password' ? showPassword : undefined}
            style={{
              position: 'absolute',
              right: '12px',
              top: '50%',
              transform: 'translateY(-50%)',
              background: 'none',
              border: 'none',
              cursor: disabled ? 'not-allowed' : 'pointer',
              padding: '4px',
              color: 'var(--color-text-secondary)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              minWidth: '44px',
              minHeight: '44px',
            }}
          >
            {rightIcon || (showPassword ? '🙈' : '👁️')}
          </button>
        )}

        {error && (
          <span
            id={`${name}-error`}
            className="form-error"
            role="alert"
            aria-live="assertive"
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '4px',
              marginTop: '6px',
              fontSize: '0.75rem',
              color: 'var(--color-danger-600)',
            }}
          >
            <span aria-hidden="true">⚠️</span>
            <span>{error}</span>
          </span>
        )}

        {!error && hint && (
          <span
            id={`${name}-hint`}
            className="form-hint"
            style={{
              display: 'block',
              marginTop: '6px',
              fontSize: '0.75rem',
              color: 'var(--color-text-tertiary)',
            }}
          >
            {hint}
          </span>
        )}
      </div>
    </div>
  );
};
