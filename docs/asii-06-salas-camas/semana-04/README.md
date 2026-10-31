# ASII-06 — Semana 4: arquitectura del catálogo

## Objetivo

Consolidar la arquitectura mantenible del catálogo de wards y camas a partir
del modelo de Semana 1, los requisitos de Semana 2 y el micro-HIS implementado
en Semana 3. Esta entrega documenta y traza lo que existe; no duplica semanas
anteriores ni agrega código.

## Alcance

Incluye creación de wards y camas, validación de sus atributos y estados
permitidos, y la asociación obligatoria `bed.wardId`. Los únicos conceptos del
catálogo son `wards` y `beds`; no se crea ni propone una tabla `rooms`.

El catálogo no coordina admisiones, ocupación, estancias, traslados ni altas.
`occupied` está admitido como valor de estado en el esquema y el dominio, pero
el ciclo de ocupación pertenece a sus módulos consumidores.

## Componentes existentes

- **Domain:** `Ward`, `Bed`, `DomainRuleViolation`.
- **Application:** `CatalogService`, `WardRepository`, `BedRepository`,
  `CatalogOperationFailure`, `PersistenceFailure`.
- **Persistence:** `PdoWardRepository` y `PdoBedRepository`.
- **Presentation:** `CatalogController`, adaptador que traduce el resultado a
  códigos y cuerpos de respuesta; no es una ruta HTTP Laravel.
- **Evidencia:** `micro-his/salas-camas/tests/run.php`; esquema de wards y beds
  en la migración hospitalaria `2026_08_11_000300`.

## Decisiones

1. Mantener las capas que Semana 3 ya implementa; no agregar servicios,
   interfaces ni adaptadores sin una necesidad funcional demostrada.
2. `CatalogService` coordina la consulta del ward y el alta de la cama. Las
   restricciones de FK y unicidad de PostgreSQL siguen siendo la garantía final
   de integridad.
3. Conservar los contratos separados `WardRepository` y `BedRepository`;
   persistence depende de ellos, no al revés.
4. La prueba de Semana 3 usa un repositorio fake compartido por las dos
   interfaces. No existe un adaptador InMemory de producción.
5. No inventar transiciones de estado ni impedir camas en wards inactivos: el
   modelo actual no define esas reglas.

## Riesgos y estado final

El micro-HIS implementa altas, pero aún no expone consulta/listado general ni
edición del catálogo. El adapter PDO está implementado; la suite de Semana 3
verifica el flujo con doubles, no prueba PostgreSQL. El controlador es una
frontera de presentación interna, no una API publicada.

**Estado:** arquitectura de Semana 3 revisada y documentada; sin cambios de
código ni afirmaciones de persistencia real. Véanse [arquitectura](./arquitectura.md)
y [trazabilidad](./trazabilidad.md).
