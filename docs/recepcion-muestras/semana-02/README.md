# Semana 2 — Requisitos verificables y sustitución de Liskov

**Módulo:** ASII-18, recepción de muestras y códigos identificadores.
**Autor de la entrega:** María de los Ángeles López Fajardo, Mar-03.
**Issue:** [ASII-18 #6](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/6).
**Objetivo de la matriz:** RF/RNF, criterios de aceptación y diseño antes/después con LSP aplicado al flujo de recepción.

## Índice

1. Introducción y alcance.
2. Desarrollo del entregable y decisiones.
3. Aplicación al microproyecto y al HIS.
4. Verificación, límites y conclusión.
5. Fuentes y evidencia Git.

## Introducción y alcance

Una muestra puede estar bien conservada y pertenecer al paciente equivocado. La aceptación requiere
identidad, orden disponible y conformidad física simultáneamente. La prioridad del dominio es rechazar
con una causa controlada y registrar una nueva toma, sin permitir que la UI omita esas reglas.

## Requisitos funcionales y aceptación

| ID | Regla | Criterio de aceptación |
|---|---|---|
| RF01 | Comparar código y nombre con la orden confiable | Si cualquiera difiere, devolver rejected e identificacion_discordante; nunca generar código. |
| RF02 | Persistir muestra y estado de orden atómicamente | Un error de escritura deja cero muestras nuevas y conserva el estado previo. |
| RF03 | Código único verificable y reimpresión | Una muestra accepted tiene código válido; consultar/imprimir devuelve exactamente ese código. |
| RF04 | Solo recibir ítems disponibles | pending/pending_new_sample permiten recepción; received/cancelled dan 409; ítem ajeno da 404. |
| RF05 | Aplicar requisitos físicos por tipo | Tipo, contenedor, volumen, estabilidad, temperatura y condiciones determinan causa de rechazo. |
| RF06 | Conservar aviso de rechazo | La transacción crea outbox y pending_new_sample; un fallo de red posterior no elimina rechazo. |
| RF07 | Recuperar respuesta perdida | Misma request_key, actor y payload devuelve el registro anterior; datos distintos dan 409. |
| RF08 | Reintento selectivo por código | Reintentar como máximo tres veces por colisión del índice de código; no por otro fallo SQL. |
| RF09 | Localizar orden pendiente | Buscar prefijo de ítem/código devuelve hasta 20 coincidencias del hospital actual y requisitos de referencia. |
| RF10 | Autorizar etiqueta | GET de etiqueta requiere muestras.etiquetar y no modifica la muestra ni genera identificador. |

## Requisitos no funcionales

| ID | Condición | Verificación |
|---|---|---|
| RNF01 | JWT vigente, membresía, hospital y permiso se verifican en servidor | Pruebas HTTP con login real, rol lector y cabecera de otro hospital. |
| RNF02 | SQL preparado, transacción y bloqueo PostgreSQL | Fallo de outbox revierte; dos conexiones compiten por el mismo ítem. |
| RNF03 | UI usable con teclado y móvil | Etiquetas, foco de errores, cuatro tamaños de pantalla y revisión automática axe. |
| RNF04 | Minimizar exposición | Respuestas no incluyen request_hash; consultas limitadas a tenant y etiquetas a tres campos. |

## LSP: diseño antes y después

Antes, un controlador que cambia sus excepciones/retornos según sangre, orina o hisopado obliga al
consumidor a conocer cada variante. Por ejemplo, devolver true para sangre y lanzar una excepción
por volumen insuficiente solo para orina rompe una expectativa uniforme del consumidor.

Después, RequirementsValidator define reason(observed, requirements, now): ?string.
SampleValidator cumple el mismo contrato para blood, urine y swab: null significa conformidad;
una cadena del catálogo significa rechazo. No se agregan precondiciones particulares al consumidor.

~~~mermaid
classDiagram
  class RequirementsValidator {
    <<interface>>
    +reason(observed, requirements, now) string_or_null
  }
  RequirementsValidator <|.. SampleValidator
  ReceiveSpecimen --> SampleValidator
  ReceiveSpecimen --> ReceptionRepository
~~~

La implementación parametrizada evita tres clases casi idénticas. ReceiveSpecimen conserva identidad,
disponibilidad e idempotencia; SampleValidator concentra requisitos físicos; el repositorio escribe.
El servicio todavía instancia SampleValidator: no se presenta como un contenedor de estrategias inyectables.

## Aplicación y verificación

HIS: [contrato del validador](../../../app/Modules/Reception/Domain/RequirementsValidator.php),
[validador](../../../app/Modules/Reception/Domain/SampleValidator.php) y
[servicio](../../../app/Modules/Reception/Application/ReceiveSpecimen.php).
Micro: mismas responsabilidades didácticas con namespace Reception, sin dependencia de Laravel.
Los tests del HIS sustituyen el tipo esperado por blood/urine/swab y repiten aceptación y replay.
Los límites de temperatura/volumen/tiempo se prueban con valores justo dentro y fuera del rango.

## Conclusión

LSP se demuestra manteniendo significado, entradas y retorno del contrato al cambiar el tipo.
Los RF/RNF se consideran verificados solo cuando existe un caso ejecutado vinculado en semana 15.
La fuente SOLID obligatoria es [MVP Cluster](https://mvpcluster.com/diseno-de-software-2/).

## Fuentes y evidencia Git

- [README original: plan, matriz ASII-18 y Definition of Done](../../../README.md).
- [Declaración de uso de IA y validación humana](../DECLARACION_IA.md).
- [Índice de semanas y archivos](../README.md).
- [Ramas, PR y destino develop](../semana-15/ramas-prs.md).
- Rama de entrega: feature/asii-18-semana-02-recepcion-muestras-mar-03.
- La referencia corregida es el worktree local recepcion-integracion-javier, integrado desde develop y las 18 ramas.

## Corrección de integración — 21 de octubre de 2026

La tabla propia del HIS es specimens (migración 000601, propuesta de Javier); lab_order_item_id es UUID lógico sin FK a Órdenes. Las FK locales son indexadas y RESTRICT. Se reutiliza outbox_events.
El módulo finalizado de Gerson se conserva; esta entrega no cambia Resultados/Validación. El contrato de lectura con ASII-16 debe verificarse con su proveedor; si no está disponible se responde 503 sin guardar muestras.
La evidencia vigente y sus límites están en [semana 15](../semana-15/README.md). Esta corrección sigue en local, sin push ni cambio de estado de PR.
