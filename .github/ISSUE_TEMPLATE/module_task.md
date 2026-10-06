---
name: Tarea de módulo / área
about: Asignar y dar seguimiento al trabajo de un área vertical del HIS
title: "ASII-0X — <Área>"
labels: ''
assignees: ''
---

## Área

<!-- Número y nombre, p. ej. 7 · Laboratorio (módulos originales 16–20) -->
Responsable: @<!-- usuario -->

## Submódulos

<!-- Lista de submódulos o capacidades que cubre el área. -->

## Fases

- [ ] F0 · Análisis: actores, casos de uso, RF/RNF y criterios de aceptación (`docs/modulo-<area>.md`)
- [ ] F0 · Vista arquitectónica / componentes y contrato API preliminar
- [ ] F1 · <!-- primera entrega funcional -->
- [ ] F2 · <!-- ... -->
- [ ] UI Vue del flujo principal
- [ ] Integración con otras áreas y matriz de amenazas

## Definition of Done

- [ ] RF/RNF y criterios de aceptación documentados
- [ ] Diseño arquitectónico, por capas y de componentes
- [ ] Contrato API documentado (siguiendo `docs/contrato-api.md`)
- [ ] Backend con controllers delgados, validaciones, servicios y control por rol/hospital
- [ ] UI mínima funcional
- [ ] Pruebas en SQLite y `phpunit.pgsql.xml` con evidencia
- [ ] Seguridad revisada (permisos por ruta, `BelongsToTenant`, datos sensibles)
- [ ] Evidencia de integración con otras áreas
- [ ] Demo o capturas

## Dependencias

<!-- Áreas e issues de coordinación relacionados (p. ej. "Área 5: #11"). -->
