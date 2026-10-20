import React, { useState } from 'react';
import { useOfflineQueue } from './useOfflineQueue';
import { OfflineQueueManager } from './OfflineQueueManager';

export function OfflineIndicator() {
  const { isOnline, isSyncing, pendingCount, failedCount, processQueue } = useOfflineQueue();
  const [showDetails, setShowDetails] = useState(false);

  if (isOnline && pendingCount === 0 && failedCount === 0) {
    return null;
  }

  return (
    <div
      style={{
        position: 'fixed',
        top: 0,
        left: 0,
        right: 0,
        zIndex: 9999,
        background: !isOnline ? 'var(--color-warning-600)' : 
                    failedCount > 0 ? 'var(--color-danger-600)' : 'var(--color-primary-600)',
        color: 'white',
        padding: '8px 16px',
        fontSize: '0.875rem',
        fontWeight: 500,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingLeft: '16px',
        paddingRight: '16px',
        boxShadow: 'var(--shadow-lg)',
        animation: 'slideDown 0.3s ease-out',
      }}
      onClick={() => setShowDetails(!showDetails)}
      role="status"
      aria-live="polite"
    >
      <style>{`
        @keyframes slideDown {
          from { transform: translateY(-100%); opacity: 0; }
          to { transform: translateY(0); opacity: 1; }
        }
      `}</style>
      <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flex: 1 }}>
        {!isOnline && <span aria-hidden="true">📴</span>}
        {isSyncing && <span className="spinner" style={{ width: '16px', height: '16px', border: '2px solid white', borderTopColor: 'transparent', borderRadius: '50%', animation: 'spin 0.8s linear infinite' }} />}
        <span>
          {!isOnline 
            ? `Sin conexión ${pendingCount > 0 ? `(${pendingCount} pendientes)` : ''}` 
            : failedCount > 0
              ? `${failedCount} acciones fallidas`
              : `${pendingCount} acciones pendientes de sincronizar`}
        </span>
      </div>
      
      <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
        {showDetails && (
          <>
            <button
              onClick={(e) => { e.stopPropagation(); window.location.href = '/offline-queue'; }}
              style={{
                background: 'rgba(255,255,255,0.2)',
                border: 'none',
                color: 'white',
                padding: '4px 12px',
                borderRadius: '4px',
                fontSize: '0.75rem',
                cursor: 'pointer',
              }}
            >
              Ver cola
            </button>
          </>
        )}
        <button
          onClick={(e) => { e.stopPropagation(); setShowDetails(!showDetails); }}
          style={{
            background: 'rgba(255,255,255,0.2)',
            border: 'none',
            color: 'white',
            padding: '4px 8px',
            borderRadius: '4px',
            cursor: 'pointer',
            fontSize: '0.75rem',
          }}
        >
          {showDetails ? 'Ocultar' : 'Detalles'}
        </button>
        {isOnline && pendingCount > 0 && (
          <button
            onClick={(e) => { e.stopPropagation(); OfflineQueueManager.getInstance().processQueue(); }}
            style={{
              background: 'rgba(255,255,255,0.2)',
              border: 'none',
              color: 'white',
              padding: '4px 12px',
              borderRadius: '4px',
              cursor: 'pointer',
              fontSize: '0.75rem',
            }}
          >
            Sincronizar ahora
          </button>
        )}
      </div>
    </div>
  );
}
