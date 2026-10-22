# Semana 8 - Flujo UX por rol

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Entrega:** user flow, wireframes iniciales y reglas de interacción

## Objetivo UX

Permitir decisiones operativas y directivas con datos agregados, corte temporal visible y discrepancias explícitas. La interfaz nunca permite asignar o liberar camas, nunca muestra pacientes y nunca transforma una falla en ocupación cero.

- [User flow editable](./fuentes/01-user-flow.mmd)
- [Declaración de IA](./DECLARACION_IA.md)

## Roles

| Rol | Necesidad | Vista y acciones permitidas |
|---|---|---|
| Coordinación de camas | Detectar capacidad e inconsistencias por sala | Resumen, filtro de sala, fecha de corte, detalle agregado y recarga |
| Dirección hospitalaria | Observar ocupación global y distribución | Indicadores globales y comparación visual por sala; sin acciones operativas |
| Sin `ocupacion.ver` | Ninguna cifra autorizada | Acceso denegado; no se monta ni precarga el dashboard |

## Flujo principal

1. El usuario entra al dashboard; el router comprueba sesión, tenant y permiso.
2. La vista muestra estructura de carga y solicita la fotografía actual.
3. En éxito, muestra corte, antigüedad, indicadores y salas.
4. Coordinación puede filtrar una sala y revisar discrepancias agregadas.
5. Dirección conserva la lectura global sin controles operativos.
6. Ante `503`, se muestra la última fotografía solo si existe, rotulada como desactualizada; si no existe, se ofrece reintentar.
7. Ante `401/403`, se elimina cualquier dato previo y se muestra acceso denegado.

## Wireframe 1 - Carga

```text
┌ Ocupación hospitalaria ──────────────────────────────┐
│ Actualizando fotografía...              [10:00:00]  │
│ [████████] [████████] [████████] [████████]          │
│ Sala                    Ocupadas       Disponibles   │
│ ██████████████████████████████████████████████████   │
│ No se muestran cifras anteriores como si fueran nuevas│
└──────────────────────────────────────────────────────┘
```

## Wireframe 2 - Éxito para coordinación

```text
┌ Ocupación hospitalaria ─ Corte 14/10 10:00 ─────────┐
│ Sala [Todas ▼]                           [Actualizar]│
│ Ocupadas 12 │ Disponibles 6 │ Mant. 1 │ Tasa 66.7% │
│ ⚠ 2 inconsistencias operativas                       │
├ Sala ─────── Ocup. Disp. Mant. No verif. Tasa ──────┤
│ MED              8     4     1       1      66.7%   │
│ UCI              4     2     0       0      66.7%   │
└──────────────────────────────────────────────────────┘
```

La advertencia usa icono, texto y cantidad; no depende solo del color. El detalle explica la regla, pero no expone cama, paciente ni episodio en esta vista agregada.

## Wireframe 3 - Dirección hospitalaria

```text
┌ Resumen directivo ─ Corte 14/10 10:00 ──────────────┐
│ Tasa global 66.7%      Capacidad operativa 18       │
│ MED  [█████████████░░░░░░] 66.7%                    │
│ UCI  [█████████████░░░░░░] 66.7%                    │
│ Datos agregados · sin identificación de pacientes    │
└──────────────────────────────────────────────────────┘
```

## Wireframe 4 - Sin resultados

```text
┌ Ocupación hospitalaria ──────────────────────────────┐
│ No hay camas para la sala y el corte seleccionados.  │
│ Pruebe otra sala o restablezca el filtro.             │
│                     [Ver todas las salas]             │
└───────────────────────────────────────────────────────┘
```

Un resultado vacío es distinto de una falla: total `0` solo se presenta tras una respuesta `200` válida para el filtro solicitado.

## Wireframe 5 - Servicio no disponible

```text
┌ ⚠ No pudimos actualizar la ocupación ────────────────┐
│ Última fotografía: 14/10 09:45 · hace 15 minutos     │
│ DATOS DESACTUALIZADOS                    [Reintentar] │
│ Ocupadas 11 │ Disponibles 7 │ ...                    │
└───────────────────────────────────────────────────────┘
```

Si no existe fotografía previa, las tarjetas se ocultan. Nunca se reemplazan con ceros. Los reintentos son manuales después del único reintento automático transitorio.

## Wireframe 6 - Acceso denegado

```text
┌ Acceso restringido ───────────────────────────────────┐
│ No tiene permiso para consultar ocupación hospitalaria│
│                         [Volver al inicio]             │
└───────────────────────────────────────────────────────┘
```

No aparecen totales, nombres de salas ni datos almacenados de una sesión anterior.

## Reglas de interacción

1. Mostrar siempre hospital, fecha de corte y antigüedad de la fotografía.
2. Conservar un solo filtro de sala por tenant; cambiar hospital limpia estado y caché.
3. Deshabilitar `Actualizar` durante una solicitud para evitar duplicados visuales.
4. Anunciar carga, error y actualización mediante región `aria-live="polite"`.
5. Mantener contraste mínimo WCAG AA y foco visible; no comunicar estados solo con color.
6. Formatear tasas con una cifra decimal, pero conservar conteos enteros originales.
7. Explicar `N/A` cuando no existe capacidad operativa; nunca mostrar `0 %` como sustituto.
8. Tratar inconsistencias como advertencias de lectura, sin botones de corrección.
9. En `401`, cerrar sesión; en `403`, limpiar datos y volver a una ruta autorizada.
10. Registrar request ID para soporte sin mostrar tokens, DSN o identificadores clínicos.

## Criterios de aceptación

- Cada rol tiene un camino explícito y mínimo privilegio.
- Existen estados de carga, éxito, vacío, desactualizado, error y acceso denegado.
- El usuario distingue dato actual, dato antiguo, ausencia real y falla técnica.
- Teclado, lectores de pantalla y usuarios que no perciben color reciben la misma información.
- Ningún wireframe permite modificar camas o estancias.

## Evidencia

```bash
npx --yes @mermaid-js/mermaid-cli -i docs/ocupacion-hospitalaria/semana-08/fuentes/01-user-flow.mmd -o /tmp/semana-08-user-flow.svg
php micro-his/ocupacion-hospitalaria/tests/run.php
git diff --check
```

## Conclusión

El flujo traduce las garantías del backend a señales comprensibles: corte visible, discrepancias explícitas, falla segura y aislamiento por rol y hospital. Los wireframes sirven como base verificable para las evaluaciones de usabilidad, responsive y prototipo de las siguientes semanas.
