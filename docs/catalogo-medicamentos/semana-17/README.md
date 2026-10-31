# ASII-14 - Catálogo de medicamentos - Semana 17

- **Tema:** seguridad y despliegue final
- **Issue:** [#165](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/165)

| Requisito | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | **Hecho** | [amenazas.md](./amenazas.md): 16 amenazas, 3 abiertas |
| Pruebas de permisos y datos sensibles | **Hecho** | `tests/Feature/MedicationCatalog/`: 12 pruebas (OK sobre `develop`) con autorización real e inyección contra PDO real |
| Bitácora de despliegue | **Parcial** | [bitacora-despliegue.md](./bitacora-despliegue.md): despliegue **local** de verificación |
| Flujo funcional con PostgreSQL | **Hecho (local)** | Tabla H5 `medications` (#766), adaptador (#774) e interfaz (#776). Recorrido real interfaz → API → PostgreSQL 11/11 y fallo 4/4: [evidencia-funcional.md](./evidencia-funcional.md) |
| Instalación reproducible | **Hecho (local)** | [instalacion-local.md](./instalacion-local.md) |
| Despliegue final | **Bloqueado** | No hay entorno staging u on-premise autorizado |
| Aislamiento entre hospitales | **Corregido el 30/10/2026** | M2b en [amenazas.md](./amenazas.md); #1561 |
| Búsqueda con tildes con locale C | **Corregida el 30/10/2026** | `MedicationCatalogPostgresTest` sobre un clúster con locale C; #1563 |

**La Semana 17 no se declara completa:** el despliegue final depende de un entorno staging u on-premise autorizado. El esquema H5 (#766) y la aprobación RBAC de las escrituras (#627) ya están integrados.

[Declaración de uso de IA](./DECLARACION_IA.md)
