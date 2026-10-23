# Notas SOAP y diagnósticos — Semana 18: evaluación final y defensa

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-11 — Notas SOAP y diagnósticos · **Entrega:** demo funcional ejecutada, evidencia consolidada, trazabilidad de las 18 semanas y pendientes declarados.

- **Issue:** Refs #22
- **PR de código de la semana:** #1083 (firma), #1087 (corrección), #1089 (catálogo CIE-10 provisional) y #1090 (pantalla Vue). Este PR solo agrega documentación.

[Trazabilidad de las 18 semanas](./trazabilidad-semanas.md) · [Guion de defensa, cambio solicitado y preguntas esperadas](./guion-defensa.md) · [Evidencia de la demo](./evidencia-demo.txt) · [Declaración de IA](./DECLARACION_IA.md)

## Qué entrega el módulo

El médico tratante registra una nota SOAP en borrador, le asocia diagnósticos CIE-10, la firma (queda inmutable) y, si hay un error, la corrige como una versión nueva en borrador que vuelve a firmar. Cada escritura se audita en la misma transacción: si la bitácora falla, no queda nada a medias. El módulo **no** decide la conducta clínica ni sustituye el criterio médico.

| Pieza | Ubicación | Semana |
|---|---|---|
| Micro-HIS por capas (Domain, Application, Persistence, Presentation, Client) | `micro-his/notas-soap/` | 3 a 7 |
| Contrato API, frontera de microservicio y OpenAPI | `docs/notas-soap/semana-05/` | 5 y 18 |
| Rutas `/api/v1` de Laravel detrás de tenant, `jwt.auth` y RBAC | `app/Providers/NotasSoapServiceProvider.php`, `app/Http/Controllers/Api/V1/SoapNoteController.php` | 16 y 18 |
| Adaptadores PDO del esquema HOSPITAL con auditoría | `micro-his/notas-soap/src/Persistence/Pdo*.php` | 16 |
| Identidad: `sub` del JWT → usuario local | `app/NotasSoap/` | 16 |
| Firma (UC-SOAP-05) y corrección (UC-SOAP-06) | `SignSoapNote`, `CorrectSoapNote`, `SignaturePolicy`, `CorrectionPolicy` | **18** |
| Catálogo CIE-10 provisional, apagado por defecto | `FallbackIcd10Catalog`, `database/icd10-provisional.php` | **18** |
| Pantalla Vue `/notas-soap` | `resources/js/modules/notas-soap/` | **18** |
| Matriz de 17 amenazas y bitácora de despliegue local | `docs/notas-soap/semana-17/` | 17 |
| Prototipo navegable y diseño móvil | `docs/notas-soap/semana-10/` y `semana-11/` | 10 y 11 |

## Qué funciona hoy

| Capacidad | Estado |
|---|---|
| Registrar una nota SOAP (médico tratante) | Funciona de extremo a extremo con PostgreSQL |
| Leer una nota | Funciona; sin asignación activa responde 404 |
| Asociar un diagnóstico | Funciona **con el catálogo provisional encendido** (`NOTAS_SOAP_ICD10_PROVISIONAL=true`); sin él, 503 porque `icd10_codes` no existe |
| Firmar | Funciona: exige las cuatro secciones y un solo diagnóstico principal; 409 si ya está firmada |
| Corregir | Funciona: versión `n + 1` en borrador; 409 si ya fue corregida |
| Más de una nota por expediente | **No:** R-06 (409 `RECORD_ALREADY_HAS_NOTE`) |
| Pantalla | Compila y sus reglas están probadas; **no se ejercitó en un navegador** |

## Demo funcional

Reproducible con PHP 8.3, Node 22 y PostgreSQL 16 en un contenedor desechable con las migraciones de `develop`:

```bash
# 1. Reglas, casos de uso y arquitectura sin base de datos
cd micro-his/notas-soap/tests
php unit.php && php whitebox.php && php blackbox.php && php run.php && php api.php
cd ../../..

# 2. Rutas HTTP con la matriz RBAC real
php vendor/bin/phpunit tests/Feature/NotasSoap

# 3. PostgreSQL real (HOSPITAL_DB_* apuntando al contenedor)
php -d extension=pgsql -d extension=pdo_pgsql vendor/bin/phpunit \
  tests/Database/Hospital/NotasSoapPostgresTest.php tests/Database/Hospital/NotasSoapSecurityPostgresTest.php \
  tests/Database/Hospital/NotasSoapSignaturePostgresTest.php tests/Database/Hospital/NotasSoapProvisionalCatalogPostgresTest.php

# 4. Interfaz
node resources/js/modules/notas-soap/notas-soap.test.mjs && npx vite build
```

