# Matriz de riesgos

| Riesgo | Prob. | Impacto | Nivel | Control preventivo / detectivo | Responsable |
|---|---|---|---|---|---|
| Aislamiento tenant: conexión `hospital` no se selecciona dinámicamente por hospital. | Media | Alto | Alto | Definir resolver/conexión por tenant antes de multi-hospital; probar cruce de tenant en PostgreSQL. | Tenant/DB |
| Bypass RBAC al añadir rutas o cambiar middleware. | Baja | Alto | Medio | Permiso explícito por ruta; prueba permitidos/denegados y revisión de rutas. | API/RBAC |
| Conexión hospital fija/configurada, sin cableado dinámico al tenant. | Alta | Alto | Alto | No declarar aislamiento dinámico; diseñar selección segura y verificar conexión/tenant en integración. | DB/Tenant |
| Exposición de errores internos en respuestas. | Baja | Alto | Medio | Respuesta genérica y `no-store`; prueba que excluye SQLSTATE, PDO y rutas internas. | Pacientes/QA |
| Inconsistencia de IDs entre esquema, dominio y contrato. | Media | Medio | Medio | Mapear `id` a `patient_id` en la frontera; aserción que excluye `id` en JSON. | Pacientes/API |
| Falta de prueba PostgreSQL real del repositorio y tenant. | Alta | Alto | Alto | Agregar prueba con esquema hospital PostgreSQL y conexión aislada antes de ampliar el slice. | QA/DB |
