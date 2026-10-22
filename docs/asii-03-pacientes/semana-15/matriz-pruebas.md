# Matriz de pruebas

Resultados registrados de las ejecuciones locales; HTTP usa SQLite/PDO y dobles
de tenant/JWT. PostgreSQL y middleware de identidad reales no están cubiertos.

| Requisito/regla | Tipo | Caso | Resultado esperado | Evidencia/resultado |
|---|---|---|---|---|
| Fecha futura | Unitaria | Servicio registra fecha futura | Rechazo `BIRTH_DATE_FUTURE` | `PatientServiceTest`, PASS |
| Código duplicado | Unitaria | Segundo registro del mismo código | Rechazo `PATIENT_CODE_EXISTS` | `PatientServiceTest`, PASS |
| Edición válida | Unitaria | Cambiar nombre | Actualiza datos, conserva identidad | `PatientServiceTest`, PASS |
| Búsqueda | Caja blanca | `search` encuentra registro | Rama encontrada | `PatientServiceTest`, PASS |
| Búsqueda | Caja blanca | `search` sin registro | Rama not found | `PatientServiceTest`, PASS |
| Alta | Caja negra | POST válido | 201, envelope y `patient_id` | `PatientApiTest`, PASS |
| Duplicados | Caja negra | POST código/global_id existentes | 409 | `PatientApiTest`, PASS |
| Validación | Caja negra | POST UUID/fecha inválidos | 422 con campo | `PatientApiTest`, PASS |
| Edición | Caja negra | PATCH válido | 200; ID/código estables | `PatientApiTest`, PASS |
| No encontrado | Caja negra | PATCH código ausente | 404 | `PatientApiTest`, PASS |
| Acceso | Caja negra | POST/PATCH sin permiso | 403 | `PatientApiTest`, PASS |
| Integración GET | Caja negra | GET, auth/tenant, error PDO | 200/404/401/403/500 esperados | `PatientApiTest`, PASS |

**Totales:** Unitarias 5/5 (10 aserciones); HTTP 10/10 (47 aserciones).
PHPUnit emite warnings ambientales por ausencia del archivo `.env`.
