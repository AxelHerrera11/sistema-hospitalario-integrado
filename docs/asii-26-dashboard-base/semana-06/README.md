# Tablero de indicadores (dashboard base) - Semana 6

> Tema guia: **Primera evaluacion parcial: defensa teorica y caso practico de arquitectura y diseno**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26` | **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** consulta del tablero de indicadores por periodo
- **Principio SOLID aplicado:** Single Responsibility (SRP)
## Entregables

- [Presentacion (7 diapositivas)](./presentacion.md)
- [Diagrama trazable RF -> CU -> componente -> prueba](./01-trazabilidad.svg) | [fuente](./fuentes/01-trazabilidad.puml)
- Matriz decision -> evidencia y resolucion del cambio practico (este documento).

## Defensa teorica (guion)

1. **Problema:** Cada modulo del HIS tiene su pantalla de inicio distinta; no hay un tablero comun por rol ni estados de carga/vacio/error consistentes.
2. **Actores:** Todos los usuarios autenticados (ven tarjetas segun permisos), Direccion medica (indicadores por periodo).
3. **Por que capas:** separar la regla de negocio de HTTP y SQL permite probarla sola y cambiar la
   infraestructura sin tocarla.
4. **Por que Single Responsibility (SRP):** es el principio que mas protege la regla central del modulo (ver D-02).
5. **Como se integra:** por contrato API versionado; ningun modulo lee las tablas de otro.

## Matriz decision -> evidencia

| ID | Decision | Alternativa descartada | Justificacion | Evidencia |
|---|---|---|---|---|
| D-01 | Arquitectura en 4 capas | Calcular en la vista Vue | el calculo de cumplimiento se prueba sin interfaz | [Semana 3: capas](../semana-03/01-capas.svg) |
| D-02 | SRP: controlador, caso de uso y repositorio separados | Una clase `Dashboard` que hace todo | cada clase cambia por una sola razon | [Semana 2: SRP](../semana-02/solid-srp.md) |
| D-03 | Read model / composicion por API | Leer tablas de otros modulos | respeta la autonomia de Citas y Ocupacion | [Semana 3: contexto C4](../semana-03/02-contexto-c4.svg) |
| D-04 | Menu por permiso con falla cerrada | Menu fijo para todos | no expone rutas sin permiso | [Contrato UI base](../contrato-ui-base.md) |
| D-05 | Estados de pagina transversales | Cada pantalla resuelve sus estados | consistencia UI/UX en todo el HIS | [Contrato UI base](../contrato-ui-base.md) |
| D-06 | Patron Repository por periodo | Consultas ad hoc | el tablero no conoce SQL | [Semana 3: Domain](../semana-03/micro-his/src/Domain/IndicadorRepository.php) |

## Caso practico: resolucion de cambio

**Cambio solicitado:** Algunos indicadores **mejoran cuando bajan** (tiempo de espera, reingresos).

**Analisis de impacto:** `Indicador` agrega `sentido` (`mayor_mejor` o `menor_mejor`) y `cumpleMeta()` lo usa. Por SRP solo cambia la entidad: `ObtenerTablero`, controlador y repositorio quedan igual. El contrato agrega el campo `sentido` (cambio compatible).

```php
public function cumpleMeta(): bool
{
    return $this->sentido === 'menor_mejor'
        ? $this->valor <= $this->meta
        : $this->valor >= $this->meta;
}
```

**Prueba de aceptacion del cambio:** Espera 15 min con meta 20 y `menor_mejor` -> cumple; ocupacion 70 con meta 80 y `mayor_mejor` -> no cumple.

| Capa | Cambia? |
|---|---|
| Presentation (controlador / API) | No |
| Application (caso de uso) | Solo si recibe la nueva politica por constructor |
| Domain | Si: la regla vive aqui |
| Infrastructure (Repository) | No |

## Criterios de evaluacion cubiertos

- Presentacion de 6-8 diapositivas: 7 en `presentacion.md`.
- Diagrama trazable: cada RF llega a un componente y a una prueba existente de la Semana 3.
- Matriz decision -> evidencia: 6 decisiones con enlace a artefactos ya fusionados.
- Cambio practico: impacto por capa, codigo propuesto y prueba de aceptacion.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-26-dashboard-base/semana-06/fuentes/*.puml -o ..
```
