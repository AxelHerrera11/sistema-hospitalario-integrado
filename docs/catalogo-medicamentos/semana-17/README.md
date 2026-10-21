# ASII-14 - Catálogo de medicamentos - Semana 17

- **Tema:** seguridad y despliegue final
- **Issue:** [#165](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/165)

| Requisito | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | **Hecho** | [amenazas.md](./amenazas.md): 16 amenazas, 3 abiertas |
| Pruebas de permisos y datos sensibles | **Hecho** | `tests/Feature/MedicationCatalog/`: 12 pruebas (OK sobre `develop`) con autorización real e inyección contra PDO real |
| Bitácora de despliegue | **Parcial** | [bitacora-despliegue.md](./bitacora-despliegue.md): despliegue **local** de verificación |
| Despliegue final | **Bloqueado** | Sin staging u on-premise y sin tabla H5. La autenticación de Tenants ya está integrada (#657) |

**La Semana 17 no se declara completa:** el despliegue final depende del entorno, del esquema H5 y de la aprobación RBAC de los permisos de escritura.

[Declaración de uso de IA](./DECLARACION_IA.md)
