# Centro de documentacion y manuales por rol - Semana 4

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-24` | **Issue:** [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208)
- **Proceso:** publicacion y consulta de documentacion y manuales diferenciados por rol
- **Principio SOLID aplicado:** Interface Segregation (ISP)
## Entregables

- [Diagrama MVC + Repository](./01-mvc-repository.svg) | [fuente](./fuentes/01-mvc-repository.puml)
- [Integracion con repositorio de datos compartido](./02-repositorio-compartido.svg) | [fuente](./fuentes/02-repositorio-compartido.puml)
- Codigo ejecutable [Micro-HIS MVC + Repository](./micro-his/); requisitos, criterios y evidencia abajo.

## Capas, responsabilidades y objetos reutilizables

La entrada se organiza con MVC y el acceso a datos con el patron Repository. El controlador no
tiene SQL ni reglas; la interfaz `DocumentoRepository` vive en el Dominio y la Infraestructura
provee dos adaptadores intercambiables.

| Capa | Responsabilidad | Artefactos |
|---|---|---|
| Presentation (MVC) | Router y controlador: HTTP a los casos de uso | `public/index.php`, `Presentation/DocumentoController.php` |
| Application | Casos de uso publicar y consultar | `PublicarDocumento`, `ConsultarDocumentos` |
| Domain | Entidad `Documento` e interfaz `DocumentoRepository` | `src/Domain/` |
| Infrastructure | Adaptadores `DocumentoRepositoryPDO` y `DocumentoRepositoryInMemory` | `src/Infrastructure/` |

El modulo se integraria a un repositorio de datos compartido del HIS implementando la misma
interfaz `DocumentoRepository` sobre el esquema comun (tenant + rol), sin cambiar Dominio ni
controlador (ver `02-repositorio-compartido.puml`).

## Requisitos, criterios de aceptacion y evidencia

- **RF-01**: publicar un documento recibiendo la solicitud por la entrada MVC (HTTP).
- **RF-02**: rechazar la publicacion cuando no se indique ningun rol.
- **RF-03**: listar el catalogo visible para el rol del usuario.
- **RF-04**: persistir y recuperar documentos mediante la interfaz `DocumentoRepository`.
- **RF-05**: intercambiar el adaptador (PDO o InMemory) sin cambiar controlador ni dominio.
- **RNF-01**: PHP 8.2+ sin framework; controlador sin SQL ni reglas; Repository en el Dominio.
- **AC-01**: responde `201` al publicar, `200` al listar y `422 documento_sin_roles` sin roles.
- **AC-02**: la lista solo devuelve documentos visibles para el rol consultado.
- **AC-03**: ambos adaptadores cumplen el mismo contrato sin tocar el controlador.
- **AC-04**: `src/Presentation/` y `src/Application/` no contienen SQL.

Evidencia (desde `micro-his/`): `php -d zend.assertions=1 -d assert.exception=1 tests/ejecutar_pruebas.php`
(3 pruebas, 0 fallidas); y con `php -S localhost:8080 -t public`, la entrada MVC responde
`201`/`200` en el camino feliz y `422` cuando faltan roles.

## Regeneracion

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-24-centro-documentacion/semana-04/fuentes/*.puml -o ..
```

Este entregable corresponde a la Semana 4 del modulo; el PR apuntara a `develop` con `Refs #208`.
