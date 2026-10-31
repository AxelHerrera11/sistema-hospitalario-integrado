# Centro de documentacion y manuales por rol - Semana 6

> Tema guia: **Primera evaluacion parcial: defensa teorica y caso practico de arquitectura y diseno**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-24` | **Issue:** [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208)
- **Proceso:** publicacion y consulta de documentacion y manuales diferenciados por rol
- **Principio SOLID aplicado:** Interface Segregation (ISP)
## Entregables

- [Presentacion (7 diapositivas)](./presentacion.md)
- [Diagrama trazable RF -> CU -> componente -> prueba](./01-trazabilidad.svg) | [fuente](./fuentes/01-trazabilidad.puml)
- Matriz decision -> evidencia y resolucion del cambio practico (este documento).

## Defensa teorica (guion)

1. **Problema:** Los manuales y contratos API estan dispersos (Drive, chats, PDFs) y cada rol ve documentos que no le corresponden o versiones viejas.
2. **Actores:** Administrador de documentacion (publica), personal clinico y administrativo (consulta segun su rol), desarrolladores (consumen OpenAPI).
3. **Por que capas:** separar la regla de negocio de HTTP y SQL permite probarla sola y cambiar la
   infraestructura sin tocarla.
4. **Por que Interface Segregation (ISP):** es el principio que mas protege la regla central del modulo (ver D-02).
5. **Como se integra:** por contrato API versionado; ningun modulo lee las tablas de otro.

## Matriz decision -> evidencia

| ID | Decision | Alternativa descartada | Justificacion | Evidencia |
|---|---|---|---|---|
| D-01 | Arquitectura en 4 capas | Script unico que lee y filtra | la regla de visibilidad se prueba aislada | [Semana 3: capas](../semana-03/01-capas.svg) |
| D-02 | ISP: `PublicarDocumento` y `ConsultarDocumentos` separados | Un servicio con todos los metodos | los lectores no dependen de operaciones de escritura | [Semana 2: ISP](../semana-02/solid-isp.md) |
| D-03 | Visibilidad por rol en el Dominio (`visiblePara`) | Filtrar en la vista | un cliente no puede saltarse el filtro | [Semana 3: Domain](../semana-03/micro-his/src/Domain/Documento.php) |
| D-04 | OpenAPI como fuente unica del API v1 | Documentar en Word | contrato verificable y versionado con el codigo | [openapi.yaml](../openapi.yaml) |
| D-05 | Formato comun de respuestas y errores | Cada modulo con su formato | clientes y Postman reutilizan el mismo manejo de errores | [Formato de errores](../formato-respuestas-errores.md) |
| D-06 | Patron Repository con 2 adaptadores | Acceso directo a archivos | permite cambiar el almacenamiento | [Semana 3: Persistence](../semana-03/micro-his/src/Persistence/) |

## Caso practico: resolucion de cambio

**Cambio solicitado:** Los manuales deben tener **version** y solo debe mostrarse la **vigente**.

**Analisis de impacto:** `Documento` agrega `version` y `vigente`. `PublicarDocumento` marca como no vigente la version anterior del mismo titulo; `ConsultarDocumentos` filtra `vigente`. Por ISP se agrega una interfaz de lectura `HistorialDocumentos` solo para quien necesite versiones viejas, sin tocar a los lectores actuales.

```php
interface HistorialDocumentos
{
    /** @return list<Documento> todas las versiones de un titulo, la vigente primero */
    public function versiones(string $titulo): array;
}
```

**Prueba de aceptacion del cambio:** Publicar 'Manual v1' y luego 'Manual v2' -> el catalogo devuelve solo v2; `versiones('Manual')` devuelve ambas.

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
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-24-centro-documentacion/semana-06/fuentes/*.puml -o ..
```
