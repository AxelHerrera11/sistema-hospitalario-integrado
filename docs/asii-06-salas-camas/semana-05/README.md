# ASII-06 — Semana 5: integración Laravel

## Objetivo y alcance

Conectar el micro-HIS de wards y beds al contenedor de Laravel usando los
contratos y adaptadores existentes. El alcance es exclusivamente el catálogo
`wards` / `beds`, la asociación cama→ward y sus atributos/estados. No se
agregan migraciones, `rooms`, endpoints, UI ni lógica clínica.

## Integración agregada

`SalasCamasServiceProvider` carga el bootstrap del micro-HIS y registra en el
contenedor los contratos `WardRepository` y `BedRepository` con sus adaptadores
PDO. También hace resoluble `CatalogService`. El provider quedó registrado en
`bootstrap/providers.php`. Los bindings `scoped` comparten el ciclo de vida de
la petición Laravel y usan `DB::connection('hospital')->getPdo()`.

Se reutilizan `Ward`, `Bed`, `CatalogService`, `WardRepository`,
`BedRepository`, `PdoWardRepository` y `PdoBedRepository`; no se duplicó
dominio ni SQL.

## Tenant y permisos

`wards` y `beds` no tienen columna `tenant_id`. El provider usa la conexión
hospitalaria fija configurada por Laravel; `TenantMiddleware` valida
`X-Tenant-ID`, pero no selecciona aquí una base distinta por hospital. Por
tanto, esta integración no demuestra aislamiento físico/lógico entre tenants y
no debe exponerse en una ruta autenticada hasta que la selección de la conexión
hospitalaria por tenant esté resuelta por la arquitectura compartida.

`config/rbac.php` ya contiene permisos `camas.ver` y `camas.asignar`, pero esta
semana no crea rutas que los consuman ni agrega permisos de administración de
catálogo.

## Estado

El servicio ya puede resolverse desde Laravel y su flujo de alta se probó
contra PostgreSQL HOSPITAL de pruebas. API y UI siguen pendientes. La base de
prueba se protege en el test por nombre/puerto y las escrituras se revierten.
Los resultados y comandos están en
[evidencia de integración](./evidencia-integracion.md).
