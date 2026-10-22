# Centro de documentación y manuales por rol - Semana 3

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-24`
- **Issue:** [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208)
- **Proceso:** publicación y consulta de documentación y manuales diferenciados por rol
- **Principio SOLID aplicado:** Interface Segregation (ISP)

## Entregables

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Evidencia verificable](./evidencia-verificable.md)
- [Diagrama de capas](./01-capas.svg) | [fuente PlantUML](./fuentes/01-capas.puml)
- [Diagrama de contexto C4](./02-contexto-c4.svg) | [fuente PlantUML](./fuentes/02-contexto-c4.puml)
- Código ejecutable [Micro-HIS en capas](./micro-his/) (PR hermano de código)

## Vista arquitectónica

El Micro-HIS del centro de documentación separa responsabilidades en cuatro capas. La
dirección de dependencias apunta siempre hacia el Dominio.

| Capa | Responsabilidad | Artefactos |
|---|---|---|
| Presentation | Adapta la entrada CLI al caso de uso | `micro-his/bin/consultar_documentos.php` |
| Application | Orquesta casos de uso de publicación y consulta | `PublicarDocumento`, `ConsultarDocumentos` |
| Domain | Entidad `Documento`, regla de roles y puerto `DocumentoRepository` | `src/Domain/` |
| Persistence | Adaptador PDO e implementaciones en memoria | `DocumentoRepositoryPDO`, `DocumentoRepositoryEnMemoria` |

## Regeneración

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-24-centro-documentacion/semana-03/fuentes/*.puml -o ..
php -d zend.assertions=1 -d assert.exception=1 docs/asii-24-centro-documentacion/semana-03/micro-his/tests/ejecutar_pruebas.php
```

Este entregable corresponde a la Semana 3 del módulo; el PR apunta a `develop` con `Refs #208`.
