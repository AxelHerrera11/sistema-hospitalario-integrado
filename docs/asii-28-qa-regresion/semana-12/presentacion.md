# 1. ASII-28 — Segunda evaluación parcial

Madelin Jazmín Cerón Molina · MadelinCeron · Refs #12.
Objetivo: defender diseño UX y trazabilidad de campañas de regresión y defectos.
Evaluación preparada; defensa oral pendiente. Guion en [guion-defensa.md](./guion-defensa.md).

---

# 2. Problema, actores y alcance

- Problema: resultados, evidencias y defectos dispersos impiden reconstruir qué se probó.
- Analista QA diseña casos, ejecuta y registra hallazgos; responsable de corrección consulta defectos. Actores conceptuales, sin RBAC implementado.
- Cadena objetivo: campaña → caso → ejecución → evidencia/defecto.
- Real: RF-01 en Micro-HIS CLI. Simulado: flujo HTML. Futuro: integración y almacenamiento del flujo completo.
- Fuente: [narrativa de Semana 1](../semana-01/narrativa-alcance-trazabilidad.md).

---

# 3. Requisitos y aceptación

- RF-01/02: caso completo y campaña con casos asociados; RF-03/04: observación y resultado coherente.
- RF-05/06: evidencia de ejecución y defecto solo desde fallo; RF-07/08: consulta e historia conservada.
- RNF-01/02/03: integridad, consistencia e inmutabilidad; RNF-04: sustituibilidad del registro de ejecución; RNF-05: resultado inequívoco.
- Ejemplo: caso sin pasos debe rechazarse; aprobado no debe habilitar defecto.
- [Fuente exacta: Semana 2](../semana-02/rf-rnf-criterios.md); [matriz y estado individual](./matriz-trazabilidad.md).

---

# 4. Arquitectura, Repository y LSP

- Presentation: CaseController/CaseView; Application: DesignCase; Domain: ManualCase/CaseRepository; Persistence: InMemoryCaseRepository/PdoCaseRepository.
- Application depende del puerto Domain; adaptadores implementan ese puerto. Domain no conoce SQL ni transporte.
- Repository separa almacenamiento; ambos adaptadores rechazan duplicados sin sobrescribir y traducen fallos a StorageFailure.
- Misma batería verifica sustitución para casos; no demuestra RNF-04 de ejecuciones todavía inexistentes.
- [Arquitectura y diagrama editable](../semana-03/README.md), [capas reales](../semana-04/arquitectura-capas.md), [componentes propuestos/refactor pendiente](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/e131ecaa316432607c8baa01c075cd329794a563/docs/asii-28-qa-regresion/semana-07/componentes.md).

---

# 5. Diseño UX de escritorio

- Seis vistas: panel, campañas, detalle, nuevo caso, ejecución y defecto.
- Separar consultar/crear y cancelar/consultar; mostrar obligatoriedad, errores y contexto del caso.
- Flujo: seleccionar campaña → abrir/diseñar caso → observar/comparar → resultado → defecto si falló → panel.
- [User flow editable y decisiones](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/508deb1b276d574cec5c6f179b3f067d875b8572/docs/asii-28-qa-regresion/semana-08/user-flow.md).
- [Seis wireframes estáticos](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/508deb1b276d574cec5c6f179b3f067d875b8572/docs/asii-28-qa-regresion/semana-08/wireframes.html): controles deshabilitados; antecedente del prototipo interactivo.

---

# 6. Móvil, accesibilidad y hallazgos

- Etiquetas verticales, menú envuelto y una columna en móvil; [diseño responsive previo](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/63f00ed0a2295d69234d7ce4b8637adc351fa8c7/docs/asii-28-qa-regresion/semana-10/diseno-responsive.md).
- Semana 9: tres defectos de navegación/instrucciones (UX-01/02/03) y tres riesgos (UX-04 errores accesibles, UX-05 evidencia, UX-06 guardado incierto).
- Semana 11 atiende consulta/creación, cancelación separada y obligatoriedad; resumen enfocable y ayuda ficticia atienden parcialmente los riesgos.
- Nueva prueba a 1280/390/320 px sin desborde horizontal y Tab/Enter con foco; no equivale a zoom real, lector de pantalla ni certificación WCAG.
- Hallazgos originales no se cierran automáticamente: [registro de Semana 9](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/3161aa928478678b69d1fade23ad6264d7c47ef8/docs/asii-28-qa-regresion/semana-09/hallazgos.md).

---

# 7. Demostración práctica

- Seguir [recorrido reproducible](./recorrido-prototipo.md): panel → campañas → detalle → caso → ejecución → evidencia ficticia → defecto → panel.
- Mostrar error de formulario; registrar fallo y defecto; después aprobado y bloqueo de defecto.
- Panel final: una campaña, dos casos, dos ejecuciones y un defecto, todos simulados.
- Recargar elimina los registros. Mostrar backend CLI por separado: no existe conexión HTML–PHP.
- Cuatro capturas publicadas se reutilizan mediante enlaces; no son pantallas de producción.

---

# 8. Resultados y pendientes

- Nueva ejecución: 17 comprobaciones HTML aprobadas/0 fallidas; 17 pruebas PHP aprobadas/0 fallidas. Son suites distintas.
- CLI: 201 válido y 422 incompleto; navegabilidad, formularios, foco, diálogo y reflow comprobados.
- PR #1400/#1401/#1402/#1403/#1408/#1452/#1492 abiertos: diseños consultables, integración no asumida.
- Pendientes: revisión humana/asistiva, persistencia, contrato HTTP, tenant/permisos, evidencia y recuperación incierta.
- Madelin mantiene entrega/evidencia; revisión y evaluación docente pendientes. [Resultados y límites](./README.md).
