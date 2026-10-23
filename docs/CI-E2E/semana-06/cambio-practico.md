# Registro del cambio práctico defendido

**Cambio:** «Un archivo de evidencia adicional vacío (por ejemplo el log de `php -l`, que no imprime nada cuando todo está bien) no debe invalidar la decisión del gate de toda la versión».

## Cómo apareció (21-oct-2026)

La primera corrida completa de `bin/pipeline-local.sh` sobre `develop` terminó así:

```text
==> Quality gate
ERROR DE CONFIGURACIÓN DEL GATE: MicroHis\QaCalidad\Domain\InvalidQaInput: Una evidencia vacía no demuestra nada.
quality-gate exit=3 (0 aprobado, 1 rechazado, 2 bloqueado, 3 configuración)
```

La regla de dominio era correcta (`EvidenceRef` rechaza 0 bytes, U12), pero `GateRunner` la dejaba escapar y el CLI la trataba como error de configuración: un detalle de una etapa ocultaba los resultados de las 12 suites.

## Decisión y alternativa descartada

| Opción | Efecto | Elegida |
|---|---|---|
| Aceptar evidencias de 0 bytes | Debilita RNF-03 (una evidencia vacía no demuestra nada) | No |
| Descartar la evidencia **adicional** inválida con una nota visible y seguir con el JUnit de la suite | El gate decide; la omisión queda en «Notas de recolección» | **Sí** |
| Además, que `junit-command.php` escriba siempre un pie en el log | El log deja de estar vacío aunque el comando no imprima nada | **Sí** |

## Cambios en el código

- `src/Ci/GateRunner.php`: cada evidencia adicional se recolecta dentro de `try/catch (RuntimeException|InvalidQaInput)` y se anota «evidencia adicional descartada … (motivo)».
- `bin/junit-command.php`: agrega `[junit-command] <comando> → exit N en X s` al final de cada log.
- El JUnit principal sigue siendo obligatorio: si falta, la suite queda sin resultado y el gate bloquea (CI18 sin cambios).

## Evidencia después del cambio

- Prueba de regresión `tests/ci.php` **CI21**: un log vacío se descarta con nota y el gate termina con exit 0.
- Segunda corrida de `pipeline-local.sh`: el gate decidió `RECHAZADO` por un hallazgo real (`SKIPPED_IN_REQUIRED` en micro-his por `signos-vitales` sin `tests/run.php`; sobre `origin/develop` 5e6f0044 ya son 4 módulos), con la nota de la evidencia descartada visible. Salida en [semana-15/evidencias](../semana-15/evidencias/).
