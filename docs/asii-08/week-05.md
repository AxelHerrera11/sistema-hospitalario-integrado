# Documento del Área 8: Alertas Críticas, Notificaciones, Dashboard y Reportes

*Responsable:* Cindy Ruano
*Módulos originales:* 9, 21, 23 y 27
*Entrega:* Semana 5 — Contrato API Preliminar y Plan de Integración
*Sistema:* Sistema Hospitalario Integrado (HIS)

## 1. Especificación de Endpoints

Todos los endpoints bajo /api/v1 requieren Accept: application/json y X-Tenant-ID UUID. Las rutas de usuario requieren Authorization: Bearer <token>; el tenant debe coincidir con el JWT. La ingesta de eventos requiere identidad autorizada de sistema/origen; no es anónima ni se concede solo por tener alertas.ver.

| Método y ruta                | Autorización propuesta                                                                | Propósito                                                                                                          |
|------------------------------|---------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------|
| POST /api/v1/alerts          | Identidad autenticada del módulo origen/servicio; tenant + autenticación de servicio. | Ingesta idempotente desde Áreas 5, 6 y 7. En monolito se invoca el servicio internamente para sostener atomicidad. |
| GET /api/v1/alerts           | tenant + auth.jwt + permission:alertas.ver.                                           | Listado paginado con filtros.                                                                                      |
| POST /api/v1/alerts/{id}/ack | tenant + auth.jwt + permission:alertas.confirmar.                                     | Acusar recibo y auditar al actor autenticado.                                                                      |
| GET /api/v1/dashboard/kpis   | tenant + auth.jwt + permiso de dashboard por acordar.                                 | Métricas hospitalarias. dashboard.ver no existe actualmente; debe agregarse a RoleSeeder.php si se aprueba.        |

### Ingesta idempotente

Se requiere Idempotency-Key o un source_event_id estable; la identidad de la clave se limita al tenant.

```http
POST /api/v1/alerts
Accept: application/json
Content-Type: application/json
X-Tenant-ID: 00000000-0000-4000-8000-000000000001
Idempotency-Key: area7-result-9812-critical-v1
Authorization: Bearer <token-de-servicio>
```

```json
{
  "source_module": "laboratorio",
  "source_event_id": "area7-result-9812-critical-v1",
  "alert_type": "valor_critico_lab",
  "severity": "critical",
  "patient_id": 42,
  "notified_user_id": 17,
  "lab_result_id": 9812,
  "message": "Resultado de laboratorio crítico requiere revisión.",
  "payload": {
    "lab_order_id": 1530,
    "priority": "STAT"
  }
}
```

El servidor valida tipo/severidad, tenant de referencias, destinatario e identidad del origen. tenant_id, acknowledged, acknowledged_by y fechas de acuse no son aceptados del cliente. Primera inserción: 201 Created, Location y recurso envuelto como alert. Repetición idéntica: 200 OK con recurso existente. Misma clave con contenido distinto: `422` sin mutación.

El flujo del Área 7 exige que validar resultado crítico y crear critical_alerts ocurran en una transacción. En el monolito se llama al servicio compartido dentro de esa transacción; para un consumidor externo se usa outbox, reintentos y consistencia eventual explícita, no HTTP autollamado.

### Listado paginado y filtros

```http
GET /api/v1/alerts?severity=critical&acknowledged=false&alert_type=valor_critico_lab&per_page=15&page=1
Accept: application/json
X-Tenant-ID: 00000000-0000-4000-8000-000000000001
Authorization: Bearer <token>
```

Filtros propuestos: severity (critical, warning, info), acknowledged (true/false), alert_type, date_from, date_to, page, per_page (default 15, máximo 50). Cualquier columna de orden debe estar en lista blanca. La colección retorna directamente el paginador nativo Laravel, sin envoltorio propio, conforme a [`contrato-api.md`](../contrato-api.md).

### Acuse de recibo

```http
POST /api/v1/alerts/550e8400-e29b-41d4-a716-446655440000/ack
Accept: application/json
X-Tenant-ID: 00000000-0000-4000-8000-000000000001
Authorization: Bearer <token>
```

