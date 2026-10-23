# Notas SOAP y diagnósticos — Semana 16: integración y primera entrega funcional

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-11 — Notas SOAP y diagnósticos · **Entrega:** API v1 integrada en el HIS (Laravel) con identidad, tenant y RBAC reales, defectos D-01 y D-03 corregidos, y los bloqueos declarados.

## Ficha de la asignación

- **Semana:** Semana 16 — Integración y primera entrega funcional
- **Issue:** Refs #22
- **PR de código:** #1049 (corrección de defectos) y #1059 (integración en Laravel). Este PR solo agrega documentación.

**La Semana 16 no se declara completa:** la persistencia real contra PostgreSQL y el despliegue dependen de terceros (ver [bloqueos](#bloqueos-y-limitaciones-no-se-declaran-completados)).

## Qué se integró

| Requisito | Estado | Evidencia |
|---|---|---|
| Rutas del API v1 en el HIS | **Hecho** | `POST /api/v1/care-episodes/{id}/soap-notes`, `POST /api/v1/soap-notes/{id}/diagnoses` y `GET /api/v1/soap-notes/{id}` bajo `tenant`, `jwt.auth` y `rbac:notas_soap.*` (`NotasSoapServiceProvider`) |
| Identidad y hospital reales | **Hecho y probado** | 401 sin token, 400 sin tenant, 403 `TENANT_MISMATCH`; el actor sale del token y no de una cabecera |
| Permisos con la matriz real | **Hecho y probado** | `medico` registra, asocia y lee; `enfermeria` solo lee; `recepcion` y `enfermeria` escribiendo reciben 403 `RBAC_DENIED` con la denegación registrada |
| Regla clínica de médico tratante | **Hecho y probado** | Sin asignación activa: 403 `NOT_TREATING_PHYSICIAN` al escribir y 404 `NOTE_NOT_FOUND` al leer (D-03) |
| Rechazos y fallos | **Probado** | 422 `SOAP_SECTION_INVALID` y `PAYLOAD_INVALID` (incluye JSON malformado), 503 `ICD10_UNAVAILABLE` con `trace_id` |
| Errores de la Semana 15 | **D-01 y D-03 corregidos; D-02 abierto** | #1049; `unit.php` 14 OK y `blackbox.php` 17 OK sin defectos abiertos |

## Comandos y resultados

PHP 8.3.33 y PHPUnit 11.5.55, ejecutado el 2026-10-22 sobre #1059.

```bash
php vendor/bin/phpunit tests/Feature/NotasSoap                          # 7 pruebas, 38 aserciones
php vendor/bin/phpunit tests/Feature/MedicationCatalog tests/Feature/Rbac   # 57 pruebas: sin regresión
cd micro-his/notas-soap && php tests/run.php && php tests/api.php        # regresión del micro-HIS
cd micro-his/notas-soap && php tests/unit.php && php tests/whitebox.php && php tests/blackbox.php
```

`whitebox.php` termina con 8 OK y 1 defecto abierto (D-02); las demás suites no tienen defectos abiertos.

## Qué cambió en el contrato

- Lectura de una nota sin asignación activa: ahora `404 NOTE_NOT_FOUND`, igual que una nota inexistente, para no revelar qué identificadores existen. Está en [contrato-api.md](../semana-05/contrato-api.md).
- Una sección con solo espacio duro, de ancho cero o ideográfico se rechaza con `422 SOAP_SECTION_INVALID`.

## Bloqueos y limitaciones (no se declaran completados)

| # | Bloqueo | Por qué | Quién |
|---|---|---|---|
| 1 | **Persistencia real no probada** | Los adaptadores PDO no coinciden con el esquema de `develop`: `clinical_notes.medical_record_id` es obligatorio y el adaptador no lo envía; no existe `icd10_codes`; `local_access_audits` no está en las migraciones. Las pruebas usan adaptadores InMemory | Javier (ASII-09) |
| 2 | **D-02 abierto** | Una nota firmada entre la lectura y la escritura recibe el diagnóstico. Exige repetir la comprobación en la transacción y un trigger en `diagnoses` (el trigger actual de `clinical_notes` solo protege la nota firmada) | Javier (ASII-09) |
| 3 | **Mapeo de identidad** | El id del token se usa como `user_ref_id`; falta confirmar su relación con `local_user_refs` | Javier y María (ASII-02) |
| 4 | **Firma, corrección y versionado** | Los permisos `notas_soap.firmar` y `notas_soap.corregir` existen en RBAC, pero no hay rutas ni código | Pendiente de semanas posteriores |
| 5 | **Auditoría local (Anexo A, R-02)** | `local_access_audits` y `correlation_id` sin cerrar | Javier |
| 6 | **Staging u on-premise** | No hay acceso a ningún entorno; solo ejecución local | Equipo |
| 7 | **R-06** | `clinical_notes_record_version_unique` choca con el riesgo R1 de la Semana 1 | Javier |

## Estado de la cadena de PR

#128 → #129 → #376 → #378 → #404 → #405 → #551 → **#1049** → **#1059** → este PR. Todos en borrador, revisora Yamilet, apilados con merge. #1059 apunta a `develop` porque contiene un merge de `develop` y depende de #1049.

## Archivos

- [Declaración de uso de IA](./DECLARACION_IA.md)
