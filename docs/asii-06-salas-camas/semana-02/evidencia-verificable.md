# Evidencia verificable

## Fuente primaria

`database/migrations/hospital/2026_08_11_000300_create_scheduling_and_bed_tables.php` define las tablas relacionadas con el catálogo.

| Elemento | Evidencia del esquema | Uso en esta entrega |
|---|---|---|
| `wards` | `ward_id` UUID; `code`, `name`, `location` obligatorios; `status` predeterminado `active`, restringido a `active` e `inactive`; `code` único. | Datos RF-01, RF-02 y RF-05. |
| `beds` | `bed_id` UUID; `ward_id` obligatorio con referencia a `wards(ward_id)` y `ON DELETE RESTRICT`; `code` y `type` obligatorios; `status` predeterminado `available`, restringido a `available`, `occupied`, `maintenance` e `inactive`. | Datos RF-03, RF-04 y RF-06. |
| `beds_ward_code_unique` | Restricción única sobre `(ward_id, code)`. | Impide repetir el código de cama dentro de una misma sala, RF-04. |
| `beds_ward_id_index` | Índice sobre `beds(ward_id)`. | Confirma que la relación de camas se consulta por sala. |
| `bed_stays` | Tabla de estancias con `bed_id`, intervalo y restricciones temporales; no declara reglas de transición de `beds.status`. | Delimita el alcance: la entrega no infiere política de estado desde la estancia. |

## Verificación de criterios

Una revisión o prueba de persistencia puede comprobar directamente:

1. Crear un ward y confirmar el estado predeterminado `active`.
2. Rechazar un segundo ward con el mismo `code`.
3. Crear una cama para un `ward_id` existente y confirmar `available`.
4. Rechazar la referencia a una sala inexistente y un código duplicado dentro de esa sala.
5. Rechazar estados que no estén en los dominios declarados para cada tabla.
6. Verificar que un fallo al guardar no deje un registro parcial, usando una transacción si la operación del flujo comprende más de una escritura.

Estas comprobaciones contrastan el diseño propuesto con las restricciones de la migración. No prueban por sí solas la existencia de componentes de aplicación ni un comportamiento que el esquema no imponga.

## Estado de implementación

En `app/` no se localizaron controladores o modelos específicos de administración del catálogo de wards y beds. Por ello, los componentes descritos en `solid-srp.md` y sus responsabilidades son una propuesta académica; la evidencia actual es el esquema de base de datos citado, no una ejecución de un flujo implementado.

