# Semana 17 — Seguridad y despliegue final

**Tarea:** analizar amenazas del flujo, probar permisos y protección de datos sensibles, corregir lo encontrado y
ejecutar el despliegue final con su bitácora.

## Entregables

| Archivo | Contenido |
|---|---|
| [`matriz-amenazas.md`](matriz-amenazas.md) | 13 amenazas STRIDE con control, prueba y riesgo residual |
| [`bitacora-despliegue.md`](bitacora-despliegue.md) | Despliegue on-premise con releases, validaciones y rollback ensayado |
| [`evidencias/01-pruebas-seguridad.txt`](evidencias/01-pruebas-seguridad.txt) | Suite de seguridad S-01…S-08, regresiones y pruebas Feature |
| [`evidencias/02-ensayo-despliegue.txt`](evidencias/02-ensayo-despliegue.txt) | Salida del ensayo de despliegue con la hora de cada paso |

## Pruebas nuevas (`micro-his/expediente-medico/tests/seguridad.php`)

| Prueba | Qué comprueba | Amenaza |
|---|---|---|
| S-01 | Matriz activa rol × operación: solo la médica tratante consulta; nadie abre | A-06, A-12, A-13 |
| S-02 | Con la concesión propuesta: abren medicina y recepción; enfermería, admin y ajenos no | A-12 |
| S-03 | Paciente inexistente y paciente sin vínculo responden igual (sin enumeración) | A-07 |
| S-04 | Inyección SQL, `../`, 300 caracteres, byte nulo y HTML → 422 sin eco ni SQL | A-03 |
| S-05 | Otro hospital, hospital inexistente, sin hospital y no miembro | A-02 |
| S-06 | 12 respuestas de 6 actores: sin contacto ni `global_id`; `no-store` y `nosniff` | A-08 |
| S-07 | La bitácora registra cada acceso sin motivos, nombres ni fechas de nacimiento | A-05, A-09 |
| S-08 | Apertura repetida con la bitácora caída → 503 sin número de expediente | A-05 |

Resultado: **97 OK, 0 FALLO** en el micro-HIS y 5/5 pruebas Feature de Laravel.

## Hallazgo y corrección

**H-EXP-17-01 (Mayor, repudio):** al repetir la apertura (o perder la carrera con otro proceso), el sistema devolvía el
id y el número del expediente sin registrar el acceso, en contra de RF-EXP-05. Lo detectó S-07. Corrección en
`OpenMedicalRecord`: evento `expediente_medico.apertura_repetida` en ambos caminos y 503 si no se puede registrar
(S-08). Las pruebas de caja blanca A3 y A6 se actualizaron al requisito.

## Archivos cambiados

`micro-his/expediente-medico/src/Application/{OpenMedicalRecord,ClinicalAccessAudit}.php`,
`micro-his/expediente-medico/tests/{seguridad,caja-blanca,run}.php` y esta carpeta.

## Riesgos que quedan

- A-11 (Medio): sin límite de peticiones por usuario; requiere una decisión común del grupo.
- Despliegue con PostgreSQL y HTTPS sin ensayar: no hay servidor del grupo.
