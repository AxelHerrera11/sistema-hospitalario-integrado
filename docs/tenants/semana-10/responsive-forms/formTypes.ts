import type * as React from 'react';
export interface FormFieldProps {
  label: string;
  name: string;
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url';
  required?: boolean;
  disabled?: boolean;
  error?: string;
  hint?: string;
  value: string;
  onChange: (name: string, value: string) => void;
  onBlur?: (name: string, value: string) => void;
  autocomplete?: string;
  inputMode?: 'text' | 'email' | 'numeric' | 'decimal' | 'tel' | 'url';
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
  onRightIconClick?: () => void;
  rightIconAriaLabel?: string;
  className?: string;
}
export interface FormFieldGroupProps {
  label: string;
  name: string;
  required?: boolean;
  disabled?: boolean;
  error?: string;
  hint?: string;
  children: React.ReactNode;
  className?: string;
}
export interface PasswordStrengthProps {
  value: string;
  className?: string;
}
export interface ButtonProps {
  children: React.ReactNode;
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'outline';
  size?: 'sm' | 'md' | 'lg';
  fullWidth?: boolean;
  disabled?: boolean;
  loading?: boolean;
  onClick?: () => void;
  type?: 'button' | 'submit' | 'reset';
  className?: string;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
  fullWidthMobile?: boolean;
}
export interface FormFieldRowProps {
  children: React.ReactNode;
  className?: string;
}
export interface LoginFormProps {
  onSubmit: (data: { email: string; password: string; tenantId: string; rememberMe: boolean }) => Promise<void>;
  onForgotPassword?: () => void;
  onMfaHelp?: () => void;
  tenantOptions?: { id: string; name: string }[];
  loading?: boolean;
  error?: string;
}
export interface AdminCreateUserFormProps {
  onSubmit: (data: any) => Promise<void>;
  loading?: boolean;
  tenantOptions?: { id: string; name: string }[];
  rolesOptions?: { value: string; label: string; requiresMfa?: boolean }[];
}
