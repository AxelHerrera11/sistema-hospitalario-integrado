# Matriz decisión → evidencia

Cada decisión indica el requerimiento que la motiva, el archivo donde vive y la prueba que la sostiene. Las rutas son relativas a la raíz del repositorio y los PR son los de la entrega semanal.

| # | Decisión | Requerimiento | Evidencia en código o documento | Prueba | PR |
|---|---|---|---|---|---|
| D1 | Solo el médico tratante registra | RF-SV-01, RNF-SV-01 | `docs/signos-vitales/semana-01/narrativa-alcance-trazabilidad.md`; trigger en migración `000401` | `run.php`: «asignación terminada → 403» y «episodio cerrado → 409» | #131, #132 |
| D2 | Lo imposible se rechaza antes de evaluar umbrales | RF-SV-02 | `micro-his/signos-vitales/src/Domain/VitalSigns.php` | `run.php`: «temperatura 50 °C → 422 antes de evaluar» | #131 |
| D3 | Las reglas de umbral son sustituibles | RF-SV-03, RF-SV-05, RNF-SV-02, RNF-SV-05 | `src/Domain/VitalSignRule.php`, `RangeRule.php` (`evaluate()` final), `ThresholdEvaluator.php`; `docs/signos-vitales/semana-02/solid-lsp.md` | `run.php`: «LSP: las 6 reglas cumplen la misma prueba de contrato»; `docs/signos-vitales/semana-02/evidencia/demo-lsp.php` | #67, #131 |
| D4 | La severidad es el máximo de los hallazgos | RF-SV-04 | `src/Domain/Assessment.php`, `Severity.php` | `run.php`: «FC 138 + SpO2 94 → critical con dos hallazgos» | #131 |
| D5 | Sin perfil aplicable se guarda y se avisa | RF-SV-06 | `src/Application/RecordVitalSigns.php` (`ADULT_AGE`) | `run.php`: «paciente de 9 años → `THRESHOLDS_NOT_APPLICABLE`, auditado y sin alerta» | #131 |
| D6 | Medición y auditoría en una transacción; se notifica después del `COMMIT` | RF-SV-07, RNF-SV-03 | `src/Persistence/PdoVitalSignRepository.php`, `QueuedAlertNotifier.php` | `run.php`: «auditoría caída → 500, rollback y sin notificación» | #132 |
| D7 | `VitalSignRepository` vive en Application con adaptadores PDO e InMemory | DIP | `src/Application/VitalSignRepository.php`, `src/Persistence/` | `repository.php`: 9 casos contra los dos adaptadores | #529 |
| D8 | El controlador no tiene SQL ni reglas | SRP | `src/Presentation/VitalSignController.php`, `Router.php`, `Request.php`, `JsonView.php` | `run.php`: «el controlador y el router no contienen SQL, PDO ni reglas de umbral» | #530 |
| D9 | El mismo MVC corre con PDO o InMemory | DIP, LSP | `app.php` (`signos_vitales_router`) | `run.php`: «InMemory: el mismo MVC da 201 critical…» | #530 |
| D10 | API versionada, permisos `modulo.accion` y falla cerrada | RNF-SV-01 | `src/Presentation/ApiKernel.php`; `docs/signos-vitales/semana-05/contrato-api.md`, `openapi.yaml` | `api.php`: 401, 403 con permiso faltante, «RBAC caído → 503», «ruta sin permiso → 404» | #546, #549 |
| D11 | Consultar exige permiso y asignación activa | RNF-SV-01 | `src/Application/GetVitalSign.php` | `api.php`: «GET de quien no atiende el episodio → 403 `NOT_TREATING_PHYSICIAN`» | #549 |
| D12 | Todo error y respuesta lleva `trace_id`; la bitácora no lleva datos clínicos | Observabilidad | `ApiKernel::handle()` | `api.php`: «la bitácora registra trace_id, actor, método, ruta y estado, sin el cuerpo» | #549 |
| D13 | El cliente reintenta solo lecturas | Resiliencia | `src/Client/VitalSignApiClient.php`, `StreamTransport.php` | `client.php`: lectura 2 intentos, registro 1 intento | #550 |
| D14 | No dividir en microservicio | Criterios medibles | `docs/signos-vitales/semana-05/frontera-microservicio.md` | `php micro-his/signos-vitales/tests/bench.php`: p95 0,087 ms con PDO en proceso | #546 |
| D15 | Normalizar unidades en la frontera, no en las reglas | R3, RF-SV-03 | [cambio-practico.md](./cambio-practico.md) | Demo ejecutable en la Semana 7 | (Semana 6) |

## Cómo leer una fila

D6 como ejemplo: RF-SV-07 pide que un valor anormal no exista sin traza → la decisión es una sola transacción con la auditoría → el archivo es `PdoVitalSignRepository.php` → la prueba fuerza la caída de `local_access_audits` y comprueba que `vital_signs` queda en 0 filas y la cola vacía → llegó en #132.
