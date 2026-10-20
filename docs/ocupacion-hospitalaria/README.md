# ASII-09 — Dashboard de ocupación hospitalaria

Contrato de integración publicado para coordination con el equipo. Este README
indexa el contrato vigente; las entregas semanales permanecen en
`semana-01/`, `semana-02/` y `semana-03/` dentro de este mismo directorio.

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** `ASII-09 — Dashboard de ocupación hospitalaria`
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Contrato API:** [contrato-api.md](./contrato-api.md)
- **Pendientes de coordinación:** [PENDIENTES-INTEGRACION.md](./PENDIENTES-INTEGRACION.md)

## Estado

| Elemento | Estado |
|---|---|
| Contrato API de ocupación | Publicado en este PR, pendiente de validación del equipo |
| Endpoint en Laravel | No implementado; el módulo PHP de las semanas 3 y 5 aún no está integrado |
| Permiso `ocupacion.ver` | Propuesto; no existe aún en el RBAC de `develop` |
| Migraciones | No requiere tablas nuevas; usa `wards`, `beds`, `bed_stays` y `care_episodes` |

## Dependencias funcionales

- ASII-06 (salas y camas): Altas, cambios de estado y baja de camas.
- ASII-07 (admisión): Apertura y cierre de episodes de cuidado.
- ASII-08 (traslados y altas): Cierre y traslado de `bed_stays`.
- Auth/RBAC/tenant: Validación de JWT, hospital y permiso antes del caso de uso.

## Pendientes acordados con el equipo

1. Crear el permiso `ocupacion.ver` y publicarlo en el RBAC común.
2. Confirmar el formato de `X-Tenant-ID` y la lista de hospitales permitidos.
3. Confirmar si el corte por defecto es la hora del servidor o un cierre de turno.
4. Validar este contrato antes del PR de la semana 5 con la API en Laravel.
