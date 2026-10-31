# Médicos, especialidades y disponibilidad — Semana 17: seguridad y despliegue final

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-04 · **Tema:** matriz de amenazas, pruebas de permisos y datos sensibles, y bitácora de despliegue.

| Requisito | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | **Hecho** | [amenazas.md](./amenazas.md): 18 amenazas, 13 probadas, 1 verificada por lectura, 2 parciales, 2 abiertas |
| Pruebas de permisos y datos sensibles | **Hecho** | `tests/Feature/Medicos/DoctorSecurityTest.php` (7 pruebas) y `DoctorEndpointTest.php` (11): OK, 18 tests y 79 assertions |
| Bitácora de despliegue | **Parcial** | [bitacora-despliegue.md](./bitacora-despliegue.md): despliegue **local** de verificación con PostgreSQL 16 efímero |
| Despliegue final | **Bloqueado** | Sin staging u on-premise ni semillas aprobadas de `specialties` y `local_user_refs` |

**La Semana 17 no se declara completa:** el despliegue final depende del entorno, dos amenazas siguen abiertas y dos son parciales.

## 1. Qué se probó

`DoctorSecurityTest` usa el adaptador PDO real sobre SQLite en memoria, no un doble, para que la inyección SQL y la asignación masiva se prueben contra sentencias de verdad.

| Prueba | Amenaza | Qué verifica |
|---|---|---|
| T1 | MED-04 | Una licencia con `'); DROP TABLE ...;--` se guarda como texto y la tabla sigue existiendo; `NEURO' OR '1'='1` no devuelve médicos; un id de ruta con comillas es 404 |
| T2 | MED-05 | Enviar `doctor_profile_id`, `status`, `valid_to` u `hospital_id` no fija nada: el sistema pone el uuid, `active` y la vigencia |
| T3 | MED-02 | Las cabeceras `X-User-ID` y `X-Actor` del cliente se ignoran: el actor auditado es el resuelto en el servidor |
| T4 | MED-09 | Con la base caída la respuesta es solo `{"code": "DOCTOR_PERSISTENCE_ERROR"}`: sin SQL, rutas, `vendor` ni el dato enviado |
| T5 | MED-10 | Éxitos y errores llevan `Cache-Control: no-store, private` |
| T6 | MED-11 | La disponibilidad solo contiene `doctor_id`, `status` y `slots`: sin pacientes, licencias ni usuarios, aunque exista una cita |
| T7 | MED-01, 03 | Sin identidad 401; con otro hospital 403; en ambos casos no se escribe |

Los permisos por operación (MED-17) y el actor sin referencia local (MED-18) los cubre `DoctorEndpointTest` y la matriz de roles de `DoctorPermissionsTest`.

## 2. Riesgos abiertos

| ID | Riesgo | Qué haría falta |
|---|---|---|
| MED-13 (parcial) | `EnsureHospitalDatabaseTenant` rechaza otro hospital (falla cerrada), pero la conexión `hospital` no cambia por tenant: el aislamiento depende de desplegar una base por hospital | Decisión de plataforma (enrutar la conexión por tenant o una base por despliegue); no es de este módulo |
| MED-14 | Las rutas no tienen límite de tasa | `throttle` en las tres rutas |
| MED-15 | Licencia, referencia de usuario y código de especialidad no tienen longitud máxima | Validación en `RegisterDoctor` y `Request` |

Ninguno se declara mitigado.

## 3. Lo que no se verificó

- La inyección SQL (T1) se probó con SQLite; en PostgreSQL es el mismo código de adaptador, pero `DoctorsPostgresTest` no incluye casos de inyección.
- La ausencia de `v-html` en la pantalla (MED-12) se comprobó leyendo el código, sin prueba automatizada.
- La suite `Database` completa tiene una falla en una prueba de CENTRAL ajena al módulo (`AuditChainIntegrityTest`); no se investigó.

[Declaración de uso de IA](./DECLARACION_IA.md)
