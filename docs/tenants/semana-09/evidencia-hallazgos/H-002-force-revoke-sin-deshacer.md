# Evidencia Hallazgo H-002: Force-Revoke sin "Deshacer" ni Doble Confirmación Mobile
## Información General
- **ID**: H-002
- **Severidad**: Crítica
- **Componente**: Admin - Force-Revoke Sesiones
- **Heurística Nielsen**: #3 Control y Libertad + #5 Prevención Errores
- **Fecha Detección**: 2026-10-18
- **Detectado Por**: Heurística + Testing Usuarios (24%)

## Descripción del Problema
La acción de "Revocar TODAS las sesiones" (Force-Revoke) es irreversible y de alto impacto (afecta seguridad, productividad, confianza), pero carece de:
1. **Mecanismo "Deshacer" inmediato** (toast con action "Deshacer" por 5-10 segundos)
2. **Doble confirmación en mobile** (botones pegados, touch targets pequeños)
3. **Feedback claro de consecuencias** antes de ejecutar

## Evidencia Técnica
### Código Actual (Problemático)
```tsx
// AdminUserList.tsx - Modal Force-Revoke
<Dialog open={open} onClose={handleClose} aria-labelledby="revoke-title">
  <DialogTitle id="revoke-title">🔴 Revocar Sesiones - {userName}</DialogTitle>
  <DialogContent>
    <Alert icon="⚠️">
      Se cerrarán TODAS las sesiones de {userName} en 3 dispositivos.
      El usuario deberá iniciar sesión nuevamente.
    </Alert>
    <FormControl>
      <InputLabel id="reason-label">Motivo *</InputLabel>
      <Select labelId="reason-label" value={reason} onChange={handleReasonChange}>
        <MenuItem value="security">Seguridad</MenuItem>
        <MenuItem value="role_change">Cambio de rol</MenuItem>
        <MenuItem value="compromise">Sospecha compromiso</MenuItem>
        <MenuItem value="device_lost">Dispositivo perdido</MenuItem>
      </Select>
    </FormControl>
    <TextField label="Comentario (opcional)" multiline rows={2} value={comment} onChange={handleCommentChange} />
    <Alert severity="warning">Esta acción es IRREVERSIBLE.</Alert>
  </DialogContent>
  <DialogActions>
    <Button onClick={handleClose} variant="secondary">Cancelar</Button>
    <Button onClick={handleConfirm} variant="danger" color="error">
      REVOCAR SESIONES
    </Button>
  </DialogActions>
</Dialog>
```

