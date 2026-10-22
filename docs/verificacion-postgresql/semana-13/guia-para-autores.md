# Guía breve para quien abre un PR que toca la base (Semana 13)

Resumen operativo de la revisión formal, para evitar el ida y vuelta.

## Antes de abrir el PR

1. **Avisa** en el grupo de la tabla nueva y de quién la escribe. Ninguna tabla entra sin
   el aviso al módulo de verificación de BD.
2. **Consulta la lista de entidades** (`semana-03/asignacion-entidades.md`): si tu tabla
   ya existe, lo que necesitas es una migración incremental, no una tabla nueva.
3. **Elige el número de archivo** acordándolo en el grupo: los slices H5, H6 y H7 ya
   tienen números reservados.
4. **Escribe la prueba de integridad primero** (ciclo RED → GREEN): en
   `tests/Database/`, con `#[Group('database-integrity')]` y ejecutable con
   `php artisan test tests/Database/<Carpeta>/<Prueba>.php`.

## Reglas que no se negocian

| Regla | Cómo se cumple |
|---|---|
| Clave primaria `uuid` con `gen_random_uuid()` | En la definición de la tabla |
| FK **locales** con `ON DELETE RESTRICT` e índice | Una línea por FK más su `CREATE INDEX` |
| Instantes en `timestamptz` y fechas en `date` | Sin `TEXT` para fechas |
| Estados en `text` con `CHECK` de dominio | Nada de enums |
| Unicidad parcial cuando la regla es "activo a la vez" | Índice único con `WHERE` |
| Intervalos que no pueden solaparse | Exclusión GiST con `btree_gist` |
| Registros clínicos inmutables y retenidos | Trigger de inmutabilidad y de retención |
| `down` reversible y probado | Prueba que hace rollback y vuelve a aplicar |
| Menos de 400 líneas por PR | Dividir la entrega si se pasa |

## Si tu PR usa un doble (SQLite o InMemory)

El doble debe declarar **qué restricción del esquema federado sustituye**. Si no la
replica, las pruebas pasan en local y fallan al conectar el adaptador real: es
exactamente el hallazgo R-04 de la revisión del PR #376.

## Antes de pedir revisión

```bash
php artisan test tests/Database/<Carpeta>/<Prueba>.php   # prueba focalizada en verde
vendor/bin/pint --test <archivos modificados>             # formato
php -l <archivos modificados>                            # sintaxis
git diff --check                                          # espacios
```

Pega la salida real en el cuerpo del PR. El checklist del grupo pide comandos y
salidas, no resúmenes.
