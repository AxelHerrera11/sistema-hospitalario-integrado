# Evidencia verificable — Alergias clínicas y alerta visual en expediente

## Checklist de revisión

- [x] Los RF describen acciones observables y cada uno se traza a un UC de la Semana 1.
- [x] Los RNF son medibles: seguridad, falla segura, consistencia, concurrencia, rendimiento, accesibilidad, privacidad y mantenibilidad.
- [x] Cada criterio de aceptación tiene un escenario *Dado / Cuando / Entonces* que se puede probar.
- [x] OCP se aplica al flujo de la alerta visual con un diseño antes y después.
- [x] Las responsabilidades y dependencias están justificadas.
- [x] Una demo ejecutable comprueba que una regla nueva no modifica las clases existentes.

## Matriz RF/RNF hacia el diseño

| Requerimiento | Componente o mecanismo que lo cubre |
|---|---|
| RF-ALG-01 | `AllergyController::store` → `AllergyService::registrar` → `AllergyRepository` (Semana 1, escenario A) |
| RF-ALG-02 | `StoreAllergyRequest` y reglas de dominio del servicio |
| RF-ALG-03 | `AllergyRepository::existeActivaEquivalente` + índice `allergies_active_equivalent_unique` |
| RF-ALG-04 | `AllergyAlertEvaluator` + `SevereAllergyRule` + `NonSevereAllergyRule` → `AlertLevel` |
| RF-ALG-05 | `AllergyAlertEvaluator` devuelve `Unavailable` cuando no hay datos; el controlador responde `503` |
| RF-ALG-06 | `AllergyService::cambiarEstado` + `CareRelationPolicy` + `allergies_status_date_check` |
| RF-ALG-07 | Consulta de solo lectura de alergias activas (contrato `AllergyChecker` de ASII-15) |
| RF-ALG-08 | `AuditLogger` en la misma transacción `hospital` |
| RNF-ALG-01 | Middlewares JWT, tenant y RBAC |
| RNF-ALG-02 | Estado `AlertLevel::Unavailable`, que ninguna regla puede reemplazar |
| RNF-ALG-03 | Transacción única: `allergies` + `local_access_audits` + `outbox_events` |
| RNF-ALG-04 | Índice único parcial de la migración `000401` |
| RNF-ALG-05 | Índice `allergies_local_patient_id_index` y evaluación en memoria de ≤ 50 alergias |
| RNF-ALG-06 | Componente `AllergyBanner` con ícono, texto y `role="alert"` |
| RNF-ALG-07 | `AuditLogger` con metadatos mínimos |
| RNF-ALG-08 | Interfaz `AllergyAlertRule` y registro de reglas por *tag* |

## Validaciones ejecutadas

```bash
# comando:
php -l docs/alergias/semana-02/evidencia/demo-ocp.php
# resultado:
No syntax errors detected in docs/alergias/semana-02/evidencia/demo-ocp.php

# comando (PHP 8.4.21, CLI):
php docs/alergias/semana-02/evidencia/demo-ocp.php
# resultado (código de salida 0):
== Diseño después reproduce el comportamiento del diseño antes ==
PASS  sin alergias → neutro
PASS  penicilina leve → ámbar
PASS  maní que amenaza la vida + polen leve → rojo
PASS  lactosa (intolerancia, severidad desconocida) → ámbar
PASS  alergia severa resuelta se ignora → neutro
PASS  consulta fallida → no disponible (falla segura)

== Extensión OCP: LatexAllergyRule ==
PASS  sin la regla nueva, látex leve → ámbar
PASS  con la regla registrada, látex leve → rojo
PASS  la alergia que dispara el rojo es el látex
PASS  las reglas anteriores siguen igual (penicilina leve → ámbar)
PASS  AllergyAlertEvaluator no menciona ninguna regla concreta

OK: todas las aserciones pasaron
```

Los diagramas `fuentes/01-diseno-antes.mmd` y `fuentes/02-diseno-despues.mmd` se renderizaron sin errores con `@mermaid-js/mermaid-cli`.

## Evidencia para la defensa

**¿Qué cambia al aplicar OCP?** Antes, la decisión del banner era un `switch` dentro de un servicio: cada pedido clínico nuevo obligaba a modificarlo. Después, cada regla es una clase que implementa `AllergyAlertRule` y el evaluador solo las recorre. Una regla nueva es una clase nueva más su registro.

**¿Cómo lo demuestro?** Con la demo: la misma alergia al látex leve da ámbar sin `LatexAllergyRule` y rojo con ella, sin tocar `AllergyAlertEvaluator`, cuyo código además se inspecciona para comprobar que no nombra ninguna regla.

**¿Por qué la falla segura no es una regla?** Porque no depende de una alergia, sino de que los datos existan. Si fuera una regla, alguien podría quitarla o reordenarla y mostrar «Sin alergias registradas» cuando en realidad no se pudieron leer.

**¿Qué pasa si dos reglas aplican a la misma alergia?** El evaluador se queda con el nivel más alto, así que agregar una regla nunca puede **bajar** una alerta existente: solo puede subirla.
