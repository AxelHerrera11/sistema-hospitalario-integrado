# Gobernanza y Auditoría — Semana 3: arquitectura y prototipo

## Objetivo

Definir la arquitectura del módulo (C4 de contexto y de contenedores, componentes y dependencias), los contratos iniciales y el modelo de dominio, y entregar un prototipo funcional de registro y consulta con un adaptador InMemory y pruebas iniciales.

## Entregables

| Entregable | Ubicación |
|---|---|
| C4 contexto y contenedores | [arquitectura.md §2–3](arquitectura.md), `01-c4-contexto.svg`, `02-c4-contenedores.svg` |
| Componentes y dependencias | [§4](arquitectura.md#4-componentes-y-dependencias), `03-componentes.svg` |
| Contratos iniciales | [§5](arquitectura.md#5-contratos-iniciales-de-los-servicios) |
| Modelo de dominio | [§6](arquitectura.md#6-modelo-de-dominio), `04-modelo-dominio.svg` |
| Prototipo de registro y consulta | `micro-his/gobernanza-auditoria/src/**` |
| Adaptador InMemory | `micro-his/gobernanza-auditoria/src/Persistence/InMemoryAuditLog.php` |
| Pruebas iniciales | `micro-his/gobernanza-auditoria/tests/unit.php` (el contrato con PDO, `contract.php`, llega en la Semana 4) |
| Instrucciones de ejecución | [§7](arquitectura.md#7-prototipo-funcional) y `micro-his/gobernanza-auditoria/README.md` |

## Archivos creados

- **Código:** `micro-his/gobernanza-auditoria/` (`bootstrap.php`, `src/Domain/*`, `src/Application/*`, `src/Persistence/InMemoryAuditLog.php`, `src/Presentation/*`, `public/index.php`, `tests/harness.php`, `tests/fixtures.php`, `tests/unit.php` y `tests/run.php`).
- **Documentación:** `arquitectura.md`, `fuentes/01…04.mmd` y sus SVG.

El código se mantiene en un solo lugar. Las semanas siguientes lo amplían sin copiarlo.

## Relación con semanas anteriores

Implementa las responsabilidades de la [Semana 2](../semana-02/diseno-solid.md). Las clases de los diagramas de secuencia y de componentes son las mismas de la Semana 1.

## Pruebas ejecutadas y resultados reales

| Comando | Resultado |
|---|---|
| `php micro-his/gobernanza-auditoria/tests/run.php` (entrega de esta semana: solo `unit.php`) | 20 pruebas, 0 fallos. La Semana 13 agrega 2 regresiones (22 en total). |
| `curl` al servidor de demostración (sesión `admin`, `modulo=rbac`, `por_pagina=2`) | 200, con 2 eventos y `meta`. |
| `curl` al servidor de demostración (sesión `medico`) | 403. |
| `curl` con `demo_falla=almacen` | 503 `AUDIT_UNAVAILABLE`. |
| `curl` sin sesión | 401 `AUTH_REQUIRED`. |

La salida completa de todas las suites está en [../evidencias/](../evidencias/).

## Dependencias pendientes

- La integración con Laravel necesita `RbacServiceProvider` (develop), `audit_log` fusionado y `TenantMiddleware` funcional (hoy depende de `App\Models\Tenant`, que no existe).

## Limitaciones conocidas

- El servidor de demostración simula la identidad con la cabecera `X-Demo-Session`, que solo sirve para la demostración. No está bajo `public/` de Laravel y no se expone en el despliegue.
- Los datos de la demostración se regeneran en cada petición.
