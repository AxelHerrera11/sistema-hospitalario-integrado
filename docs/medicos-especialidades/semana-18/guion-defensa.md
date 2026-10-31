# Guion de defensa y demo funcional

Material para la evaluación final. El guion se apoya en evidencia ejecutada; nada de lo que se demuestra depende de una afirmación sin respaldo. Los comandos se ejecutaron el 22 de octubre de 2026 en el equipo del autor (Windows 11, PHP 8.3.33, Node 22, Docker Desktop).

## Apertura (2 minutos)

«Entrego el módulo de **médicos, especialidades y disponibilidad**. La decisión de diseño que sostiene todo lo demás se defendió en la Semana 6 y sigue siendo una sola: **la disponibilidad que se ofrece es verdadera**. Un médico aparece solo si su perfil y su especialidad están vigentes en la fecha, y una franja se ofrece solo si está dentro de su horario y ninguna cita activa la solapa. Lo que el dominio sabe que es inválido se rechaza antes de llegar a la base, y lo que la base sabe, lo vuelve a garantizar con sus restricciones.»

## Demo funcional (7 minutos)

### Paso 1 - El dominio sin base de datos

```bash
php micro-his/medicos-especialidades/tests/core.php
php micro-his/medicos-especialidades/tests/run.php
```

Señalar: la misma suite pasa con el adaptador PDO y con el InMemory (Repository); la política de especialidades rechaza una segunda principal solapada; y la prueba de arquitectura comprueba que el controlador no tiene SQL ni PDO. Mostrar la prueba de **franjas por intervalo**: una cita de 08:15 a 08:45 bloquea 08:00 y 08:30 (antes del defecto D-02, el 08:00 se ofrecía libre).

### Paso 2 - El contrato HTTP en Laravel

```bash
php vendor/bin/phpunit tests/Feature/Medicos tests/Feature/Rbac/DoctorPermissionsTest.php
```

Señalar: tres rutas, cada una con su permiso (`medicos.registrar`, `medicos.asociar_especialidad`, `medicos.ver_disponibilidad`); 401 sin identidad, 403 con otro hospital o sin permiso; y que el actor auditado sale del servidor, no de una cabecera del cliente.

### Paso 3 - El esquema real en PostgreSQL 16

```bash
docker run -d --rm --name his-central -e POSTGRES_DB=his_central_test -e POSTGRES_USER=postgres -e POSTGRES_PASSWORD=postgres -p 55431:5432 postgres:16
docker run -d --rm --name his-hospital -e POSTGRES_DB=his_hospital_test -e POSTGRES_USER=postgres -e POSTGRES_PASSWORD=postgres -p 55433:5432 postgres:16
docker exec his-hospital psql -U postgres -d his_hospital_test -c 'CREATE EXTENSION IF NOT EXISTS btree_gist'
php vendor/bin/phpunit tests/Database/Hospital/DoctorsPostgresTest.php tests/Database/Hospital/DoctorScheduleIntegrityTest.php
```

(Si `pdo_pgsql` no está habilitada en el `php.ini`, anteponer `-d extension=pdo_pgsql -d extension=pgsql` a `php`.)

Señalar: el alta es atómica (si falla la auditoría no queda perfil ni vínculo); el id es un uuid; el horario se parte con el `slot_minutes` de `doctor_schedules`; y la inyección SQL se guarda como texto sin afectar la tabla. Resultado verificado: 25 tests, 168 assertions.

### Paso 4 - La pantalla y sus utilidades

```bash
node --test "resources/js/modules/medicos/*.test.mjs"
npm run build
```

Señalar: la pantalla `/medicos` no decide nada de negocio; valida lo mismo que el servidor para avisar antes de enviar, muestra mensajes seguros por código de error y oculta los formularios para los que la sesión no tiene permiso (el servidor los vuelve a exigir). Distingue «sin horario configurado» de «sin franjas libres». Para mostrar los estados sin levantar el backend, usar las 9 capturas del PR #1040 (`docs/medicos-especialidades/semana-16/capturas/`): disponibilidad, validación, alta, licencia duplicada, servicio caído, rol de recepción, sin permiso y la vista a 390 px.

### Paso 5 - Prototipo y accesibilidad (opcional)

```bash
node docs/medicos-especialidades/semana-12/evidencia/verificar-prototipo.mjs
node docs/medicos-especialidades/semana-12/evidencia/medir-contraste.mjs
```

Resultado verificado: 27 de 27 comprobaciones y 16 de 16 pares de color cumplen. Prototipo: `docs/medicos-especialidades/semana-11/prototipo/index.html`.

## Preguntas esperables

| Pregunta | Respuesta con evidencia |
|---|---|
| ¿Por qué la disponibilidad no muestra al paciente? | RNF-MED-03: el lector de citas entrega solo intervalos. La prueba T6 de la Semana 17 verifica que la respuesta contiene únicamente `doctor_id`, `status` y `slots`. |
| ¿Qué cambió entre la Semana 15 y la 16? | Los 5 defectos de la Semana 15 se resolvieron así: D-01, D-02 y D-04 se corrigieron en el dominio; D-03 y D-05 los cubren `tenant` y `rbac:` en las rutas de Laravel. |
| ¿Qué pasa si la base cae? | 503 `DOCTOR_STORAGE_UNAVAILABLE` con código fijo, sin SQL ni rutas; el alta no deja datos a medias. |
| ¿Cómo se evita el abuso? | Límite de tasa por usuario (30 escrituras y 120 consultas por minuto, con contadores separados) y longitudes máximas de los textos (422 antes de tocar la base). |
| ¿Por qué hay una hora local del hospital? | Las citas son `timestamptz`; la disponibilidad se informa en `hospitals.timezone` del tenant, y si esa zona no es válida se usa la de la aplicación. |
| ¿Por qué hay tantos PR? | El límite de 400 líneas por PR obliga a dividir la Semana 16 en una cadena apilada; cada uno apunta a la rama del anterior. |

## Lo que no se debe afirmar

- Que el módulo está desplegado: **no hay despliegue** fuera del equipo local.
- Que la inyección SQL o los límites se probaron en CI: se probaron localmente con PostgreSQL 16 efímero.
- Que las 18 semanas están integradas sin revisión: las semanas 1 a 17 están en `develop` y la 18 es este PR.
- Que la pantalla se probó contra el backend real: las 9 capturas (PR #1040, `semana-16/capturas`) son de Chrome sin interfaz con un **API simulado**. Sirven para mostrar los estados y la adaptación a 390 px, no como prueba de integración. El contraste y el teclado se midieron sobre el prototipo de la Semana 12, no sobre esta pantalla.
- Que se resolvió la amenaza MED-13 (aislamiento entre hospitales): es parcial; el guard rechaza otro hospital, pero varios hospitales dependen de la plataforma.

## Antes de entrar a la defensa

- [ ] Docker Desktop encendido y las dos bases de PostgreSQL arriba.
- [ ] Rama de trabajo con los PR de las semanas 16 y 17 aplicados (o los comandos ejecutados en la rama más alta de la cadena).
- [ ] Este guion y la [trazabilidad de las 18 semanas](./trazabilidad-semanas.md) abiertos.
