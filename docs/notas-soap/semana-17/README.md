# Notas SOAP y diagnósticos — Semana 17: seguridad y despliegue final

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-11 — Notas SOAP y diagnósticos · **Entrega:** matriz de amenazas, pruebas de seguridad contra PostgreSQL real, recorrido real con JWT y bitácora de despliegue local.

## Ficha de la asignación

- **Semana:** Semana 17 — Seguridad y despliegue final
- **Issue:** Refs #22
- **PR de código:** #1077 (pruebas de seguridad y correcciones) y #1080 (conflicto R-06). Este PR solo agrega documentación.

| Requisito | Estado | Evidencia |
|---|---|---|
| Matriz de amenazas | **Hecho** | [amenazas.md](./amenazas.md): 17 amenazas, 12 cubiertas, 1 parcial y 4 abiertas |
| Pruebas de permisos y datos sensibles | **Hecho** | `NotasSoapSecurityPostgresTest` (8 pruebas, T1 a T8) y `NotasSoapPostgresTest` (9) contra PostgreSQL real, más `tests/Feature/NotasSoap` (9) |
| Hallazgos corregidos | **Hecho** | id que no es UUID: 500 → 404; código CIE-10 con basura → 422 antes del catálogo; segunda nota del expediente: 500 → 409 |
| Flujo funcional con PostgreSQL | **Hecho (local)** | Registrar y leer una nota con JWT, tenant y RBAC reales: [evidencia-funcional.md](./evidencia-funcional.md) |
| Bitácora de despliegue | **Parcial** | [bitacora-despliegue.md](./bitacora-despliegue.md): despliegue **local** de verificación |
| Instalación reproducible | **Hecho (local)** | [instalacion-local.md](./instalacion-local.md) |
| Despliegue final | **Bloqueado** | No hay entorno staging u on-premise autorizado |

**La Semana 17 no se declara completa:** el despliegue final depende de un entorno autorizado, y el módulo tiene cuatro amenazas abiertas y tres dependencias de ASII-09.

## Estado funcional del módulo

| Capacidad | Estado |
|---|---|
| Registrar una nota SOAP (médico tratante) | Funciona de extremo a extremo con PostgreSQL |
| Leer una nota | Funciona; sin asignación activa responde 404 |
| Asociar un diagnóstico | **No funciona con la API real:** falta `icd10_codes` (503). Funciona con el catálogo en memoria |
| Más de una nota por expediente | **No:** R-06 (409) |
| Firmar y corregir notas | No implementado (permisos RBAC creados, sin rutas) |

## Lo que se necesita de otros

| Qué | Quién |
|---|---|
| Tabla `icd10_codes` (o decidir de dónde sale el catálogo) | Javier (ASII-09) |
| Resolver R-06 (unicidad por cadena de versiones) | Javier (ASII-09) |
| Trigger de D-02 en `diagnoses` | Javier (ASII-09) |
| Quién crea el expediente abierto de cada paciente | Admisión o Pacientes |
| Entorno de despliegue | Equipo |
| Facturación de GitHub Actions (los checks no arrancan) | Administración de la organización |

## Archivos

[Amenazas](./amenazas.md) · [Evidencia funcional](./evidencia-funcional.md) · [Bitácora de despliegue](./bitacora-despliegue.md) · [Instalación local](./instalacion-local.md) · [Declaración de uso de IA](./DECLARACION_IA.md)
