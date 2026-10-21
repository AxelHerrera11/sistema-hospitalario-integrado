# ASII-03 Pacientes — Semana 10: diseño responsive y experiencia móvil

## Objetivo

Adaptar los flujos propuestos de Pacientes a 320–430 px, manteniendo claridad,
permisos, privacidad y accesibilidad básica, sin presentar el diseño como interfaz implementada.

## Alcance y material base

- [Flujo de usuario de Semana 8](../semana-08/flujo-usuario.md), [wireframes](../semana-08/wireframes.md) y [decisiones UX](../semana-08/decisiones-ux.md).
- [README de Semana 7](../semana-07/README.md) y estructura/casos de uso del módulo Pacientes.
- Contrato y permisos del [módulo Pacientes](../../../micro-his/pacientes/) y [OpenAPI](../../../micro-his/pacientes/openapi.yaml).
- [Matriz RBAC](../../../config/rbac.php), `resources/js/`, `resources/views/` y `routes/api.php`.
- Estado y hallazgos previos: Semana 9 no está disponible en esta rama.

No se encontró pantalla, ruta ni cliente API funcional para Pacientes en Vue. Los cinco diseños son especificaciones, no una interfaz operativa.

## Anchos considerados

320, 360, 390 y 430 px. Los breakpoints adaptan espaciado y acciones; el contenido fluye sin una composición distinta por modelo.

## Entrega

- [Estrategia responsive](./estrategia-responsive.md): breakpoints y comportamiento general.
- [Pantallas móviles](./pantallas-moviles.md): cinco wireframes anotados.
- [Escenarios móviles](./escenarios-moviles.md): dos recorridos con alternativas de error recuperable.

## Fuera de alcance

No se agregan vistas, componentes, rutas, adaptador HTTP ni cambios a RBAC, persistencia o migraciones. El servidor sigue siendo autoridad de autorización. No hay datos reales ni afirmación de conformidad WCAG completa.

## Validaciones

Se inspeccionaron material base, RBAC y frontend. Se ejecutó `php micro-his/pacientes/tests/run.php` y los cuatro comandos `git diff` solicitados. Los archivos nuevos se contaron por separado; resultados en el resumen final.
