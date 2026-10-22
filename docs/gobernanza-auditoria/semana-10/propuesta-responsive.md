# Semana 10 — Propuesta responsive

> El proyecto **no tiene una aplicación móvil nativa** para este módulo: la SPA es Vue 3 y NativePHP es una investigación aparte (módulo de Hugo). Esta entrega es una **propuesta responsive** de la misma interfaz web. Lo marcado como «implementado» ya existe en el prototipo (`public/prototipo/styles.css`); lo marcado como «propuesto» no.

## 1. Puntos de corte

| Ancho | Distribución | Estado |
|---|---|---|
| > 960 px (escritorio) | Dos columnas: filtros (260–340 px) y resultados en tabla. | Implementado |
| 601–960 px (tableta) | Una columna; filtros en una cuadrícula de 2 columnas sobre los resultados. | Implementado |
| ≤ 600 px (móvil) | Un campo por fila, botones al ancho completo y tabla convertida en **tarjetas** (`td::before` con `data-label`). | Implementado |
| ≤ 600 px (móvil) | Filtros **plegables** (`<details>`) con el resumen «Filtros (N activos)», cerrados después de buscar. | **Propuesto** (B-01, hallazgo F-01) |
| Cualquiera, con 401/403 | Sin filtros; el mensaje ocupa todo el ancho. | Implementado (H-GOB-10) |

## 2. Pantallas representativas

### P1 · Móvil — resultados como tarjetas (implementado)

```
┌───────────────────────────┐
│ Bitácora de auditoría     │
│ 52 eventos · Pág. 1 de 3  │  ← role=status
│ ┌───────────────────────┐ │
│ │ Fecha y hora          │ │
│ │ 15/10/2026 23:09      │ │
│ │ Usuario               │ │
│ │ aaaaaaaa-…-001        │ │
│ │ Evento                │ │
│ │ Sesión revocada       │ │
│ │ Resultado  [Ocurrió]  │ │
│ │ [ Ver detalle ]       │ │  ← objetivo ≥ 44 px
│ └───────────────────────┘ │
│ [Anterior]  1/3 [Siguiente]│
└───────────────────────────┘
```

Evidencia real a 375 px: [s11-04-movil-375-tarjetas.jpg](../evidencias/s11-04-movil-375-tarjetas.jpg).

### P2 · Móvil — filtros plegables (propuesto)

```
┌───────────────────────────┐
│ ▸ Filtros (2 activos)     │  ← <details><summary>
│   módulo: rbac · denied   │
├───────────────────────────┤
│ 13 eventos · Pág. 1 de 1  │
│ …tarjetas…                │
└───────────────────────────┘
   al abrir ▾:
│ Usuario  [____________]   │
│ Módulo   [rbac        ]   │
│ …                         │
│ [Buscar] (cierra el panel │
│  y mueve el foco a        │
│  "Resultados")            │
```

### P3 · Móvil — error de validación (implementado)

```
┌───────────────────────────┐
│ ▣ Revise los filtros:     │  role=alert, recibe el foco
│ • Hasta debe ser igual o  │
│   posterior a «Desde».    │  ← enlace al campo
│ Hasta [15/10/2026]  ⚠     │
│ Error: Hasta debe ser…    │
└───────────────────────────┘
```

Con la propuesta P2, el panel debe **abrirse automáticamente** si hay un 422.

### P4 · Móvil — sin resultados y servicio no disponible (implementado)

```
┌───────────────────────────┐   ┌───────────────────────────┐
│ Sin resultados            │   │ Servicio no disponible    │
│ Ningún evento coincide…   │   │ … Referencia: req-…       │
│ Amplíe el rango de fechas │   │ (sin tabla ni paginación) │
└───────────────────────────┘   └───────────────────────────┘
```

### P5 · Detalle en el móvil (implementado)

El diálogo usa `max-width: calc(100vw - 2rem)` y la lista `dl` pasa a una columna: cada etiqueta queda sobre su valor.

## 3. Tabla o listado adaptable

| Decisión | Motivo |
|---|---|
| Por encima de 600 px, la tabla real (`table`, `th scope`) va dentro de `.table-wrap { overflow-x: auto }`. | Comparar filas es la tarea principal del auditor. |
| Hasta 600 px, las tarjetas se hacen con CSS sobre la **misma** tabla (sin duplicar HTML). `thead` queda oculto visualmente, pero sigue disponible para los lectores. | Hay una sola fuente de datos y un solo orden del foco. |
| En las tarjetas solo van 5 campos (fecha, usuario, evento, recurso y resultado); el resto queda en el detalle. | Reduce el desplazamiento. |
| Los identificadores largos usan `word-break: break-all`. | Evita el desplazamiento horizontal (comprobado: `scrollWidth = 375`). |

## 4. Consideraciones de accesibilidad

- Objetivos táctiles de 44 px o más; foco visible de 3 px; `prefers-reduced-motion` respetado.
- Los campos `type=date` nativos muestran el selector del sistema operativo en el móvil.
- Pendientes (Semana 9): F-06 (zona horaria visible) y F-07 (anuncio completo de los errores).

## 5. Escenarios de uso por rol

| Rol | Dispositivo | Escenario | Pantallas |
|---|---|---|---|
| `superadmin` | Escritorio | Revisar las denegaciones de RBAC de la última semana: `modulo=rbac`, `resultado=denied`, rango de fechas. | W1, W2 |
| `superadmin` | Móvil (de guardia) | Un usuario reporta «acceso denegado, referencia trace-…»: se busca por `correlacion` y se abre el detalle. | P1, P5 |
| `admin_hospital` (si recibe el permiso) | Tableta | Verificar quién revocó sesiones hoy: `modulo=sesiones`, `desde=hasta=hoy`. | P1 (tableta) |
| `medico` | Cualquiera | Entra por URL → acceso denegado, sin filtros ni datos. | W5 |