No requiere body. El servidor resuelve la alerta dentro del tenant, toma actor del JWT y actualiza en una transacción acknowledged=true, acknowledged_by y acknowledged_at. Primera ejecución y repetición idempotente responden 200 OK con `{ "alert": { ... } }`. Un registro de otro tenant responde 404. El acuse no cierra ni resuelve clínicamente la alerta.

### KPI hospitalarios

```http
GET /api/v1/dashboard/kpis?as_of=2026-10-05T12:00:00Z
Accept: application/json
X-Tenant-ID: 00000000-0000-4000-8000-000000000001
Authorization: Bearer <token>
```

Ejemplo de respuesta (cifras ilustrativas):

```json
{
  "as_of": "2026-10-05T12:00:00Z",
  "metrics": {
    "occupied_beds": 84,
    "admissions_today": 12,
    "lab_orders_pending": 19,
    "critical_alerts_pending": 3
  },
  "occupancy_by_ward": [
    { "ward_id": 4, "ward_name": "Medicina Interna", "occupied": 18, "capacity": 24 }
  ]
}
```

Las definiciones de cama ocupada, admisión del día, orden pendiente y ventana de alertas deben basarse en estados reales de los módulos propietarios, no en conteos indiscriminados.

## 2. Respuestas y Manejo de Errores

Seguir [`contrato-api.md`](../contrato-api.md): recurso individual envuelto por su nombre singular (alert), paginador Laravel directo, mensajes en español y errores con al menos message; validaciones/reglas incluyen errors por campo. No crear `{success, data}`.

| Código                   | Uso en Área 8                                                                                                                      |
|--------------------------|------------------------------------------------------------------------------------------------------------------------------------|
| 200 OK                   | Listado, KPI, acuse o repetición idempotente equivalente.                                                                          |
| 201 Created              | Alerta nueva; incluir Location cuando exista recurso identificable.                                                                |
| 400 Bad Request          | Falta X-Tenant-ID o no es UUID.                                                                                                    |
| 401 Unauthorized         | Token o credencial de servicio ausente, inválido o expirado.                                                                       |
| 403 Forbidden            | Falta permiso o token no corresponde al tenant de la cabecera.                                                                     |
| 404 Not Found            | ID inexistente, de otro tenant o no visible; no revelar cuál situación ocurrió.                                                    |
| 422 Unprocessable Entity | Input/filtro inválido, referencia inválida o ajena al tenant, transición no permitida o clave idempotente con contenido diferente. |

Ejemplo `422`:

```json
{
  "message": "La severidad indicada no es válida.",
  "errors": {
    "severity": ["La severidad debe ser critical, warning o info."]
  }
}
```

El contrato común no define 409; se propone representar una clave idempotente reutilizada con otro payload como 422, sin modificar la alerta original.

## 3. Plan de Integración con Áreas 5, 6 y 7

| Área                                | Evento / responsabilidad de origen                                                        | Integración y coordinación                                                                                      |
|-------------------------------------|-------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------|
| SOAP, diagnósticos y signos vitales | Define umbral y produce signo_vital_anormal con source_event_id, paciente y destinatario. | Acordar política clínica, destinatarios y repetición; Área 8 no recalcula el dato de origen.                    |
| Alergia, medicamento y prescripcion | Detecta alergia_prescripcion en el punto acordado del flujo.                              | Definir si ocurre al guardar o firmar, comportamiento de bloqueo y destinatario. Área 8 no silencia validación. |
| Laboratorio                         | Valida resultado crítico y genera valor_critico_lab dirigido al médico que ordenó.        | Mantener alerta única en validación; acordar ID de origen y columnas; no usar sent_to_emr como estado.          |

Cada contrato define tenant, actor, tipo/severidad, paciente, destinatario, referencia de origen, idempotencia y reintentos. Probar tenant cruzado, duplicados concurrentes, referencias inexistentes, destinatario no autorizado, falla de transporte y atomicidad. Cambios a tablas compartidas o RoleSeeder.php requieren PR coordinado.
