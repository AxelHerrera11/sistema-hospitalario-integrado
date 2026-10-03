# Proyecto Final ASII 2026 — Sistema Hospitalario Integrado

Sistema Hospitalario Integrado del **grupo de 9 integrantes** para el **Proyecto Final de Análisis de Sistemas II 2026**. Parte de la base común del curso, estabilizada (ver [`docs/CAMBIOS-BASE.md`](docs/CAMBIOS-BASE.md)). Cada integrante es responsable de un **área vertical** del sistema, trabajando con ramas, pull requests, documentación y evidencia de pruebas.

> Este proyecto no consiste en “hacer pantallas”. Cada módulo debe demostrar análisis, diseño, arquitectura, implementación, pruebas, seguridad e integración.

## Estado actual del repositorio

| Área | Estado real |
|---|---|
| Stack backend | Laravel 12, PHP 8.2+, PostgreSQL 13+, JWT (`tymon/jwt-auth`) y Spatie Laravel Permission. |
| Stack frontend | Vue 3, Vite, Pinia, Vue Router y Axios. |
| Seguridad base | JWT con validación token ↔ `X-Tenant-ID`, aislamiento automático por hospital (trait `BelongsToTenant`), RBAC con 6 roles y 45 permisos `modulo.accion`. |
| Modelo clínico | Migraciones, modelos Eloquent (22), factories y seeders demo para pacientes, médicos, camas, admisiones, EMR, laboratorio, auditoría y notificaciones. |
| API actual | Autenticación bajo `/api/v1`: login, usuario actual (con permisos), refresh, logout, alta y listado paginado de usuarios (solo Admin). |
| Pruebas | 18 pruebas automatizadas (autenticación, permisos, aislamiento entre hospitales y búsqueda), verificadas en SQLite y PostgreSQL. |
| Pendiente | Endpoints, pantallas y pruebas de cada área clínica. |

## Trabajo por áreas verticales

Cada integrante construye una capacidad completa del HIS, no una pieza aislada. Un área terminada debe incluir:

- análisis del problema, actores y casos de uso;
- requerimientos funcionales y no funcionales;
- diseño arquitectónico y de componentes;
- contrato API y reglas de permisos;
- implementación backend/frontend mínima funcional;
- pruebas y evidencia de ejecución;
- documentación y evidencia de integración.

## Asignación de áreas

| # | Integrante | Área | Módulos originales que absorbe |
|---:|---|---|---|
| 1 | María Lindo | Auth, usuarios, RBAC y auditoría | 1, 2, 22 |
| 2 | María de los Ángeles López | Pacientes y expediente base | 3, 10 |
| 3 | Gerson Orellana | Médicos, especialidades y citas | 4, 5 |
| 4 | Madelin Cerón | Salas, camas, admisión, traslados y altas | 6, 7, 8 |
| 5 | Lis Rosales | Notas SOAP, diagnósticos y signos vitales | 11, 13 |
| 6 | Javier Fajardo | Alergias, medicamentos y prescripciones con validación de alergias | 12, 14, 15 |
| 7 | Josué Hicho | Laboratorio: órdenes, catálogo, muestras, resultados y validación | 16, 17, 18, 19, 20 |
| 8 | Cindy Ruano | Alertas críticas, notificaciones, dashboard y reportes | 9, 21, 23, 27 |
| 9 | Axel Herrera | Líder técnico: contrato API, UI transversal, QA/CI e integración | 24, 25, 26, 28 |

Fuera de alcance: prototipo NativePHP (módulo 29).

Dependencias clave entre áreas (acordar contratos temprano):

- **Área 6 ↔ 5:** la prescripción cuelga de una nota SOAP (`prescriptions.soap_note_id`).
- **Área 6 ↔ 8:** una prescripción bloqueada por alergia genera `critical_alerts` tipo `alergia_prescripcion`.
- **Área 7 ↔ 5 y 8:** la orden de laboratorio nace de una nota SOAP; un resultado crítico genera alerta `valor_critico_lab`.
- **Área 5 ↔ 8:** signos vitales anormales generan alerta `signo_vital_anormal`.
- **Área 4 ↔ 2:** la admisión requiere paciente y expediente existentes.
- **Área 1:** toda área registra en `audit_logs` las acciones sobre datos clínicos.

## Reglas técnicas obligatorias

