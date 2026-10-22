# Evidencia verificable — Catálogo de pruebas de laboratorio

## Matriz RF/RNF hacia el diseño

| Requerimiento | Componente |
|---|---|
| RF-LAB-02 | `SpecimenRequirementRule` |
| RF-LAB-03 | `ResultTypeRegistry` + `NumericResultSpec` / `QualitativeResultSpec` (+ `OrdinalResultSpec`) |
| RF-LAB-04 | `ValidityRule` |
| RF-LAB-05 | Repositorio + exclusión GiST propuesta |
| RNF-LAB-06 | Interfaces `ResultTypeSpec` y `LabTestRule` |

## Ejecución de la demo (PHP 8.4.21, código de salida 0)

```text
$ php docs/catalogo-pruebas-lab/semana-02/evidencia/demo-ocp.php
== Diseño después reproduce el comportamiento del diseño antes ==
PASS  glucosa numérica válida → sin errores
PASS  crítico bajo ≥ referencia baja → RANGES_INCOHERENT
PASS  ayuno sin horas → SPECIMEN_REQUIREMENT_INVALID
PASS  alta retroactiva → VALIDITY_INVALID
PASS  cualitativa sin valores permitidos → RESULT_DEFINITION_INVALID
PASS  tipo ordinal sin soporte → RESULT_TYPE_UNSUPPORTED

== Extensión OCP: OrdinalResultSpec ==
PASS  con la especificación registrada, la proteinuria ordinal es válida
PASS  umbral crítico fuera de la escala → RESULT_DEFINITION_INVALID
PASS  las reglas comunes también aplican al tipo nuevo (alta retroactiva)
PASS  los tipos anteriores siguen igual (glucosa válida)
PASS  LabTestValidator no menciona ningún tipo de resultado concreto
PASS  ResultTypeRegistry no menciona ningún tipo de resultado concreto

OK: todas las aserciones pasaron
```

**¿Cómo lo demuestro?** Sin `OrdinalResultSpec`, la proteinuria se rechaza con `RESULT_TYPE_UNSUPPORTED`. Al registrarla es válida, se rechaza un umbral fuera de la escala y la regla de vigencia se le aplica sola. `LabTestValidator` y `ResultTypeRegistry` no cambiaron.
