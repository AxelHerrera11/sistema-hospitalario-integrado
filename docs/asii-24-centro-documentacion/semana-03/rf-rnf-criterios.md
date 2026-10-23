# Requisitos y criterios de aceptación - ASII-24 Semana 3

## Requisitos funcionales

| ID | Requisito |
|---|---|
| RF-01 | Publicar un documento con título y al menos un rol destinatario. |
| RF-02 | Consultar el catálogo de documentos visibles para el rol del usuario. |
| RF-03 | Rechazar la publicación cuando no se indique ningún rol. |
| RF-04 | Normalizar los roles (sin duplicados ni vacíos) antes de persistir. |
| RF-05 | Exponer la publicación y la consulta por CLI sin duplicar reglas de negocio. |

## Requisitos no funcionales

| ID | Requisito |
|---|---|
| RNF-01 | PHP 8.2+ sin framework ni Composer. |
| RNF-02 | Acceso a datos con PDO y sentencias preparadas. |
| RNF-03 | El Dominio no depende de PDO, entrada CLI ni infraestructura. |
| RNF-04 | Errores de persistencia traducidos a excepciones de Dominio. |
| RNF-05 | Sin credenciales ni datos reales en el repositorio. |

## Criterios de aceptación

- **AC-01**: publicar sin roles lanza `DocumentoSinRolesException` (`422 documento_sin_roles`).
- **AC-02**: un documento publicado conserva sus roles normalizados.
- **AC-03**: la consulta de un rol solo devuelve sus documentos visibles.
- **AC-04**: el adaptador PDO usa sentencias preparadas.
- **AC-05**: un fallo de persistencia se propaga como `ErrorPersistenciaException`.
- **AC-06**: `src/Domain/` no contiene `PDO`, `$_GET`, `$_POST` ni `header(`.

## Trazabilidad

| Criterio | Artefacto | Prueba |
|---|---|---|
| AC-01 | `src/Domain/Documento.php` | dominio: rechaza documento sin roles |
| AC-02 | `src/Domain/Documento.php` | dominio: normaliza y detecta visibilidad por rol |
| AC-03 | `src/Application/ConsultarDocumentos.php` | aplicacion: no lista documentos de otro rol |
| AC-04 | `src/Persistence/DocumentoRepositoryPDO.php` | revisión manual |
| AC-05 | `src/Persistence/DocumentoRepositoryPDO.php` | aplicacion: error de persistencia se propaga |
| AC-06 | `src/Domain/` | revisión manual |
