# Médicos, especialidades y disponibilidad - Semana 18

## Portada

- **Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`)
- **Módulo:** ASII-04 - Médicos, especialidades y disponibilidad
- **Entrega:** evaluación final y defensa. Demo funcional y evidencia consolidada, con pendientes técnicos declarados.

- [Trazabilidad de las 18 semanas](./trazabilidad-semanas.md)
- [Guion de defensa y demo funcional](./guion-defensa.md)
- [Declaración de IA](./DECLARACION_IA.md)

## Qué entrega el módulo

El alta de médicos con su especialidad principal, la asociación de especialidades con vigencia y la consulta de **disponibilidad verdadera** por especialidad y fecha. Este módulo no crea citas: informa qué franjas están libres para que Citas y Admisión las ofrezcan.

| Pieza | Ubicación | Estado |
|---|---|---|
| Micro-HIS por capas (Presentation, Application, Domain, Persistence) y Repository con adaptadores PDO e InMemory | `micro-his/medicos-especialidades/` | Integrado en `develop` (semanas 3 y 4) |
| Contrato API v1 y OpenAPI | `docs/medicos-especialidades/semana-05/` | Integrado (#371) |
| Prototipo navegable, diseño móvil y accesibilidad | `docs/medicos-especialidades/semana-08/` a `semana-12/` | Integrado |
| Adaptador PDO compatible con el esquema HOSPITAL de PostgreSQL | `PdoDoctorStore` | Integrado (#1038) |
| Rutas en Laravel con tenant, JWT y un permiso por operación | `routes/api.php`, `DoctorApiController` | Integrado (#1039) |
| Defectos D-01, D-02 y D-04 de la Semana 15 | dominio y aplicación | Integrado (#1042) |
| Prueba contra PostgreSQL 16 | `tests/Database/Hospital/DoctorsPostgresTest.php` | Integrado (#1043) |
| Horario partido con el `slot_minutes` de `doctor_schedules` | `SlotPlanner`, `PdoScheduleProvider` | Integrado (#1044) |
| Pantalla Vue `/medicos` | `resources/js/modules/medicos/` | Integrado (#1045) |
| Citas leídas en la zona horaria del hospital del tenant | `DoctorApiController` | Integrado (#1046) |
| Pruebas de seguridad, matriz de amenazas y bitácora de despliegue local | `tests/Feature/Medicos/DoctorSecurityTest.php`, `semana-17/` | Integrado (#1052) |
| Límite de tasa y longitudes máximas (MED-14 y MED-15) | `routes/api.php`, `Limits` | Integrado (#1061) |

Estado verificado el 31 de octubre de 2026: toda la cadena está integrada en `develop` (ver la [trazabilidad](./trazabilidad-semanas.md)).

## Demo funcional

Recorrido reproducible en cualquier equipo con PHP 8.3, Node 22 y Docker. Los comandos, qué señalar en cada paso y las preguntas esperables están en el [guion de defensa](./guion-defensa.md).

```bash
# 1. Dominio y arquitectura, sin base de datos
php micro-his/medicos-especialidades/tests/core.php
php micro-his/medicos-especialidades/tests/run.php

# 2. Contrato HTTP, permisos y seguridad
php vendor/bin/phpunit tests/Feature/Medicos tests/Feature/Rbac/DoctorPermissionsTest.php

# 3. Esquema real en PostgreSQL 16 (dos contenedores efímeros; ver el guion)
php vendor/bin/phpunit tests/Database/Hospital/DoctorsPostgresTest.php tests/Database/Hospital/DoctorScheduleIntegrityTest.php

# 4. Pantalla
node --test "resources/js/modules/medicos/*.test.mjs"
npm run build
```

## Evidencia consolidada

Resultados ejecutados el 22 de octubre de 2026 en el equipo del autor, cada uno en la rama del PR que lo introduce y los últimos sobre la rama más alta de la cadena:

| Verificación | Resultado |
|---|---|
| Micro-HIS (`core.php`, `run.php`) | Todas las pruebas pasaron |
| Contrato HTTP y seguridad (`tests/Feature/Medicos`) | OK (21 tests, 242 assertions) |
| Médicos con la suite Rbac (`tests/Feature/Medicos`, `tests/Feature/Rbac`) | OK (61 tests, 4084 assertions) |
| PostgreSQL 16 (`DoctorsPostgresTest`, `DoctorScheduleIntegrityTest`) | OK (25 tests, 168 assertions) |
| Utilidades de la pantalla (`node --test`) | 1 test, 0 fallas |
| `npm run build` | Correcto |
| Prototipo de la Semana 11 (guion de la Semana 12) | 27 de 27 comprobaciones; 16 de 16 pares de color |
| Matriz de amenazas | 18 amenazas: 15 probadas, 1 verificada por lectura, 2 parciales (MED-08 y MED-13), 0 abiertas |
| Rendimiento (RNF-MED-04), [rendimiento.md](./rendimiento.md) | Un día con 20 médicos: p95 de 120 a ~13 ms. Ventana de 14 días: p95 de 1561 a 145–194 ms (límite 500 ms) |
| Backend real de punta a punta, [e2e-real.md](./e2e-real.md) | 16 de 16 verificaciones (Laravel, JWT, tenant, RBAC y PostgreSQL 16) y 6 capturas con sesión real |

## Pendientes técnicos declarados

| # | Pendiente | Efecto |
|---|---|---|
| 1 | ~~Integrar los PR en orden~~ **Hecho** | `develop` ya tiene las rutas, el adaptador PostgreSQL, la pantalla y las pruebas |
| 2 | ~~Servidor API y cliente HTTP de la Semana 5 (#372)~~ **Integrado** | El cliente `DoctorApiClient` está en `develop`; las rutas de Laravel hacen de servidor |
| 3 | Contraste y teclado sobre la pantalla `/medicos` | Se probó contra el backend real con sesión real (16 de 16 verificaciones y 6 capturas, ver [e2e-real.md](./e2e-real.md)), pero el contraste y el teclado solo se midieron sobre el prototipo de la Semana 12 |
| 4 | MED-13 (parcial): `EnsureHospitalDatabaseTenant` rechaza otro hospital, pero la conexión `hospital` no cambia por tenant | El aislamiento entre varios hospitales depende de una base por hospital; decisión de la plataforma |
| 5 | MED-08: las lecturas de disponibilidad no se auditan | Parcial |
| 6 | Despliegue final | Sin staging u on-premise ni semillas aprobadas de `specialties` y `local_user_refs` |
| 7 | Falla de `AuditChainIntegrityTest` en la suite `Database` | Ajena al módulo; no se investigó; confirmar en CI |
| 8 | Formato de slots que espera Citas (issue #207) | Se entregan franjas `HH:MM` partidas por `slot_minutes`; falta confirmar con Citas |
| 9 | Licencias ya guardadas sin normalizar | La corrección de D-01 actúa en el alta; no migra datos |

## Validación humana

Declarada en `DECLARACION_IA.md`: realizada el 2026-10-22 por el autor.
