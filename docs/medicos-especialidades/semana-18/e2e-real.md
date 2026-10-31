# Prueba de punta a punta contra el backend real

Hasta aquí las pruebas HTTP usaban dobles de identidad y las capturas de la Semana 16 usaban un API simulado. Este informe cubre lo que faltaba: **el módulo completo, con Laravel, JWT, tenant, RBAC y PostgreSQL reales**, y la pantalla de médicos con una sesión real iniciada por la interfaz.

## Qué se montó

| Pieza | Detalle |
|---|---|
| Base CENTRAL y HOSPITAL | Dos contenedores `postgres:16` efímeros (`his_central_e2e`, `his_hospital_e2e`), con las migraciones reales del repositorio y `btree_gist` |
| Identidad | El sembrador del repositorio (`tests/E2E/fixtures/seed-e2e.php`): 2 hospitales y 2 usuarios ficticios (`admin_hospital` y `medico`), contraseña aleatoria por ejecución |
| Datos de HOSPITAL | `local_user_refs` de los dos usuarios y las especialidades `CARD` y `NEURO` (el módulo no tiene aún rutas para cargarlas) |
| Servidor | Laravel real con `JWT_SECRET` efímero y la caché en base de datos (la del proyecto), sobre el servidor integrado de PHP |
| Frontend | `npm run build` y Chrome sin interfaz |

Los nombres de base deben terminar en `_e2e`: el sembrador y el script de medición se niegan a correr en otra base. No se tocó ninguna base compartida ni real.

## Resultado: 16 de 16 verificaciones

[`evidencia/e2e-real.mjs`](./evidencia/e2e-real.mjs) hace todo por `/api/v1` con tokens obtenidos de `/auth/login`:

| # | Verificación | Resultado |
|---|---|---|
| 1 | Login real de `admin_hospital` y de `medico` | Cumple |
| 2 | Alta como administrador: 201, id uuid, `trace_id` en cuerpo y cabecera | Cumple |
| 3 | `Cache-Control: no-store, private` | Cumple |
| 4 | La licencia ` col-e2e-1 ` se guarda como `COL-E2E-1` (D-01) | Cumple |
| 5 | Auditoría H8 con el actor local (`local_user_refs`), no con el id del JWT | Cumple |
| 6 | Licencia repetida con otra grafía: 409 | Cumple |
| 7 | Asociar `NEURO` como secundaria: 201 | Cumple |
| 8 | Segunda principal solapada: 409 | Cumple |
| 9 | Disponibilidad como `medico`: la cita 08:15-08:45 bloquea 08:00 y 08:30; quedan 09:00 y 09:30 | Cumple |
| 10 | La disponibilidad no expone paciente, licencia ni usuario | Cumple |
| 11 | `medico` no puede registrar: 403 `RBAC_DENIED` | Cumple |
| 12 | Sin token: 401 | Cumple |
| 13 | Token del hospital 1 con `X-Tenant-ID` del hospital 2: 403 `TENANT_MISMATCH`, sin escritura | Cumple |
| 14 | Fecha imposible: 422 con `trace_id` | Cumple |
| 15 | Licencia de 65 caracteres: 422 | Cumple |
| 16 | Límite de tasa: 8 respuestas 429 en 125 consultas más | Cumple |

El horario (`doctor_schedules`) y la cita se insertan por SQL directo: el módulo todavía no tiene rutas para cargar horarios y las citas son de otro módulo.

## Pantalla con sesión real

[`evidencia/capturar-real.mjs`](./evidencia/capturar-real.mjs) abre `/login` en Chrome, inicia sesión por el formulario y recorre `/medicos`. El navegador vio estas respuestas del API: 200 de `/auth/login`, 200 de `/auth/me`, 200 de `/availability`, 409 de `/doctors` y ningún error de JavaScript.

| Captura | Qué muestra |
|---|---|
| [r01](./capturas-reales/r01-disponibilidad-real-escritorio.png) | Disponibilidad real: franjas 09:00 y 09:30 (la cita de 08:15 bloquea las dos primeras) |
| [r02](./capturas-reales/r02-fecha-invalida-real-escritorio.png) | Fecha imposible rechazada por la pantalla |
| [r03](./capturas-reales/r03-otra-consulta-real-escritorio.png) | Consulta de otra especialidad y fecha: sin franjas libres |
| [r04](./capturas-reales/r04-licencia-duplicada-real-escritorio.png) | 409 real del servidor: «Esa licencia ya está registrada» |
| [r05](./capturas-reales/r05-disponibilidad-real-movil.png) | Disponibilidad a 390 px |
| [r06](./capturas-reales/r06-medico-solo-consulta-real-escritorio.png) | Sesión de `medico`: solo la consulta de disponibilidad; el menú también cambia con el rol |

## Hallazgos de esta prueba

1. **El límite de tasa solo funciona con una caché compartida y persistente.** Con `CACHE_STORE=array` no se contó ninguna petición (0 respuestas 429); con la caché en base de datos, que es la del proyecto, sí (8 de 125). Quien despliegue debe confirmar que `CACHE_STORE` no es `array` ni un almacén por proceso. Se refleja en la matriz de amenazas (MED-14).
2. **Una corrección de estilos no había llegado a las ramas posteriores.** Las primeras capturas reales salieron con campos sin borde porque la rama de trabajo era posterior a la corrección de #1045. Al integrar en orden esto se resuelve solo; las capturas de este informe son del estado final, con la corrección mezclada en una rama temporal que no se publicó.
3. **El módulo no tiene forma de cargar horarios ni especialidades por la API.** Hubo que insertarlos por SQL. Es la brecha funcional más grande y queda fuera de este PR.

## Cómo repetirlo

1. Dos contenedores `postgres:16` (puertos 55431 y 55433), con `his_central_e2e` y `his_hospital_e2e`, y `CREATE EXTENSION btree_gist` en la de HOSPITAL.
2. Con las variables de conexión, `APP_ENV=e2e`, `JWT_SECRET`, `E2E_ALLOW_SEED=1` y una contraseña de 16 caracteres o más: migrar CENTRAL, el esquema `framework` y HOSPITAL (`composer migrate:central`, `migrate:framework`, `migrate:hospital`) y ejecutar `tests/E2E/fixtures/seed-e2e.php`.
3. Insertar en HOSPITAL los `local_user_refs` de los dos usuarios sembrados y las especialidades `CARD` y `NEURO`.
4. Levantar Laravel con `pdo_pgsql` habilitada, **desde la carpeta `public`** y con `CACHE_STORE=database` (`artisan serve` lanza un PHP hijo sin las extensiones pasadas por línea de comandos).
5. `npm run build`, y ejecutar los dos scripts de `evidencia/` con las variables que documentan en su cabecera.

## Lo que esta prueba no cubre

- Una sola base HOSPITAL: no demuestra el aislamiento entre hospitales (MED-13), que sigue abierto.
- No es un despliegue: sigue sin staging ni semillas aprobadas.
- Los datos y los usuarios son ficticios; el rol `medico` del sembrador no es un perfil de médico (`doctor_profiles`).
- No se midió contraste ni teclado sobre esta pantalla.
