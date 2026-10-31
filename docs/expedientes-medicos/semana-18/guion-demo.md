# Guion de la demo funcional (10 minutos)

Flujo: **apertura y consulta autorizada del expediente longitudinal**. Todo con datos ficticios de
`micro-his/expediente-medico/database/demo.sql`. Salida real de esta demo: [`evidencias/01-demo.txt`](evidencias/01-demo.txt).

## Preparación (antes de presentar)

```bash
php micro-his/expediente-medico/tests/run.php                      # debe terminar en «0 FALLO»
php -S 127.0.0.1:8096 -t micro-his/expediente-medico/public micro-his/expediente-medico/public/index.php
```

Abrir `http://127.0.0.1:8096/ui/` en el navegador. Plan B si el navegador falla: los mismos pasos con `curl`
(sección «Por API») y las capturas de la Semana 11 (`../semana-11/evidencias/`).

## Recorrido

| Min | Paso | Qué mostrar | Resultado esperado | Requisito |
|---|---|---|---|---|
| 0–1 | Contexto | Problema: cada servicio veía solo su episodio; objetivo: un expediente longitudinal con acceso autorizado | — | Alcance S1 |
| 1–3 | Consulta de la Dra. López (P-0001) | Tres episodios, el más reciente primero; sin teléfono ni id global | 200, `no-store` | RF-EXP-02, RNF-EXP-01 |
| 3–4 | Dr. Pérez (asignación terminada) y enfermería | Tienen el permiso pero no vínculo asistencial | 403 `SIN_VINCULO_ASISTENCIAL` | RF-EXP-04 |
| 4–5 | P-9999 (no existe) | Responde igual que un paciente sin vínculo: no se puede enumerar | 403 | Matriz A-07 |
| 5–6 | Apertura de P-0002 por recepción y repetición | El segundo intento devuelve el mismo expediente | 201, luego 200 `created:false` | RF-EXP-01 |
| 6–7 | Bitácora de Gobernanza | `consultado`, `acceso_denegado`, `abierto`, `apertura_repetida`; un `UPDATE` es rechazado | Append-only | RF-EXP-05 |
| 7–8 | Laravel | `route:list` con `JwtAuth` y `RequirePermission:expediente.ver`; pruebas Feature | 5/5 | Integración |
| 8–9 | Calidad | Suite del micro-HIS (unitarias, caja blanca y negra, PDO, seguridad, arquitectura) | 0 fallos | Semanas 15 y 17 |
| 9–10 | Cierre | Hallazgos corregidos (DEF-EXP-10, H-EXP-17-01) y pendientes con otros módulos | — | Semanas 16–17 |

## Por API (plan B)

```bash
B=http://127.0.0.1:8096/patients; H="-H X-Tenant-ID:hospital-demo"
curl -s $B/P-0001/medical-record $H -H X-User-ID:11111111-1111-4111-8111-111111111111     # 200
curl -s $B/P-0001/medical-record $H -H X-User-ID:22222222-2222-4222-8222-222222222222     # 403
curl -s -X POST $B/P-0002/medical-record $H -H X-User-ID:44444444-4444-4444-8444-444444444444  # 201 y luego 200
```

## Preguntas probables y respuesta corta

| Pregunta | Respuesta |
|---|---|
| ¿Por qué enfermería no consulta si tiene el permiso? | La consulta exige permiso **y** vínculo asistencial; hoy solo existe la asignación médica. No se inventó una regla: la decide RBAC. |
| ¿Por qué la apertura no está en Laravel? | Su permiso no está en el catálogo de RBAC; el micro-HIS la prueba con una concesión **propuesta** que solo activa el prototipo. |
| ¿Qué pasa si la bitácora falla? | No se entrega nada: 503 `AUDITORIA_NO_DISPONIBLE` (N-12, S-08). |
| ¿Cómo evitan la enumeración de pacientes? | Inexistente y sin vínculo responden el mismo 403 (S-03). |
| ¿Qué no se probó? | PostgreSQL real, lector de pantalla y teléfono físico; se declara en cada semana. |
