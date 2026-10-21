# Evidencia Hallazgo H-001: Contraste Countdown MFA
## Información General
- **ID**: H-001
- **Severidad**: Crítica
- **Componente**: MFA Challenge (TOTP)
- **Regla WCAG**: 1.4.3 Contraste Mínimo (AA)
- **Fecha Detección**: 2026-10-18
- **Detectado Por**: axe-core automatizado + Testing usuarios (32%)

## Descripción del Problema
El contador regresivo (countdown) del desafío MFA TOTP muestra el tiempo restante en color gris `#9E9E9E` sobre fondo blanco `#FFFFFF`, resultando en una relación de contraste de **2.81:1**, muy por debajo del mínimo requerido de **4.5:1** para texto normal (WCAG 2.1 AA).

## Evidencia Técnica
### Código Fuente (Antes)
```tsx
<span className="countdown" aria-live="polite">
  {timeRemaining}s
</span>
```
```css
.countdown {
  color: #9E9E9E; 
  font-weight: 600;
  font-size: 1.125rem;
}
```
### Resultado axe-core
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  VIOLATION: color-contrast                                                  │
│  Element: <span class="countdown">25s</span>                                │
│  Selector: .countdown                                                       │
│  Foreground: #9E9E9E (rgb 158, 158, 158)                                    │
│  Background: #FFFFFF (rgb 255, 255, 255)                                    │
│  Contrast Ratio: 2.81:1                                                     │
│  Required: 4.5:1 (AA) / 7:1 (AAA)                                           │
│  Impact: Critical                                                           │
│  Help: https://dequeuniversity.com/rules/axe/4.10/color-contrast            │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Captura Visual
```
┌─────────────────────────────────────────────────────────────────────────────┐
│   Autenticación de Doble Factor Requerida                                   │
│  ─────────────────────────────────────────────────────────────────────────  │
│   Método: Aplicación Autenticadora (TOTP)                                   │
│   Código expira en: 25 segundos  ████████████░░░░░░░░░░░░░░░░  (gris)       │
│  ┌─────────────────────────────────────────────────────────────┐            │
│  │  Código: [ 1 ][ 2 ][ 3 ][ 4 ][ 5 ][ 6 ]                     │            │
│  └─────────────────────────────────────────────────────────────┘            │
│  [ VERIFICAR CÓDIGO ]                                                       │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Testing Usuarios - Evidencia Cualitativa
| Usuario | Rol          | Comentario                                                  | Tiempo Completar MFA   |
|---------|--------------|-------------------------------------------------------------|------------------------|
| U-003   | MEDICO       | "El contador gris apenas se ve, no sé cuánto me queda"      | Falló (tiempo agotado) |
| U-007   | ENFERMERO    | "Tuve que acercar la cara a la pantalla para ver el tiempo" | Éxito (apurado)        |
| U-012   | PACIENTE     | "No vi el contador, pensé que tenía más tiempo"             | Falló (tiempo agotado) |
| U-018   | TENANT_ADMIN | "El contador es muy tenue, uso WebAuthn por eso"            | Éxito (usó WebAuthn)   |
**Estadísticas**: 8/25 usuarios (32%) reportaron problemas con visibilidad del countdown → 3 fallos directos por tiempo agotado.

## Corrección Propuesta
### Opción A: Color Gris Oscuro (Recomendado)
```css
.countdown {
  color: #666666;  
  font-weight: 600;
  font-size: 1.125rem;
  transition: color 0.3s ease;
}
.countdown.warning {
  color: #ED6C02;  
  animation: pulse 1s infinite;
}
.countdown.critical {
  color: #C62828;  
  animation: pulse 0.5s infinite;
}
```
### Opción B: Color Negro (Máximo Contraste)
```css
.countdown {
  color: #333333;  
}
```
### JavaScript - Lógica Countdown con Warning
```tsx
const { timeRemaining } = useCountdown(60); 
const getCountdownStyle = (seconds: number) => {
  if (seconds <= 10) return 'critical';
  if (seconds <= 20) return 'warning';
  return 'normal';
};
return (
  <span 
    className={`countdown ${getCountdownStyle(timeRemaining)}`}
    aria-live="polite"
    aria-atomic="true"
  >
    {timeRemaining}s
  </span>
);
```
## Verificación Post-Fix
### Test Automatizado (axe-core)
```bash
npm run test:a11y
```
### Test Manual - Contraste
```bash
```
### Test Manual - Screen Reader (NVDA)
```bash
# 1. Abrir NVDA
# 2. Navegar a MFA Challenge
# 2. Verificar: "25 segundos, contando regresivamente" anunciado
# 3. Verificar: Cambio a "warning" a 20s anunciado
# 4. Verificar: Cambio a "critical" a 10s anunciado
```
## Métricas Post-Fix Objetivo
| Métrica                             | Pre-Fix | Post-Fix Objetivo |
|-------------------------------------|---------|-------------------|
| Contraste countdown                 | 2.8:1   | ≥ 4.5:1           |
| Tasa éxito MFA (1er intento)        | 80%     | > 95%             |
| Fallos MFA por tiempo agotado       | 32%     | < 5%              |
| Violaciones axe-core color-contrast | 1       | 0                 |
