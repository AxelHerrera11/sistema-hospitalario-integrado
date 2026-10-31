# Semana 01 — Conceptos generales, orientación a objetos y UML

**Tarea:** modelar el proceso «apertura y consulta autorizada del expediente longitudinal» con casos de uso
(actores y objetivo), actividad (decisiones, excepciones y resultado) y secuencia (participantes, mensajes,
validaciones y respuesta), manteniendo trazabilidad entre los tres.

## Entregables

| Archivo | Contenido |
|---|---|
| [`01-casos-de-uso.svg`](01-casos-de-uso.svg) · fuente [`fuentes/01-casos-de-uso.mmd`](fuentes/01-casos-de-uso.mmd) | Actores (4 roles reales de `config/rbac.php`), UC-01…UC-06 y sistemas externos reales (RBAC, Gobernanza, Pacientes) |
| [`02-actividad.svg`](02-actividad.svg) · fuente [`fuentes/02-actividad.mmd`](fuentes/02-actividad.mmd) | Decisiones C1…C7 y A1…A7, excepciones con su código HTTP y fin explícito en cada rama |
| [`03-secuencia.svg`](03-secuencia.svg) · fuente [`fuentes/03-secuencia.mmd`](fuentes/03-secuencia.mmd) | Mensajes numerados entre las clases reales, con `alt` para denegación, falta de vínculo y bitácora caída |
| [`narrativa-alcance.md`](narrativa-alcance.md) | Objetivo, alcance, actores, supuestos S-01…S-06 y tabla de trazabilidad diagrama ↔ código ↔ prueba |

## Trabajo realizado

Los diagramas se dibujaron **después** de revisar el repositorio: los roles salen de `config/rbac.php`, las tablas
de las migraciones hospital 000200 y 000400, y los participantes de la secuencia son las clases que existen en
`micro-his/expediente-medico/src`. Por eso las semanas siguientes no cambian nombres: el código se escribió con
estos mismos nombres.

## Cómo verificar

1. Abrir los tres SVG (cualquier navegador) y comparar sus nombres con el código:

   ```bash
   grep -rn "function execute\|function requirePermission\|function hasActiveAssignment" micro-his/expediente-medico/src
   ```

2. Cada código HTTP del diagrama de actividad aparece en la tabla «Códigos de respuesta» de
   `micro-his/expediente-medico/README.md` y en las pruebas `tests/caja-blanca.php` (C1…C7, A1…A7).
3. Regenerar los SVG desde las fuentes: ver [`../diagramas/README.md`](../diagramas/README.md).

## Estado

- **Implementado:** todo lo modelado para la consulta y la apertura.
- **Propuesto:** el permiso de apertura (S-02) y el vínculo asistencial como condición de consulta (S-01).
