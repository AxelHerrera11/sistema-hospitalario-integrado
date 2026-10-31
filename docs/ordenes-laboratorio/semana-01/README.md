# Órdenes de laboratorio desde EMR — Semana 1: UML y narrativa de alcance

**Módulo:** Órdenes de laboratorio desde EMR (ASII-16) · **Entrega académica:** Fajardo López, Javier Alexander (`Javifa2324`, ASII-09)
**Flujo:** «emisión de orden desde el expediente y seguimiento de su estado»
**Narrativa completa:** [`narrativa-alcance-trazabilidad.md`](./narrativa-alcance-trazabilidad.md)

## 1. Alcance

El módulo convierte una decisión clínica tomada dentro del expediente en una **orden de laboratorio
trazable** y permite seguir su estado hasta la validación del resultado. Esta entrega modela el
proceso; no implementa todavía el micro-HIS.

| Dentro del alcance | Fuera del alcance (otros dueños) |
|---|---|
| Emitir la orden desde un paciente, episodio y nota clínica | Catálogo y versiones de pruebas (`lab_tests`, ASII-17) |
| Validar coherencia expediente–episodio–prueba y permisos | Toma y recepción física de muestras (`specimens`) |
| Registrar y consultar los estados de la orden | Captura, validación y publicación de resultados (`lab_result_versions`) |
| Cancelar mientras el proceso lo permita | Notificaciones y alertas críticas (ASII-21) |
| Dejar rastro de cada transición (quién, cuándo, por qué) | Infraestructura de identidad, RBAC y auditoría global |

## 2. Actores y permisos

| Actor | Objetivo | Permiso propuesto |
|---|---|---|
| Médico del expediente | Emitir la orden con las pruebas indicadas | `laboratorio.ordenes.crear` |
| Médico o enfermera | Consultar la orden y su estado | `laboratorio.ordenes.ver` |
| Personal de laboratorio | Avanzar el estado (tomada, en proceso, con resultado) | `laboratorio.ordenes.actualizar` |
| Médico autorizado | Cancelar una orden que aún no terminó | `laboratorio.ordenes.cancelar` |
| Sistema | Validar tenant, idempotencia, transiciones y auditoría | — |

Los nombres `laboratorio.ordenes.*` son **propuestos**: el catálogo RBAC de `develop` todavía no los
define (`PENDIENTES-INTEGRACION.md`, punto 2). El micro-HIS los representa con roles locales y nunca
modifica el RBAC del sistema completo.

## 3. Casos de uso

| ID | Caso de uso | Actor primario | Resultado |
|---|---|---|---|
| CU-OL-01 | Emitir orden desde el expediente | Médico | Orden `ISSUED` con ítems y evento inicial |
| CU-OL-02 | Consultar el estado de una orden | Médico / enfermera | Orden, ítems e historial de estados |
| CU-OL-03 | Avanzar el estado de la orden | Personal de laboratorio | Nueva transición válida registrada |
| CU-OL-04 | Cancelar una orden | Médico autorizado | Orden `CANCELLED` con motivo y actor |
| CU-OL-05 | Listar órdenes por paciente o episodio | Médico / enfermera | Órdenes del tenant, filtradas y paginadas |

### 3.1 Diagrama de casos de uso

```mermaid
flowchart LR
  M([Médico]):::actor
  E([Enfermera]):::actor
  L([Personal de laboratorio]):::actor
  S([Sistema]):::actor

  subgraph OL[Órdenes de laboratorio desde EMR]
    UC1((CU-OL-01<br/>Emitir orden))
    UC2((CU-OL-02<br/>Consultar estado))
    UC3((CU-OL-03<br/>Avanzar estado))
    UC4((CU-OL-04<br/>Cancelar orden))
    UC5((CU-OL-05<br/>Listar órdenes))
    VAL((Validar expediente,<br/>tenant e idempotencia))
  end

  M --> UC1
  M --> UC2
  M --> UC4
  M --> UC5
  E --> UC2
  E --> UC5
  L --> UC3
  L --> UC2
  UC1 --> VAL
  UC3 --> VAL
  UC4 --> VAL
  S -.-> VAL

  classDef actor fill:#eef5ff,stroke:#3b6ea5,stroke-width:1px;
```

### 3.2 Diagrama de actividad

Incluye las decisiones, las excepciones y el resultado del flujo principal.

