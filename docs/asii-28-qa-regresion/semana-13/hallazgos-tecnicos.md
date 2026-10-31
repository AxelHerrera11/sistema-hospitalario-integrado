# Hallazgos técnicos sustentados

Fuentes/versiones en [README](./README.md); criterios en [checklist](./checklist-revision-tecnica.md).
Todos abiertos; ninguno corregido ni aprobado mediante esta entrega documental.
No hay vulnerabilidad explotada confirmada. Alta = bloqueo para integración segura o
integridad del flujo; Media = brecha de integración/fiabilidad/validación; Baja = deuda acotada.
No se usa Crítica sin evidencia de impacto real. La severidad no implica fecha de corrección aprobada.
B = backend QA, S = seguridad, T = pruebas, F = frontend QA: roles propuestos,
**Pendiente de asignación**. MC mantiene sus documentos y evidencia; no asume aprobaciones.
Verificador de cada cierre: revisor técnico independiente, **Pendiente de asignación**.

| ID / RT | Tipo / severidad justificada | Descripción, causa y evidencia | Impacto / recomendación | Responsable de corrección / estado |
|---|---|---|---|---|
| REV-01 / RT-11 | Riesgo / Alta: impide separación de datos en integración compartida | ManualCase no incluye ámbito; schema.sqlite.sql solo id/purpose/steps/expected; PDO find(id) no filtra hospital. Origen: demo sin política de propiedad | Una futura base compartida podría mezclar ámbitos. Acordar modelo de propiedad antes de exponer y probar acceso cruzado; no se afirma fuga actual | S/B, Pendiente de asignación; Abierto |
| REV-02 / RT-10 | Riesgo / Alta: acceso multiusuario sin frontera propia | Controller/DesignCase no reciben identidad/permisos; bootstrap solo autoload. S05 qa.casos.registrar/ver son propuestas | Exponer el núcleo sin adaptación permitiría uso sin controles QA. Resolver autenticación/autorización en host y pruebas; demo local no es endpoint público vulnerable probado | S/B, Pendiente de asignación; Abierto |
| REV-03 / RT-04/06/12 | Pendiente / Alta: faltan reglas e integridad del flujo principal | Domain contiene ManualCase/CaseRepository/StorageFailure; no campañas/ejecuciones/defectos. PHP 17 prueba casos; HTML simula flujo. RNF-04 trata ejecuciones | RF-02–08/RNF-01–05 no acreditados por backend. Definir contratos/invariantes e implementar por incrementos revisados con pruebas de historia/vínculos/coherencia/LSP | B/T, Pendiente de asignación; Abierto |
| REV-04 / RT-08 | Pendiente / Media: contrato sin transporte operativo | S05 propone POST/GET; búsqueda routes sin QA; find existe, pero falta caso de uso/controlador de lectura y adaptador HTTP | No puede conectarse UI como si API existiera. Revisar payload/errores/permisos/límites antes de adaptar; esperar decisiones REV-01/02 | B/S, Pendiente de asignación; Abierto |
| REV-05 / RT-03 | Deuda técnica / Baja: acoplamiento futuro, sin fuga demostrada | CaseView::created usa get_object_vars en base; cualquier nueva propiedad pública entraría al JSON. #1402 propone cuatro claves explícitas, aún abierto | Extender dominio podría ampliar representación involuntaria. Revisar propuesta/compatibilidad; no copiar ni fusionar su código en esta revisión | B, Pendiente de asignación; Abierto |
| REV-06 / RT-07 | Riesgo / Media: fallos de arranque fuera de envoltura segura | demo.php construye PDO/ejecuta esquema antes del try; solo JsonException se captura allí. Controller normaliza dominio/StorageFailure durante create | Si falla conexión/extensión/esquema, podría emitirse error sin normalizar. Riesgo por inspección, no fallo de arranque inducido; definir manejo seguro y pruebas aisladas | B/T, Pendiente de asignación; Abierto |
| REV-07 / RT-09 | Deuda técnica / Media: conservación durable no demostrada | demo.php y tests usan sqlite::memory; mapa volátil; script HTML verifica reinicio tras recarga. Sin esquema de campañas/evidencias | No sirve para historial ni evidencia durable. Acordar almacenamiento aislado y retención, integridad/recuperación y consulta histórica; no tocar PostgreSQL hospitalario | B/T, Pendiente de asignación; Abierto |
| REV-08 / RT-14 | Pendiente / Media: validación UX/asistiva incompleta | S09 UX-04/05/06; S10 solo diseño/mediciones históricas; S11 prueba foco/reflow pero no lector, zoom, descarte con texto o participantes | No permite certificar accesibilidad/recuperación. Ejecutar protocolo autorizado y mantener estado parcial; no inventar incumplimientos WCAG ni cierre retrospectivo de UX-01–06 | F/T, Pendiente de asignación; Abierto |

Balance: 8 hallazgos, 3 Alta, 4 Media, 1 Baja, 0 Crítica; dos riesgos de seguridad
distintos (propiedad tenant y autorización), sin duplicar evidencia como vulnerabilidades confirmadas.
La falta de aislamiento pertenece a ASII-28 revisado; no se atribuye a módulos de otros estudiantes.
