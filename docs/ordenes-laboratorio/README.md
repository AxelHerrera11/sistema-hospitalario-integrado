# Contrato de órdenes de laboratorio

Contrato de integración preparado para la coordinación del grupo antes de
escribir el módulo de órdenes.

- **Preparado por:** Fajardo López, Javier Alexander (`Javifa2324`), `ASII-09`
- **Módulo dueño:** `ASII-16 — Órdenes de laboratorio`
- **Origen:** plan transversal de integración recibido el 18 de octubre de 2026
- **Contrato API:** [contrato-api.md](./contrato-api.md)

## Estado

| Elemento | Estado |
|---|---|
| Contrato API de órdenes | Publicado en este PR, pendiente de validación del equipo |
| Migración H6 de órdenes | No existe en `develop`; depende de los responsables de la tabla |
| Permisos `laboratorio.ordenes.*` | Propuestos; no existen aún en el RBAC de `develop` |
| Implementación | Fuera del alcance de ASII-09; corresponde a ASII-16 |

## Módulos con los que se coordina

- ASII-17 (catálogo de pruebas): dueño de `lab_tests` y de las versiones vigentes.
- Recepción de muestras (María de los Ángeles): dueño de `specimens`.
- Ingreso y validación de resultados: dueño de `lab_result_versions`.
- ASII-09 (Javier): aporta este contrato por la coordinación transversal y
  conserva su módulo de ocupación hospitalaria.

## Reglas de coordinación

1. Ningún módulo crea la tabla de otro; cada entidad tiene un dueño declarado.
2. Las dependencias se aíslan detrás de una interfaz con adaptador InMemory/fake
   hasta que exista la tabla real.
3. Los contratos se comparten antes que el código.
4. Todo bloqueo se comunica el mismo día indicando qué falta y quién debe resolverlo.
