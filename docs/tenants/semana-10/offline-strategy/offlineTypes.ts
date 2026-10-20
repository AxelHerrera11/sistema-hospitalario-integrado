export interface PendingAction {
  id: string;
  type: 'login' | 'logout' | 'refresh' | 'forceRevoke' | 'createUser' | 'updateUser' | 'auditLog';
  payload: any;
  timestamp: number;
  retryCount: number;
  maxRetries: number;
  status: 'pending' | 'processing' | 'completed' | 'failed';
  priority: 'high' | 'normal' | 'low';
  failedAt?: number;
}

export interface OfflineQueueState {
  pendingActions: PendingAction[];
  isOnline: boolean;
  lastSync: number | null;
  syncInProgress: boolean;
  failedActions: PendingAction[];
}

export interface SyncResult {
  success: boolean;
  syncedCount: number;
  failedCount: number;
  errors: string[];
}
