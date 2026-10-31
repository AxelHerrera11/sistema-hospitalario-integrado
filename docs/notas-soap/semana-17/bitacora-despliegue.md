# Notas SOAP — Bitácora de despliegue

## Entorno disponible

Solo un **entorno local de verificación**: Windows 11, PHP 8.3.33 (con `pdo_pgsql` activada por línea de comandos), Composer, Docker 29.5.2 y PostgreSQL 16 en contenedores desechables propios (CENTRAL en el puerto 55442, HOSPITAL en el 55443; se borran al terminar). **No hay acceso a staging ni a on-premise**, y no se desplegó sobre infraestructura ajena. Los contenedores de otras sesiones (puertos 55431 y 55433) no se tocaron.

## Registro (2026-10-22)

| # | Paso | Comando | Resultado |
|---|---|---|---|
| 1 | Dependencias | `composer install --no-interaction` | Instaladas (`vendor` ignorado por Git) |
| 2 | Bases desechables | `docker run … postgres:16` (CENTRAL y HOSPITAL) | Aceptan conexiones |
| 3 | Migraciones | `php -d extension=pdo_pgsql artisan migrate --database=central\|hospital --path=… --realpath --force` | Todas `DONE` |
| 4 | Usuarios ficticios | `E2E_ALLOW_SEED=1 APP_ENV=e2e … php tests/E2E/fixtures/seed-e2e.php` | 2 hospitales y 2 usuarios; la guarda exige bases `*_e2e` locales |
| 5 | Datos clínicos de prueba | SQL propio: usuario local, perfil médico, paciente, episodio, asignación y expediente | Insertados en HOSPITAL |
| 6 | Servidor | `php -d extension=pdo_pgsql -S 127.0.0.1:8099 -t public` con `JWT_SECRET` y `APP_KEY` efímeros, `APP_DEBUG=false` | Sirve el API |
| 7 | Recorrido real | `curl` con login y rutas de notas ([evidencia](./evidencia-funcional.md)) | 7 de 7 como se esperaba, con el paso 6 mejorado después |
| 8 | Contrato de rutas | `route:list --json --path=api` y `micro-his/qa-calidad-versiones/bin/route-guard.php` | Las tres rutas de notas pasan con su permiso `notas_soap.*` |
| 9 | Suites de micro-HIS | `micro-his/qa-calidad-versiones/bin/micro-his-suites.php` | `notas-soap` OK |
| 10 | Pruebas contra PostgreSQL | `phpunit tests/Database/Hospital/NotasSoap*` | 17 OK, 92 aserciones |

## Errores encontrados y corregidos en el proceso

| Error | Corrección |
|---|---|
| `pdo_pgsql` no activada en `php.ini` | `php -d extension=pdo_pgsql …` (la DLL ya viene con PHP) |
| Puertos 55431 y 55433 ocupados por contenedores de otra sesión | Se usaron otros puertos y no se tocaron |
| `GET /soap-notes/no-es-un-uuid` respondía 500 por el error de sintaxis de PostgreSQL | `PostgresIds`: un id que no es UUID no existe (#1077) |
| Un código de diagnóstico con basura llegaba al catálogo | `Diagnosis` valida la forma CIE-10 (#1077) |
| La segunda nota del expediente respondía 500 | 409 `RECORD_ALREADY_HAS_NOTE` (#1080) |
| `422 PAYLOAD_INVALID` con un cuerpo válido enviado desde la consola de Windows | La consola no envía UTF-8; las pruebas con `curl` usan texto ASCII. No es un defecto del API |
| Los checks de GitHub no arrancan en los PR | «recent account payments have failed» en la cuenta de GitHub; afecta a toda la organización y no depende del código |

## Pendiente para un despliegue real

1. Entorno staging u on-premise con PostgreSQL CENTRAL y HOSPITAL (credenciales fuera del repositorio).
2. Carga del catálogo oficial en `icd10_codes` (la tabla existe desde #1620) y decisión sobre R-06 y D-02 (Javier, ASII-09).
3. Un expediente abierto por paciente: ¿quién lo crea? (Admisión o Pacientes).
4. Repetir los pasos 3 a 10 contra ese entorno y registrar las salidas.
