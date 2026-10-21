# ASII-03 Pacientes — Semana 9: evaluación heurística y accesibilidad

## Objetivo

Evaluar el flujo y los wireframes de Semana 8 con una revisión heurística de
usabilidad y una comprobación básica de criterios de accesibilidad aplicables
al diseño disponible. Registrar problemas y acciones antes de implementar la
interfaz de Pacientes.

## Alcance y material evaluado

- [Flujo de usuario de Semana 8](../semana-08/flujo-usuario.md), [wireframes](../semana-08/wireframes.md) y [decisiones UX](../semana-08/decisiones-ux.md).
- [Contrato API de Semana 5](../semana-05/contrato-api.md) y [OpenAPI](../../../micro-his/pacientes/openapi.yaml).
- [README de Semana 7](../semana-07/README.md), [módulo PHP de Pacientes](../../../micro-his/pacientes/) y sus pruebas.
- `config/rbac.php`, `routes/api.php`, `resources/js/` y `resources/views/` para comprobar permisos e interfaz existente.

Las únicas pantallas evaluables son seis wireframes esquemáticos, no una
implementación visual. No se usaron ni reprodujeron datos reales.

## Metodología

Inspección estática del flujo, wireframes y contrato; contraste con las rutas,
permisos y componentes existentes. Cada criterio se clasifica según evidencia
del diseño. Sin interfaz ejecutable, no se infiere conformidad de teclado,
foco, contraste, etiquetas ni anuncios de tecnología asistiva. La evaluación
es básica y no declara cumplimiento WCAG completo.

## Resultado y prioridades

Se documentan **7 hallazgos**: 1 de prioridad/severidad alta y 6 medios.
El backlog ordena primero la fuente verificable de permisos; luego la
recuperación ante errores del contrato; después foco y contraste; finalmente
la claridad de campos y la protección de cambios sin guardar.

## Fuera de alcance

No se implementan componentes, rutas ni cliente API; no se modifican RBAC,
API, persistencia o migraciones. La autorización del servidor debe seguir
validando cada operación y es la autoridad final. No es una auditoría WCAG,
prueba con usuarios ni evaluación visual de una pantalla renderizada.

## Validaciones realizadas

- Inspección de los documentos y superficies indicados arriba.
- `php micro-his/pacientes/tests/run.php`: ejecutado al cierre; resultado en el resumen de entrega.
- `git diff --check origin/develop...HEAD`, `git diff --stat origin/develop...HEAD`, `git diff --numstat origin/develop...HEAD` y `git diff --name-only origin/develop...HEAD`: ejecutados al cierre; se contaron también los archivos nuevos.
