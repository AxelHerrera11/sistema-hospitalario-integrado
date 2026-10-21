# Semana 6 — Primera evaluación parcial

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 6 — Primera evaluación parcial
- **Entregable / evidencia:** Presentación con modelo UML, requisitos de datos, arquitectura federada y matriz de acceso ([presentacion.md](./presentacion.md)) y la autoevaluación en [autoevaluacion.md](./autoevaluacion.md)).
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Evidencia de la semana

| Entregable | Archivo | Semana |
|---|---|---|
| Modelo de clases UML | `semana-01/01-modelo-clases.svg` | 1 |
| Correspondencia clase → tabla | `semana-01/02-clase-tabla.md` | 1 |
| RF/RNF de datos | `semana-02/rf-rnf-datos.md` | 2 |
| Matriz de verificación | `semana-02/matriz-verificacion.md` | 2 |
| Vista de despliegue federada | `semana-03/01-arquitectura-federada.svg` | 3 |
| Asignación de entidades | `semana-03/asignacion-entidades.md` | 3 |
| Diagrama de capas | `semana-04/01-capas-persistencia.svg` | 4 |
| Consultas principales | `semana-04/consultas-principales.sql` | 4 |
| Matriz endpoint → tablas | `semana-05/README.md` | 5 |

## 2. Validación ejecutada

```bash
# comando: podman run --rm --network host -v $PWD:/app -w /app localhost/php85-pgsql-full php vendor/bin/phpunit tests/Database
# resultado: OK (53 tests, 679 assertions) — el esquema que se defiende es el que pasa sus pruebas

# comando: xmllint --noout docs/verificacion-postgresql/semana-{01,03,04}/*.svg
# resultado: sin salida (los tres SVG de las semanas 1, 3 y 4 son XML valido)
```

## 3. Riesgos y pendientes

- La evaluación parcial se entrega con las semanas 1 a 5 publicadas; las siguientes
  semanas siguen abiertas.
- El caso práctico usa el esquema y las restricciones reales; no hay datos de
  pacientes ni información clínica identificable.
- Si entran migraciones nuevas antes del corte, el modelo y la matriz deben
  actualizarse.
