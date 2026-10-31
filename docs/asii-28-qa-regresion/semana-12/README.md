# PR de módulo ASII

## Resumen

Segunda evaluación parcial: defensa preparada del diseño UX y del alcance real QA.
No acredita defensa oral, evaluación docente ni aprobación de entregas pendientes.

## Módulo asignado

- ASII-28 — QA funcional, pruebas manuales y matriz de regresión.
- Estudiante: Madelin Jazmín Cerón Molina. GitHub: MadelinCeron. Refs #12.
- Rama: `feature/asii-28-semana-12-qa-regresion-madelinceron`; destino: `develop`.
- HEAD inicial: `fba5bdf9`; árbol inicialmente limpio. Tras fetch origin/develop avanzó a `16654473`; se conserva la base sin pull, merge ni rebase.

## Alcance

Incluido: cinco documentos nuevos; [ocho diapositivas](./presentacion.md),
[guion y preguntas](./guion-defensa.md), [recorrido y evidencia nueva](./recorrido-prototipo.md)
y [matriz de ocho RF/cinco RNF](./matriz-trazabilidad.md).
Abrir presentacion.md en VS Code, Ctrl+Shift+V: Markdown editable, ocho encabezados y siete separadores; vista continua, sin PowerPoint generado.
Fuera de alcance: funcionalidades nuevas, cambios PHP, semanas anteriores, PostgreSQL, integración HTTP y estudios con personas.

## Evidencia obligatoria

- [ ] Worktree: no utilizado por instrucción expresa; trabajo en el repositorio abierto.
- [x] Sin trabajo en main ni mezcla de módulos; Refs #12.
- [x] RF/RNF, criterios, arquitectura, flujo UX, capturas referenciadas y pruebas registradas.
- [x] Seguridad delimitada: actores conceptuales, sin credenciales ni datos clínicos; tenant/RBAC QA pendientes.

## Validación ejecutada

29/10/2026: consulta GitHub confirmó **abiertos/no fusionados** #1400, #1401, #1402,
#1403, #1408, #1452 y #1492. Se leyeron sus documentos con git show desde ramas remotas,
sin incorporar commits. Semanas 1–4 y Micro-HIS están en la base; Semanas 5–11 no.

| Comprobación nueva | Resultado |
|---|---|
| `php micro-his/qa-regresion/tests/run.php` | 17 aprobadas, 0 fallidas; suite existente, InMemory/PDO SQLite en memoria |
| Demo CLI válida / propósito vacío | 201 con identidad / 422 CASE_INCOMPLETE |
| `node --check <TEMP>/verificar-prototipo.mjs` y ejecución CDP | Sintaxis válida; 17 comprobaciones aprobadas, 0 fallidas; detalles en recorrido |
| Revisión documental | Ocho diapositivas, trece filas RF/RNF, enlaces locales y fuentes Git comprobados |
| `git diff --check`; `git diff --cached --check` | Sin errores antes del commit; diff completo acotado a Semana 12 y menor de 401 líneas |

## Contrato API si aplica

#1400 propone POST `/api/v1/qa/manual-cases` y GET `/api/v1/qa/manual-cases/{id}`;
no hay rutas QA implementadas en routes. Los status del Micro-HIS son datos CLI.
Sin permisos QA registrados ni propiedad tenant definida; no exponer el núcleo como API segura.

## Evidencia visual si aplica

Cuatro capturas reales de Semana 11 enlazadas en recorrido; no se duplican binarios.
Reejecución aislada en Edge 156.0.4314.50, 1280/390/320 px; capturas nuevas solo temporales.

## Riesgos y pendientes

Backend real: solo diseño/almacenamiento de casos (RF-01). HTML: seis vistas y registros
simulados en memoria, sin conexión PHP. Faltan campañas persistentes, historial completo,
evidencias privadas, seguridad y contratos aprobados. RNF-04 completo no demostrado.
Semana 13: acordar tenant/permisos, políticas de evidencia/severidad y recuperación incierta;
revisar con participantes autorizados, lector de pantalla y zoom real antes de integración.
Madelin Cerón responde por documentación y validaciones; revisión/aprobación pendientes,
sin atribuirlas a compañeros. No se certifica WCAG ni se afirma evaluación docente realizada.

## Checklist final del estudiante

- [x] Entrega pequeña, documentación acotada y limitaciones explícitas.
- [x] Evidencia mínima revisada; sin secretos, datos reales o cambios en otros módulos.
- [ ] Revisión y aprobación externas pendientes.

Resumen de cambios: presentación, defensa, recorrido comprobado y trazabilidad de diseño.
