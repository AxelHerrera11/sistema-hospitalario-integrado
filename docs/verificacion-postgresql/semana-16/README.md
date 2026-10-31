# Semana 16 — Verificación de integración PostgreSQL

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo Odoo:** Verificación de Base de Datos PostgreSQL
- **Semana:** 16 — pruebas de integración, validación, sistema y primera entrega funcional
- **Issue relacionado:** `Refs #36`

## Alcance

Esta entrega verifica que la integración de los módulos que ya llegaron a `develop` conserve
las reglas de la base HOSPITAL: migraciones reproducibles, pruebas PostgreSQL localizadas en
`tests/Database/Hospital`, aislamiento por tenant y ausencia de datos clínicos reales en la
evidencia. No crea tablas ni cambia la propiedad de otro módulo.

## Matriz de comprobación

| Control | Evidencia en `develop` | Resultado esperado |
|---|---|---|
| Migraciones de laboratorio | `database/migrations/hospital/` | Cargan en orden y sin SQL fuera del esquema HOSPITAL |
| Pruebas PostgreSQL | `tests/Database/Hospital/*PostgresTest.php` | Cada slice prueba persistencia real o declara su bloqueo |
| Aislamiento | `X-Tenant-ID`, middleware `tenant` y pruebas de seguridad | Un tenant no lee datos de otro |
| Identidad clínica | `ResolutorActorLocal`/resolver equivalente | No se aceptan actores sin referencia local |
| Errores | pruebas Feature y contrato API | SQLSTATE no se expone al cliente |
| Evidencia | `docs/**/semana-16/` | Comandos, resultado y limitaciones explícitas |

## Validación reproducible

```bash
php docs/verificacion-postgresql/semana-16/verificar_integracion.php
php artisan test tests/Database/Hospital
php artisan test tests/Feature
```

El primer comando es independiente de PostgreSQL y valida la presencia de los contratos y
pruebas mínimas. Los dos comandos de Laravel requieren las bases HOSPITAL/CENTRAL de prueba;
si no están disponibles, el resultado debe registrarse como bloqueo de entorno y no como
`OK` falso.

## Resultado de esta entrega

La verificación está preparada para ejecutarse contra `develop` actualizado. La prueba de
integración real con PostgreSQL queda condicionada a que el entorno tenga las dos conexiones
de prueba configuradas; no se presentan datos sembrados en memoria como persistencia real.

## Fuera de alcance

- Crear migraciones pertenecientes a órdenes, resultados, camas, medicamentos o alertas.
- Modificar controladores de otros módulos.
- Declarar staging o despliegue productivo sin evidencia ejecutada.