- Todo modelo con `tenant_id` usa el trait `BelongsToTenant`. **Nunca** filtrar `tenant_id` a mano ni usar `withoutGlobalScope('tenant')` sin revisión del líder técnico.
- Rutas protegidas: `Route::middleware(['tenant', 'auth.jwt'])` + `->middleware('permission:modulo.accion')`. No usar `jwt.auth` (ese alias pertenece al paquete JWT y se salta la validación de hospital).
- Permisos nuevos: se agregan en `database/seeders/RoleSeeder.php` mediante PR, con la convención `modulo.accion`.
- Cada área agrega sus rutas en su bloque comentado de `routes/api.php` y sus pantallas en `resources/js/modules/<area>/`.
- Toda PR debe pasar `php artisan test` y `npm run build`.

## Flujo de trabajo con Git

- `main`: solo versiones entregables. `develop`: integración.
- Cada área trabaja en su rama: `feature/<area>-<descripcion>` creada desde `develop` (p. ej. `feature/laboratorio-ordenes`).
- Toda PR apunta a `develop` y la revisa el líder técnico. Nada de `force push` ni de commits directos a `main`/`develop`.
- No modificar archivos de otra área sin coordinarlo; los archivos compartidos (`routes/api.php`, `RoleSeeder`, `router/index.js`) se tocan en bloques pequeños para evitar conflictos.
- Los worktrees son opcionales; la guía sigue en [`docs/worktree-guide.md`](docs/worktree-guide.md).

## Instalación rápida Laravel/Vue

Requisitos: PHP 8.2+ con las extensiones `pdo_pgsql` y `pgsql`, Composer 2, Node.js 20+, npm y **PostgreSQL 13+**.

### 1. Base de datos (una sola vez)

En `psql` (o pgAdmin) como superusuario:

```sql
CREATE USER his WITH PASSWORD 'his123';
CREATE DATABASE hospital_his OWNER his;
CREATE DATABASE hospital_his_test OWNER his;  -- solo para pruebas contra PostgreSQL
```

En Windows, habilitar en `php.ini`: `extension=pdo_pgsql` y `extension=pgsql`.

### 2. Backend

```bash
composer install
cp .env.example .env        # ya viene configurado para PostgreSQL
php artisan key:generate
php artisan jwt:secret
php artisan migrate:fresh --seed
```

### 3. Frontend y servidor local

```bash
npm install
npm run dev
```

En otra terminal:

```bash
php artisan serve
```

### Validaciones antes de abrir PR

```bash
php artisan route:list --path=api
npm run build
php artisan test                                       # rápido, SQLite en memoria
php artisan test --configuration=phpunit.pgsql.xml     # contra PostgreSQL real
```

La segunda corrida es la que vale antes de integrar: hay comportamientos que
solo aparecen en PostgreSQL (ver reglas abajo).

### Reglas específicas de PostgreSQL

- **Búsquedas de texto:** usar siempre `->whereSearch(['columna1', 'columna2'], $termino)`.
  En PostgreSQL, `where('col', 'like', ...)` distingue mayúsculas y no ignora
  acentos; `whereSearch` usa `ILIKE` + `unaccent` ("maria" encuentra "María").
- **UUID:** `tenant_id` es de tipo `uuid`. Validar con `Str::isUuid()` o la regla
  `uuid` cualquier valor que llegue del cliente antes de consultarlo.
- **Enums:** Laravel los crea como `varchar` + `CHECK`. Un valor fuera de la lista
  provoca error SQL (500), así que validar con `Rule::in([...])` en el request.
- **Producción:** `APP_DEBUG=false`; con `true`, los errores SQL se muestran completos
  al cliente.

## API base disponible

Todas las rutas están bajo `/api/v1` y requieren la cabecera `X-Tenant-ID`.

| Método | Ruta | Acceso |
|---|---|---|
| POST | `/auth/login` | Público (máx. 10 intentos por minuto) |
| GET | `/auth/me` | Bearer JWT — devuelve usuario, roles y permisos |
| POST | `/auth/refresh` | JWT refresh |
| POST | `/auth/logout` | Bearer JWT |
| POST | `/auth/register` | Bearer JWT, solo rol **Admin** — crea usuarios del hospital con un rol |
| GET | `/users` | Bearer JWT + `usuarios.ver` — lista usuarios y roles del hospital actual |

Datos demo tras `php artisan migrate:fresh --seed` (contraseña `password`):

| Hospital | X-Tenant-ID | Usuarios |
|---|---|---|
| Hospital General San Marcos | `00000000-0000-4000-8000-000000000001` | `admin+san-marcos-demo@demo.local`, `recep+…`, `lab+…`, `doctor1+…` a `doctor5+…` |
| Clínica Santa Elena | `00000000-0000-4000-8000-000000000002` | los mismos con `santa-elena-demo` |

Roles: Admin, Médico, Enfermera, TecnicoLab, Bioquimico y Recepcionista.

