# ASII-14 Semana 18 — Evaluación final

**Issue:** `Refs #165` · **Estudiante:** María Yamilet Lindo Pablo (`Yamilet1235`).

**Estado:** preparada. La demostración final y la defensa individual **no han ocurrido**. El detalle del requisito en Odoo no está disponible: por confirmar.

## Guion de la demostración (8 minutos)

| # | Actor | Acción | Resultado esperado |
|---|---|---|---|
| 1 | Administración | Abrir «Medicamentos» | Búsqueda y formulario de alta |
| 2 | Administración | Registrar `ASA-100`, «Ácido acetilsalicílico 100 mg» | «Registrado ASA-100 (Vigente)» |
| 3 | Administración | Registrar otra vez `asa-100` | Error junto al código, sin alta |
| 4 | Administración | Buscar `ÁCIDO` y `acido` | Aparece el registro con sus tildes originales |
| 5 | Administración | Buscar `zzz` | «No hay medicamentos…» |
| 6 | Administración | «Marcar como no vigente» y luego Cancelar | Nada cambia |
| 7 | Administración | Repetir y confirmar `Inactivar ASA-100` | Vigencia «No vigente»; el registro sigue consultable |
| 8 | Medicina | Abrir «Medicamentos» | Consulta sin formulario ni botón de vigencia |
| 9 | Recepción | Entrar | Sin entrada en el menú |
| 10 | Administración de otro hospital | Buscar | «Este hospital no está disponible en esta instalación.» |

## Matriz de evidencia

| Tema | Evidencia |
|---|---|
| Procesos y requisitos | `semana-01/`, `semana-02/` |
| Arquitectura y contrato | `semana-03/` a `semana-05/`, `semana-07/` |
| Experiencia | `semana-08/` a `semana-11/` |
| Parciales preparados | `semana-06/presentacion.pdf`, `semana-12/presentacion.pdf` |
| Calidad | `semana-13/` a `semana-15/` |
| Integración y seguridad | `semana-16/`, `semana-17/` |
| Recorrido final en Chrome | [`evidencia/salida-e2e.txt`](./evidencia/salida-e2e.txt) (28/28, Chrome headless y PostgreSQL con locale C; no es revisión humana) |

## Preguntas de defensa

- **¿Por qué `acido` encuentra `Ácido` y `nandu` no encuentra `Ñandú`?** La búsqueda ignora mayúsculas y tildes, pero la ñ es otra letra. Solo se compara así: el texto almacenado no cambia.
- **¿Qué defectos graves se hallaron tarde?** El cruce entre hospitales y la dependencia del locale del clúster.
- **¿Qué queda abierto?** Staging, elegir la base por hospital y la revisión formal con roles reales.

## Pendiente de la estudiante

1. Ejecutar el guion y registrar qué revisó. Faltan búsqueda sin resultados, teclado, escrituras forzadas, otro hospital y caída de la base.
2. Realizar la defensa.
