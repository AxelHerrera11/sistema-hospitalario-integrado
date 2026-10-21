import type { OfflineQueueState, PendingAction, SyncResult } from './offlineTypes';

export class OfflineQueueManager {
  private static instance: OfflineQueueManager;
  private dbName = 'tenants-auth-offline';
  private dbVersion = 1;
  private db: IDBDatabase | null = null;
  private state: OfflineQueueState = {
    pendingActions: [],
    isOnline: typeof navigator !== 'undefined' ? navigator.onLine : false,
    lastSync: null,
    syncInProgress: false,
    failedActions: [],
  };
  private listeners: Set<(state: OfflineQueueState) => void> = new Set();
  private syncInterval: number | null = null;
  private onlineCheckInterval: number | null = null;
  private constructor() {
    if (typeof window !== 'undefined' && 'indexedDB' in window) {
      void this.initDB().catch(error => console.error('Offline database initialization failed:', error));
      this.setupOnlineListener();
      this.startPeriodicSync();
    }
  }
  static getInstance(): OfflineQueueManager {
    if (!OfflineQueueManager.instance) {
      OfflineQueueManager.instance = new OfflineQueueManager();
    }
    return OfflineQueueManager.instance;
  }
  private async initDB(): Promise<void> {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.dbVersion);
      request.onerror = () => reject(request.error);
      request.onsuccess = () => {
        this.db = request.result;
        this.loadPendingActions();
        resolve();
      };
      request.onupgradeneeded = (event) => {
        const db = (event.target as IDBOpenDBRequest).result;
        if (!db.objectStoreNames.contains('pendingActions')) {
          const store = db.createObjectStore('pendingActions', { keyPath: 'id' });
          store.createIndex('type', 'type', { unique: false });
          store.createIndex('status', 'status', { unique: false });
          store.createIndex('timestamp', 'timestamp', { unique: false });
          store.createIndex('priority', 'priority', { unique: false });
        }
        if (!db.objectStoreNames.contains('failedActions')) {
          db.createObjectStore('failedActions', { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains('syncMeta')) {
          db.createObjectStore('syncMeta', { keyPath: 'key' });
        }
      };
    });
  }
  private setupOnlineListener(): void {
    window.addEventListener('online', () => {
      this.state.isOnline = true;
      this.notifyListeners();
      this.processQueue();
    });
    window.addEventListener('offline', () => {
      this.state.isOnline = false;
      this.notifyListeners();
    });
    this.onlineCheckInterval = window.setInterval(() => {
      const wasOnline = this.state.isOnline;
      this.state.isOnline = navigator.onLine;
      if (!wasOnline && this.state.isOnline) {
        this.notifyListeners();
        this.processQueue();
      }
    }, 30000); // Check every 30 seconds
  }
  private startPeriodicSync(): void {
    this.syncInterval = window.setInterval(() => {
      if (this.state.isOnline && !this.state.syncInProgress) {
        this.processQueue();
      }
    }, 5 * 60 * 1000); // 5 minutes
  }
  private async loadPendingActions(): Promise<void> {
    if (!this.db) return;
    return new Promise((resolve) => {
      const transaction = this.db!.transaction(['pendingActions', 'failedActions'], 'readonly');
      const pendingRequest = transaction.objectStore('pendingActions').getAll();
      const failedRequest = transaction.objectStore('failedActions').getAll();
      let completedRequests = 0;
      const finishIfReady = () => {
        completedRequests += 1;
        if (completedRequests === 2) {
          this.state.pendingActions = pendingRequest.result || [];
          this.state.failedActions = failedRequest.result || [];
          this.notifyListeners();
          resolve();
        }
      };
      pendingRequest.onsuccess = finishIfReady;
      failedRequest.onsuccess = finishIfReady;
      pendingRequest.onerror = () => {
        console.error('Failed to load pending actions:', pendingRequest.error);
        resolve();
      };
      failedRequest.onerror = () => {
        console.error('Failed to load failed actions:', failedRequest.error);
        resolve();
      };
    });
  }
  getState(): OfflineQueueState {
    return { ...this.state };
  }
  subscribe(listener: (state: OfflineQueueState) => void): () => void {
    this.listeners.add(listener);
    return () => this.listeners.delete(listener);
  }
  private notifyListeners(): void {
    this.listeners.forEach(listener => listener(this.getState()));
  }
  async enqueueAction(
    type: PendingAction['type'],
    payload: any,
    options: {
      priority?: 'high' | 'normal' | 'low';
      maxRetries?: number;
    } = {}
  ): Promise<string> {
    const action: PendingAction = {
      id: crypto.randomUUID(),
      type,
      payload,
      timestamp: Date.now(),
      retryCount: 0,
      maxRetries: options.maxRetries ?? 3,
      status: 'pending',
      priority: options.priority ?? 'normal',
    };
    await this.saveAction(action);
    this.state.pendingActions.push(action);
    this.state.pendingActions.sort((a, b) => {
      const priorityOrder = { high: 3, normal: 2, low: 1 };
      if (priorityOrder[a.priority] !== priorityOrder[b.priority]) {
        return priorityOrder[b.priority] - priorityOrder[a.priority];
      }
      return a.timestamp - b.timestamp;
    });
    this.notifyListeners();
    if (this.state.isOnline && !this.state.syncInProgress) {
      this.processQueue();
    }
    return action.id;
  }
  private async saveAction(action: PendingAction): Promise<void> {
    if (!this.db) return;
    return new Promise((resolve, reject) => {
      const transaction = this.db!.transaction(['pendingActions'], 'readwrite');
      const store = transaction.objectStore('pendingActions');
      const request = store.put(action);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }
  async processQueue(): Promise<SyncResult> {
    if (this.state.syncInProgress || !this.state.isOnline) {
      return { success: false, syncedCount: 0, failedCount: 0, errors: ['Already processing or offline'] };
    }
    this.state.syncInProgress = true;
    this.notifyListeners();
    const pending = this.state.pendingActions.filter(a => a.status === 'pending');
    const results: SyncResult = { success: true, syncedCount: 0, failedCount: 0, errors: [] };
    for (const action of pending) {
      try {
        action.status = 'processing';
        await this.updateAction(action);
        const success = await this.executeAction(action);
        if (success) {
          action.status = 'completed';
          await this.removeAction(action.id);
          results.syncedCount++;
        } else {
          action.retryCount++;
          if (action.retryCount >= action.maxRetries) {
            action.status = 'failed';
            await this.moveToFailed(action);
            results.failedCount++;
            results.errors.push(`${action.type}: Max retries exceeded`);
          } else {
            action.status = 'pending';
            await this.updateAction(action);
          }
        }
      } catch (error) {
        action.retryCount++;
        if (action.retryCount >= action.maxRetries) {
          action.status = 'failed';
          await this.moveToFailed(action);
          results.failedCount++;
          results.errors.push(`${action.type}: ${error instanceof Error ? error.message : 'Unknown error'}`);
        } else {
          action.status = 'pending';
          await this.updateAction(action);
        }
      }
    }
    this.state.lastSync = Date.now();
    await this.loadPendingActions();
    this.state.syncInProgress = false;
    this.notifyListeners();
    return results;
  }
  private async executeAction(action: PendingAction): Promise<boolean> {
    try {
      switch (action.type) {
        case 'login':
          return await this.executeLogin(action.payload);
        case 'logout':
          return await this.executeLogout(action.payload);
        case 'refresh':
          return await this.executeRefresh(action.payload);
        case 'forceRevoke':
          return await this.executeForceRevoke(action.payload);
        case 'createUser':
          return await this.executeCreateUser(action.payload);
        case 'updateUser':
          return await this.executeUpdateUser(action.payload);
        case 'auditLog':
          return await this.executeAuditLog(action.payload);
        default:
          console.warn(`Unknown action type: ${action.type}`);
          return false;
      }
    } catch (error) {
      console.error(`Action execution failed: ${action.type}`, error);
      return false;
    }
  }
  private async executeLogin(payload: any): Promise<boolean> {
    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    return response.ok;
  }
  private async executeLogout(payload: any): Promise<boolean> {
    const response = await fetch('/api/auth/logout', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(payload?.token ? { Authorization: `Bearer ${payload.token}` } : {}),
      },
      body: JSON.stringify(payload ?? {}),
    });
    return response.ok;
  }
  private async executeRefresh(payload: any): Promise<boolean> {
    const response = await fetch('/api/auth/refresh', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    return response.ok;
  }
  private async executeForceRevoke(payload: any): Promise<boolean> {
    const response = await fetch(`/api/admin/users/${payload.userId}/sessions`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${payload.token}`,
      },
      body: JSON.stringify({ reason: payload.reason, requesterId: payload.requesterId }),
    });
    return response.ok;
  }
  private async executeCreateUser(payload: any): Promise<boolean> {
    const response = await fetch('/api/admin/users', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${payload.token}`,
      },
      body: JSON.stringify(payload.data),
    });
    return response.ok;
  }
  private async executeUpdateUser(payload: any): Promise<boolean> {
    const response = await fetch(`/api/admin/users/${payload.userId}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${payload.token}`,
      },
      body: JSON.stringify(payload.data),
    });
    return response.ok;
  }
  private async executeAuditLog(payload: any): Promise<boolean> {
    try {
      await fetch('/api/audit/log', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        keepalive: true, // Important for audit logs during unload
      });
      return true;
    } catch {
      return false;
    }
  }
  private async updateAction(action: PendingAction): Promise<void> {
    if (!this.db) return;
    return new Promise((resolve, reject) => {
      const transaction = this.db!.transaction(['pendingActions'], 'readwrite');
      const store = transaction.objectStore('pendingActions');
      const request = store.put(action);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }
  private async removeAction(id: string): Promise<void> {
    if (!this.db) return;
    return new Promise((resolve, reject) => {
      const transaction = this.db!.transaction(['pendingActions'], 'readwrite');
      const store = transaction.objectStore('pendingActions');
      const request = store.delete(id);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }
  private async moveToFailed(action: PendingAction): Promise<void> {
    if (!this.db) return;
    await this.removeAction(action.id);
    return new Promise((resolve, reject) => {
      const transaction = this.db!.transaction(['failedActions'], 'readwrite');
      const store = transaction.objectStore('failedActions');
      const request = store.put({ ...action, failedAt: Date.now() });
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }
  async retryFailedAction(id: string): Promise<void> {
    if (!this.db) return;
    return new Promise((resolve, reject) => {
      const transaction = this.db!.transaction(['failedActions', 'pendingActions'], 'readwrite');
      const failedStore = transaction.objectStore('failedActions');
      const pendingStore = transaction.objectStore('pendingActions');
      const getRequest = failedStore.get(id);
      getRequest.onsuccess = () => {
        const action = getRequest.result as PendingAction | undefined;
        if (!action) {
          resolve();
          return;
        }
        action.status = 'pending';
        action.retryCount = 0;
        pendingStore.put(action);
        failedStore.delete(id);
        transaction.oncomplete = () => {
          void this.loadPendingActions();
          resolve();
        };
        transaction.onerror = () => reject(transaction.error);
      };
      getRequest.onerror = () => reject(getRequest.error);
    });
  }
  async clearFailedActions(): Promise<void> {
    if (!this.db) return;
    return new Promise((resolve, reject) => {
      const transaction = this.db!.transaction(['failedActions'], 'readwrite');
      const store = transaction.objectStore('failedActions');
      const request = store.clear();
      request.onsuccess = () => {
        this.state.failedActions = [];
        this.notifyListeners();
        resolve();
      };
      request.onerror = () => reject(request.error);
    });
  }
  getPendingCount(): number { return this.state.pendingActions.filter(a => a.status === 'pending').length; }
  getFailedCount(): number { return this.state.failedActions.length; }
  isOnline(): boolean { return this.state.isOnline; }
  isSyncing(): boolean { return this.state.syncInProgress; }
  getLastSyncTime(): number | null { return this.state.lastSync; }
  destroy(): void {
    if (this.syncInterval) clearInterval(this.syncInterval);
    if (this.onlineCheckInterval) clearInterval(this.onlineCheckInterval);
    if (this.db) this.db.close();
  }
}
