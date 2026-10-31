# Pendientes de coordinación del contrato de ocupación

Registro de acuerdos de integración del endpoint `GET /api/v1/occupancy`. El endpoint ya vive en
Laravel; la tabla conserva la decisión resuelta y los puntos que todavía requieren coordinación.

| # | Acuerdo | Depende de | Estado |
|---:|---|---|---|
| 1 | Publicar y aplicar el permiso `ocupacion.ver` | `config/rbac.php`, ruta y pruebas | Resuelto |
| 2 | Confirmar el formato definitivo de `X-Tenant-ID` y la lista de hospitales permitidos | Cindy (tenants) | Pendiente |
| 3 | Confirmar si el corte por defecto es la hora del servidor o un cierre de turno | Madelin (camas) | Pendiente |
| 4 | Adoptar un único formato común de errores y paginación en `/api/v1` | Lis (contratos) | Pendiente |
| 5 | Definir la capa común de filtros y exportación para no duplicarla en dashboards | Yamilet, Lis | Pendiente |
| 6 | Confirmar que `beds.status` sigue siendo estado operativo y no fuente autoritativa | Madelin, Axel (admisión) | Pendiente |
| 7 | Confirmar quién cierra `bed_stays` en traslados y altas | Yamilet | Pendiente |

## Riesgos

- `ocupacion.ver` ya protege la ruta; cualquier cambio futuro de nombre o matriz exige actualizar
  `config/rbac.php`, el middleware de la ruta y sus pruebas en el mismo cambio.
- Si el formato de `X-Tenant-ID` cambia, el adaptador PostgreSQL de la semana 5 debe
  ajustarse antes del mismo día del adaptador real.
