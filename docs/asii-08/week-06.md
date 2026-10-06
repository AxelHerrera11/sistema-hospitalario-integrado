# Documento del Área 8: Alertas Críticas, Notificaciones, Dashboard y Reportes

*Responsable:* Cindy Ruano
*Módulos originales:* 9, 21, 23 y 27
*Entrega:* Semana 6 — Primera Evaluación Parcial y Estrategia de Defensa
*Sistema:* Sistema Hospitalario Integrado (HIS)

## 1. Síntesis Arquitectónica y Justificación

### Seguridad multitenant y RBAC

X-Tenant-ID establece el hospital de contexto, auth.jwt comprueba que coincida con el tenant del token y BelongsToTenant limita consultas y asigna el tenant al crear. Las referencias a paciente, resultado, destinatario y alerta se validan bajo ese mismo tenant. Un ID ajeno responde 404; no se confía en tenant_id del payload ni se omite el scope para resolver IDs del cliente. Spatie verifica los permisos por ruta. Médico Tratante y Director comparten hoy el rol técnico Médico, por lo que no se les pueden otorgar permisos distintos sin definir rol/capacidad nueva.

### Rendimiento

El objetivo para ingesta es p95 <= 200 ms bajo la carga definida en Semana 2, medido hasta persistencia y separado de la latencia de transporte. Índices compuestos por tenant, destinatario, acuse, severidad y fecha respaldan listas/KPIs; el paginador limita las lecturas. Las métricas se calculan mediante agregaciones SQL y ventanas de tiempo acordadas con las áreas propietarias.

### Resiliencia e idempotencia

La alerta se persiste una sola vez mediante clave única (tenant_id, source_event_id). En el monolito, validación del resultado crítico de Área 7 y alerta comparten transacción. La publicación ocurre después del commit; falla de WebSocket/SSE no borra la alerta. Si se separan procesos, outbox y reintentos garantizan entrega eventual sin duplicar el registro.

### Desacoplamiento y trazabilidad

Áreas 5, 6 y 7 determinan si su dato produce evento crítico; Área 8 gestiona ingesta, acuse, notificación, dashboard y reportes. NotificationPublisherInterface desacopla dominio de canal database/WebSockets/SSE. audit_logs registra creación y acuse con actor, tenant y entidad; acknowledged_by identifica quién recibió la alerta, que no es necesariamente su destinatario.

### Compatibilidad de persistencia

El esquema actual de critical_alerts usa ID bigint incremental y no posee severity, payload, acknowledged_by ni clave idempotente propia. La evolución se implementa con migración aditiva y pruebas/backfill. Si se decide UUID, debe coordinarse con FKs y consumidores; audit_logs.entity_id también es unsignedBigInteger, por lo que la auditoría requiere una referencia compatible antes de cambiar el ID.

## 2. Matriz de Validación y Checklist

| Verificación            | Criterio de salida                                                                                        | Estado para la evaluación                                  |
|-------------------------|-----------------------------------------------------------------------------------------------------------|------------------------------------------------------------|
| RF/RNF                  | Requisitos medibles y estrategia de validación documentados.                                              | Especificado; validar en implementación.                   |
| Componentes y capas     | Controllers delgados, servicios SRP y persistencia PostgreSQL definida.                                   | Diseño documentado; rutas/servicios pendientes.            |
| Tenant/JWT/RBAC         | Token coincide con X-Tenant-ID, scope activo, permisos Spatie y 404 entre tenants.                        | Middleware base existe; endpoints del Área 8 pendientes.   |
| Idempotencia            | Restricción única por tenant/evento y pruebas de concurrencia.                                            | Requiere campo/índice y pruebas.                           |
| Acuse auditado          | Actor desde JWT, tiempo de servidor, transacción y fila audit_logs.                                       | Requiere acknowledged_by, servicio y pruebas.              |
| Migración compatible    | Decisión bigint vs UUID, FKs y auditoría compatibles, migración reversible.                               | Decisión pendiente.                                        |
| Integración áreas 5/6/7 | Contratos, destinatarios y pruebas de tenant, duplicidad, atomicidad y transporte.                        | Acuerdos con responsables pendientes.                      |
| KPI y reportes          | Definiciones de estados/fechas, filtros, tenant y permisos validados.                                     | Métricas y permiso de dashboard pendientes de aprobación.  |
| Rendimiento             | Benchmark repetible prueba p95 <= 200 ms con la carga acordada.                                           | Objetivo especificado; evidencia pendiente.                |
| Pruebas de entrega      | php artisan test, php artisan test --configuration=phpunit.pgsql.xml y npm run build verdes antes del PR. | Ejecutar al implementar; no son pruebas de este documento. |

## 3. Puntos de Defensa Técnica

- *Por qué no enviar WebSocket dentro de una transacción:* un canal externo puede fallar o tardar; persistir primero mantiene integridad clínica y publicar post-commit evita revertir/duplicar efectos externos.
- *Cómo evita fuga entre hospitales:* middleware valida tenant↔token, BelongsToTenant aplica scope y las referencias se resuelven dentro del mismo contexto; los IDs ajenos se ocultan con 404.
- *Cómo evita duplicados:* clave idempotente persistente con índice único por tenant, manejando la colisión concurrente en base de datos y devolviendo el recurso existente.
- *Por qué separar servicios:* ingesta, publicación y KPIs tienen motivos de cambio distintos; sus contratos permiten pruebas enfocadas y sustitución de transporte sin cambiar las reglas de origen.
- *Cómo se audita el acuse:* identidad del actor viene del JWT, no del payload; acknowledged_by/acknowledged_at y evento append-only se registran de forma atómica.
- *Qué no está decidido:* mapeo clínico de severidad/umbrales, identidad de servicio de ingesta, rol diferenciado de Médico Director, permiso de dashboard y compatibilidad de PK/auditoría.

## 4. Decisiones Pendientes para Cierre

1. Aprobación clínica de severidades, umbrales de signos vitales y destinatarios.
2. Decidir si se conserva bigint o se migra critical_alerts.id a UUID; acordar el formato de referencia en audit_logs.
3. Definir autenticación de eventos de sistema; en el monolito preferir llamada interna al servicio dentro de la transacción.
4. Acordar con Área 7 la clave idempotente y la creación atómica de la alerta en la validación de resultado.
5. Definir si Enfermería recibe alertas.confirmar, cómo diferenciar Médico Director y qué rol puede exportar; aplicar cambios únicamente en RoleSeeder.php mediante PR.
6. Elegir canal inicial de notificaciones y criterio para incorporar WebSockets, SSE u outbox.
