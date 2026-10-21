# Evidencia verificable — Signos vitales y alertas por valores anormales

## Matriz RF/RNF hacia el diseño

| Requerimiento | Componente que lo cubre |
|---|---|
| RF-SV-01, RF-SV-02 | `VitalSignService` + `CHECK` de plausibilidad y trigger `enforce_vital_sign_integrity` (migración `000401`) |
| RF-SV-03 | Contrato de `VitalSignRule` y plantilla `final` de `RangeRule` (signo ausente → `None`) |
| RF-SV-04 | `ThresholdEvaluator` (severidad máxima de los hallazgos) |
| RF-SV-05 | `NoAlertRule` para peso y talla |
| RF-SV-06 | `ThresholdPolicy` sin perfil aplicable → `THRESHOLDS_NOT_APPLICABLE` en el servicio |
| RF-SV-07 | `AuditLogger` en la transacción `hospital` + `AlertNotifier` con `afterCommit` |
| RNF-SV-01 | Middlewares JWT, tenant y RBAC |
| RNF-SV-02, RNF-SV-05 | Prueba de contrato `honorsContract()` contra cada subtipo; evaluador sin `instanceof` |
| RNF-SV-03 | Transacción única `vital_signs` + `local_access_audits` |

## Validaciones ejecutadas

```bash
php -l docs/signos-vitales/semana-02/evidencia/demo-lsp.php   # No syntax errors detected
# comando (PHP 8.3.33, CLI):
php docs/signos-vitales/semana-02/evidencia/demo-lsp.php
# resultado (código de salida 0):
== Diseño después reproduce el comportamiento del diseño antes ==
PASS  FC 80 y glucosa 100 → sin alerta
PASS  FC 138 → critical
PASS  FC 95 sin glucosa → warning
PASS  glucosa 60 → warning
PASS  solo peso → sin alerta

== LSP: cada subtipo sustituye al tipo base ==
PASS  antes: GlucoseRuleAntes y WeightRuleAntes incumplen el contrato base
PASS  antes: sin instanceof, la excepción de glucosa ocultaría la FC 138 crítica
PASS  antes: ThresholdEvaluatorAntes depende de instanceof
PASS  después: las 5 reglas cumplen la misma prueba de contrato
PASS  después: FC 138 + SpO2 94 → critical (máximo de los hallazgos)
PASS  después: ThresholdEvaluator no usa instanceof ni nombra reglas concretas
PASS  después: RespiratoryRateRule (FR 26) se agrega sin tocar el evaluador → critical

OK: todas las aserciones pasaron
```

Los diagramas de [solid-lsp.md](./solid-lsp.md) se renderizaron sin errores con `@mermaid-js/mermaid-cli` 11.4.2.

## Evidencia para la defensa

- **¿Dónde se violaba LSP?** `GlucoseRuleAntes` exigía glucosa (precondición más fuerte) y `WeightRuleAntes` lanzaba siempre (herencia rechazada). El evaluador los "toleraba" con `instanceof`, lo que es el síntoma clásico de una jerarquía no sustituible.
- **¿Cómo lo demuestro?** `honorsContract()` es una sola prueba que se ejecuta a través del tipo base contra cada subtipo: falla con dos reglas del diseño antes y pasa con las cinco del después.
- **¿Por qué importa clínicamente?** Sin los `instanceof`, la excepción de glucosa cortaría la evaluación y una FC 138 no generaría alerta. Con el contrato, ninguna regla puede ocultar la alerta de otra.
- **¿Por qué `evaluate()` es `final`?** Para que una subclase futura no pueda volver a reforzar la precondición: solo aporta las bandas del perfil.
