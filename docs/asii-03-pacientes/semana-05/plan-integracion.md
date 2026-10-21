# Plan de integración cliente-servidor

## Flujo

El frontend llama al HIS por HTTP JSON, no accede a PDO ni a `local_patients`. Envía `X-Tenant-ID` y, opcionalmente, `X-Request-Id`. La capa de identidad confiable valida la sesión y establece `X-User-Ref` para el servicio; no se confía en un valor de usuario enviado directamente por el navegador. El gateway/servidor valida tenant y permiso antes de invocar `PatientController`/`PatientService`. La implementación actual no cablea todavía esta frontera HTTP.

Registro devuelve `201`; el frontend conserva `patient_id` como identificador estable y `code` para consulta y edición. La búsqueda es exacta por código (`GET /api/v1/patients/{code}`), no una búsqueda textual o paginada que el código aún no implementa. Para editar se envía únicamente el conjunto de campos cambiados mediante `PATCH`; el servidor valida el DTO y el dominio vuelve a validar las invariantes.

## Ownership y consistencia

El hospital es dueño de su fila local, código y datos demográficos en `local_patients`. `global_id` referencia lógicamente al MPI central (la migración lo documenta así; no existe FK entre bases). El módulo de Pacientes no administra `mpi_link_status`: ese campo existe en la tabla pero no está en `Patient` ni en el repositorio actual. No se presenta como parte de este contrato.

La persistencia actual protege unicidad de `code`/`global_id`; el dominio impide fechas futuras y valores inválidos. No hay versión, ETag ni control de concurrencia optimista: si dos ediciones se cruzan, prevalece la última escritura. Antes de exponer escritura concurrente relevante, añadir control de versión y prueba de conflicto como evolución explícita, no simularlo con una cabecera que el repositorio no valida.

## Seguridad y errores

Validar tenant y permiso en el servidor en cada solicitud; nunca confiar en un tenant del navegador sin contrastarlo con la identidad autenticada. Autorizar `pacientes.ver`, `pacientes.registrar` y `pacientes.editar` según operación. Exponer el UUID local estable únicamente con el nombre contractual `patient_id`; no exponer el nombre físico de la columna, SQL, credenciales ni detalles internos de PDO. Aplicar TLS, limitar acceso por tenant y minimizar datos en logs; no se incorpora infraestructura de producción ni JWT aquí.

Responder errores con el esquema `Problem` del OpenAPI y `application/problem+json`. No devolver mensajes de excepción o fallos de base de datos. Preservar/generar `request_id` en cada respuesta de error y correlacionarlo en logs con tenant, usuario, operación, estado y duración, sin registrar datos demográficos completos.

## Resiliencia e idempotencia

`GET` es seguro para reintentar; repetir el mismo `PATCH` con los mismos valores conserva el estado solicitado. `POST` no ofrece `Idempotency-Key`: el código y `global_id` únicos impiden crear duplicados, pero una repetición responde conflicto. Tras timeout de alta, consultar por `code` antes de reintentar; no afirmar éxito por un fallback local. El cliente distingue errores de validación/permisos de fallos transitorios y solo reintenta estos últimos con límites y espera incremental.

El módulo actual convierte fallos PDO a `PersistenceFailure`; la frontera HTTP debe traducirlos a error explícito, no devolver una respuesta exitosa vacía. Añadir alarmas/SLO o circuit breaker solo al integrar dependencias remotas; hoy persistencia es local al HIS.

## ¿Microservicio independiente?

**No separar físicamente ahora.** La separación lógica por capas ya permite un límite de módulo, pero varias entidades clínicas conservan FK a `local_patients` (`care_episodes`, citas, alergias y otras). Extraer el dueño de esa tabla antes de migrar consumidores rompería integridad referencial y convertiría lecturas locales en llamadas distribuidas.

Beneficio potencial: despliegue, escalado y ownership de Pacientes independientes. Costos/riesgos actuales: nueva operación y observabilidad, disponibilidad de red en flujos clínicos, contratos/versionado, seguridad multi-tenant, consistencia eventual, migración de FK y manejo de fallos/duplicados. La identidad global añade dependencia del MPI; no se debe asumir que existe ya una sincronización remota.

Reconsiderar solo cuando haya equipo/operación responsable y una necesidad demostrada de despliegue o escalado independiente, y antes de extraer: inventariar y migrar todos los consumidores FK a identificadores estables y API/eventos versionados, eliminar dependencias de escritura compartida, definir SLO/seguridad por tenant y probar degradación, reintentos y reconciliación. Hasta entonces, mantener el módulo en el HIS y su base hospitalaria.
