# Evidencia verificable — Notas SOAP y diagnósticos

## Matriz RF/RNF hacia el diseño

| Requerimiento | Componente que lo cubre |
|---|---|
| RF-SOAP-01, RF-SOAP-02 | `SoapNoteRegistrar` + `SoapSectionsValidator` + `ClinicalNoteRepository` |
| RF-SOAP-03 | `TreatingPhysicianGuard` + `CareAssignmentReader` (y el trigger de autor de la migración `000400`) |
| RF-SOAP-04, RF-SOAP-06 | `DiagnosisAssociator` + `Icd10Catalog` (`503` ante `CatalogUnavailable`) + `DiagnosisPolicy` (tipo de diagnóstico) |
| RF-SOAP-05 | `DiagnosisPolicy` |
| RF-SOAP-07 | `DiagnosisAssociator` (estado `draft`) y la inmutabilidad de `clinical_notes` firmadas |
| RNF-SOAP-01 | Middlewares JWT, tenant y RBAC |
| RNF-SOAP-02 | Triggers de `clinical_notes` y `diagnoses` (migración `000400`) |
| RNF-SOAP-03 | Rama `CatalogUnavailable` → `503` en `DiagnosisAssociator` |
| RNF-SOAP-06 | Una clase por razón de cambio; interfaces para catálogo, asignaciones y persistencia |

## Validaciones ejecutadas

```bash
php -l docs/notas-soap/semana-02/evidencia/demo-srp.php   # No syntax errors detected
# comando (PHP 8.3.33, CLI):
php docs/notas-soap/semana-02/evidencia/demo-srp.php
# resultado (código de salida 0):
== Diseño después reproduce el comportamiento del diseño antes ==
PASS  registrar nota válida → 201
PASS  faltan las secciones O, A y P → 422
PASS  sección P vacía o solo con espacios → 422
PASS  médico no tratante → 403
PASS  diagnóstico principal J18.9 → 201
PASS  tipo fuera de primary, secondary o differential → 422
PASS  código inexistente → 422
PASS  mismo código otra vez → 409
PASS  segundo principal → 409
PASS  otro médico en una nota ajena → 403
PASS  la nota recuperada conserva S, O, A y P en ambos
PASS  el tipo inválido no guardó cambios: solo J18.9 asociado
PASS  nota firmada no acepta diagnósticos → 409 en ambos

== SRP: cada cambio toca una sola clase ==
PASS  catálogo caído → 503 cambiando solo el Icd10Catalog
PASS  el diseño antes guarda el catálogo como constante interna
PASS  SoapNoteRegistrar no depende del catálogo CIE-10
PASS  DiagnosisPolicy no tiene dependencias
PASS  DiagnosisPolicy se prueba sola: rechaza un segundo principal

OK: todas las aserciones pasaron
```

## Evidencia para la defensa

- **¿Qué cambia con SRP?** Antes, un solo servicio cambiaba por acceso, formato, catálogo, reglas o persistencia. Después, cada motivo tiene su clase y los casos de uso solo coordinan.
- **¿Cómo lo demuestro?** La demo da los mismos resultados con ambos diseños. Luego cambia solo el catálogo (`DownCatalog`) y obtiene el `503` sin editar otra clase, cosa imposible en el diseño antes, donde el catálogo es una constante del servicio. `DiagnosisPolicy`, la regla clínica más sensible, se prueba sin base de datos ni catálogo.
