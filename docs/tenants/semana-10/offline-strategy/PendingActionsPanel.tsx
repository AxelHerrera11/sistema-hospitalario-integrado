import React, { useState } from 'react';
import { useOfflineQueue } from './useOfflineQueue';

export function PendingActionsPanel() {
  const { state, processQueue, retryFailed, clearFailed, isOnline, isSyncing, pendingCount, failedCount } = useOfflineQueue();
  const [showPanel, setShowPanel] = useState(false);

  if (!showPanel) {
    return (
      <button
        type="button"
        onClick={() => setShowPanel(true)}
        aria-label="Abrir cola de sincronización"
        style={{
          position: 'fixed',
          right: '16px',
          bottom: '16px',
          zIndex: 9998,
          padding: '10px 14px',
          border: 'none',
          borderRadius: '8px',
          background: 'var(--color-primary-600)',
          color: 'white',
          cursor: 'pointer',
        }}
      >
        Cola de sincronización
      </button>
    );
  }

  return (
    <div style={{
      position: 'fixed',
      top: 0,
      right: 0,
      bottom: 0,
      width: '400px',
      maxWidth: '100vw',
      background: 'var(--color-bg-primary)',
      borderLeft: '1px solid var(--color-border-default)',
      boxShadow: 'var(--shadow-xl)',
      zIndex: 9999,
      display: 'flex',
      flexDirection: 'column',
      overflow: 'hidden',
    }}>
      <div style={{
        padding: '16px',
        borderBottom: '1px solid var(--color-border-default)',
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
      }}>
        <h3 style={{ margin: 0, fontSize: '1rem', fontWeight: 600 }}>
          Cola de Sincronización
        </h3>
        <button
          onClick={() => setShowPanel(false)}
          style={{
            background: 'none',
            border: 'none',
            fontSize: '1.5rem',
            cursor: 'pointer',
            color: 'var(--color-text-secondary)',
            padding: '4px',
            lineHeight: 1,
          }}
        >
          ×
        </button>
      </div>

      <div style={{
        padding: '16px',
        borderBottom: '1px solid var(--color-border-default)',
        background: 'var(--color-gray-50)',
        display: 'flex',
        gap: '16px',
        flexWrap: 'wrap',
        fontSize: '0.75rem',
      }}>
        <span>🟢 Online: {isOnline ? 'Sí' : 'No'}</span>
        <span>🔄 Sync: {isSyncing ? 'En progreso' : 'Inactivo'}</span>
        <span>⏳ Pendientes: {pendingCount}</span>
        <span style={{ color: failedCount > 0 ? 'var(--color-danger-600)' : 'inherit' }}>
          ❌ Fallidos: {failedCount}
        </span>
      </div>

      <div style={{ flex: 1, overflow: 'auto', padding: '16px' }}>
        {state.pendingActions.length === 0 && state.failedActions.length === 0 && (
          <div style={{ textAlign: 'center', padding: '48px', color: 'var(--color-text-tertiary)' }}>
            Cola vacía
          </div>
        )}

        <div style={{ marginBottom: '16px' }}>
          <h4 style={{ margin: '0 0 8px', fontSize: '0.875rem' }}>Pendientes ({state.pendingActions.filter(a => a.status === 'pending').length})</h4>
          {state.pendingActions
            .filter(a => a.status === 'pending')
            .slice(0, 20)
            .map(action => (
              <div key={action.id} style={{
                padding: '12px',
                background: 'var(--color-gray-50)',
                borderRadius: '8px',
                marginBottom: '8px',
                fontSize: '0.75rem',
              }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                  <span style={{ fontWeight: 600, textTransform: 'capitalize' }}>{action.type}</span>
                  <span style={{ 
                    padding: '2px 6px',
                    borderRadius: '4px',
                    fontSize: '0.625rem',
                    fontWeight: 600,
                    background: action.priority === 'high' ? 'var(--color-danger-100)' :
                              action.priority === 'low' ? 'var(--color-gray-100)' : 'var(--color-primary-100)',
                    color: action.priority === 'high' ? 'var(--color-danger-700)' :
                           action.priority === 'low' ? 'var(--color-gray-700)' : 'var(--color-primary-700)',
                  }}>
                    {action.priority.toUpperCase()}
                  </span>
                </div>
                <div style={{ fontSize: '0.6875rem', color: 'var(--color-text-tertiary)', marginBottom: '4px' }}>
                  {new Date(action.timestamp).toLocaleString()} · Intento {action.retryCount}/{action.maxRetries}
                </div>
                <div style={{ fontSize: '0.6875rem', color: 'var(--color-text-tertiary)', wordBreak: 'break-all' }}>
                  {JSON.stringify(action.payload).slice(0, 100)}...
                </div>
              </div>
            ))}
          </div>

          {state.failedActions.length > 0 && (
            <div>
              <h4 style={{ margin: '16px 0 8px', fontSize: '0.875rem', color: 'var(--color-danger-600)' }}>
                Fallidos ({state.failedActions.length})
              </h4>
              {state.failedActions.map(action => (
                <div key={action.id} style={{
                  padding: '12px',
                  background: 'var(--color-danger-50)',
                  border: '1px solid var(--color-danger-200)',
                  borderRadius: '8px',
                  marginBottom: '8px',
                  fontSize: '0.75rem',
                }}>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                    <span style={{ fontWeight: 600, textTransform: 'capitalize' }}>{action.type}</span>
                    <button
                      onClick={() => retryFailed(action.id)}
                      style={{
                        padding: '4px 8px',
                        fontSize: '0.6875rem',
                        background: 'var(--color-primary-600)',
                        color: 'white',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: 'pointer',
                      }}
                    >
                      Reintentar
                    </button>
                  </div>
                  <div style={{ fontSize: '0.6875rem', color: 'var(--color-text-tertiary)' }}>
                    Falló: {new Date(action.failedAt).toLocaleString()} · {action.retryCount}/{action.maxRetries} intentos
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        <div style={{
          padding: '16px',
          borderTop: '1px solid var(--color-border-default)',
          display: 'flex',
          gap: '8px',
        }}>
          <button
            onClick={processQueue}
            disabled={isSyncing || !isOnline}
            style={{
              flex: 1,
              padding: '10px',
              background: 'var(--color-primary-600)',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              fontWeight: 600,
              cursor: 'pointer',
            }}
          >
            {isSyncing ? 'Sincronizando...' : 'Sincronizar Ahora'}
          </button>
          <button
            onClick={clearFailed}
            disabled={failedCount === 0}
            style={{
              padding: '10px 16px',
              background: 'var(--color-danger-600)',
              color: 'white',
              border: 'none',
              borderRadius: '8px',
              fontWeight: 600,
              cursor: 'pointer',
            }}
          >
            Limpiar Fallidos
          </button>
          <button
            onClick={() => setShowPanel(false)}
            style={{
              padding: '10px 16px',
              background: 'var(--color-gray-100)',
              color: 'var(--color-text-primary)',
              border: '1px solid var(--color-border-default)',
              borderRadius: '8px',
              fontWeight: 600,
              cursor: 'pointer',
            }}
          >
            Cerrar
          </button>
        </div>
      </div>
    );
  };