```mermaid
flowchart TD
  A([Inicio: médico abre el expediente]) --> B[Selecciona paciente, episodio y nota clínica]
  B --> C[Agrega una o más pruebas solicitadas]
  C --> D{¿Hay al menos una prueba<br/>y ninguna repetida?}
  D -- No --> E[422 ORDER_ITEMS_INVALID<br/>no se crea la orden] --> Z([Fin])
  D -- Sí --> F{¿Episodio abierto y del paciente?}
  F -- No --> G[422 CARE_EPISODE_NOT_OPEN<br/>no se crea la orden] --> Z
  F -- Sí --> H{¿Cada prueba está activa y vigente?}
  H -- No --> I[422 LAB_TEST_NOT_ACTIVE<br/>no se crea la orden] --> Z
  H -- Sí --> J{¿La Idempotency-Key<br/>ya fue usada en este hospital?}
  J -- Sí --> K[200 con la orden existente<br/>sin duplicar] --> Z
  J -- No --> L[Crear orden ISSUED + evento inicial]
  L --> M[Guardar orden, ítems e historial<br/>en una transacción]
  M --> N{¿Falló la persistencia?}
  N -- Sí --> O[503 PERSISTENCE_FAILURE<br/>rollback completo] --> Z
  N -- No --> P[201 con orden, estado y correlation_id] --> Z

  style E fill:#ffe8e8,stroke:#b33
  style G fill:#ffe8e8,stroke:#b33
  style I fill:#ffe8e8,stroke:#b33
  style O fill:#ffe8e8,stroke:#b33
  style K fill:#fff7e0,stroke:#a80
  style P fill:#eaf7ea,stroke:#2a7
```

### 3.3 Diagrama de secuencia

Muestra participantes, mensajes, validaciones y respuesta.

```mermaid
sequenceDiagram
  autonumber
  actor M as Médico
  participant UI as Pantalla del expediente
  participant API as API Órdenes
  participant APP as Caso de uso EmitirOrden
  participant DOM as Dominio LabOrder
  participant EMR as Expediente (puerto)
  participant REP as Repositorio

  M->>UI: Selecciona pruebas y confirma emisión
  UI->>API: POST /api/v1/lab-orders<br/>X-Tenant-ID · Idempotency-Key · JWT
  API->>API: Validar autenticación y permiso laboratorio.ordenes.crear
  API->>APP: issue(draft, actor, tenant, key)
  APP->>REP: buscarPorIdempotencia(key, tenant)
  alt La clave ya existe
    REP-->>APP: Orden existente
    APP-->>API: Orden existente (idempotente)
    API-->>UI: 200 OK (sin duplicar)
  else Clave nueva
    APP->>EMR: episodioYNota(paciente, episodio, nota)
    EMR-->>APP: Coherencia y episodio abierto
    APP->>EMR: pruebasActivas(ids)
    EMR-->>APP: Versiones vigentes
    APP->>DOM: issue(datos validados)
    alt Regla de dominio incumplida
      DOM-->>APP: DomainRuleViolation
      APP-->>API: Error de validación
      API-->>UI: 422 con código estable
    else Orden válida
      DOM-->>APP: LabOrder ISSUED + evento
      APP->>REP: guardar(orden, evento) en transacción
      REP-->>APP: OK
      APP-->>API: Orden creada
      API-->>UI: 201 Created + estado ISSUED + correlation_id
    end
  end
  Note over API,REP: Fallo de persistencia → rollback y 503 sin filtrar SQLSTATE
```

## 4. Trazabilidad entre los tres diagramas

| Caso de uso | Nodo de actividad | Mensajes de secuencia | Regla o excepción |
|---|---|---|---|
| CU-OL-01 Emitir | B → C → D → F → H → L → M | `POST /lab-orders` → `issue` → `guardar` | Ítems, episodio abierto, prueba vigente, idempotencia |
| CU-OL-02 Consultar | Lectura de orden e historial | `GET /lab-orders/{id}` → `buscar` | Aislamiento por tenant; 404 si es de otro hospital |
| CU-OL-03 Avanzar | Transición de estado permitida | `PATCH /lab-orders/{id}/status` | Solo transiciones del catálogo; actor autorizado |
| CU-OL-04 Cancelar | Decisión de cancelación y motivo | `POST /lab-orders/{id}/cancel` | No cancelar una orden ya validada; motivo obligatorio |
| CU-OL-05 Listar | Filtro por paciente o episodio | `GET /lab-orders?patient=…` | Paginación limitada y tenant obligatorio |

La narrativa detalla cada decisión, los supuestos y los límites: [`narrativa-alcance-trazabilidad.md`](./narrativa-alcance-trazabilidad.md).

## 5. Evidencia y honestidad

- Los tres diagramas son fuentes Mermaid versionadas en este mismo README; GitHub las renderiza.
- Esta semana es **diseño**: no hay código ejecutado ni capturas que presentar.
- La declaración de uso de IA está en [`DECLARACION_IA.md`](./DECLARACION_IA.md) y queda pendiente de validación humana.
