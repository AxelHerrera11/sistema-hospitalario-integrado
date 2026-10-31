# Guion de la demo funcional (10 minutos)

Flujo: **cada rol abre el centro de documentacion y solo ve y lee lo que le corresponde**. Datos ficticios.
Salida real de esta demo: [`evidencias/01-demo.txt`](evidencias/01-demo.txt).

## Preparacion

```bash
php vendor/bin/phpunit tests/Feature/CentroDocumentacion --testdox   # OK (10 tests, 57 assertions)
php micro-his/centro-documentacion/tests/run.php                      # OK: 7 verificaciones aprobadas
```

Apoyo visual: abrir `docs/asii-24-centro-documentacion/semana-11/prototipo/` en el navegador. Plan B: la
salida de `01-demo.txt` y el diagrama `semana-11/01-camino-feliz-errores.svg`.

## Recorrido

| Min | Paso | Que mostrar | Resultado esperado | Requisito |
|---|---|---|---|---|
| 0-1 | Contexto | Manuales y contratos por rol; catalogo en `config/centro_documentacion.php` | - | Alcance S1 |
| 1-3 | Catalogo por rol | `GET /api/v1/documentacion` como medico y como enfermeria | Listas distintas | RF-02 |
| 3-4 | Rol enviado por el cliente | `?rol=admin_hospital` siendo enfermeria | Se ignora | RTF-01 |
| 4-5 | Leer un documento | `GET /api/v1/documentacion/openapi-v1` como admin | 200 `application/yaml` con `X-Document-Version` | RF-04, RF-05 |
| 5-6 | Documento ajeno o inexistente | `openapi-v1` como enfermeria y `no-existe` | 404 identicos | Seguridad |
| 6-7 | Traza del error | `X-Request-Id: req-doc-0001` en el 404 | `trace_id` igual a la cabecera | Formato comun |
| 7-8 | Sesion y tenant | Sin sesion; otro hospital por cabecera | 401; 403 `TENANT_MISMATCH` | Seguridad S17 |
| 8-9 | Catalogo integro | Cada entrada apunta a un archivo real dentro de `docs/` | Prueba aprobada | RF-04 |
| 9-10 | Cierre | ISP en la consulta y lo que queda propuesto (publicar, buscar) | - | RF-06 |

## Preguntas probables y respuesta corta

| Pregunta | Respuesta |
|---|---|
| ¿Por que un documento ajeno da 404 y no 403? | Un 403 confirmaria que existe; el 404 identico no revela el catalogo de otros roles. |
| ¿Como se evita leer archivos fuera de `docs/`? | Se resuelve la ruta real y se exige que empiece por `docs/`; el catalogo lo prueba. |
| ¿Que tiene que ver con OpenAPI/Postman? | La especificacion OpenAPI y el formato de errores son documentos del catalogo, servidos por rol. |
| ¿Por que el catalogo no esta en la base? | Es documentacion compartida y versionada con el codigo; pasarlo a tabla queda propuesto con `tenant_id`. |
| ¿Que no esta implementado? | Publicar desde la interfaz, busqueda por texto y vigencia: se declaran como propuestos. |