### CSS Mobile (Problemático)
```css
/* AdminUserList.css - Mobile */
@media (max-width: 480px) {
  .revoke-modal .MuiDialogActions-root {
    flex-direction: row;
    gap: 0;  /* SIN GAP - BOTONES PEGADOS */
    padding: 8px;
  }
  .revoke-modal .MuiButton-root {
    min-height: 40px;  /* MENOR A 44px REQUERIDO */
    min-width: 80px;
    padding: 6px 12px;
    font-size: 0.875rem;
  }
}
```
## Evidencia Visual - Mobile (320px)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  🔴 Revocar Sesiones - Dra. Torres                    [×] Cerrar (ESC)      │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ⚠️ Revocar TODAS las sesiones de Dra. Torres                              │
│  Se cerrarán sesión en TODOS los dispositivos (3 activos):                 │
│    📱 iPhone 15 Pro (iOS) - Chrome 119  -  Hoy 08:30                      │
│    💻 MacBook Pro (macOS) - Safari 17  -  Ayer 14:22                      │
│    🔐 YubiKey 5C NFC (Passkey) -  Hace 2 horas                             │
│  Motivo *:  ▼ [ Seguridad ▼ ]  [ Cambio de rol ]  [ Sospecha ]           │
│  Comentario: [____________________________________________________]        │
│  ⚠️ Esta acción es IRREVERSIBLE. Se registrará en auditoría.              │
│  ─────────────────────────────────────────────────────────────────────────  │
│  [ Cancelar ]  [ REVOCAR SESIONES ]  ← BOTONES PEGADOS, 40px height       │
└─────────────────────────────────────────────────────────────────────────────┘
PROBLEMAS IDENTIFICADOS:
❌ Botones pegados (gap: 0px) → Click accidental fácil
❌ Touch targets: 40x40px (requiere ≥ 44x44px WCAG)
❌ Gap: 0px (requiere ≥ 12px Material Design)
❌ Sin "Deshacer" tras ejecución
❌ Mobile: Botón "Revocar" al lado de "Cancelar" → click accidental
```
## Testing Usuarios - Evidencia Cuantitativa
| Métrica                                    | Resultado  | Detalle                                              |
|--------------------------------------------|------------|------------------------------------------------------|
| **Usuarios que dudaron antes de clickear** | 6/25 (24%) | "Me dio miedo clicar 'Revocar', no hay vuelta atrás" |
| **Click accidental en testing**            | 2/25 (8%)  | 2 usuarios clickearon "Revocar" en vez de "Cancelar" |
| **Usuarios que pidieron "Deshacer"**       | 6/25 (24%) | "Me dio miedo clicar, no hay vuelta atrás"           |
| **Tiempo decisión Force-Revoke**           | 41s (p50)  | Objetivo: < 30s                                      |

### Comentarios Usuarios Textuales
> **U-004 (TENANT_ADMIN)**: *"Me dio miedo clicar 'Revocar', no hay vuelta atrás. Debería haber un '¿Estás seguro?' más fuerte o un 'Deshacer'."*
> **U-011 (TENANT_ADMIN)**: *"En el móvil los botones están muy juntos. Por poco le doy a 'Revocar' en vez de 'Cancelar'."*
> **U-019 (TENANT_ADMIN)**: *"Si me equivoco, el usuario pierde todo. Necesito un 'Deshacer' inmediato."*

## Testing Automatizado - Casos de Prueba
### Cypress Test - Force-Revoke Mobile
```javascript
describe('Admin Force-Revoke - Mobile', () => {
  beforeEach(() => {
    cy.loginAsTenantAdmin();
    cy.visit('/admin/users');
    cy.viewport('iphone-x'); 
  });
  it('debe tener touch targets ≥ 44px y gap ≥ 12px en modal Force-Revoke', () => {
    cy.get('[data-testid="user-row"]').first().find('[data-testid="actions-menu"]').click();
    cy.contains('Revocar TODAS las sesiones').click();
        cy.get('.revoke-modal .MuiButton-root')
      .should('have.length', 2)
      .each(($btn) => {
        expect($btn).to.have.css('min-height', '44px');
        expect($btn).to.have.css('min-width', '44px');
      });
        cy.get('.revoke-modal .MuiDialogActions-root')
      .should('have.css', 'gap', '12px');
  });
  it('debe mostrar toast con "Deshacer" tras revocación', () => {
    cy.get('[data-testid="user-row"]').first().find('[data-testid="actions-menu"]').click();
    cy.contains('Revocar TODAS las sesiones').click();
    cy.get('[data-testid="revoke-reason"]').select('Seguridad');
    cy.contains('REVOCAR SESIONES').click();
        cy.get('[role="status"]')
      .should('contain', 'sesiones revocadas')
      .and('contain', 'Deshacer')
      .click(); 
    cy.get('[role="status"]').should('contain', 'Revocación deshecha');
  });
  it('debe requerir doble confirmación en mobile', () => {
    cy.get('[data-testid="user-row"]').first().find('[data-testid="actions-menu"]').click();
    cy.contains('Revocar TODAS las sesiones').click();
    cy.contains('REVOCAR SESIONES').click();
    cy.get('[role="alert"]')
      .should('contain', 'Confirmar revocación')
      .contains('Confirmar').click(); });});
```
## Corrección Implementada (Post-Fix)
### 1. Toast con "Deshacer" (5 segundos)
```tsx
const useToast = () => {
  const showRevokeToast = (userId: string, userName: string, onUndo: () => void) => {
    toast.success(`${revokedCount} sesiones revocadas para ${userName}`, {
      action: {
        label: 'Deshacer',
        onClick: () => {
          onUndo(userId);
          toast.dismiss(toastId);
        },
      },
      duration: 5000, 
      dismissible: true, }); }; return { showRevokeToast }; };

