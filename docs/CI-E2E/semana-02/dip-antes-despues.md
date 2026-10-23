# Principio de inversión de dependencias (DIP) aplicado al flujo

> Fuente obligatoria del curso: SOLID según <https://mvpcluster.com/diseno-de-software-2/>. DIP: los módulos de alto nivel no dependen de los de bajo nivel; ambos dependen de abstracciones.

## Antes (diseño descartado, no existe en el repositorio)

El primer boceto ponía la decisión dentro del propio pipeline: cada paso del YAML terminaba con su exit code y un script leía archivos, consultaba la base y escribía el resumen de GitHub en la misma función.

```php
// BOCETO DESCARTADO — ilustra el acoplamiento que se evitó.
function decidir(): int {
    $pdo = new PDO(getenv('DB_DSN'));                            // detalle de persistencia
    $xml = simplexml_load_file('qa-evidence/phpunit/unit.xml');  // detalle de formato
    if ((int) $xml->testsuite['failures'] > 0) {                 // regla de negocio mezclada
        file_put_contents(getenv('GITHUB_STEP_SUMMARY'), 'FALLÓ'); // detalle de la plataforma CI
        return 1;
    }
    $pdo->exec("INSERT INTO qa_runs ...");                       // SQL junto a la regla
    return 0;
}
```

Problemas: la regla «¿se aprueba la versión?» dependía de PDO, de SimpleXML, del formato de PHPUnit y de GitHub; no se podía probar sin base ni sin GitHub; una suite **sin reporte** pasaba como aprobada porque nadie la contaba; cambiar de CI obligaba a reescribir la regla.

## Después (código actual)

```text
        alto nivel                                   bajo nivel (adaptadores)
Domain\QualityGate  (reglas puras, sin dependencias)
Application\EvaluateQualityGate ──► Domain\TestRunRepository «interface» ◄── Persistence\PdoTestRunRepository
                                │                                         ◄── Persistence\InMemoryTestRunRepository
                                ├─► Application\AccessGate «interface»    ◄── Integration\StaticAccessGate (hoy) / RBAC (propuesto)
                                ├─► Application\AuditTrail «interface»    ◄── Integration\GobernanzaAuditTrail
                                └─► Application\Clock «interface»         ◄── Integration\SystemClock
Ci\GateRunner usa los MISMOS casos de uso; Ci\JUnitReportReader traduce el formato.
QaModule::compose() es el único lugar que conoce las clases concretas.
```

| Responsabilidad | Clase | Depende de |
|---|---|---|
| Reglas del gate (vacío no es éxito, umbrales, omitidas) | `Domain/QualityGate.php` | Solo tipos del dominio |
| Orquestar: autorizar → cargar → evaluar → guardar → auditar | `Application/EvaluateQualityGate.php` | Interfaces `TestRunRepository`, `AccessGate`, `AuditTrail`, `Clock` |
| Guardar con sentencias preparadas | `Persistence/PdoTestRunRepository.php` | `\PDO` (detalle) |
| Leer el formato JUnit | `Ci/JUnitReportReader.php` | SimpleXML (detalle) |
| Publicar en GitHub | `bin/quality-gate.php` + `Ci/GateReportWriter.php` | `GITHUB_STEP_SUMMARY` (detalle) |

## Evidencia verificable

| Afirmación | Cómo se comprueba | Resultado |
|---|---|---|
| El dominio no importa ninguna otra capa ni PDO | `php micro-his/qa-calidad-versiones/tests/architecture.php` → AR01, AR02 | OK (ver [evidencias/architecture.txt](evidencias/architecture.txt)) |
| La aplicación solo depende de Domain/Application | AR03 | OK |
| El mismo caso de uso funciona con tres almacenes distintos | A01 (InMemory), P01 (PDO SQLite), A11/BN23 (doble que falla) | OK |
| Cambiar el almacén no toca la regla | `ModuleFactory::repository(null \| ruta)` elige InMemory o PDO; `QualityGate` no cambia | revisión de código |
| Una suite sin reporte ya no se aprueba | CI18: exit 2 «BLOQUEADO» con nota | OK |
