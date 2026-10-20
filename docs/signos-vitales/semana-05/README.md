# Signos vitales y alertas por valores anormales — Semana 5

## Ficha de la asignación

- **Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`)
- **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales
- **Semana:** Semana 5 — Cliente-servidor, API REST, microservicios e integración
- **Tarea individual:** Evolucione el MISMO «Micro-HIS Signos vitales y alertas por valores anormales» y el flujo «captura de signos vitales, evaluación de umbrales y generación de alerta» hacia un diseño cliente-servidor y evalúe una frontera de microservicio. Especifique contrato API, propiedad de datos, comunicación, seguridad, resiliencia, observabilidad y consistencia; incluya diagramas y una migración razonada. Puede entregar diseño/prototipo: no construya infraestructura de producción ni divida el sistema sin una justificación medible.
- **Entregable / evidencia:** Contrato API, diagramas y migración razonada. Prototipo en [`micro-his/signos-vitales/`](../../../micro-his/signos-vitales/).

## Resumen

- **Contrato:** API REST `/api/v1` con dos endpoints implementados (registrar y consultar) y dos propuestos; permisos `signos_vitales.registrar`, `.ver` y `.corregir`; errores `{code, trace_id}`.
- **Decisión:** no dividir el módulo. Los criterios medidos (costo, acoplamiento a tres FK y a un trigger, consistencia en una transacción, un solo equipo) no lo justifican. Sí se define una frontera de **eventos** hacia Alertas críticas.
- **Migración:** cuatro pasos, y el corte de base y proceso solo si se cumple un disparador medible.

## Archivos

- [Contrato API](./contrato-api.md) y [OpenAPI](./openapi.yaml)
- [Frontera de microservicio](./frontera-microservicio.md): diagramas, criterios medibles, decisión, migración y los seis temas pedidos.
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Cómo verificar

```bash
php micro-his/signos-vitales/tests/run.php
php micro-his/signos-vitales/tests/bench.php
```

`bench.php` imprime las cifras de la [tabla de criterios](./frontera-microservicio.md#criterios-medibles). Resultado con PHP 8.3.33 (código de salida 0), sin red ni PostgreSQL:

```text
InMemory: 5000 peticiones, p50 0.007 ms, p95 0.012 ms, máx 74.490 ms, 43199 peticiones/s
PDO (SQLite en memoria): 5000 peticiones, p50 0.049 ms, p95 0.087 ms, máx 0.675 ms, 17829 peticiones/s
```

El máximo de InMemory es la primera petición (carga de clases), no una cola.

## Organización de la entrega

| PR | Contenido | Estado |
|---|---|---|
| 1 de 3 (este) | Contrato, OpenAPI, frontera, `tests/bench.php` | Documentación y medición |
| 2 de 3 | `ApiKernel`, permisos, `trace_id`, `GET /vital-signs/{id}` y pruebas | Por desarrollar |
| 3 de 3 | `VitalSignApiClient` y transportes | Por desarrollar |

## Pendientes de integración

| Con quién | Qué |
|---|---|
| María (ASII-02, RBAC) | Alta de `signos_vitales.registrar`, `signos_vitales.ver` y `signos_vitales.corregir`, con la matriz propuesta |
| Javier (base de datos) | `local_access_audits` (slice H8), `outbox_events`, enteros en FC/FR/presión, `medical_record_id` obligatorio y estado `draft` o `validated` |
| Alertas críticas (ASII-21) | Consumo de `signo_vital.alerta_generada` y canal de notificación (C3) |
| Admisión | Carga real para validar el supuesto de la tabla de criterios |
