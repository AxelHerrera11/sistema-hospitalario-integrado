# Médicos, especialidades y disponibilidad — Semana 15: pruebas unitarias, caja blanca y caja negra

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-04 · **Entrega:** matriz RF/RNF → prueba, 36 casos diseñados y ejecutados (11 unitarios, 12 de caja blanca, 13 de caja negra), comandos, resultados y defectos registrados.

**Tarea individual:** Diseñe y ejecute pruebas de Médicos, especialidades y disponibilidad trazadas a RF/RNF: unitarias para reglas de médicos, especialidades, horarios, disponibilidad y conflictos de agenda; caja blanca con ramas/condiciones del flujo «registro de médico, asociación de especialidad y consulta de disponibilidad»; caja negra con equivalencia, límites, permisos y errores, sin probar módulos ajenos.

> Las pruebas **no modifican el módulo**: son un script aparte ([`evidencia/pruebas.php`](./evidencia/pruebas.php)) que se ejecuta contra su código. Un caso con defecto conocido espera lo que dice el contrato; si falla, se informa como `DEFECTO Dnn` y no como prueba rota. La salida completa está en [`evidencia/resultado.txt`](./evidencia/resultado.txt).

## 1. Objeto, entorno y alcance

| Dato | Valor |
|---|---|
| Código probado | Pila de ASII-04 (#126 → #355 → #356 → #372), punta `e28cc9e`, sin cambios |
| Entorno | PHP 8.3.33 (CLI), adaptadores en memoria (`InMemoryDoctorRepository`) y esquema SQLite del módulo |
| Operaciones | `POST /api/v1/doctors`, `POST /api/v1/doctors/{id}/specialties`, `GET /api/v1/availability` |
| Contrato de referencia | [Semana 5](../semana-05/contrato-api.md) y los RF/RNF-MED citados en la [Semana 14](../semana-14/README.md) |

**Módulos ajenos.** Citas y RBAC se sustituyen por los dobles del propio módulo (`InMemoryAppointmentReader`, `InMemoryPermissionChecker`) y el horario por `ConfigScheduleProvider`. Se prueba cómo **reacciona este módulo** a lo que le devuelven (cita ocupada, permiso denegado, RBAC caído); no se prueba que Citas o RBAC funcionen. La tabla `doctor_schedules` no existe todavía y no se toca.

## 2. Matriz RF/RNF → prueba

| Requisito | Qué se verifica | Unitarias | Caja blanca | Caja negra | Resultado |
|---|---|---|---|---|---|
| RF-MED-01 | Registro con especialidad principal | — | B01–B04 | N01, N02 | ✔ |
| RF-MED-02 | Licencia única | U11 | B03 | N02 | ✖ D-01 · un perfil por usuario sin probar (H-03) |
| RF-MED-03 | Solo especialidades activas | — | B02, B05, B12 | N02 | ✔ |
| RF-MED-04 | Vigencia `[desde, hasta)` | U01–U04, U07 | B07 | N03 | ✔ |
| RF-MED-05 | Sin repetidas ni segunda principal solapada | U05, U06 | B09–B11 | N04 | ✔ |
| RF-MED-06 | Franjas libres, fecha y entrada válidas | U08–U10 | B12 | N08, N09, N11, N13 | ✖ D-02, D-04 · rango de 14 días y `generated_at` sin probar (H-07) |
| RF-MED-07 | Médico sin horario o sin franjas, informado | — | B12 | N10 | ✔ (con reserva H-09) |
| RNF-MED-01 | Permisos, falla cerrada y tenant | — | — | N05, N06, N07, N12 | ✖ D-03, D-05 |
| RNF-MED-02 | Atomicidad del alta | — | — | — | Cubierta por `tests/run.php` (auditoría caída); no se duplicó |
| RNF-MED-03 | Disponibilidad sin datos del paciente | — | — | N09 | ✔ el lector solo entrega horas |

Todo requisito con al menos un caso, salvo los tres huecos señalados, que quedan como pruebas pendientes y no se dan por cumplidos.

## 3. Diseño de los casos

### 3.1 Unitarias (11): reglas sin HTTP ni base

| ID | Regla probada | Técnica | Esperado |
|---|---|---|---|
| U01 | `Period`: hasta = desde | Límite | `INVALID_PERIOD` 422 |
| U02 | `Period`: hasta = desde + 1 día | Límite | Se acepta |
| U03 | `Period`: 30-feb y `2026-1-1` | Partición inválida | `INVALID_PERIOD` 422 |
| U04 | `overlaps`: `[01-01, 02-01)` frente a `02-01` y `01-31` | Límite del intervalo abierto | No solapa / solapa |
| U05 | Misma especialidad solapada | Equivalencia | `SPECIALTY_ALREADY_ASSOCIATED` 409 |
| U06 | Segunda principal solapada / contigua | Límite | 409 / se acepta |
| U07 | Vigencia fuera del perfil | Equivalencia | `PERIOD_OUTSIDE_PROFILE` 422 |
| U08 | `SlotPlanner`: 08:00–09:30 menos 08:30 | Caso típico | `08:00`, `09:00` |
| U09 | Ventana menor a un bloque; bloque 0 | Límite | `[]` / excepción |
| U10 | Cita 08:15 fuera de la rejilla de 30 min | Conflicto de agenda | `08:00` no queda libre |
| U11 | Licencia ` COL-9` frente a `COL-9` | Equivalencia (variante de entrada) | 409 |

### 3.2 Caja blanca (12): cada decisión del flujo con sus ramas

Cobertura de ramas y de condiciones diseñada sobre el código; no se midió con una herramienta (el entorno no tiene Xdebug ni pcov).

| ID | Función | Decisiones | Casos | Ramas |
|---|---|---|---|---|
| B01–B04 | `RegisterDoctor::execute` | campos vacíos · especialidad inactiva · licencia existente · alta | Un caso por salida | 4 de 4 |
| B05–B08 | `AssociateSpecialty::execute` | especialidad inactiva · médico inexistente · período fuera del perfil · alta | Un caso por salida | 4 de 4 |
| B09–B11 | `SpecialtyAssignmentPolicy`, condición `link.principal && nueva.principal` | (V,V) · (V,F) · (F,V) | MC/DC: cada condición cambia sola el resultado | 3 de 3 |
| B12 | `QueryAvailability::execute` | fecha inválida · especialidad inactiva · sin médicos · sin horario · con franjas | Cinco entradas en una prueba | 5 de 5 |

Las ramas de `Policy` que no son la condición compuesta (período fuera, sin solape, misma especialidad) las cubren U05, U06 y U07, de modo que sus 5 salidas quedan ejercitadas.

### 3.3 Caja negra (13): solo la interfaz HTTP

| ID | Técnica | Entrada → esperado |
|---|---|---|
| N01 | Equivalencia válida | Alta correcta → 201 |
| N02 | Equivalencias inválidas | Licencia duplicada 409 · especialidad inactiva 422 · inexistente 422 · cuerpo vacío 422 |
| N03 | Límites de vigencia | `valid_to` = `valid_from` 422 · +1 día 201 · fecha imposible 422 |
| N04 | Reglas de agenda | Asociación repetida 409 · segunda principal 409 |
| N05 | Permisos | Sin actor 401 · sin permiso 403 · con permiso 201 |
| N06 | Falla cerrada | RBAC caído: las tres rutas 503 |
| N07 | Tenant | `X-Tenant-ID` ajeno → 401/403 |
| N08 | Tipos y errores | Número en vez de texto, `"false"` en vez de booleano, JSON malformado → 422 |
| N09 | Disponibilidad | Lunes 08:00–09:30 con cita 08:30 → `08:00`, `09:00` |
| N10 | Estados vacíos | Día sin franjas y especialidad sin médicos, respuestas explícitas |
| N11 | Entrada inválida | Fecha imposible 422 `INVALID_DATE_RANGE` · sin `specialty` 422 `PAYLOAD_INVALID` |
| N12 | Seguridad por defecto | Ruta sin permiso declarado no despacha abierta |
| N13 | Trazabilidad | Toda respuesta, también la 422, lleva `X-Trace-Id` y `trace_id` |

## 4. Ejecución

```bash
git archive origin/feature/asii-04-semana-05-medicos-especialidades-api-jhos-hgnu micro-his/medicos-especialidades | tar -x -C <carpeta-vacía>
php <carpeta>/micro-his/medicos-especialidades/tests/run.php      # Todas las pruebas pasaron.
php <carpeta>/micro-his/medicos-especialidades/tests/api.php      # Todas las pruebas del API pasaron.
php docs/medicos-especialidades/semana-15/evidencia/pruebas.php <carpeta>/micro-his/medicos-especialidades
```

Resultado (captura de consola en [`resultado.txt`](./evidencia/resultado.txt)):

| Tipo | Casos | Pasan | Defecto conocido | Fallas inesperadas |
|---|---|---|---|---|
| Unitarias | 11 | 9 | 2 (U10, U11) | 0 |
| Caja blanca | 12 | 12 | 0 | 0 |
| Caja negra | 13 | 10 | 3 (N07, N11, N12) | 0 |
| **Total** | **36** | **31** | **5** | **0** |

Las dos suites propias del módulo (58 pruebas) siguen en verde: los defectos son casos que ellas no cubren. Las capturas son la salida de consola de los tres comandos; no hay capturas de pantalla porque el módulo no tiene interfaz en este alcance.

## 5. Defectos registrados

Estado de todos: **Abierto**. Ninguno se corrige desde esta entrega; cada corrección va en un PR aparte con la prueba que hoy falla.

| ID | Caso | Severidad | Defecto | Evidencia | Relación | Responsable |
|---|---|---|---|---|---|---|
| D-01 | U11 | Medio | La licencia se compara tal cual: ` COL-9` (espacio inicial) se registra aunque `COL-9` exista, y se burla la unicidad de RF-MED-02 | `RegisterDoctor::execute` devuelve `OK` | Nuevo | Autor |
| D-02 | U10 | Medio | `SlotPlanner` solo bloquea franjas que empiezan exactamente a la hora de la cita: una cita a las 08:15 deja `08:00` libre | Salida `["08:00","08:30"]` | Misma raíz que H-02 (duración ignorada) | Autor + Lis |
| D-03 | N07 | **Mayor** | Una petición con `X-Tenant-ID` ajeno se atiende: 201 | Alta con tenant `hospital-ajeno` → `201` | H-01 | Autor + María · la plataforma lo valida con `tenant` y `rbac:` (ver sección 7) |
| D-04 | N11 | Menor | `GET /availability` sin `specialty` responde `SPECIALTY_INACTIVE`, no `PAYLOAD_INVALID` | `422 SPECIALTY_INACTIVE` | H-08 | Autor |
| D-05 | N12 | **Mayor** | `permissionFor('GET','/specialties')` devuelve `NULL`; una ruta nueva nacería sin permiso | `NULL` | H-04 | Autor · cada ruta de la plataforma declara su `rbac:` (ver sección 7) |

**Recomendación mínima.** D-01: normalizar (`trim`, mayúsculas) la licencia antes de comprobarla y guardarla, y alinear el `UNIQUE` de la base. D-02: pasar de horas de inicio a intervalos `[inicio, fin)`. D-03, D-04 y D-05: las de la [Semana 13](../semana-13/hallazgos.md).

## 6. Limitaciones y siguientes pasos

- No se probó contra PostgreSQL con la migración real; la persistencia se ejerció con los adaptadores en memoria y el esquema SQLite (riesgo H-06).
- Sin probar y pendientes: un solo perfil activo por usuario (H-03), el rango de 14 días con `generated_at` (H-07) y la atomicidad bajo fallos reales de la base (RNF-MED-02).
- La cobertura de ramas se calculó por análisis del código, no con un instrumento.
- Al corregirse cada defecto, el caso correspondiente pasa de `DEFECTO` a `PASA (corregido Dnn)` sin cambiar el script.

## 7. Actualización del 2026-10-20: RBAC y tenant de la plataforma

María (ASII-02) confirmó que los permisos `medicos.registrar`, `medicos.asociar_especialidad` y `medicos.ver_disponibilidad` ya están en `config/rbac.php` de `develop`, y que cada ruta usa `tenant`, `jwt.auth` y `rbac:<permiso>`. Efecto sobre esta entrega:

| Caso | Estado en el módulo aislado | Con la plataforma |
|---|---|---|
| N07 / D-03 (tenant ajeno) | `DEFECTO`: el `ApiKernel` no mira `X-Tenant-ID` | `tenant` y `rbac:` responden 400 `TENANT_REQUIRED` o 403 `TENANT_MISMATCH` |
| N12 / D-05 (ruta sin permiso) | `DEFECTO`: `permissionFor` devuelve `NULL` | Cada ruta declara su `rbac:medicos.*` |
| N05 y N06 (401, 403, 503) | `PASA` con los códigos del contrato de la Semana 5 | Los códigos pasan a `AUTH_REQUIRED`, `RBAC_DENIED` y `RBAC_UNAVAILABLE` |

Los resultados de la sección 4 no cambian: el script sigue ejecutándose contra el módulo aislado y no contra la aplicación Laravel, donde el módulo aún no está montado. D-03 y D-05 se darán por cerrados cuando existan las pruebas de integración con RBAC real; los códigos de N05 y N06 se actualizarán junto con el contrato.
