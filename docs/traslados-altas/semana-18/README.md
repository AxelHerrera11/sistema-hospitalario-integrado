# ASII-08 Semana 18 — Evaluación final

**Issue:** `Refs #9` · **Estudiante:** María Yamilet Lindo Pablo (`Yamilet1235`).

**Estado:** preparada. La demostración final y la defensa individual **no han ocurrido**. El detalle del requisito en Odoo no está disponible: por confirmar.

## Condición para demostrar sobre `develop`

Los permisos siguen en `pending`. Hasta que RBAC integre #1577, el módulo responde 403 a todos y no aparece en el menú. Si la demostración ocurre antes, se usa la copia local con la rama de la propuesta **y se dice que es una activación no integrada**.

## Guion de la demostración (8 minutos)

| # | Actor | Acción | Resultado esperado |
|---|---|---|---|
| 1 | Enfermería | Entrar y abrir «Traslados y altas» | Formulario de consulta |
| 2 | Enfermería | Consultar una estancia | Episodio, cama y «Trasladar»; sin «Dar de alta» |
| 3 | Enfermería | Trasladar sin motivo | Aviso; no se envía |
| 4 | Enfermería | Trasladar a una cama ocupada | Rechazo; nada cambia |
| 5 | Enfermería | Trasladar a una cama libre y confirmar | «Traslado confirmado» y nueva estancia |
| 6 | Médico | Consultar la nueva estancia y dar el alta | «Alta confirmada» |
| 7 | Médico | Consultar de nuevo | Episodio cerrado y sin acciones |
| 8 | Recepción | Entrar | Sin entrada en el menú |
| 9 | Enfermería de otro hospital | Consultar la misma estancia | Rechazo, sin datos |

## Matriz de evidencia

| Tema | Evidencia |
|---|---|
| Procesos y requisitos | `semana-01/`, `semana-02/` |
| Arquitectura y contrato | `semana-03/` a `semana-05/`, `semana-07/` |
| Experiencia | `semana-08/` a `semana-11/` |
| Parciales preparados | `semana-06/presentacion.pdf`, `semana-12/presentacion.pdf` |
| Calidad | `semana-13/` a `semana-15/` |
| Integración y seguridad | `semana-16/`, `semana-17/` |

## Preguntas de defensa

- **¿Cómo se evita que dos personas ocupen una cama?** `FOR UPDATE`, restricción `EXCLUDE` y prueba de concurrencia con dos procesos.
- **¿Qué pasa si no se sabe si se guardó?** Resultado incierto (500) sin reintento automático; se consulta la estancia.
- **¿Qué defecto real se encontró con PostgreSQL?** D5: la lectura de la cama con una instantánea anterior al bloqueo.
- **¿Por qué no funciona aún en `develop`?** Falta la decisión de RBAC; el módulo no se concede permisos a sí mismo.

## Pendiente de la estudiante

1. Ejecutar el guion en el navegador y registrar qué revisó en [`../revision-humana.md`](../revision-humana.md).
2. Realizar la defensa.