Resultado registrado el 2026-10-22 sobre el tope del código (salida completa en [evidencia-demo.txt](./evidencia-demo.txt)):

| Comando | Resultado |
|---|---|
| `unit`, `whitebox`, `blackbox` | 22, 12 y 20 OK; 0 fallos y 0 defectos abiertos |
| `run` y `api` | pasan (54 comprobaciones en `run`, con adaptadores PDO y en memoria) |
| Rutas HTTP (`tests/Feature/NotasSoap`) | `OK (13 tests, 94 assertions)` |
| PostgreSQL 16.15 | `OK (28 tests, 159 assertions)` |
| Interfaz | reglas, permisos y navegación OK; `vite build` genera `NotasSoapPage` |
| `Unit,Feature` completo | 215 pruebas, 1 falla ajena al módulo: `SalasCamas\CatalogLaravelIntegrationTest` exige una base `his_hospital_test` en el puerto 55433 que no existe en este equipo |

La prueba `NotasSoapProvisionalCatalogPostgresTest` recorre por el API real (token, RBAC, PDO, PostgreSQL) registrar, diagnosticar, firmar, corregir, completar y volver a firmar, y comprueba las seis auditorías esperadas en orden.

## Lo que no está resuelto

Se declara en vez de ocultarse:

| # | Pendiente | Impacto | Quién |
|---|---|---|---|
| 1 | **Los checks de GitHub Actions no arrancan** por la facturación de la cuenta | Ningún PR tiene CI verde ni roja; las pruebas se corrieron en local | Administración de la organización |
| 2 | **Ningún PR de la cadena se ha revisado:** 21 abiertos (20 en borrador y #404 listo), sin contar este, y 14 fusionados | El código no está en `develop` | Revisión de Yamilet; los PR siguen en borrador hasta que el autor los marque |
| 3 | **R-06:** una sola nota por expediente (`clinical_notes_record_version_unique` + trigger `version_no = 1`) | La segunda consulta del mismo paciente se rechaza con 409. **Aprobado por Javier el 22-oct:** `note_series_id` y `UNIQUE (note_series_id, version_no)`; falta su migración y el visto bueno de Yamilet y del grupo. Cuando exista, la corrección debe heredar la serie del padre y la lectura exponer `note_series_id` | Javier (ASII-09) |
| 4 | **Tabla `icd10_codes` inexistente** | Sin el flag, asociar un diagnóstico da 503. El catálogo provisional son 20 códigos sin revisión clínica: **no apto para uso asistencial**. Javier la creará (`code`, `description`, `active boolean`, sin FK desde `diagnoses`); el seed mínimo está en #1110 y `PdoIcd10Catalog` ya consulta `active IS TRUE`. El catálogo oficial completo aún no tiene dueño asignado por el grupo | Javier (ASII-09) y el grupo |
| 5 | **D-02 abierto en la base:** el trigger de `diagnoses` no mira si la nota está firmada | Un `INSERT` directo se salta la mitigación del módulo (#1074). **Aprobado por Javier:** trigger con `FOR SHARE` que rechaza con `diagnoses_note_signed_trigger` (23514), también al mover un diagnóstico fuera de una nota firmada; pendiente de migración | Javier (ASII-09) |
| 6 | **Quién crea el expediente abierto de cada paciente** | Sin él, registrar una nota da 409 `MEDICAL_RECORD_NOT_FOUND`, que es la respuesta correcta mientras tanto. Javier indica que le corresponde a ASII-10 (Maryori) y lo coordina | ASII-10 |
| 7 | **No se escribe `outbox_events`** al firmar y corregir | La Semana 1 lo prevé; no hay contrato del evento hacia CENTRAL. La auditoría local sí queda | Gobernanza y ASII-09 |
| 8 | **Pantalla no ejercitada en navegador**, sin listado de notas por episodio y sin edición de borrador | Se abre una nota por su identificador; la corrección envía las secciones completas | Autor |
| 9 | **Sin despliegue en staging ni producción** | Solo ejecución local; ver la [bitácora de la Semana 17](../semana-17/bitacora-despliegue.md) | Equipo |
| 10 | **Firmar o corregir una nota inexistente da 404 y una ajena da 403** | Permite saber si un identificador existe; mitigado porque son UUID v4. La lectura sí responde igual en ambos casos | Autor |
| 11 | **Declaraciones de IA con validación humana pendiente** | Hasta que el estudiante las apruebe | Estudiante |

Las Semanas 16 y 17 decían que firma y corrección no estaban implementadas; esas filas quedan superadas por #1083 y #1087 y no se reescriben en sus documentos.
