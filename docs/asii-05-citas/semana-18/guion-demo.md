# Guion de la demo funcional (10 minutos)

Flujo: **recepcion agenda una cita, la confirma y el sistema protege la agenda**. Datos ficticios.
Salida real de esta demo: [`evidencias/01-demo.txt`](evidencias/01-demo.txt).

## Preparacion

```bash
php vendor/bin/phpunit tests/Feature/Citas --testdox        # OK (10 tests, 72 assertions)
php artisan route:list --path=api/v1/citas                  # Showing [7] routes
```

Apoyo visual: abrir `docs/asii-05-citas/semana-11/prototipo/` en el navegador. Plan B: la salida de
`01-demo.txt` y el diagrama `semana-11/01-camino-feliz-errores.svg`.

## Recorrido

| Min | Paso | Que mostrar | Resultado esperado | Requisito |
|---|---|---|---|---|
| 0-1 | Contexto | Agenda por hospital; el actor sale del JWT, no del cuerpo | - | Alcance S1 |
| 1-3 | Agendar | `POST /api/v1/citas` como recepcion | 201 `scheduled`, fecha normalizada a UTC | RF-01 |
| 3-4 | Datos invalidos | Falta paciente o `ends_at` antes de `starts_at` | 422 `validation_failed` con `errors` | RF-01 |
| 4-5 | Solape | Misma franja para el mismo doctor | 409 `appointments_overlap` | RF-02 |
| 5-6 | Confirmar y cancelar | `POST .../confirmar` y `.../cancelar` | 200 `confirmed` y `cancelled` | RF-03, RF-04 |
| 6-7 | Cita finalizada | Confirmar una cita ya cancelada | 409 `appointments_finalized_immutable` | RF-05 |
| 7-8 | Permisos y tenant | Enfermeria intenta crear; otro hospital por cabecera | 403 `RBAC_DENIED`, 403 `TENANT_MISMATCH` | Seguridad S17 |
| 8-9 | Borrado e inexistente | `DELETE` y una cita que no existe | 405 y 404 | Trazabilidad |
| 9-10 | Cierre | DIP con el adaptador en memoria y pendientes con PostgreSQL | - | RF-06, RNF-02 |

## Preguntas probables y respuesta corta

| Pregunta | Respuesta |
|---|---|
| ¿Donde se detecta el solape? | En la base, con una exclusion GiST; la API traduce el error a 409. El doble de pruebas la reproduce. |
| ¿Por que no se borran citas? | La trazabilidad clinica exige cancelar; `DELETE` responde 405 sin tocar la base. |
| ¿Que aplica DIP aqui? | El controlador pide `AppointmentRepositoryContract`; Eloquent y la memoria son adaptadores intercambiables. |
| ¿Como se evita cambiar el actor? | El usuario sale del JWT; el cuerpo solo trae paciente, doctor, especialidad, fechas y motivo. |
| ¿Que no esta probado? | La exclusion GiST y los triggers contra PostgreSQL real y la zona horaria de la conexion. |
