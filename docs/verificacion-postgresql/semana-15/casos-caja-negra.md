# Casos límite de caja negra del contrato de ocupación (Semana 15)

## 1. Caja negra: casos límite del contrato

Los casos de caja negra se escriben contra el **contrato publicado** del módulo de
ocupación (`GET /api/v1/occupancy`): entradas, respuestas y errores.

| Id | Entrada | Respuesta esperada |
|---|---|---|
| CN-01 | Sin `Authorization` | `401 UNAUTHENTICATED` |
| CN-02 | Token válido sin `ocupacion.ver` | `403 FORBIDDEN` |
| CN-03 | `X-Tenant-ID` de otro hospital | `403 FORBIDDEN` |
| CN-04 | `cutoff_at` sin zona horaria | `400 INVALID_CUTOFF` |
| CN-05 | `cutoff_at` con formato inválido | `400 INVALID_CUTOFF` |
| CN-06 | `cutoff_at` ausente | `200` con el corte del servidor en `meta` |
| CN-07 | Método `POST` | `405 METHOD_NOT_ALLOWED` |
| CN-08 | Ruta inexistente | `404 NOT_FOUND` |
| CN-09 | Base HOSPITAL caída | `503 OCCUPANCY_UNAVAILABLE`, nunca ceros |
| CN-10 | Sala sin camas | `200` con lista vacía, no error |
| CN-11 | Cama ocupada y `status = 'available'` | `200` con inconsistencia visible |
| CN-12 | Corte exactamente en `ended_at` | La cama figura libre |

**Estado de ejecución:** la ruta `GET /api/v1/occupancy` **todavía no está
implementada en Laravel**, por lo que CN-01 a CN-12 no se pudieron ejecutar contra
HTTP. Lo que sí está ejecutado es la parte de caja blanca que se apoya en las mismas
reglas: los casos CN-11 y CN-12 se verifican en
`OccupancySnapshotIntegrityTest` (`test_operational_status_does_not_change_the_interval_derived_occupancy`
y `test_bed_is_occupied_only_inside_the_stay_interval`).

Los casos de caja negra se entregarán al módulo de ocupación cuando exista la ruta,
como prueba de contrato reutilizable.
