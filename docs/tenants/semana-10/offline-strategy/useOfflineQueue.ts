import { useCallback, useEffect, useState } from 'react';
import { OfflineQueueManager } from './OfflineQueueManager';
import type { OfflineQueueState, PendingAction, SyncResult } from './offlineTypes';

export function useOfflineQueue() {
  const [state, setState] = useState<OfflineQueueState>({
    pendingActions: [],
    isOnline: typeof navigator !== 'undefined' ? navigator.onLine : false,
    lastSync: null,
    syncInProgress: false,
    failedActions: [],
  });

  useEffect(() => {
    const manager = OfflineQueueManager.getInstance();
    setState(manager.getState());
    
    const unsubscribe = manager.subscribe(setState);
    return unsubscribe;
  }, []);

  const enqueueAction = useCallback(async (
    type: PendingAction['type'],
    payload: any,
    options?: { priority?: 'high' | 'normal' | 'low'; maxRetries?: number }
  ) => {
    const manager = OfflineQueueManager.getInstance();
    return manager.enqueueAction(type, payload, options);
  }, []);

  const processQueue = useCallback(async () => {
    const manager = OfflineQueueManager.getInstance();
    return manager.processQueue();
  }, []);

  const retryFailed = useCallback(async (id: string) => {
    const manager = OfflineQueueManager.getInstance();
    return manager.retryFailedAction(id);
  }, []);

  const clearFailed = useCallback(async () => {
    const manager = OfflineQueueManager.getInstance();
    return manager.clearFailedActions();
  }, []);

  return {
    state,
    ...state,
    enqueueAction,
    processQueue,
    retryFailed,
    clearFailed,
    pendingCount: state.pendingActions.filter(a => a.status === 'pending').length,
    failedCount: state.failedActions.length,
    isOnline: state.isOnline,
    isSyncing: state.syncInProgress,
  };
}

// ============================================================
// BACKGROUND SYNC REGISTRATION
// ============================================================

export async function registerBackgroundSync(): Promise<void> {
  if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
    try {
      const registration = await navigator.serviceWorker.ready;
      await (registration as any).sync.register('pending-actions');
      console.log('Background sync registered');
    } catch (error) {
      console.warn('Background sync registration failed:', error);
    }
  }
}

// Service Worker message handler for sync completion
export function setupSyncMessageHandler(onSyncComplete: (result: SyncResult) => void): void {
  if (typeof navigator === 'undefined' || !('serviceWorker' in navigator)) return;
  navigator.serviceWorker.addEventListener('message', (event) => {
    if (event.data.type === 'SYNC_COMPLETE') {
      onSyncComplete(event.data.result);
    }
  });
}
