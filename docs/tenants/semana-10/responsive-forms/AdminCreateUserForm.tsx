import React, { useCallback, useState } from 'react';
import type { AdminCreateUserFormProps } from './formTypes';
import { PasswordStrengthMeter } from './PasswordStrengthMeter';

export const AdminCreateUserForm: React.FC<AdminCreateUserFormProps> = ({
  onSubmit,
  loading = false,
  tenantOptions = [],
  rolesOptions = [
    { value: 'MEDICO', label: 'Médico', requiresMfa: false },
    { value: 'ENFERMERO', label: 'Enfermero', requiresMfa: false },
    { value: 'TENANT_ADMIN', label: 'Admin Tenant', requiresMfa: true },
    { value: 'AUDITOR', label: 'Auditor', requiresMfa: true },
    { value: 'PACIENTE', label: 'Paciente', requiresMfa: false },
  ],
}) => {
  const [step, setStep] = useState(1);
  const [formData, setFormData] = useState({
    email: '',
    name: '',
    password: '',
    roles: [] as string[],
    tenantId: '',
    mfaRequired: false,
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [showPassword, setShowPassword] = useState(false);
  const [passwordStrength, setPasswordStrength] = useState(0);

  const validateStep = useCallback((stepNum: number) => {
    const newErrors: Record<string, string> = {};

    if (stepNum >= 1) {
      if (!formData.email) newErrors.email = 'Email obligatorio';
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) newErrors.email = 'Formato inválido';
      else if (!formData.email.endsWith('@hospital.com')) newErrors.email = 'Use @hospital.com';

      if (!formData.name) newErrors.name = 'Nombre obligatorio';
      else if (formData.name.length < 2) newErrors.name = 'Mínimo 2 caracteres';

      if (!formData.tenantId) newErrors.tenantId = 'Seleccione organización';
    }

    if (stepNum >= 2) {
      if (!formData.password) newErrors.password = 'Contraseña obligatoria';
      else if (formData.password.length < 12) newErrors.password = 'Mínimo 12 caracteres';
      else if (!/[A-Z]/.test(formData.password)) newErrors.password = 'Falta mayúscula';
      else if (!/[a-z]/.test(formData.password)) newErrors.password = 'Falta minúscula';
      else if (!/\d/.test(formData.password)) newErrors.password = 'Falta número';
      else if (!/[^A-Za-z0-9]/.test(formData.password)) newErrors.password = 'Falta símbolo';
    }

    if (stepNum >= 3) {
      if (formData.roles.length === 0) newErrors.roles = 'Seleccione al menos un rol';
      if (formData.roles.length > 5) newErrors.roles = 'Máximo 5 roles';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  }, [formData]);

  const handleChange = (name: string, value: any) => {
    setFormData(prev => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors(prev => ({ ...prev, [name]: '' }));
  };

  const handleRoleToggle = (role: string) => {
    setFormData(prev => ({
      ...prev,
      roles: prev.roles.includes(role)
        ? prev.roles.filter(r => r !== role)
        : [...prev.roles, role],
    }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (validateStep(3)) {
      try {
        await onSubmit(formData);
      } catch (error) {
        // Handled by parent
      }
    }
  };

  const isLastStep = step === 3;
  const isFirstStep = step === 1;

  return (
    <form onSubmit={handleSubmit} className="admin-create-user-form" style={{
      maxWidth: '520px',
      margin: '0 auto',
    }}>
      {/* Progress Indicator */}
      <div style={{
        display: 'flex',
        justifyContent: 'space-between',
        marginBottom: '32px',
        position: 'relative',
      }}>
        {[1, 2, 3].map((stepNum) => (
          <div key={stepNum} style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', flex: 1 }}>
            <div style={{
              width: '40px', height: '40px', borderRadius: '50%',
              background: step >= stepNum ? 'var(--color-primary-600)' : 'var(--color-gray-300)',
              color: step >= stepNum ? 'white' : 'var(--color-gray-500)',
              display: 'flex', alignItems: 'center', justifyContent: 'center',
              fontWeight: 600, fontSize: '0.875rem',
              border: step === stepNum ? '3px solid var(--color-primary-600)' : 'none',
              boxShadow: step === stepNum ? '0 0 0 4px var(--color-primary-100)' : 'none',
            }}>
              {stepNum}
            </div>
            <span style={{ marginTop: '8px', fontSize: '0.75rem', color: step >= stepNum ? 'var(--color-primary-600)' : 'var(--color-text-secondary)', fontWeight: step >= stepNum ? 600 : 400 }}>
              {['Datos', 'Credenciales', 'Roles'][stepNum - 1]}
            </span>
          </div>
        ))}
        {[1, 2].map((stepNum) => (
          <div key={`line-${stepNum}`} style={{
            flex: 1,
            height: '2px',
            background: step > stepNum ? 'var(--color-primary-600)' : 'var(--color-gray-300)',
            marginTop: '20px',
            marginBottom: '-20px',
            zIndex: 0,
          }} />
        ))}
      </div>

      {/* Step 1: Datos Básicos */}
      {step === 1 && (
        <div style={{ animation: 'fadeIn 0.2s ease' }}>
          <h3 style={{ marginBottom: '24px', fontSize: '1.125rem', fontWeight: 600 }}>Datos Básicos</h3>

          <div className="form-field" style={{ marginBottom: '24px' }}>
            <label htmlFor="email" className="form-label">Email *</label>
            <input
              type="email"
              id="email"
              name="email"
              autoComplete="email"
              value={formData.email}
              onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
              className={`form-input ${errors.email ? 'has-error' : ''}`}
              placeholder="nuevo@hospital.com"
              required
            />
            <span className="form-hint">Use email @hospital.com</span>
            {errors.email && <span className="form-error" role="alert">⚠️ {errors.email}</span>}
          </div>

          <div className="form-field" style={{ marginBottom: '24px' }}>
            <label htmlFor="name" className="form-label">Nombre Completo *</label>
            <input
              type="text"
              id="name"
              name="name"
              value={formData.name}
              onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
              className={`form-input ${errors.name ? 'has-error' : ''}`}
              placeholder="Dr. Juan Pérez"
              required
            />
            {errors.name && <span className="form-error" role="alert">⚠️ {errors.name}</span>}
          </div>

          <div className="form-field" style={{ marginBottom: '24px' }}>
            <label htmlFor="tenantId" className="form-label">Organización *</label>
            <select
              id="tenantId"
              name="tenantId"
              value={formData.tenantId}
              onChange={(e) => setFormData(prev => ({ ...prev, tenantId: e.target.value }))}
              className={`form-input ${errors.tenantId ? 'has-error' : ''}`}
              required
            >
              <option value="">Seleccione organización</option>
              {tenantOptions.map(opt => (
                <option key={opt.id} value={opt.id}>{opt.name}</option>
              ))}
            </select>
            {errors.tenantId && <span className="form-error" role="alert">⚠️ {errors.tenantId}</span>}
          </div>

          <div style={{ display: 'flex', justifyContent: 'space-between', gap: '16px' }}>
            <button
              type="button"
              onClick={() => setStep(2)}
              disabled={!validateStep(1)}
              className="btn btn-primary"
              style={{ width: '100%', minHeight: '48px' }}
            >
              Siguiente →
            </button>
          </div>
          </div>
        )}

        {/* Step 2: Credenciales */}
        {step === 2 && (
          <div style={{ animation: 'fadeIn 0.2s ease' }}>
            <h3 style={{ marginBottom: '24px', fontSize: '1.125rem', fontWeight: 600 }}>Credenciales</h3>

            <div className="form-field" style={{ marginBottom: '24px', position: 'relative' }}>
              <label htmlFor="password" className="form-label">Contraseña *</label>
              <div style={{ position: 'relative' }}>
                <input
                  type={showPassword ? 'text' : 'password'}
                  id="password"
                  name="password"
                  autoComplete="new-password"
                  value={formData.password}
                  onChange={(e) => {
                    setFormData(prev => ({ ...prev, password: e.target.value }));
                    const val = e.target.value;
                    let strength = 0;
                    if (val.length >= 12) strength++;
                    if (/[A-Z]/.test(val)) strength++;
                    if (/[a-z]/.test(val)) strength++;
                    if (/\d/.test(val)) strength++;
                    if (/[^A-Za-z0-9]/.test(val)) strength++;
                    setPasswordStrength(strength);
                  }}
                  onBlur={() => {}} // Validated on submit
                  className={`form-input ${errors.password ? 'has-error' : ''}`}
                  placeholder="Mín 12 chars, mayús, minús, num, símbolo"
                  required
                  style={{ paddingRight: '48px' }}
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
              <div style={{ marginTop: '8px' }}>
                <PasswordStrengthMeter value={formData.password} />
              </div>
              {errors.password && <span className="form-error" role="alert">⚠️ {errors.password}</span>}
            </div>

            <div className="form-field" style={{ marginBottom: '24px' }}>
              <label className="form-label">
                <input
                  type="checkbox"
                  id="mfaRequired"
                  name="mfaRequired"
                  checked={formData.mfaRequired}
                  onChange={(e) => setFormData(prev => ({ ...prev, mfaRequired: e.target.checked }))}
                  style={{ width: '20px', height: '20px', accentColor: 'var(--color-primary-600)' }}
                />
                <span style={{ marginLeft: '8px', fontSize: '0.875rem', color: 'var(--color-text-primary)' }}>
                  Forzar MFA al primer login (recomendado)
                </span>
              </label>
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between', gap: '16px' }}>
              <button
                type="button"
                onClick={() => setStep(1)}
                className="btn btn-secondary"
                style={{ width: '100%', minHeight: '48px' }}
              >
                ← Anterior
              </button>
              <button
                type="button"
                onClick={() => setStep(3)}
                disabled={!validateStep(2)}
                className="btn btn-primary"
                style={{ width: '100%', minHeight: '48px' }}
              >
                Siguiente →
              </button>
            </div>
          </div>
        )}

        {/* Step 3: Roles */}
        {step === 3 && (
          <div style={{ animation: 'fadeIn 0.2s ease' }}>
            <h3 style={{ marginBottom: '24px', fontSize: '1.125rem', fontWeight: 600 }}>Roles y Permisos</h3>

            <div className="form-field" style={{ marginBottom: '24px' }}>
              <label className="form-label">Roles * (Mín 1, Máx 5)</label>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
                {rolesOptions.map(role => (
                  <label
                    key={role.value}
                    style={{
                      display: 'flex',
                      alignItems: 'center',
                      gap: '8px',
                      padding: '12px 16px',
                      border: `2px solid ${formData.roles.includes(role.value) ? 'var(--color-primary-600)' : 'var(--color-gray-300)'}`,
                      borderRadius: '8px',
                      cursor: 'pointer',
                      transition: 'all 0.2s',
                      background: formData.roles.includes(role.value) ? 'var(--color-primary-50)' : 'transparent',
                    }}
                  >
                    <input
                      type="checkbox"
                      checked={formData.roles.includes(role.value)}
                      onChange={(e) => handleRoleToggle(role.value)}
                      style={{ width: '20px', height: '20px', accentColor: 'var(--color-primary-600)' }}
                    />
                    <span style={{ fontWeight: 500 }}>{role.label}</span>
                    {role.requiresMfa && (
                      <span style={{
                        fontSize: '0.625rem',
                        fontWeight: 600,
                        background: 'var(--color-warning-100)',
                        color: 'var(--color-warning-700)',
                        padding: '2px 6px',
                        borderRadius: '9999px',
                        marginLeft: '4px',
                      }}>
                        MFA obligatorio
                      </span>
                    )}
                  </label>
                ))}
              </div>
              {errors.roles && <span className="form-error" role="alert">⚠️ {errors.roles}</span>}
            </div>

            <div style={{ display: 'flex', justifyContent: 'space-between', gap: '16px' }}>
              <button
                type="button"
                onClick={() => setStep(2)}
                className="btn btn-secondary"
                style={{ width: '100%', minHeight: '48px' }}
              >
                ← Anterior
              </button>
              <button
                type="submit"
                disabled={!validateStep(3)}
                className="btn btn-primary"
                style={{ width: '100%', minHeight: '48px' }}
              >
                Crear Usuario
              </button>
            </div>
          </div>
        )}
      </form>
    );
};
