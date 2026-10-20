# Narrativa de alcance y trazabilidad - ASII-14

## Alcance

El proceso permite al **Gestor autorizado del catalogo** dar de alta un medicamento y consultar o cambiar su vigencia, y al **Usuario clinico autorizado** buscar y consultar medicamentos. **Control de acceso** valida identidad, hospital activo y autorizacion antes de cada operacion.

Incluye validacion de acceso y datos; alta confirmada o rechazo; busqueda con resultados, sin resultados o fallo; consulta/cambio `active/inactive`; medicamento inexistente y fallo de persistencia. Excluye lotes, existencias, fechas de vencimiento, dispensacion, prescripciones, API, UI y codigo ejecutable.

## Evidencia del repositorio

| Estado | Hallazgo |
|---|---|
| Existente | El inventario HOSPITAL exige las entidades `medication` y `prescription` en `openspec/.../hospital-clinical-schema/spec.md`. |
| Existente | El slice H5 y `000500_create_medication_tables.php` siguen pendientes en `design.md`, `tasks.md` y `apply-progress.md`. |
| Existente | No hay migracion, modelo, tabla, endpoint ni servicio de medicamentos en `origin/develop`. |
| Existente | Prescripciones documenta que necesita consultar medicamento, principio activo, grupo y estado activo, pero marca su `MedicationCatalog` como propuesto. |
| Conclusion | El esquema actual no define columnas, identidad de negocio, restriccion de duplicidad ni mecanismo de vigencia para `medication`. |

## Diseno propuesto y supuestos

- **Vigencia:** estado del registro `active/inactive`; no es vencimiento de lotes. La busqueda puede mostrar ambos estados y los consumidores clinicos deben distinguir el inactivo.
- **Identidad provisional:** codigo normalizado unico dentro de cada base HOSPITAL. El alta consulta ese codigo y rechaza duplicidad; una restriccion definitiva queda pendiente del slice H5.
- **Datos minimos propuestos:** codigo, nombre, principio activo, grupo farmacologico y estado inicial `active`.
- **Acceso:** ambos actores requieren identidad y hospital activo; solo el gestor puede dar alta o cambiar vigencia. Los permisos concretos se definiran con RBAC.
- **Confirmacion:** solo existe exito cuando la persistencia devuelve el registro confirmado. Una lectura o escritura fallida produce error, no datos parciales.

## Flujos y resultados

**Alta (UC-MED-01).** El gestor captura datos; se validan formato, campos obligatorios e identidad provisional. Datos invalidos se rechazan. Si falla la consulta de duplicidad, se informa fallo de consulta; si el codigo ya existe, se rechaza como duplicado. Si esta libre, se guarda; fallo de escritura produce error y solo la confirmacion produce "Medicamento registrado".

**Busqueda (UC-MED-02).** El usuario clinico o gestor proporciona texto o codigo. Una consulta invalida se rechaza. El repositorio devuelve resultados con vigencia, una lista vacia o un fallo de consulta claramente diferenciado.

**Vigencia (UC-MED-03/04).** Se localiza el medicamento por identificador. Una consulta fallida produce error y uno inexistente produce "Medicamento no encontrado". Consultar devuelve `active/inactive`; cambiar valida el nuevo estado, persiste y solo tras confirmacion informa "Vigencia actualizada".

## Trazabilidad

| Caso de uso y objetivo | Actividad | Secuencia |
|---|---|---|
| UC-MED-01 Dar de alta | Rama `Alta`: datos, validacion, identidad, guardado y confirmacion | Grupo `Alta`: `solicitarAlta`, `buscarPorCodigo`, `insertar` y respuesta confirmada |
| UC-MED-02 Buscar | Rama `Busqueda`: consulta valida, fallo, lista vacia o resultados | Grupo `Busqueda`: gestor o usuario clinico solicita `buscar`; recibe fallo, vacio o resultados |
| UC-MED-03 Consultar vigencia | Rama `Vigencia`: localizar y mostrar estado actual | Grupo `Consultar vigencia`: gestor o usuario clinico; `obtenerPorId` y respuesta `active/inactive` |
| UC-MED-04 Cambiar vigencia | Rama `Vigencia`: validar estado, actualizar y confirmar | Grupo `Cambiar vigencia`: solo gestor; medicamento encontrado antes de `actualizarEstado` y respuesta tras confirmar |
| UC-MED-05 Validar acceso | Decision inicial obligatoria para las tres ramas | `validar(actor, hospital, operacion)` antes de alta, busqueda, consulta o cambio |
| UC-MED-06 Validar datos e identidad | Alta: formato e identidad provisional sin duplicado | `validarDatos` y `buscarPorCodigo` antes de `insertar` |

## Pendientes

- Confirmar con el slice H5 campos, identidad unica, alcance por hospital y estados permitidos.
- Confirmar permisos RBAC, comportamiento de busqueda para consumidores clinicos y necesidad de auditoria.
- Mantener el futuro PR vinculado al [Issue #165](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/165) mediante `Refs #165`.
