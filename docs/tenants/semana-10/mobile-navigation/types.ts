import type { ReactNode } from 'react';

export interface BottomNavItem {
  id: string;
  label: string;
  icon: ReactNode;
  href?: string;
  onClick?: () => void;
  badge?: number | string;
  disabled?: boolean;
}

export interface NavItem {
  id: string;
  label: string;
  icon: ReactNode;
  href?: string;
  onClick?: () => void;
  children?: NavItem[];
  badge?: number | string;
  danger?: boolean;
}

export interface TabItem {
  id: string;
  label: string;
  icon?: ReactNode;
  badge?: number | string;
  disabled?: boolean;
}

export interface TabBarProps {
  tabs: TabItem[];
  activeTab: string;
  onChange: (tabId: string) => void;
  scrollable?: boolean;
  className?: string;
}

export interface BreadcrumbItem {
  label: string;
  href?: string;
  onClick?: () => void;
  current?: boolean;
}

export interface BreadcrumbProps {
  items: BreadcrumbItem[];
  maxItems?: number;
  separator?: ReactNode;
  className?: string;
}

export interface SidebarProps {
  isOpen: boolean;
  onClose: () => void;
  items: NavItem[];
  user?: {
    name: string;
    role: string;
    tenant: string;
    avatar?: string;
  };
  onLogout?: () => void;
  className?: string;
}
