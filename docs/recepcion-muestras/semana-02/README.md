# Semana 2 — Requisitos y LSP

| ID | Requisito y aceptación observable |
|---|---|
| RF01 | Aceptar solo si código y nombre coinciden con orden y requisitos cumplen. Responder accepted. |
| RF02 | Aceptar cambia ítem a received en la misma transacción; un fallo no cambia estado. |
| RF03 | Código único verificable; consulta y reimpresión devuelven el mismo código. |
| RF04 | Ítem cancelado/recibido devuelve 409; ajeno/inexistente 404. |
| RF05 | Rechazo guarda motivo controlado y code=null; límites exactos de volumen/tiempo/temperatura aceptan. |
| RF06 | Rechazo cambia ítem a pending_new_sample y registra aviso en outbox atómicamente. |
| RNF01 | JWT, permisos y tenant comprobados en servidor; consulta ajena no revela muestras. |
| RNF02 | PDO preparado, rollback, sin SQL en controlador; fallo no deja aceptación parcial. |
| RNF03 | Formulario por teclado, etiquetas, feedback y adaptación móvil; auditoría manual pendiente. |

Antes: un controlador con condiciones por tipo, SQL e impresión sería difícil de sustituir/probar.
Después: RequirementsValidator retorna null/motivo para todos los tipos; SampleValidator parametrizado
con blood/urine/swab mantiene el contrato. ReceiveSpecimen depende de ReceptionRepository.
Pruebas: aceptación blood/urine/swab y rechazos en `tests/Unit/Reception` desde semana 6.
No se implementaron las clases BloodSpecimenValidator descritas en los documentos históricos.
Fuente obligatoria consultada: [MVP Cluster, SOLID](https://mvpcluster.com/diseno-de-software-2/).
Aplicación propia: conservar entradas/salidas y semántica del contrato al sustituir un validador.

Código del HIS: `app/Modules/Reception/Domain` y `Application`, con autoload PSR-4 de Laravel.
RF07: request_key conserva resultado ante reenvío idéntico; cambiar datos o actor con la misma clave da 409.
RF08: colisión del identificador reintenta la transacción hasta tres veces; otros fallos no se reintentan.
Decisión de código: 96 bits aleatorios y dígito decimal, sin secuencia por hospital; UNIQUE valida unicidad.
Esta evolución sustituye el formato ilustrativo de semana 1 y evita coordinar secuencias entre hospitales.
