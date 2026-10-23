# Traslados, altas y liberación de camas — Semana 5

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo · **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Destino:** `develop`
- **Asignación:** cliente-servidor y API: contrato, propiedad de datos, comunicación, seguridad, resiliencia, observabilidad y consistencia; evaluar la frontera de microservicio y una migración razonada (diseño o prototipo, sin dividir servicios sin justificación); plan académico de Issue, rama, worktree y PR.

## Contenido

- [Contrato OpenAPI del prototipo](./openapi.yaml)
- [Propiedades cliente-servidor y frontera de microservicio](./cliente-servidor.md)
- [Plan académico de Issue, rama, worktree y PR](./plan-academico.md)
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Cumplimiento

| Pedido | Evidencia | Estado |
|---|---|---|
| Contrato API | `openapi.yaml`: 3 operaciones, sin `$ref` rotos (validado con symfony/yaml) | Cumplido |
| Propiedades del contrato | [cliente-servidor.md](./cliente-servidor.md) | Cumplido; seguridad y observabilidad declaradas como pendientes de la integración |
| Frontera de microservicio y migración | [cliente-servidor.md](./cliente-servidor.md#frontera-de-microservicio) | Decidido no dividir; migración descrita por pasos |
| Prototipo | `public/index.php` y `HttpTraslados` (#849), ejecutado contra PostgreSQL | Prototipo funcional, sin autenticación real |
| Plan académico | [plan-academico.md](./plan-academico.md) | Cumplido; se aclara el uso de un clon en lugar de un worktree |

## Recorrido HTTP comprobado (21/10/2026)

`php -S 127.0.0.1:8790 micro-his/traslados-altas/public/index.php` con una base HOSPITAL desechable sembrada con `database/demo.sql`:

| Paso | Respuesta |
|---|---|
| Sin actor | 401 |
| Actor sin referencia activa | 403 |
| Destino en mantenimiento | 409 |
| Traslado | 201 |
| Consulta | 200, con la estancia destino abierta |
| Alta | 200 |
| Alta repetida | 409 |
| Consulta | episodio `closed` |

`tests/run.php`: 88/88.

## PR

| Parte | PR | Promoción |
|---|---|---|
| 1/2 Prototipo HTTP y consulta del estado | #849 | #850 |
| 2/2 Contrato, propiedades, frontera y plan | Este PR | — |
