# Dashboard de ocupación hospitalaria - Semana 4

## Ficha

- **Estudiante/módulo:** Javier Alexander Fajardo López (`Javifa2324`) - ASII-09
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Tarea Odoo:** Arquitectura en capas y patrón Repository. Continuar el mismo Micro-HIS y el
  mismo flujo «consolidación y consulta de ocupación por sala y estado de cama»; organizar la
  entrada con MVC, definir la interfaz Repository en el límite apropiado y los adaptadores PDO e
  InMemory; el controlador sin SQL ni reglas de negocio; analizar y diagramar la integración
  mediante un repositorio de datos compartido.
- **Entregables:** capas, responsabilidades y objetos reutilizables.

## Capas y responsabilidades

El Micro-HIS de la [semana 3](../semana-03/README.md) se reorganiza con MVC y un puerto de
lectura en el límite del dominio. Los nombres que aparecen abajo son los del código ya
integrado en `develop` (`micro-his/ocupacion-hospitalaria/`), verificado el 21 de octubre de 2026.

| Capa MVC | Implementación integrada | Responsabilidad | Forbidden en esta capa |
|---|---|---|---|
| Model | `ConsultOccupancy`, `OccupancySummaryQuery` (Application) y `OccupancyCalculator` (Domain) | Consultar y aplicar las reglas de ocupación. | Conocer PDO o el esquema. |
| View | `OccupancyController` (Presentation) devuelve `['status' => int, 'body' => array]` | Traducir la petición y serializar la respuesta sin decidir reglas. | SQL o clasificación de camas. |
| Controller | `OccupancyController` (Presentation) | Invocar el caso de uso y elegir entre `200` y `503`. | SQL o reglas de negocio. |

El puerto `BedSnapshotReader` (Application) expone una sola operación:

```php
interface BedSnapshotReader
{
    /** @return list<BedSnapshot> */
    public function readAt(\DateTimeImmutable $cutoff): array;
}
```

Sus adaptadores son:

- `PdoBedSnapshotReader` (Persistence): obtiene una fotografía atómica con sentencia preparada.
- Doble en memoria: las verificaciones de `micro-his/ocupacion-hospitalaria/tests/run.php`
  ejecutan el mismo caso de uso con una implementación en memoria del puerto, sin base de datos.

El controlador no contiene SQL ni reglas de clasificación; `Application` depende del puerto y
nunca de `PDO`; `Domain` no depende de nada externo. Las tres reglas se comprueban en las
verificaciones de arquitectura.

- [Diagrama MVC y Repository](./fuentes/01-mvc-repository.mmd)
- [Diagrama del repositorio compartido](./fuentes/02-repositorio-compartido.mmd)
- [Declaración de IA](./DECLARACION_IA.md)

## Repositorio de datos compartido

La base HOSPITAL se comparte como mecanismo de integración, pero no borra la propiedad funcional:
ASII-06 administra `wards` y `beds`, ASII-07 administra `care_episodes` y `bed_stays`, ASII-08
cierra y traslada estancias, y ASII-09 solo **lee** mediante su Repository. El dashboard nunca
escribe en la base: no asigna, traslada ni libera camas.

Riesgos de compartir el repositorio: acoplamiento al esquema, migraciones incompatibles entre
dueños y lecturas inconsistentes. Mitigaciones: un propietario por tabla, migraciones
coordinadas, contrato de lectura estable, una sentencia por fotografía y pruebas de integración
contra PostgreSQL 16.

## Evidencia ejecutada

Salida real del 21 de octubre de 2026, sobre `develop` en `75bd69ce`, con PHP 8.5:

```bash
$ php micro-his/ocupacion-hospitalaria/tests/run.php
== Camino feliz y reglas de dominio ==
PASS  responde 200 con la misma fecha de corte
PASS  cada cama se cuenta exactamente una vez
PASS  la estancia activa prevalece sobre status available
PASS  occupied sin estancia queda no verificada
PASS  la tasa usa solo camas operativas
PASS  la distribución usa el inventario total
PASS  consolida las salas sin perder camas
PASS  sin capacidad operativa la tasa es null

== Error de persistencia ==
PASS  la falla devuelve 503 y nunca ocupación cero

== Arquitectura ==
PASS  Domain y Application no dependen de PDO
PASS  Presentation no contiene SQL
PASS  Persistence usa prepare y no query/exec

OK: 12 verificaciones aprobadas
```

Los dos diagramas se validaron generándolos con Mermaid CLI:

```bash
$ npx -y @mermaid-js/mermaid-cli -i docs/ocupacion-hospitalaria/semana-04/fuentes/01-mvc-repository.mmd -o /tmp/01-mvc-repository.svg
$ npx -y @mermaid-js/mermaid-cli -i docs/ocupacion-hospitalaria/semana-04/fuentes/02-repositorio-compartido.mmd -o /tmp/02-repositorio-compartido.svg
$ xmllint --noout /tmp/01-mvc-repository.svg /tmp/02-repositorio-compartido.svg
```

Los `.mmd` son las fuentes editables; el SVG se regenera cuando cambia el contenido.

## Estado

La entrega de la semana 4 se publica como documentación sobre el código ya integrado: el
Micro-HIS en capas llegó a `develop` en el PR #171 y el endpoint en Laravel en el PR #574, que
conservan el puerto `BedSnapshotReader`, el adaptador `PdoBedSnapshotReader` y el
`OccupancyController` aquí descritos. La verificación de integridad de la regla de ocupación
vive en `tests/Database/Hospital/OccupancySnapshotIntegrityTest.php`.

## Riesgos y pendientes

- El repositorio compartido implica dependencia del esquema de ASII-06/07/08: cualquier cambio
  en `bed_stays` o `beds.status` obliga a revisar el contrato de lectura.
- La clasificación depende del intervalo de la estancia, no de `beds.status`; si ASII-08 cambia
  la forma de cerrar estancias hay que revalidar la regla.
