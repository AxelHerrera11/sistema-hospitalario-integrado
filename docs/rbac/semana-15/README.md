# Semana 15 — Pruebas unitarias, caja blanca y caja negra del módulo RBAC

Rama: `feature/asii-02-semana-15-rbac-mar-03` (worktree `s15-rbac`).
Base: `origin/develop` (`255472f`). PHP 8.2.12 + `pdo_sqlite`; Node 26 (solo evidencia aislada).

## 1. Auditoría de pruebas existentes

| Prueba | Rama que la contiene | ¿En develop? | Dependencias no integradas |
| --- | --- | --- | --- |
| `micro-his/rbac/tests/run.php` | develop (S3, `237df12`/`0007faa`) | **Sí** | — (solo pila de develop) |
| `micro-his/rbac/tests/inmemory.php` | `feature/asii-02-semana-04-rbac-mar-03` (PR #70) | No | `InMemoryRbacStore`, `RoleResolver` |
| `micro-his/rbac/tests/authorization.php` | `feature/asii-02-semana-05-rbac-mar-03` (PR #134) | No | `Application\Authorize`, `RbacHttpHandler::authorize()`, `InMemoryRbacStore`, `RoleResolver` |
| `resources/js/router/permissions.test.mjs` | `feature/asii-02-semana-07-rbac-mar-03` (PR #136) | No | `resources/js/router/permissions.js` |

`run.php` es la única prueba de RBAC presente en `develop`; las otras tres viven en ramas no integradas. Por eso las pruebas nuevas (S15) se escriben **solo contra el código de `develop`**: `Authorizer`, `AssignerPrivilegeRule`, `RoleGrantsPermission`, `AssignRole`, `PdoRbacStore`, `RbacHttpHandler`.

## 2. Entorno aislado para S4/S5/S7 (dependencias temporales)

Fuera del repositorio, en `%TEMP%\opencode\s15-isolated`, se combinaron por archivo las ramas no integradas **sin modificar sus ramas ni incorporarlas al commit**:

- `s4s5/`: `git archive` de S4 + superposición de `Application/Authorize.php` y `Presentation/RbacHttpHandler.php` de S5 (S5 no contiene a S4).
- `s7/`: `git archive` de `resources/js/router` de S7 (`permissions.js` + su test).

## 3. Pruebas nuevas implementadas (código de develop)

| Archivo | Tipo | Objetivo |
| --- | --- | --- |
| `micro-his/rbac/tests/harness.php` | soporte | `check()`/`summary()` y código de salida. |
| `micro-his/rbac/tests/whitebox_engine.php` | caja blanca | Bifurcaciones de `Authorizer` (sin reglas, permitir, cortocircuito, inyección de permisos, `trace_id`) y de las reglas (`null`/`sin_permiso_de_rol`/`rol_supera_privilegio`, abstención, rol desconocido) y **fallo seguro** con `DenialRecorder` que lanza. |
| `micro-his/rbac/tests/blackbox_contract.php` | caja negra | Entradas/respuestas/errores del contrato HTTP: 201, 403 (`code/message/trace_id` + auditoría enlazada), 422 (cabeceras ausentes, tipos no string, espacios, rol inexistente), 409, 500 con rollback. |
| `micro-his/rbac/tests/isolation_escalation.php` | caja negra | Aislamiento por hospital (rol de H1 no aplica en H2, sin membresía cruzada) y escalamiento (AdminHospital 80 no otorga SuperAdmin 100; Recepcionista 20 no otorga Medico 50; auto-elevación; autorizados 201 y denegados 403). |

Casos de permisos autorizados y denegados: cubiertos en las tres suites (autoriza sin denegación vs. `sin_permiso_de_rol`/`rol_supera_privilegio` con 403).

## 4. Evidencia real de ejecución

```
$ php micro-his/rbac/tests/run.php
OK: camino feliz: 201, membresía y auditoría guardadas
OK: regla de dominio: rol que supera el privilegio -> 403 y denegación con trace_id
OK: regla de dominio: sin permiso rbac.asignar -> 403 RBAC_DENIED
OK: regla de dominio: rol ya asignado -> 409
OK: validación: rol inexistente -> 422
OK: validación: entradas inválidas (array/objeto/espacios en user_id, headers no string) -> 422 sin TypeError
OK: validación: ninguna entrada inválida escribió membresía ni auditoría
OK: error de persistencia: auditoría caída -> 500 y rollback
OK: falla segura: bitácora caída -> sigue denegado (403)
Todas las pruebas pasaron.                                            [exit=0]
```

```
$ php micro-his/rbac/tests/whitebox_engine.php
OK: sin reglas -> deniega con trace_id
OK: sin reglas -> motivo sin_reglas
OK: denegación trazable -> registra usuario, hospital y trace_id
OK: trace_id: distinto en cada denegación
OK: regla permite -> autoriza sin denegación
OK: el motor inyecta permissions[rol] en el contexto de la regla
OK: varias reglas: la primera denegación detiene la evaluación
OK: permiso: un rol lo otorga -> null
OK: permiso: ningún rol lo otorga -> sin_permiso_de_rol
OK: permiso: se acumula entre varios roles -> null
OK: permiso: rol desconocido -> deniega sin excepción
OK: privilegio: sin role_to_assign -> se abstiene (null)
OK: privilegio: nivel igual o menor -> null
OK: privilegio: nivel mayor -> rol_supera_privilegio
OK: privilegio: asignador sin roles -> deniega cualquier nivel
OK: falla segura: bitácora caída -> sigue denegado con trace_id
[caja blanca del motor] 16 pruebas, 0 fallos.                         [exit=0]
```

```
$ php micro-his/rbac/tests/blackbox_contract.php
OK: 201: estado y claves exactas del cuerpo
OK: 201: refleja hospital del tenant y rol asignado
OK: entradas con espacios -> se recortan y autorizan (201)
OK: 403: claves exactas code/message/trace_id
OK: 403: audita acceso_denegado con trace_id y motivo
OK: 422: cabeceras ausentes -> payload_invalido
OK: 422: tipos no string (int/bool/null) -> payload_invalido
OK: 422: user_id en blanco -> payload_invalido
OK: 422: rol inexistente (dominio) -> payload_invalido sin trace_id
OK: 409: rol ya asignado -> solo code rol_ya_asignado
OK: 500: auditoría caída -> RBAC_PERSISTENCE_ERROR
OK: 500: transacción revertida -> sin membresía huérfana
[caja negra del contrato] 12 pruebas, 0 fallos.                       [exit=0]
```

```
$ php micro-his/rbac/tests/isolation_escalation.php
OK: aislamiento: sin membresía en H2 -> roles vacíos
OK: aislamiento: actuar en H2 sin rol -> 403
OK: aislamiento: no se crea membresía cruzada en H2
OK: aislamiento: la membresía propia en H1 se conserva
OK: aislamiento: la denegación se audita con el tenant solicitado (H2)
OK: escalamiento: AdminHospital(80) no puede otorgar SuperAdmin(100) -> 403
OK: escalamiento: no se persiste la membresía elevada
OK: escalamiento: admin no puede auto-elevarse -> 403
OK: escalamiento: Recepcionista(20) no puede otorgar Medico(50) -> 403
OK: escalamiento: se audita el motivo rol_supera_privilegio
OK: autorizado: AdminHospital otorga Medico(50) -> 201
OK: autorizado: Recepcionista otorga su propio nivel(20) -> 201
OK: autorizado: la membresía otorgada queda en el hospital correcto
OK: denegado: Medico sin rbac.asignar -> 403
OK: denegado: sin permiso no se crea membresía
[aislamiento y escalamiento] 15 pruebas, 0 fallos.                    [exit=0]
```

Entorno aislado S4/S5/S7 (dependencias temporales; resumen, mismo código sin integrar):

```
$ php %TEMP%\opencode\s15-isolated\s4s5\micro-his\rbac\tests\inmemory.php
... 13 pruebas OK ...  Todas las pruebas del adaptador InMemory pasaron.   [exit=0]
$ php %TEMP%\opencode\s15-isolated\s4s5\micro-his\rbac\tests\authorization.php
... 9 pruebas OK ...   Todas las pruebas de autorización pasaron.          [exit=0]
$ node %TEMP%\opencode\s15-isolated\s7\resources\js\router\permissions.test.mjs
OK: pruebas de permisos del router pasaron.                                [exit=0]
```

## 5. Limitaciones y fallos pendientes

- **Sin pruebas end-to-end de Laravel**: no hay `vendor/` (falta `composer install`) ni base de datos configurada; no se afirma E2E ni `php artisan test`.
- S4/S5/S7 siguen **sin integrar**; su evidencia proviene del entorno aislado temporal, no de `develop`.
- El `Authorize` (contrato `allowed/reason/trace_id`) y `RbacHttpHandler::authorize()` de S5 no existen en `develop`; su caja negra queda pendiente hasta integrar S5.
- `AssignerPrivilegeRule::LEVELS` es un supuesto (SD-RBAC-09) pendiente de validación de negocio.