const { showRevokeToast } = useToast();
const handleConfirm = async () => {
  const revokedCount = await revokeAllSessions(userId, reason, comment);
  showRevokeToast(userId, userName, async () => {
    await undoRevoke(userId); 
  });
  await auditLog.log(REVOKE_SESSION, { userId, reason, revokedCount, undone: false });
};
```
### 2. Doble Confirmación Mobile
```tsx
const ForceRevokeModal = ({ user, onConfirm, onCancel }) => {
  const [step, setStep] = useState<'confirm' | 'execute'>('confirm');
  const isMobile = useMediaQuery('(max-width: 480px)');
  if (step === 'confirm') {
    return (
      <Dialog open onClose={onCancel} aria-labelledby="confirm-title">
        <DialogTitle id="confirm-title">⚠️ Confirmar Revocación</DialogTitle>
        <DialogContent>
          <Typography>¿Revocar TODAS las sesiones de <strong>{user.name}</strong>?</Typography>
          <Typography variant="body2" color="text.secondary">
            Se cerrarán {user.activeSessions} sesiones activas en todos los dispositivos.
          </Typography>
        </DialogContent>
        <DialogActions>
          <Button onClick={onCancel} variant="outlined">Cancelar</Button>
          <Button 
            onClick={() => setStep('execute')} 
            variant="contained" 
            color="error"
            autoFocus>
            Continuar →
          </Button>
        </DialogActions>
      </Dialog>
    );
  }
  return (
    <Dialog open onClose={onCancel} aria-labelledby="execute-title">
      <DialogTitle id="execute-title">🔴 Ejecutar Revocación</DialogTitle>
      <DialogContent>
        <FormControl fullWidth margin="normal">
          <InputLabel id="reason-label">Motivo *</InputLabel>
          <Select labelId="reason-label" value={reason} onChange={handleReasonChange}>
            <MenuItem value="security">Seguridad</MenuItem>
            <MenuItem value="role_change">Cambio de rol</MenuItem>
            <MenuItem value="compromise">Sospecha compromiso</MenuItem>
            <MenuItem value="device_lost">Dispositivo perdido</MenuItem>
          </Select>
        </FormControl>
        <Alert severity="warning">Esta acción se puede deshacer en los próximos 5 segundos.</Alert>
      </DialogContent>
      <DialogActions style={{ gap: '12px', flexWrap: 'wrap' }}>
        <Button onClick={() => setStep('confirm')} variant="outlined" size="large" style={{ minHeight: '48px', minWidth: '120px' }}>
          ← Volver
        </Button>
        <Button 
          onClick={handleExecute} 
          variant="contained" 
          color="error" 
          size="large"
          style={{ minHeight: '48px', minWidth: '120px' }}
        >
          REVOCAR AHORA
        </Button>
      </DialogActions>
    </Dialog>
  );
};
```
### 3. CSS Mobile - Touch Targets Accesibles
```css
@media (max-width: 480px) {
  .revoke-modal .MuiDialogActions-root {
    flex-direction: column;
    gap: 12px;
    padding: 16px;
  }
  .revoke-modal .MuiButton-root {
    min-height: 48px;      
    min-width: 120px;
    padding: 12px 24px;
    font-size: 1rem;
    font-weight: 600;
  }
  .revoke-modal .MuiButton-containedError {
    background-color: #C62828;
    &:hover {
      background-color: #B71C1C;
    }
  }
  .revoke-modal .MuiButton-outlined {
    border-color: #757575;
    color: #212121;
  }
  .toast-mobile .MuiSnackbarContent-root {
    flex-direction: column;
    align-items: stretch;
    padding: 16px;
    min-width: 280px;
  }
  .toast-mobile .toast-action {
    margin-top: 12px;
    width: 100%;
  }
}
```
## Verificación Post-Fix
### Checklist de Validación
- [ ] **Touch targets ≥ 44x44px** en todos los botones modal mobile
- [ ] **Gap ≥ 12px** entre botones (Material Design)
- [ ] **Toast con "Deshacer"** visible 5 segundos
- [ ] **Doble confirmación** en mobile (Paso 1: Confirmar → Paso 2: Ejecutar)
- [ ] **Accesibilidad**: Focus trap en modal, Escape cierra, Tab cicla
- [ ] **Screen Reader**: Anuncia "Revocación ejecutada. Deshacer disponible 5 segundos"
- [ ] **Undo funcional**: Click "Deshacer" → sesiones restauradas → auditoría actualizada

### Test Cypress Post-Fix
```javascript
describe('Force-Revoke Post-Fix Validation', () => {
  it('debe permitir deshacer revocación en 5 segundos', () => {
    cy.loginAsTenantAdmin();
    cy.visit('/admin/users');
    cy.viewport('iphone-x');
    cy.get('[data-testid="user-row"]').first().find('[data-testid="actions-menu"]').click();
    cy.contains('Revocar TODAS las sesiones').click();
    cy.get('[data-testid="revoke-reason"]').select('Seguridad');
    cy.contains('Continuar →').click(); 
    cy.contains('REVOCAR AHORA').click(); 
    cy.get('[role="status"]')
      .should('contain', 'sesiones revocadas')
      .and('contain', 'Deshacer')
      .click(); 
    cy.get('[role="status"]').should('contain', 'Revocación deshecha');
    cy.request('/api/audit/events?user_id=...').then((res) => {
      const events = res.body.events;
      expect(events).to.have.length.greaterThan(1);
      expect(events).to.contain.event('SESSION_RESTORED');
    });
  });
});
```
## Métricas Post-Fix Objetivo
| Métrica                              | Pre-Fix | Post-Fix Objetivo        |
|--------------------------------------|---------|--------------------------|
| Click accidental Force-Revoke mobile | 8%      | 0%                       |
| Usuarios que piden "Deshacer"        | 24%     | 0% (disponible)          |
| Tiempo decisión Force-Revoke         | 41s     | < 30s (con flujo guiado) |
| Touch target compliance              | 0%      | 100%                     |
| Accesibilidad mobile                 | Parcial | 100%                     |
