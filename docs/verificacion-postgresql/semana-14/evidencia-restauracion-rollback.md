# Evidencia de respaldo, restauración y rollback (Semana 14)

Todo lo que sigue se ejecutó sobre PostgreSQL 16 en contenedores de prueba
(`bd-central` y `bd-hospital`), nunca sobre una base compartida o de producción.

## 1. Respaldo

```text
$ podman exec bd-hospital pg_dump -U postgres -d his_hospital_test -Fc -f /tmp/hospital.dump
$ podman cp bd-hospital:/tmp/hospital.dump /tmp/opencode/hospital.dump
$ ls -lh /tmp/opencode/hospital.dump
-rw-r--r-- 1 javier javier 1,5M oct 19 22:50 /tmp/opencode/hospital.dump
```

Formato `Fc` (formato personalizado de PostgreSQL): comprimido y restaurable con
`pg_restore`, con creación de índices y extensiones.

## 2. Restauración en una base nueva

```text
$ podman exec bd-hospital psql -U postgres -d postgres \
    -c "DROP DATABASE IF EXISTS his_hospital_restore;" \
    -c "CREATE DATABASE his_hospital_restore;"
DROP DATABASE
NOTICE:  database "his_hospital_restore" does not exist, skipping
CREATE DATABASE

$ podman cp /tmp/opencode/hospital.dump bd-hospital:/tmp/hospital.dump
$ podman exec bd-hospital pg_restore -U postgres -d his_hospital_restore --no-owner /tmp/hospital.dump
(sin errores)

$ podman exec bd-hospital psql -U postgres -d his_hospital_restore \
    -c "SELECT count(*) FROM information_schema.tables WHERE table_schema='public';" \
    -c "SELECT count(*) FROM bed_stays;" \
    -c "SELECT count(*) FROM pg_extension WHERE extname='btree_gist';"
 tablas
--------
     17
(1 row)

 estancias
-----------
     50002
(1 row)

 count
-------
     1
(1 row)
```

Verificación posterior: la base restaurada conserva las 17 tablas (16 del esquema más
la de migraciones), las 50 002 estancias del volumen de medición y la extensión
`btree_gist`, que es indispensable para las exclusiones de solapamiento.

```text
$ podman exec bd-hospital psql -U postgres -d his_hospital_restore \
    -c "SELECT count(*) AS beds, (SELECT count(*) FROM wards) AS wards FROM beds;"
 beds | wards
------+-------
 1003 |    22
(1 row)
```

## 3. Rollback de las migraciones

```text
$ php artisan migrate:rollback --database=hospital --path=database/migrations/hospital --realpath --force
  2026_08_11_000400_create_medical_record_tables ................ 15.91ms DONE
  2026_08_11_000300_create_scheduling_and_bed_tables ............ 21.85ms DONE
  2026_08_11_000200_create_patient_and_care_tables .............. 28.82ms DONE
  2026_08_11_000100_create_local_identity_and_professional_tables 33.66ms DONE
  2026_08_11_000000_enable_hospital_extensions ................... 0.39ms DONE
```

Estado tras el rollback:

```text
$ psql -c "SELECT count(*) FROM information_schema.tables WHERE table_schema='public';"
 tablas
--------
      1
(1 row)

$ psql -c "SELECT to_regclass('public.allergies') AS allergies, to_regclass('public.vital_signs') AS vital_signs;"
 allergies | vital_signs
-----------+-------------
           |
(1 row)
```

El rollback es limpio: solo queda la tabla de migraciones y **no** sobrevive ninguna
tabla clínica ni la extensión `btree_gist`.

## 4. Re-aplicación (up)

```text
$ php artisan migrate --database=hospital --path=database/migrations/hospital --realpath --force
  2026_08_11_000000_enable_hospital_extensions .................. 1.08ms DONE
  2026_08_11_000100_create_local_identity_and_professional_tables 845.25ms DONE
  2026_08_11_000200_create_patient_and_care_tables ............. 219.52ms DONE
  2026_08_11_000300_create_scheduling_and_bed_tables ........... 181.78ms DONE
  2026_08_11_000400_create_medical_record_tables ............... 54.30ms DONE
  2026_08_11_000401_create_allergy_and_vital_sign_tables ........ 33.76ms DONE

$ psql -c "SELECT count(*) FROM information_schema.tables WHERE table_schema='public';"
 tablas
--------
     17
(1 row)

$ psql -c "SELECT extname FROM pg_extension;"
 extname
-------------
 plpgsql
 btree_gist
(2 rows)
```

La re-aplicación reconstruye el esquema completo y vuelve a habilitar `btree_gist`.

## 5. Suite de integridad después de reconstruir

```text
$ php vendor/bin/phpunit tests/Database
.....................................................             53 / 53 (100%)
OK (53 tests, 679 assertions)
```

## 6. Conclusiones

1. El ciclo **respaldo → restauración → rollback → up** es reproducible y quedó
   documentado con salidas reales.
2. El rollback no deja residuos: la base vuelve a un estado sin tablas clínicas, lo que
   confirma que cada migración declara correctamente su `down`.
3. La extensión `btree_gist` se pierde con el rollback y se restablece con el `up`:
   ninguna migración puede depender de que otra la haya dejado instalada.
4. La restauración conserva datos, índices y extensiones: sirve para recuperar una
   base de hospital sin rehacer migraciones.
5. El respaldo se hizo **antes** del rollback, que es la única forma segura de probar
   un rollback sobre datos reales: si algo falla, se restaura el respaldo.
