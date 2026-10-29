# ASII-28 — QA Funcional — Semana 4: Arquitectura en capas y patrón repositorio

Autora: Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #12.
Rama: `feature/asii-28-semana-04-qa-regresion-madelinceron`; base `develop`.
HEAD inicial: `3ea30faa44e6ece877f9141405374cb519279b45`, igual a origin/develop
tras fetch; árbol inicialmente limpio. Sin pull, worktree ni commits anteriores incorporados.

## Objetivo y alcance implementado

Primera operación vertical del Micro-HIS PHP 8.2+ vanilla: **diseñar y guardar
un caso manual** (RF-01: propósito, pasos y resultado esperado obligatorios).
Entrada CLI → controlador → caso de uso → dominio → Repository → InMemory/PDO.
La vista devuelve un resultado JSON verificable, sin integración con Laravel.

- [Arquitectura y MVC](./arquitectura-capas.md).
- [Contrato y sustitución de adaptadores](./contratos-repositorio.md).
- [Ejecución y evidencia](./evidencia-validacion.md).
- Fuentes integradas: [Semana 1](../semana-01/README.md),
  [RF/RNF](../semana-02/rf-rnf-criterios.md) y [LSP](../semana-02/solid-lsp.md).

Código en `micro-his/qa-regresion/`: bootstrap.php; src/Domain (ManualCase,
CaseRepository, StorageFailure); src/Application (DesignCase); src/Presentation
(CaseController, CaseView); src/Persistence (InMemoryCaseRepository,
PdoCaseRepository); database/schema.sqlite.sql; demo.php y tests/run.php.
Dependencias: PHP 8.2+, PDO y pdo_sqlite; sin Composer/framework adicional.
Ejecutar: `php micro-his/qa-regresion/tests/run.php` → **17 aprobadas, 0 fallidas**.

## Límites y siguiente incremento

Semana 3 (#1367) sigue abierta, no fusionada; se consultó arquitectura/diagrama
desde su rama sin copiarlos. Semana 2 (#144) está integrada y permanece intacta.
Se concreta el puerto amplio propuesto como CaseRepository, limitado a este agregado.
SQLite es exclusivamente demostración en memoria, no arquitectura hospitalaria nueva.
No hay campañas, ejecuciones, evidencias, defectos, API HTTP, UI, auth ni tenant
implementados: RF-02–08 y la cadena RNF-01–03/05 siguen pendientes.
La sustitución probada cubre almacenamiento de casos, no acredita el contrato
completo de registro de ejecución de RNF-04. Semana 5 deberá acordar frontera
cliente-servidor, seguridad y propiedad de datos antes de exponer este núcleo.
Madelin Cerón responde por código/evidencia; revisión y aprobación pendientes de asignación.
