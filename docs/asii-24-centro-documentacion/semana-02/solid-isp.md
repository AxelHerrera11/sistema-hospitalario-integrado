# Aplicación de ISP al centro de documentación

## Principio

El principio de segregación de interfaces (ISP) propone que ningún cliente dependa de métodos que no usa. Es preferible tener varias interfaces específicas por consumidor que una única interfaz "gorda". En este módulo, los clientes son el catálogo (consulta), el panel de publicación (registro) y la descarga; cada uno necesita un subconjunto distinto de operaciones.

## Diseño antes de ISP: interfaz "gorda"

El siguiente esquema es conceptual. Representa una implementación problemática, no código existente:

```text
RepositorioDocumentosGordo (7 métodos)
  ├─ publicar / clasificar      (registro)
  ├─ listar / buscar            (consulta)
  ├─ descargar                  (descarga)
  └─ versionar / eliminar       (mantenimiento)

CatalogoController   -> solo necesita listar / buscar
ResponsableController -> solo necesita publicar / clasificar
DescargaController   -> solo necesita descargar
```

Cada cliente debe implementar (o dejar sin usar) métodos que no necesita; los clientes de solo lectura quedan obligados a declarar `publicar`, `descargar` o `eliminar`.

### Consecuencias

- Un cambio en `publicar` puede afectar a clientes que solo consultan.
- Probar el catálogo exige cubrir métodos ajenos a su responsabilidad.
- Implementar un cliente nuevo obliga a rellenar métodos sin uso.
- El contrato público de cada cliente es más grande y frágil de lo necesario.

## Diseño después de ISP: interfaces segregadas

```text
RegistroDocumentos   (publicar, clasificar)
ConsultaDocumentos   (listar, buscar)
DescargaDocumentos   (descargar)
VersionDocumentos    (versionar, vigencia)

CatalogoController   -> ConsultaDocumentos
ResponsableController -> RegistroDocumentos
DescargaController   -> DescargaDocumentos
DocumentosRepository -> implementa varias interfaces segregadas (adaptador único)
```

| Interfaz | Responsabilidad | Consumidor |
|---|---|---|
| `RegistroDocumentos` | Publicar y clasificar documentos con sus roles. | Panel del responsable |
| `ConsultaDocumentos` | Listar y buscar el catálogo visible por rol. | Catálogo |
| `DescargaDocumentos` | Entregar el archivo permitido al rol. | Descarga |
| `VersionDocumentos` | Gestionar versiones y vigencia. | Mantenimiento |

## Ejemplo concreto de ISP

Si cambia la firma de `publicar`, solo se ven afectados los clientes que dependen de `RegistroDocumentos`; el catálogo y la descarga, que dependen de otras interfaces, no se modifican. Si el catálogo necesita un filtro nuevo en `buscar`, el cambio queda en `ConsultaDocumentos` y no obliga a tocar el registro ni la descarga.

La evidencia (`evidencia/demo-isp.php`) demuestra que el **mismo `CatalogoController` pasa de "declarar" 7 métodos (5 sin uso) a depender de solo 2** al segregar la interfaz, y que un cliente de publicación usa otro contrato sin implementar métodos ajenos.

## Justificación de dependencias

- Cada cliente depende de la interfaz mínima que corresponde a su consumidor, no de un contrato único y grande.
- Un mismo adaptador (`DocumentosRepository`) puede implementar varias interfaces segregadas; el adaptador no se segrega, el **contrato** sí.
- La especificación base OpenAPI se mantiene abierta a nuevas rutas sin editar sus componentes ya publicados, reflejando el mismo criterio de ISP.
- La separación es una propuesta de diseño para el flujo descrito, no una afirmación de que estas interfaces estén implementadas.
