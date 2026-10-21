# Procedimiento operativo de respaldo y restauración (Semana 14)

Runbook corto para quien tenga que restaurar una base de hospital. Todas las
referencias a contenedores son del entorno de prueba; en producción se sustituyen por
las herramientas de la plataforma.

## 1. Respaldo

```bash
pg_dump -U <usuario> -d <base_hospital> -Fc -f respaldo_<base>_<fecha>.dump
```

| Verificación | Comando | Resultado esperado |
|---|---|---|
| El archivo existe y no está vacío | `ls -lh respaldo.dump` | Tamaño mayor que cero |
| Formato correcto | `pg_restore --list respaldo.dump` | Lista las tablas, índices y extensiones |
| Se puede leer | `pg_restore -l respaldo.dump \| head` | Entradas legibles |

## 2. Restauración de prueba

```bash
createdb <base>_restore
pg_restore -U <usuario> -d <base>_restore --no-owner respaldo.dump
psql -U <usuario> -d <base>_restore -c "SELECT count(*) FROM information_schema.tables WHERE table_schema='public';"
psql -U <usuario> -d <base>_restore -c "SELECT extname FROM pg_extension;"
```

Criterio de aceptación: la misma cantidad de tablas que el origen, la extensión
`btree_gist` presente y la suite de integridad en verde sobre la base restaurada.

## 3. Sustitución en producción (cuando aplique)

| Paso | Acción | Verificación |
|---|---|---|
| 1 | Ventana de mantenimiento: detener escrituras | Sin conexiones de escritura |
| 2 | Respaldar la base actual | Archivo verificado |
| 3 | Restaurar el respaldo sobre la base | Sin errores de `pg_restore` |
| 4 | Comparar conteos de las tablas críticas | Coinciden con el respaldo |
| 5 | Ejecutar la suite de integridad | Verde |
| 6 | Reanudar escrituras | Monitoreo de errores en los primeros minutos |

## 4. Qué hacer si la restauración falla

1. No reintentar a ciegas: la base restaurada queda en estado desconocido.
2. Crear una base nueva y restaurar de nuevo desde el mismo respaldo.
3. Si el respaldo falla también, el problema es el archivo, no el destino: verificar
   `pg_restore --list`.
4. Registrar el incidente con la base, la hora y el resultado del `pg_restore`.

## 5. Advertencias

- Un respaldo contiene datos clínicos: se cifra y se accede con el mismo control que
  la base.
- `pg_restore` sin `--no-owner` intenta asignar el propietario original, que puede no
  existir en el servidor destino.
- La restauración de prueba se hace **siempre** sobre una base descartable, nunca
  encima de la base de producción.
