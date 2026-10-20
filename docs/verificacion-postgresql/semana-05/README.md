# Semana 5 — Cliente-servidor, API REST, microservicios e integración

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 5 — Mapeo de endpoints a tablas y operaciones transaccionales
- **Entregable / evidencia:** Matriz endpoint → tablas → tipo de operación, con transacciones señaladas ([matriz-endpoint-tablas.md](./matriz-endpoint-tablas.md)).
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Se mapea qué endpoints leen o escriben cada tabla del esquema federado y qué
operaciones **deben** ser transaccionales. La matriz se construyó desde el código
real (`routes/api.php`, `micro-his/`) y desde los contratos publicados por los
módulos; los endpoints que todavía no existen se marcan como *contratado* o
*propuesto*, nunca como implementado.

Criterio de la columna **transacción**:

- **Sí**: la operación escribe más de una tabla o depende de que un estado quede
  coherente; si falla una parte, debe revertirse toda.
- **No**: lectura simple o escritura de una sola fila sin estado derivado.

## 3. Observaciones de la base

- Las tablas de laboratorio, alertas, medicamentos, prescripciones y auditoría local
  **no tienen migración todavía**: la columna "transacción" de esas filas es una
  expectativa de diseño y debe confirmarla el dueño de cada módulo.
- Las escrituras que tocan `central_users` hoy fallan con HTTP 500 porque los modelos
  `User` y `Tenant` del esquema anterior se eliminaron; la credencial no vive en
  `central_users` y su ubicación sigue sin definirse.
- El módulo de ocupación solo lee: no asigna, traslada ni libera camas, por lo que su
  operación es una lectura consistente sin transacción de escritura.

## 4. Validación ejecutada

```bash
# comando: grep -n "Route::" routes/api.php
# resultado: cinco rutas reales de autenticacion bajo /api/v1 con middleware tenant y jwt

# comando: grep -rn "prepare(\|execute(" micro-his/ocupacion-hospitalaria/src
# resultado: el unico endpoint de ocupacion usa sentencias preparadas sobre la conexion hospital

# comando: podman run --rm --network host -v $PWD:/app -w /app localhost/php85-pgsql-full php vendor/bin/phpunit tests/Database
# resultado: OK (53 tests, 679 assertions) — las restricciones que hacen posibles esas transacciones estan verificadas
```

## 5. Riesgos y pendientes

- La matriz mezcla endpoints implementados, contratos publicados y propuestas: cada
  fila debe revisarse cuando su módulo publique su ruta.
- Las unidades transaccionales que dependen de tablas sin migración no se pueden
  verificar todavía.
- Falta el contrato común de errores y paginación, que sigue pendiente de acuerdo
  entre los módulos.