## Plan semanal ASII

El plan completo está en [`docs/weekly-plan.md`](docs/weekly-plan.md).

| Semana | Entrega principal del módulo | Evidencia esperada |
|---:|---|---|
| 1 | Diagnóstico, actores y casos de uso del módulo. | Diagrama UML de casos de uso + narrativa breve. |
| 2 | RF/RNF, criterios de aceptación y diseño inicial. | Tabla RF/RNF + ejemplo de al menos un principio SOLID aplicado al módulo, usando como fuente `https://mvpcluster.com/diseno-de-software-2/`. |
| 3 | Vista arquitectónica del módulo. | Diagrama C4/UML o vista de componentes de alto nivel. |
| 4 | Diseño por capas y responsabilidades. | UI, API, lógica, persistencia y objetos reutilizables. |
| 5 | Contrato API preliminar y plan de integración. | Endpoints, payloads, errores, permisos, rama, worktree y PR. |
| 6 | Primera evaluación parcial. | Defensa teórica y caso práctico arquitectónico. |
| 7 | Diseño de componentes backend/frontend. | Diagrama de componentes + propuesta de refactorización. |
| 8 | Flujo UX por rol. | User flow, wireframes iniciales y reglas de interacción. |
| 9 | Evaluación de usabilidad y accesibilidad. | Checklist, hallazgos y mejoras propuestas. |
| 10 | Adaptación responsive/móvil. | Escenarios móviles y prioridades de pantalla. |
| 11 | Mockup o prototipo navegable. | Mockup desktop/móvil en Figma, Canva, Excalidraw o equivalente. |
| 12 | Segunda evaluación parcial. | Defensa teórica y caso práctico de diseño. |
| 13 | Plan de revisión técnica formal. | Checklist de revisión, responsables y evidencia. |
| 14 | Plan de aseguramiento de calidad. | Métricas, riesgos y estrategia SQA del módulo. |
| 15 | Pruebas unitarias, caja blanca y caja negra. | Casos de prueba + evidencia de ejecución. |
| 16 | Integración, validación y primera entrega funcional. | Evidencia en staging/on-premise y errores corregidos. |
| 17 | Seguridad y despliegue final. | Matriz de amenazas, pruebas de permisos/datos sensibles y bitácora de despliegue. |
| 18 | Evaluación final y defensa. | Demo funcional, PR integrado y evidencia completa. |

## Evaluación

| Componente | Puntos |
|---|---:|
| Actividades ASII semanales | 20 |
| Proyecto individual final | 15 |

Proyecto individual — 15 puntos:

| Criterio | Puntos |
|---|---:|
| Funcionalidad completa del módulo | 5 |
| Integración con el HIS y flujos existentes | 3 |
| Calidad del análisis y diseño | 3 |
| Pruebas, validaciones y seguridad | 2 |
| Documentación, demo y evidencia de despliegue | 2 |

## Definition of Done por área

Un área se considera terminada cuando cumple todo lo siguiente:

- [ ] Issue asignado y documentado.
- [ ] Rama feature creada desde `develop`.
- [ ] RF/RNF y criterios de aceptación documentados.
- [ ] Casos de uso y alcance del módulo claros.
- [ ] Diseño arquitectónico, por capas y de componentes incluido.
- [ ] Contrato API documentado: endpoints, payloads, respuestas, errores y permisos.
- [ ] Backend implementado con controllers delgados, validaciones, servicios/actions cuando aplique y control por rol/tenant.
- [ ] UI mínima funcional en Vue para el flujo principal del módulo.
- [ ] Pruebas ejecutadas (incluida `phpunit.pgsql.xml`) y evidencia adjunta.
- [ ] Seguridad revisada: permisos por ruta, modelos con `BelongsToTenant` y datos clínicos sensibles.
- [ ] PR abierto hacia `develop` con checklist completo.
- [ ] Evidencia de integración con otros módulos o con el flujo general del HIS.
- [ ] Demo o capturas incluidas cuando el módulo tenga interfaz.

## Documentos de apoyo

- [`docs/weekly-plan.md`](docs/weekly-plan.md): cronograma detallado de semanas 1 a 18.
- [`docs/CAMBIOS-BASE.md`](docs/CAMBIOS-BASE.md): correcciones aplicadas a la base del curso.
- [`docs/worktree-guide.md`](docs/worktree-guide.md): guía opcional de worktrees.
- [Plantilla de Pull Request](.github/pull_request_template.md): evidencia obligatoria para revisión.
- [Plantilla de issue de módulo](.github/ISSUE_TEMPLATE/module_task.md): estructura para asignar y dar seguimiento a cada módulo.
