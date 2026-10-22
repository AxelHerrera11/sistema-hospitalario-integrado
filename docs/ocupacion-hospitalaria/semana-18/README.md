# Dashboard de ocupación hospitalaria - Semana 18

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Entrega:** evaluación final y defensa. Demo funcional y evidencia consolidada, con pendientes
  técnicos y validación humana declarados.

- [Trazabilidad de las 18 semanas](./trazabilidad-semanas.md)
- [Guion de defensa y demo funcional](./guion-defensa.md)
- [Declaración de IA](./DECLARACION_IA.md)

## Qué entrega el módulo

Una consulta agregada y temporal de la ocupación hospitalaria por sala y estado de cama, con
discrepancias visibles. El módulo **no** asigna, traslada ni libera camas: informa.

| Pieza | Ubicación en `develop` | Estado |
|---|---|---|
| Micro-HIS por capas (Presentation, Application, Domain, Persistence) | `micro-his/ocupacion-hospitalaria/` | Integrado (PR #171) |
| Contrato API de integración | `docs/ocupacion-hospitalaria/contrato-api.md` | Integrado (PR #342) |
| Endpoint `GET /api/v1/occupancy` en Laravel | `app/Http/Controllers/Api/V1/OccupancyController.php` | Integrado (PR #574) |
| Cableado del Micro-HIS al HIS | `app/Providers/OcupacionServiceProvider.php` | Integrado (PR #574) |
| Pantalla Vue del dashboard | `resources/js/modules/ocupacion/` | Integrado (PR #575) |
| Matriz de amenazas, pruebas de permisos y bitácora de despliegue | `docs/ocupacion-hospitalaria/semana-17/` | Integrado (PR #579) |
| Semántica de ocupación verificada en la base | `tests/Database/Hospital/OccupancySnapshotIntegrityTest.php` | Integrado (PR #574) |

## Demo funcional

Recorrido corto, reproducible en cualquier entorno con PostgreSQL 16 y la imagen de pruebas del
equipo:

```bash
# 1. Verificaciones del dominio y la arquitectura
php micro-his/ocupacion-hospitalaria/tests/run.php

# 2. Contrato HTTP e integridad de la ocupación contra PostgreSQL real
php artisan test tests/Feature/Ocupacion tests/Database/Hospital/OccupancySnapshotIntegrityTest.php

# 3. Utilidades de la pantalla Vue
node --test resources/js/modules/ocupacion/ocupacion.test.mjs

# 4. Prototipo navegable (opcional, sin conexión con la API)
php -S 127.0.0.1:8099 -t docs/ocupacion-hospitalaria/semana-11/prototipo
```

Resultado registrado el 21 de octubre de 2026 sobre `develop` en `75bd69ce`:

| Comando | Resultado |
|---|---|
| Verificaciones del Micro-HIS | `OK: 12 verificaciones aprobadas` |
| Suite HTTP e integridad | `Tests: 22 passed (125 assertions)` |
| Utilidades de la pantalla | `OK: 14 aserciones` |

El guion completo de la defensa, con el cambio solicitado y las preguntas esperadas, está en
[guion-defensa.md](./guion-defensa.md).

## Evidencia completa

La evidencia del módulo se reparten en las entregas de cada semana, con salidas de comandos
reales:

| Tipo de evidencia | Dónde |
|---|---|
| Diagramas y narrativas | `semana-01/`, `semana-02/`, `semana-06/`, `semana-07/`, `semana-08/`, `semana-10/`, `semana-13/`, `semana-14/` |
| Pruebas ejecutadas y sus salidas | `semana-03/`, `semana-04/`, `semana-05/`, `semana-09/`, `semana-11/`, `semana-12/`, `semana-15/` |
| Capturas de escritorio y móvil | `semana-11/capturas/` |
| Registros de defectos | `semana-15/defectos.md` |
| Bitácora de despliegue con respuestas reales | `semana-17/bitacora-despliegue.md` |
| Matriz de amenazas y pruebas de permisos | `semana-17/matriz-threats.md` |

## Reglas del módulo que la defensa debe sostener

1. La ocupación se deriva del **intervalo** de la estancia (`started_at <= T` y `ended_at` nulo o
   posterior a `T`), nunca de `beds.status`.
2. Cada cama se cuenta **exactamente una vez**, y la suma de categorías cuadra con el inventario.
3. Una discrepancia entre estancia y estado operativo **se muestra**, no se corrige ni se oculta.
4. Una falla de persistencia responde `503` y **nunca** ocupación cero.
5. El módulo es de **solo lectura** y no expone datos de pacientes.
6. La autorización se comprueba **antes** de leer: JWT, tenant y permiso `ocupacion.ver`.
7. No se extrae un microservicio sin métricas que justifiquen la autonomía.

## Pendientes al cierre de la entrega

| # | Estado | Pendiente o decisión | Responsable | Evidencia |
|---:|---|---|---|---|
| 1 | Resuelto | Permiso `ocupacion.ver` publicado y aplicado | ASII-02 | `config/rbac.php`, `routes/api.php`, `RoleMatrixTest` y `OccupancyEndpointTest` |
| 2 | Pendiente | Formato común de errores entre el endpoint y el middleware RBAC | Lis | PR #574 |
| 3 | Pendiente | Lista blanca de hospitales permitidos | ASII-25 | PR #579 |
| 4 | Pendiente | Rate limiting de `/api/v1` | ASII-30 | PR #579 |
| 5 | Pendiente | Capa común de filtros y exportación para no duplicarla entre dashboards | Yamilet y Lis | Issue #36 |
| 6 | Pendiente | Filtro por sala (RF-OCC-05) sin implementar | ASII-09 (propuesta R-3) | Semana 13 |
| 7 | Pendiente | Contraste y reflow sin medición de navegador | ASII-09 | Semanas 9 y 14 |
| 8 | Pendiente | Limpiar la fotografía previa en memoria tras una respuesta `401/403` | ASII-09 | Semana 12 |

Los puntos 2 a 5 son acuerdos con el equipo. Los puntos 6 a 8 son deuda técnica declarada del
propio módulo; no se presentan como funcionalidades ya implementadas.

## Límites de esta entrega

- La defensa oral, la resolución en vivo del cambio solicitado y la calificación requieren
  ejecución humana: este documento no se autoatribuye una aprobación.
- No se ejecutó lector de pantalla ni certificación WCAG.
- No se midió el comportamiento con 10 000 camas: se reporta «sin datos».
