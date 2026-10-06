# Arquitectura global del HIS (modelo C4)

Vista global del Sistema Hospitalario Integrado en los niveles 1 (contexto),
2 (contenedores) y 3 (componentes de la API). Cada área documenta **su propia
vista de componentes** en `docs/modulo-<area>.md` y enlaza este documento como
punto de partida (issue #8).

## Nivel 1 · Contexto

Un único sistema atiende a **varios hospitales** (multi-tenant). Cada petición
pertenece a un hospital (`X-Tenant-ID`) y cada usuario a un solo hospital.

```mermaid
flowchart TB
    admin["👤 Admin<br/>gestiona usuarios, catálogos y configuración"]
    medico["👤 Médico<br/>atiende, evoluciona, prescribe y ordena laboratorio"]
    enfermera["👤 Enfermera<br/>registra signos vitales y alergias"]
    recep["👤 Recepcionista<br/>registra pacientes, citas y admisiones"]
    tecnico["👤 TecnicoLab<br/>recibe muestras e ingresa resultados"]
    bioq["👤 Bioquímico<br/>valida resultados y mantiene el catálogo"]

    his[["🏥 Sistema Hospitalario Integrado (HIS)<br/>Pacientes, citas, camas, expediente clínico,<br/>prescripciones, laboratorio, alertas y reportes"]]

    admin & medico & enfermera & recep & tecnico & bioq -->|"usa vía navegador (HTTPS)"| his
```

## Nivel 2 · Contenedores

```mermaid
flowchart LR
    user["👤 Personal del hospital"]

    subgraph his["HIS"]
        spa["SPA Vue 3<br/>Vite · Pinia · Vue Router · Axios<br/>resources/js"]
        api["API REST Laravel 12<br/>/api/v1 · JWT · Spatie Permission<br/>app/ · routes/api.php"]
        db[("PostgreSQL<br/>datos de todos los hospitales,<br/>aislados por tenant_id")]
    end

    user -->|HTTPS| spa
    spa -->|"JSON + Bearer JWT + X-Tenant-ID"| api
    api -->|"Eloquent (SQL)"| db
```

| Contenedor | Tecnología | Responsabilidad |
|---|---|---|
| SPA | Vue 3, Vite, Pinia, Vue Router, Axios | Pantallas por área (`resources/js/modules/<area>/`), control visual por permiso. |
| API | Laravel 12, `tymon/jwt-auth`, Spatie Permission | Reglas de negocio, validación, permisos y aislamiento por hospital. |
| Base de datos | PostgreSQL 13+ (`unaccent`) | Persistencia; cada tabla clínica tiene `tenant_id`. |

La seguridad real vive en la API: la SPA oculta opciones por permiso solo por
usabilidad.

## Nivel 3 · Componentes de la API

Toda petición privada atraviesa el mismo pipeline antes de llegar al
controller del área:

```mermaid
flowchart LR
    req(["Petición /api/v1"]) --> tenant
    subgraph pipeline["Pipeline de middleware (bootstrap/app.php)"]
        direction LR
        tenant["tenant<br/>TenantMiddleware<br/>valida X-Tenant-ID<br/>→ currentTenant"]
        bind["bindings<br/>SubstituteBindings<br/>resuelve {modelo}<br/>ya filtrado por hospital"]
        jwt["auth.jwt<br/>JwtAuth<br/>token válido y del<br/>mismo hospital"]
        perm["permission:modulo.accion<br/>Spatie"]
        tenant --> bind --> jwt --> perm
    end
    perm --> ctrl

    subgraph area["Componentes de cada área"]
        ctrl["Controller delgado<br/>Api/V1/*Controller"]
        req2["FormRequest / validación"]
        svc["Service / Action<br/>reglas de negocio"]
        model["Modelo Eloquent<br/>trait BelongsToTenant"]
        ctrl --> req2
        ctrl --> svc --> model
    end

    svc --> audit["AuditLogger<br/>audit_logs (área 1)"]
    svc --> alerts["Registro de alertas<br/>critical_alerts (área 8)"]
    model --> db[("PostgreSQL")]
```

| Componente | Dónde | Regla |
|---|---|---|
| `TenantMiddleware` | `app/Http/Middleware` | Corre **antes** del binding (ver #17), así que un id de otro hospital responde 404. |
| `JwtAuth` (`auth.jwt`) | `app/Http/Middleware` | No usar el alias `jwt.auth` del paquete: se salta la validación de hospital. |
| `BelongsToTenant` | `app/Models/Concerns` | Global scope por hospital y `tenant_id` automático al crear. Nunca filtrar a mano. |
| `whereSearch()` | `AppServiceProvider` | Búsqueda sin mayúsculas ni acentos (ILIKE + `unaccent`). |
| Contrato de respuestas | [`docs/contrato-api.md`](contrato-api.md) | Paginator de Laravel, recurso envuelto con su nombre, errores 400/401/403/404/422. |
| `AuditLogger` | `app/Services` (#14) | Acciones sobre datos clínicos. |
| `PatientSummaryResource` | `app/Http/Resources` (#16) | Paciente anidado en respuestas de otras áreas. |

## Mapa de áreas y dependencias

```mermaid
flowchart LR
    a1["1 · Auth, usuarios,<br/>RBAC y auditoría"]
    a2["2 · Pacientes y<br/>expediente base"]
    a3["3 · Médicos,<br/>especialidades y citas"]
    a4["4 · Salas, camas,<br/>admisión y altas"]
    a5["5 · SOAP, diagnósticos<br/>y signos vitales"]
    a6["6 · Alergias, medicamentos<br/>y prescripciones"]
    a7["7 · Laboratorio"]
    a8["8 · Alertas, notificaciones,<br/>dashboard y reportes"]
    a9["9 · Contrato API, UI<br/>transversal, QA/CI"]

    a3 -->|paciente| a2
    a4 -->|paciente y expediente| a2
    a5 -->|expediente| a2
    a6 -->|"nota SOAP (soap_note_id)"| a5
    a7 -->|"nota SOAP (soap_note_id)"| a5
    a5 -->|signo_vital_anormal| a8
    a6 -->|alergia_prescripcion| a8
    a7 -->|valor_critico_lab| a8
    a2 & a3 & a4 & a5 & a6 & a7 -.->|audit_logs| a1
    a9 -.->|contrato, CI, layout| a1 & a2 & a3 & a4 & a5 & a6 & a7 & a8
```

Las líneas punteadas son servicios transversales. Las continuas son datos que
un área necesita de otra; cada una requiere un contrato acordado en un issue,
por ejemplo #11 (5 ↔ 7), #12 (alertas) o #13 (paciente anidado).

## Cómo enlazar la vista de tu área

En `docs/modulo-<area>.md`, en la sección de arquitectura:

1. Enlaza este documento: `[Arquitectura global](arquitectura-c4.md)`.
2. Dibuja **tu** nivel 3: controllers, services, modelos y a qué servicios
   transversales llamas (auditoría, alertas, paciente anidado).
3. Lista los contratos con otras áreas y el issue donde se acordaron.
