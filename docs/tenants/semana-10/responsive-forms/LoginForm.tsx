import React, { useCallback, useRef, useState } from 'react';
import type { LoginFormProps } from './formTypes';

export const LoginForm: React.FC<LoginFormProps> = ({
  onSubmit,
  onForgotPassword,
  onMfaHelp,
  tenantOptions = [],
  loading = false,
  error,
}) => {
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    tenantId: '',
    rememberMe: false,
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [showPassword, setShowPassword] = useState(false);
  const emailRef = useRef<HTMLInputElement>(null);

  const validateField = useCallback((name: string, value: string) => {
    let error = '';
    switch (name) {
      case 'email':
        if (!value) error = 'El email es obligatorio';
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) error = 'Formato de email inválido';
        else if (!value.endsWith('@hospital.com')) error = 'Use su email institucional @hospital.com';
        break;
      case 'password':
        if (!value) error = 'La contraseña es obligatoria';
        else if (value.length < 12) error = 'Mínimo 12 caracteres';
        break;
      case 'tenantId':
        if (!value) error = 'Seleccione una organización';
        break;
    }
    setErrors(prev => ({ ...prev, [name]: error }));
    return !error;
  }, []);

  const handleChange = (name: string, value: string) => {
    setFormData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) validateField(name, value);
  };

  const handleBlur = (name: string, value: string) => {
    validateField(name, value);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const validations = [
      validateField('email', formData.email),
      validateField('password', formData.password),
      validateField('tenantId', formData.tenantId),
    ];
    if (!validations.every(Boolean)) return;

    try {
      await onSubmit(formData);
    } catch (error) {
      // Error handled by parent
    }
  };

  return (
    <form onSubmit={handleSubmit} noValidate className="login-form" style={{
      maxWidth: '400px',
      margin: '0 auto',
      padding: '24px',
      background: 'var(--color-bg-primary)',
      borderRadius: '16px',
      boxShadow: 'var(--shadow-lg)',
    }}>
      <div style={{ textAlign: 'center', marginBottom: '32px' }}>
        <h1 style={{ margin: 0, fontSize: '1.5rem', fontWeight: 600, color: 'var(--color-text-primary)' }}>
          🔐 Iniciar Sesión
        </h1>
        <p style={{ margin: '8px 0 0', color: 'var(--color-text-secondary)', fontSize: '0.875rem' }}>
          Sistema Hospitalario Integrado
        </p>
      </div>

      <div className="form-field" style={{ marginBottom: '24px' }}>
        <label htmlFor="email" className="form-label">
          Email institucional <span aria-hidden="true" style={{ color: 'var(--color-danger-600)' }}>*</span>
        </label>
        <input
          ref={emailRef}
          type="email"
          id="email"
          name="email"
          autoComplete="email"
          inputMode="email"
          value={formData.email}
          onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
          onBlur={(e) => validateField('email', e.target.value)}
          className={`form-input ${errors.email ? 'has-error' : ''}`}
          placeholder="dra.gonzalez@hospital.com"
          required
          aria-invalid={!!errors.email}
          aria-describedby={errors.email ? 'email-error' : 'email-hint'}
        />
        <span id="email-hint" className="form-hint">Use su email @hospital.com</span>
        {errors.email && (
          <span id="email-error" className="form-error" role="alert" style={{
            display: 'flex', alignItems: 'center', gap: '4px', marginTop: '6px',
            fontSize: '0.75rem', color: 'var(--color-danger-600)'
          }}>
            <span aria-hidden="true">⚠️</span> {errors.email}
          </span>
        )}
      </div>

      <div className="form-field" style={{ marginBottom: '24px', position: 'relative' }}>
        <label htmlFor="password" className="form-label">
          Contraseña <span aria-hidden="true" style={{ color: 'var(--color-danger-600)' }}>*</span>
        </label>
        <div style={{ position: 'relative' }}>
          <input
            type={showPassword ? 'text' : 'password'}
            id="password"
            name="password"
            autoComplete="current-password"
            value={formData.password}
            onChange={(e) => setFormData(prev => ({ ...prev, password: e.target.value }))}
            onBlur={(e) => validateField('password', e.target.value)}
            className={`form-input ${errors.password ? 'has-error' : ''}`}
            placeholder="••••••••••••"
            required
            style={{ paddingRight: '48px' }}
            aria-invalid={!!errors.password}
            aria-describedby={errors.password ? 'password-error' : 'password-hint'}
          />
          <button
            type="button"
            onClick={() => setShowPassword(!showPassword)}
            aria-label={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
            aria-pressed={showPassword}
            style={{
              position: 'absolute',
              right: '12px',
              top: '50%',
              transform: 'translateY(-50%)',
              background: 'none',
              border: 'none',
              cursor: 'pointer',
              padding: '4px',
              color: 'var(--color-text-secondary)',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            {showPassword ? '🙈' : '👁️'}
          </button>
        </div>
        <span id="password-hint" className="form-hint">Mín 12 chars, mayús, minús, num, símbolo</span>
        {errors.password && (
          <span id="password-error" className="form-error" role="alert" style={{
            display: 'flex', alignItems: 'center', gap: '4px', marginTop: '6px',
            fontSize: '0.75rem', color: 'var(--color-danger-600)'
          }}>
            <span aria-hidden="true">⚠️</span> {errors.password}
          </span>
        )}
      </div>

      <div className="form-field" style={{ marginBottom: '24px', display: 'flex', alignItems: 'center', gap: '12px' }}>
        <input
          type="checkbox"
          id="rememberMe"
          name="rememberMe"
          checked={false}
          onChange={(e) => {}} // handleChange
          style={{ width: '20px', height: '20px', accentColor: 'var(--color-primary-600)', cursor: 'pointer' }}
        />
        <label htmlFor="rememberMe" style={{ fontSize: '0.875rem', color: 'var(--color-text-secondary)', cursor: 'pointer' }}>
          Recordarme en este dispositivo (30 días)
        </label>
      </div>

      <button
        type="submit"
        disabled={false}
        className="btn btn-primary"
        style={{
          width: '100%',
          minHeight: '48px',
          marginBottom: '16px',
        }}
      >
        Iniciar Sesión
      </button>

      <div style={{ display: 'flex', justifyContent: 'center', gap: '16px', fontSize: '0.875rem' }}>
        <a href="#" onClick={(e) => { e.preventDefault(); }} style={{
          color: 'var(--color-primary-600)', textDecoration: 'none', fontWeight: 500
        }}>
          ¿Olvidó su contraseña?
        </a>
        <span style={{ color: 'var(--color-gray-400)' }} aria-hidden="true">|</span>
        <a href="#" onClick={(e) => { e.preventDefault(); }} style={{
          color: 'var(--color-primary-600)', textDecoration: 'none', fontWeight: 500
        }}>
          ¿Problemas con MFA?
        </a>
      </div>
    </form>
  );
}
