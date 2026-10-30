# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code, modelo `claude-sonnet-5-5` (ejecutado sobre Windows). Las razones de contraste se calcularon con un script de `node`.

**Propósito:** evaluar el flujo UX de Notas SOAP y diagnósticos de la Semana 8 con las heurísticas de Nielsen y WCAG 2.2 AA (teclado, foco, contraste, etiquetas, mensajes y prevención de errores), redactar la lista de comprobación, los hallazgos con evidencia y el backlog priorizado.

**Revisado y ajustado:**

- Cada hallazgo cita texto y dibujos reales de `docs/notas-soap/semana-08/wireframes.md` y del user flow; ninguno se infirió de código, porque la pantalla aún no existe.
- Las razones de contraste (2.15, 2.54, 4.83, 5.02 y 7.09) se calcularon con la fórmula de luminancia relativa de WCAG sobre blanco. Los colores son tonos típicos de paleta, no los del módulo, y así se indica.
- La prioridad ordena por impacto en nota, diagnóstico, autoría, versionado y expediente; cada criterio es una prueba repetible.
- La propuesta de borrador en servidor (B-04) implica una entidad nueva y queda condicionada a la coordinación con Javier (ASII-09).
- En esta entrega no se modificó código del micro-HIS ni del HIS.

**Validación humana:** realizada el 2026-10-20. Josué Fernando Hicho García revisó los hallazgos, el checklist y el backlog contra los wireframes de la Semana 8, y aprueba el documento.

**Responsable:** Josué Fernando Hicho García es responsable de esta entrega.
