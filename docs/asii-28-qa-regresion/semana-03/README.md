# ASII-28 — Semana 3: Diseño arquitectónico, vistas y patrones

Autora: Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #12.
Módulo: QA funcional, pruebas manuales y matriz de regresión; adaptación:
gestión de campañas de regresión y defectos. Rama de entrega:
`feature/asii-28-semana-03-qa-regresion-madelinceron`, base `develop`.
HEAD inicial: `3ea30faa44e6ece877f9141405374cb519279b45`.

## Objetivo y alcance

Diseñar un Micro-HIS PHP 8.2+ sin framework, con cuatro capas, dependencias
explícitas y trazabilidad de casos, campañas, ejecuciones, evidencia y defectos.
Esta entrega es una **propuesta arquitectónica documental**, no una aplicación
implementada. La inspección no encontró un Micro-HIS ejecutable de ASII-28.
Por ello los tres escenarios exigidos se registran como previstos, sin PASS ficticio.

- [Arquitectura](./arquitectura.md): decisiones, modelo, contratos y trazabilidad.
- [Diagrama UML editable](./diagrama-arquitectura.puml): actores, componentes y dependencias.
- [Evidencia de validación](./evidencia-validacion.md): verificaciones y escenarios futuros.
- [Requisitos Semana 2](../semana-02/rf-rnf-criterios.md) y
  [contrato LSP](../semana-02/solid-lsp.md): fuentes preservadas sin cambios.

## Responsabilidades y dependencias

| Capa propuesta | Responsabilidad | Dependencia permitida |
|---|---|---|
| Presentation | Recibir entradas, validar formato y presentar resultados/errores | Application y tipos de contrato; nunca SQL |
| Application | Coordinar diseño, campañas, registro de ejecución/defecto y consultas | Contratos y reglas de Domain |
| Domain | Entidades, valores, invariantes y contratos sustituibles | PHP estándar; sin PDO, HTTP ni framework |
| Persistence | Implementar contratos con PDO preparado o doble InMemory | Domain y PDO; sin redefinir reglas funcionales |

Un bootstrap futuro compondrá adaptadores y casos de uso por constructor.
Semana 2 está integrada en origin/develop; el PR #144 figura fusionado.
`micro-his/qa-calidad-versiones` es el módulo de ejecuciones automatizadas/CI,
no la implementación de ASII-28. Su consumo de resúmenes sería integración
futura con contrato acordado; no se modifica ni se usa como evidencia de QA manual.

## Estado, riesgos y Semana 4

Se inspeccionaron fuentes y dependencias y se contrastaron ocho RF y cinco RNF.
Las verificaciones concretas del diagrama y enlaces están en evidencia-validacion.
Pendientes: implementar el núcleo y sus tres escenarios, acordar esquema de test,
severidad de defectos, formatos de evidencia, autorización y frontera tenant.
No hay API/UI nueva, migraciones ni datos clínicos. La consigna de iniciar el
Micro-HIS queda pendiente de implementación; el entregable de vista arquitectónica
se prepara sin presentar escenarios previstos como funcionalidad terminada.
Madelin Cerón mantiene esta documentación; revisión, implementación y verificación
independiente: Pendiente de asignación. El PR requiere revisión antes de aprobación.
