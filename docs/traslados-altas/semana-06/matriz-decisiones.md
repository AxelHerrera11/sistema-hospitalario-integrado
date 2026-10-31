# Matriz decisión → requisito → evidencia

| Decisión | Requisito | Evidencia verificable | Estado |
|---|---|---|---|
| Solicitudes que se construyen completas o no se construyen | RF-TA-01, RF-TA-02 | `tests/solicitudes.php` | Probado |
| Validar estancia esperada, episodio y T antes de escribir | RF-TA-04 | `tests/reglas.php` (ramas y límites de T) | Probado |
| Validar el destino: inexistente, igual, ocupado y no disponible | RF-TA-05 (R-TA-01 propuesta) | `tests/destino.php`, `tests/postgres.php` (`DESTINO_OCUPADO`) | Probado; R-TA-01 sin acuerdo |
| Cerrar el origen antes de abrir el destino | RF-TA-06; índice de estancia abierta única | `tests/postgres.php` (estancias contiguas) | Probado con PostgreSQL |
| Cerrar estancia y episodio con el mismo T | RF-TA-07 | `tests/alta.php`, `tests/postgres.php` | Probado con PostgreSQL |
| Cuatro resultados excluyentes | RF-TA-08 | `tests/resultados.php`, `tests/protocolo.php` | Probado |
| No tocar `beds.status` | RF-TA-10 | `tests/traslado.php` y la consulta final en PostgreSQL | Probado; acuerdo pendiente con ASII-06 |
| Contrato común y protocolo compartido | RNF-TA-01 (LSP) | Batería LSP en `tests/alta.php` | Probado |
| Una transacción con `FOR UPDATE` | RNF-TA-03, RNF-TA-04 | Reversión ante una FK real (`tests/postgres.php`) | Atomicidad probada; concurrencia **pendiente** |
| COMMIT sin respuesta → incierto, sin reintento | RNF-TA-06 | `tests/protocolo.php`, `tests/http.php` (500 sin `Retry-After`) | Probado con el doble |
| Consulta para resolver un incierto | RNF-TA-06 | `tests/consulta.php` y el recorrido HTTP | Probado |
| Solo identificadores en las respuestas | RNF-TA-07 | `VistaResultado`, `openapi.yaml` | Revisado |
| Dos adaptadores con una misma batería | Semana 4 | `tests/contrato-registro.php` (8/8 y 8/8) | Probado |
| No dividir el servicio | Semana 5 | [frontera de microservicio](../semana-05/cliente-servidor.md#frontera-de-microservicio) | Decisión sin revisión de otra persona |
| `Retry-After` solo en el 503 | Resiliencia (S5) | `tests/http.php` y un 503 real con `php -S` | Probado |
