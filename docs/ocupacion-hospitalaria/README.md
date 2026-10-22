# ASII-09 — Dashboard de ocupación hospitalaria

Contrato de integración publicado para coordination con el equipo. Este README
indexa el contrato vigente; las entregas semanales permanecen en
`semana-01/`, `semana-02/` y `semana-03/` dentro de este mismo directorio.

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** `ASII-09 — Dashboard de ocupación hospitalaria`
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Contrato API:** [contrato-api.md](./contrato-api.md)
- **Evaluación final:** [semana-18/README.md](./semana-18/README.md)
- **Pendientes de coordinación:** [PENDIENTES-INTEGRACION.md](./PENDIENTES-INTEGRACION.md)

## Estado

| Elemento | Estado |
|---|---|
| Contrato API de ocupación | Publicado y validado en la semana 16 |
| Endpoint en Laravel | Implementado en la semana 16 (`GET /api/v1/occupancy`, PR #574) |
| Permiso `ocupacion.ver` | Activado en la matriz compartida; pendiente de ratificación de ASII-02 |
| Pantalla Vue del dashboard | Entregada en la semana 16 (`resources/js/modules/ocupacion/`) |
| Migraciones | No requiere tablas nuevas; usa `wards`, `beds`, `bed_stays` y `care_episodes` |

## Dependencias funcionales

- ASII-06 (salas y camas): Altas, cambios de estado y baja de camas.
- ASII-07 (admisión): Apertura y cierre de episodes de cuidado.
- ASII-08 (traslados y altas): Cierre y traslado de `bed_stays`.
- Auth/RBAC/tenant: Validación de JWT, hospital y permiso antes del caso de uso.

## Entregas publicadas

Cada semana de Odoo tiene su entrega y su Pull Request:

- `semana-01/`, `semana-02/`, `semana-03/`: análisis, diseño ISP y Micro-HIS por capas.
- `semana-04/` a `semana-14/`: arquitectura en capas, cliente-servidor, evaluaciones parciales,
  componentes, UX, usabilidad, movilidad, prototipo, revisión técnica y plan SQA.
- `semana-15/`: pruebas unitarias, de caja blanca y de caja negra trazadas a los RF/RNF.
- `semana-16/`: integración en Laravel, pantalla Vue y evidencia de validación.
- `semana-17/`: matriz de amenazas, pruebas de permisos y bitácora de despliegue.
- `semana-18/`: evaluación final y defensa, con la trazabilidad de las 18 semanas.

El detalle de qué semana corresponde a qué Pull Request está en
[`semana-18/trazabilidad-semanas.md`](./semana-18/trazabilidad-semanas.md).

## Pendientes acordados con el equipo

1. **Ratificar** el permiso `ocupacion.ver`: ya está en el catálogo RBAC de `develop` y concedido
   a cuatro roles; falta la ratificación formal de ASII-02.
2. Confirmar la lista blanca de hospitales permitidos (ASII-25).
3. Confirmar si el corte por defecto es la hora del servidor o un cierre de turno: el endpoint usa
   la hora del servidor.
4. Adoptar un formato común de errores entre el endpoint y el middleware RBAC (Lis).
5. Definir el rate limiting de `/api/v1` (ASII-30).
6. Coordinar la capa común de filtros y exportación para no duplicarla entre dashboards
   (Yamilet y Lis).
