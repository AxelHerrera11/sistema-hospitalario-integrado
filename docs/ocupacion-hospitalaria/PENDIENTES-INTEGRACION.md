# Pendientes de coordinación del contrato de ocupación

Acuerdos que deben cerrarse con el equipo antes de que el endpoint `GET /api/v1/occupancy`
viva en Laravel. Son los puntos que el plan transversal del 18 de octubre deja abiertos
y que bloquean la entrega del jueves 22 a las 12:00.

| # | Acuerdo | Depende de | Estado |
|---:|---|---|---|
| 1 | Crear el permiso `ocupacion.ver` en el catálogo de RBAC | María (RBAC) | Pendiente |
| 2 | Confirmar el formato definitivo de `X-Tenant-ID` y la lista de hospitales permitidos | Cindy (tenants) | Pendiente |
| 3 | Confirmar si el corte por defecto es la hora del servidor o un cierre de turno | Madelin (camas) | Pendiente |
| 4 | Adoptar un único formato común de errores y paginación en `/api/v1` | Lis (contratos) | Pendiente |
| 5 | Definir la capa común de filtros y exportación para no duplicarla en dashboards | Yamilet, Lis | Pendiente |
| 6 | Confirmar que `beds.status` sigue siendo estado operativo y no fuente autoritativa | Madelin, Axel (admisión) | Pendiente |
| 7 | Confirmar quién cierra `bed_stays` en traslados y altas | Yamilet | Pendiente |

## Riesgos

- Si el permiso `ocupacion.ver` no existe al momento de integrar la ruta, el endpoint
  quedaría sin autorización y no podría pasar la regresión manual del jueves.
- Si el formato de `X-Tenant-ID` cambia, el adaptador PostgreSQL de la semana 5 debe
  ajustarse antes del mismo día del adaptador real.
