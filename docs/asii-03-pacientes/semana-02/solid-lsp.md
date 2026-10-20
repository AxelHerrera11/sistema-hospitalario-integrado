# Aplicación de LSP al flujo de pacientes

## Principio

El principio de sustitución de Liskov requiere que cualquier implementación que ocupe el lugar de un contrato preserve las precondiciones, resultados e invariantes que esperan sus clientes. No basta con compartir nombres de métodos: una sustitución no debe debilitar validaciones, cambiar silenciosamente resultados ni incumplir la atomicidad prometida.

## Diseño problemático: antes

Diseño conceptual, no presente en el código actual:

```text
FlujoPacientes
  └─ depende de RepositorioPaciente
       ├─ RepositorioPostgreSQL
       └─ RepositorioSoloLectura
```

Si `RepositorioPaciente` promete registrar, buscar y actualizar, pero `RepositorioSoloLectura` implementa la actualización lanzando una excepción de “operación no soportada”, no puede sustituir al repositorio esperado por `FlujoPacientes`. El consumidor aceptó un contrato que parecía permitir actualización y falla al usar una de sus implementaciones. El resultado es una violación de LSP, aunque las clases compartan la interfaz.

El problema no es que el sistema necesite obligatoriamente un repositorio de solo lectura; el problema es que una implementación restringida se presente como si cumpliera el contrato completo.

## Propuesta: después

El contrato conserva un comportamiento explícito y común para registro, búsqueda y actualización. Toda implementación intercambiable debe cumplir las mismas reglas de identidad y validación tomadas del esquema `local_patients`, y devolver resultados compatibles. Una implementación que no pueda actualizar no se registra como sustituta de este contrato.

```text
FlujoPacientes
  └─ depende de ContratoExpedienteLocal
       ├─ Adaptador de persistencia local
       └─ Sustituto de prueba conforme al contrato
```

El diagrama expresa roles conceptuales de la propuesta, no clases existentes ni compromiso con una tecnología de persistencia específica. El contrato de actualización significa que una operación válida conserva la identidad del expediente, respeta las restricciones y no deja cambios parciales si falla. El contrato de búsqueda tampoco debe introducir resultados incompatibles entre implementaciones.

## Ejemplo claro de sustitución

El flujo solicita una búsqueda y luego actualiza un expediente mediante `ContratoExpedienteLocal`. En pruebas, el adaptador de persistencia puede reemplazarse por un sustituto en memoria **solo si** este también rechaza código o `global_id` duplicados, impide fechas futuras y valores fuera de los conjuntos permitidos, conserva identidad y mantiene la actualización atómica. El cliente no cambia ni necesita detectar qué implementación recibió.

En cambio, si el sustituto en memoria permite duplicados o acepta una actualización parcial que la persistencia local rechaza, la prueba no demuestra LSP: ha cambiado el contrato y puede ocultar errores. La sustitución se verifica ejecutando la misma batería de contrato contra ambas implementaciones.

## Responsabilidades y dependencias

| Elemento conceptual | Responsabilidad | Dependencia justificada |
|---|---|---|
| Flujo de pacientes | Coordinar registro, búsqueda y actualización y comunicar su resultado. | Depende del contrato, no de detalles de almacenamiento. |
| Contrato de expediente local | Definir operaciones y garantías observables comunes. | Se guía por las restricciones existentes de `local_patients`. |
| Adaptador de persistencia local | Traducir el contrato a la persistencia local y respetar sus restricciones. | Depende del mecanismo de almacenamiento. |
| Sustituto de prueba | Facilitar pruebas repetibles sin cambiar el comportamiento esperado. | Implementa el mismo contrato; no se considera válido solo por ser un mock. |

La búsqueda y la escritura se plantean sobre expedientes locales. El MPI central y los vínculos hospitalarios no se sustituyen ni se actualizan como parte de este flujo sin una definición de sincronización que el repositorio no aporta.
