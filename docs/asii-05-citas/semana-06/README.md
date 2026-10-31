# Gestion de citas medicas y agenda - Semana 6

> Tema guia: **Primera evaluacion parcial: defensa teorica y caso practico de arquitectura y diseno**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Principio SOLID aplicado:** Dependency Inversion (DIP)
## Entregables

- [Presentacion (7 diapositivas)](./presentacion.md)
- [Diagrama trazable RF -> CU -> componente -> prueba](./01-trazabilidad.svg) | [fuente](./fuentes/01-trazabilidad.puml)
- Matriz decision -> evidencia y resolucion del cambio practico (este documento).

## Defensa teorica (guion)

1. **Problema:** Recepcion agenda citas en papel y hojas de calculo: hay dobles reservas del mismo doctor o paciente y no queda trazabilidad de cancelaciones.
2. **Actores:** Recepcion (agenda), Medico (consulta su agenda), Coordinacion (confirma/cancela).
3. **Por que capas:** separar la regla de negocio de HTTP y SQL permite probarla sola y cambiar la
   infraestructura sin tocarla.
4. **Por que Dependency Inversion (DIP):** es el principio que mas protege la regla central del modulo (ver D-02).
5. **Como se integra:** por contrato API versionado; ningun modulo lee las tablas de otro.

## Matriz decision -> evidencia

| ID | Decision | Alternativa descartada | Justificacion | Evidencia |
|---|---|---|---|---|
| D-01 | Arquitectura en 4 capas | Controlador con SQL y reglas | la regla anti-solape queda en un solo lugar y se prueba sin BD | [Semana 3: capas](../semana-03/01-capas.svg) |
| D-02 | DIP: `AgendarCita` depende de `CitaRepository` | Depender de PDO | permite pruebas en memoria y cambiar de motor | [Semana 2: DIP](../semana-02/solid-dip.md) |
| D-03 | Patron Repository con 2 adaptadores | Active Record | el Dominio no conoce SQL | [Semana 3: Persistence](../semana-03/micro-his/src/Persistence/) |
| D-04 | Contrato API antes del codigo | Definir endpoints al final | Dashboard y Reportes consumen sin esperar la implementacion | [Contrato API](../contrato-api.md) |
| D-05 | Anti-solape en aplicacion + exclusion GiST en BD | Solo validar en aplicacion | evita la condicion de carrera entre dos recepcionistas | [Contrato API §1](../contrato-api.md) |
| D-06 | Estados como maquina (`scheduled` -> `confirmed` -> `completed` / `cancelled`) | Campo libre de estado | transiciones invalidas se rechazan en el Dominio | [Semana 3: RF/AC](../semana-03/rf-rnf-criterios.md) |

## Caso practico: resolucion de cambio

**Cambio solicitado:** La clinica pide **10 minutos de holgura** entre citas del mismo doctor.

**Analisis de impacto:** Solo cambia la regla de disponibilidad. Se introduce una `PoliticaAgenda` (holgura en minutos) que `AgendarCita` recibe por constructor; `Cita::seSolapaCon` compara contra `hasta + holgura`. Controlador, Repository y contrato API no cambian (el error sigue siendo `409 appointments_overlap`).

```php
final class PoliticaAgenda
{
    public function __construct(public readonly int $holguraMin = 10) {}
    public function finConHolgura(string $hasta): string
    {
        return (new \DateTimeImmutable($hasta))->modify("+{$this->holguraMin} minutes")->format('Y-m-d\\TH:i');
    }
}
```

**Prueba de aceptacion del cambio:** Cita 09:00-09:30; una nueva a las 09:35 con el mismo doctor -> 409; a las 09:40 -> 201.

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
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-05-citas/semana-06/fuentes/*.puml -o ..
```
