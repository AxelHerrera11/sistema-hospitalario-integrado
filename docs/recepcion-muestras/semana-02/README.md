# Semana 2 — RF/RNF y sustitución LSP

ASII-18, María de los Ángeles López Fajardo (Mar-03). El grupo reasignó el módulo 18; issue #6. Esta entrega desarrolla la fila S02 de la matriz del [README](../../../README.md).

## Requisitos y criterios

| ID | Requisito | Criterio de aceptación |
|---|---|---|
| RF01 | Identificar paciente contra la orden confiable | Código o nombre discordante producen rechazo controlado. |
| RF02 | Persistencia atómica | specimens y outbox_events se guardan o revierten juntos; no se modifica la orden externa. |
| RF03 | Código y etiqueta estables | Código único verificable; reimprimir conserva el mismo valor. |
| RF04 | Orden disponible | Estado distinto de pending/pending_new_sample da 409; ausente 404; proveedor ausente 503 LAB_ORDER_UNAVAILABLE. |
| RF05 | Requisitos físicos | Tipo, contenedor, volumen, tiempo y temperatura producen motivos controlados. |
| RF06 | Idempotencia | Misma request_key, actor y payload recuperan respuesta; cambio de datos da 409. |
| RF07 | Reintento acotado | Solo CodeCollision reintenta hasta tres veces y termina con el primer guardado exitoso. |
| RNF01 | Seguridad por hospital y rol | Middleware tenant/JWT/RBAC; una base por hospital, sin tenant_id en specimens. |
| RNF02 | Persistencia preparada | PDO y transacción; fallo SQL no provoca reintentos de código. |
| RNF03 | Minimizar exposición | La API futura no publica request_hash ni metadatos internos. |
| RNF04 | Usabilidad | Teclado, errores con foco y móvil se miden sobre la página real en S09/S10. |

## LSP antes y después

Antes: el consumidor necesita condicionales para cada tipo y retornos incompatibles. Después: RequirementsValidator mantiene reason(observed, requirements, now): ?string; null significa conforme y cualquier otro valor es un motivo controlado. SampleValidator y CompositeRequirementsValidator son dos adaptadores intercambiables; tests/lsp.php prueba ambos con las mismas entradas de aceptación, volumen y tipo incorrectos.

La única fuente del dominio está en micro-his/recepcion-muestras/src; app/Modules/Reception/bootstrap.php la carga con require_once. ReceiveSpecimen recibe ReceptionRepository y OrderGateway separados, sin depender de Laravel ni PDO. El adaptador de órdenes real y las rutas se incorporan en S05; la búsqueda de pendientes permanece PROPUESTA hasta que ASII-16 publique su tabla.

[Diagrama editable](./fuentes/contratos.mmd) · [SVG](./contratos.svg) · [Evidencia](./EVIDENCIA.md) · [Declaración IA](./DECLARACION_IA.md).

SD-REC-01: umbrales, identificación y catálogo de rechazo son fixtures ficticios pendientes de validación clínica. Esta semana demuestra contratos de dominio y LSP; las validaciones HTTP, persistencia y accesibilidad se verifican en sus semanas.

Fuente SOLID obligatoria: [MVP Cluster, diseño de software 2](https://mvpcluster.com/diseno-de-software-2/). La sustitución se contrasta con contratos y ejecuciones, sin atribuir cobertura clínica o de producción.
