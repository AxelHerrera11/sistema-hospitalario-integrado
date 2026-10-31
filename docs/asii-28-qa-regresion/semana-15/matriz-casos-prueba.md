# Matriz de nueve casos ejecutados

Fuente ejecutable: [tests/semana15.php](../../../micro-his/qa-regresion/tests/semana15.php).
Versión: base 08796d85 + este runner nuevo. Comando para todos:
`php micro-his/qa-regresion/tests/semana15.php`; [salida real](./ejecucion-evidencias.md).
Precondición general: PHP 8.2+, proc_open habilitado y pdo_sqlite para tres procesos CLI.
UT/WB y BB-04 no conectan a base; cada caso crea entidad/doble/controlador propios.
Datos comunes D: purpose=“Caso ficticio S15”, steps=“1. Abrir\n2. Revisar”, expected=“Campos visibles”.
Identidades generadas se comprueban por forma, sin depender de un valor aleatorio concreto.
RF-01 es diseño de caso completo; “Técnico” no inventa RF/RNF de transporte/errores.

| ID / tipo | Objetivo / requisito | Componente | Precondición y entrada | Procedimiento / esperado | Observado / evidencia | Estado |
|---|---|---|---|---|---|---|
| UT-01 / Unitaria | Rechazar definición incompleta; RF-01 para textos, ID como invariante técnica | ManualCase::__construct | Sin dependencias; cuatro tuplas: ID vacío o uno de los tres textos solo blanco | Construir cada tupla; capturar DomainException; exigir cuatro rechazos | Cuatro excepciones; PASS UT-01 | Aprobado |
| UT-02 / Unitaria | Conservar definición; RF-01; readonly no acredita RNF-03 de ejecuciones | ManualCase | ID fijo; propósito con espacios, pasos multilínea, esperado con tabulador final | Construir; comparar cuatro campos normalizados; intentar alterar expected; exigir Error y valor intacto | Trim correcto, salto interno conservado y escritura rechazada; PASS UT-02 | Aprobado |
| UT-03 / Unitaria con doble | Guardar caso diseñado una vez; RF-01 | DesignCase::execute | S15Repository nuevo, D | Ejecutar; exigir un save, cero find y misma instancia guardada/retornada con textos D | Una escritura, cero lecturas, misma entidad; PASS UT-03 | Aprobado |
| WB-01 / Caja blanca | Recorrer guardia de formato; control técnico de entrada, apoyo RF-01 | CaseController::create | Doble espía; null, purpose array, ausencia de expected y D válido | Invocar inválidos→400 exacto/cero save; válido→201/un save | Tres rechazos y un camino aceptado; PASS WB-01 | Aprobado |
| WB-02 / Caja blanca | Distinguir errores de dominio y almacenamiento; RF-01 solo para rechazo vacío | CaseController::create / DesignCase | D con steps blanco; luego D con doble fail=true | Exigir catch dominio→422 sin save; catch StorageFailure→503 exacto con un intento y sin datos guardados | Ambos catch y ausencia de detalle privado comprobados; PASS WB-02 | Aprobado |
| BB-01 / Caja negra CLI | Registrar definición válida por contrato público; RF-01 | demo.php por STDIN/STDOUT | SQLite en memoria del subproceso; D con propósito rodeado de espacios | Enviar JSON; exigir salida proceso 0, stderr vacío, status 201, cuatro claves/textos e ID hex32 | Sobre 201 y valores correctos; PASS BB-01 | Aprobado |
| BB-02 / Caja negra CLI | Rechazar JSON malformado; técnico, sin RF/RNF específico | demo.php | Proceso nuevo; entrada literal `{` | Enviar sin inspeccionar ramas; exigir 400/INVALID_JSON exacto, salida 0 y stderr vacío | Error público exacto; PASS BB-02 | Aprobado |
| BB-03 / Caja negra CLI | Rechazar esperado vacío tras normalizar; RF-01 | demo.php | D con expected espacio+tabulador+salto de línea | Enviar JSON estructuralmente válido; exigir 422/CASE_INCOMPLETE exacto, salida 0 y stderr vacío | Error público exacto; PASS BB-03 | Aprobado |
| BB-04 / Caja negra controlador | Responder seguro y recuperarse al restaurar dependencia; técnico sin RF/RNF específico | Interfaz pública CaseController::create | Doble que falla y luego se restaura; D válido | Evaluar solo respuestas: primera 503 exacta sin detalle; segunda llamada explícita 201 con textos D | 503 seguro y nueva llamada 201; PASS BB-04 | Aprobado |

## Decisiones internas ejercitadas, sin porcentaje de cobertura

- WB-01: `src/Presentation/CaseController.php`, método create, condición combinada
  `!is_array($input) || !isset($input[$key]) || !is_string($input[$key])`.
  null/array/campo ausente provocan retorno temprano; D recorre validación y try.
- WB-02: mismo método, catch DomainException tras crear caso incompleto, antes de save;
  con D completo el doble lanza StorageFailure durante save y se toma el segundo catch.
  No se afirma cobertura exhaustiva de cortocircuitos, ramas o sentencias.

Son nueve cierres de prueba nuevos, no aliases del runner anterior. Hay escenarios
preexistentes de vacío/alta/error; aquí se añaden aislamiento directo de unidades,
readonly, conteo de interacciones/identidad, ramas combinadas, automatización CLI
en procesos reales y recuperación tras restablecer dependencia. No se copió #1402.
