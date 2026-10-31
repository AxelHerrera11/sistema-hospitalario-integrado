# ASII-28 - QA Funcional - Semana 8 - Diseño de experiencia de usuario

Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #12.
Rama `feature/asii-28-semana-08-qa-regresion-madelinceron`; destino `develop`.
HEAD inicial 52097752bd37ea40b37f986259fc4c0aec0da2c6, igual a origin/develop
tras fetch; árbol inicialmente limpio. Sin worktrees ni cambios de base necesarios.

## Entrega y cómo visualizarla

Propuesta UX para el recorrido de campañas/casos/ejecuciones/defectos, sin convertir
el CLI en una interfaz gráfica supuestamente existente. Se entregan:

- [User flow editable](./user-flow.md), con decisiones y alternativas.
- [Seis wireframes y explicación](./wireframes.md).
- [Maqueta HTML estática editable](./wireframes.html): abrir directamente en navegador.
- [Reglas de interacción y trazabilidad](./reglas-interaccion.md).

El HTML no usa JavaScript, dependencias o API. Botones/campos están deshabilitados;
solo enlaces internos navegan entre los seis ejemplos. No captura ni guarda datos.
No son screenshots ni aplicación funcional. Las métricas usan [n], no cifras reales.

## Fuentes y estado

Se revisaron [actores](../semana-01/narrativa-alcance-trazabilidad.md),
[RF/RNF](../semana-02/rf-rnf-criterios.md), [LSP](../semana-02/solid-lsp.md),
[arquitectura](../semana-03/README.md) y [núcleo CLI](../semana-04/README.md).
#144/#1367/#1368 fusionados; #1400/#1401/#1402 abiertos/no fusionados según GitHub.
Contrato REST, defensa y componentes se consultaron en sus ramas, sin incorporar
commits ni modificar semanas previas. Solo RF-01 tiene operación CLI real.

Actor principal respaldado: Analista QA; responsable de corrección consulta defectos
según Semana 1. Son actores conceptuales, no permisos RBAC implementados.
No se atribuye un Coordinador/Revisor ni responsabilidades a otros integrantes.

## Validaciones y límites

Se comprueban seis pantallas, navegación local, etiquetas, ausencia de conexiones,
trazabilidad de los ocho RF/cinco RNF y distinción propuesta/implementación.
El flujo pasó sintaxis/render PlantUML y el HTML fue renderizado en Edge headless;
resultados registrados en user-flow.md. No se modificó PHP ni se ejecutaron pruebas funcionales
nuevas. No se realizaron pruebas con personas ni se certifica WCAG.

Pendientes para Semana 9: revisión heurística y prueba de tareas con participantes
autorizados, política de evidencia/severidad/estados, seguridad y contratos aprobados.
Sin API QA, almacenamiento durable, campañas o defectos implementados. Madelin Cerón
mantiene diseño/evidencia; revisión y aprobación pendientes de asignación.
