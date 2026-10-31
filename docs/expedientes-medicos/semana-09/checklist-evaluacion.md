# Checklist de usabilidad y accesibilidad (completado)

Evaluación del prototipo `micro-his/expediente-medico/public/ui` el 2026-10-23 con: revisión de código, script de
contraste ([`evidencias/contraste.txt`](evidencias/contraste.txt)), script de navegador
([`evidencias/auditoria-navegador.json`](evidencias/auditoria-navegador.json)) y capturas de la Semana 11.
**No** se probó con un lector de pantalla real (NVDA/JAWS): queda indicado donde aplica.

## WCAG 2.1 (nivel AA) — criterios pedidos

| # | Criterio | Resultado | Evidencia |
|---|---|---|---|
| W-01 | 2.1.1 Teclado: todo operable sin mouse | Cumple | Orden de tabulación: saltar → usuario → hospital → código → Consultar → Abrir; Enter consulta; `<details>` y `<dialog>` nativos |
| W-02 | 2.4.1 Saltar bloques | Cumple | Enlace «Saltar al contenido» primero en el orden |
| W-03 | 2.4.3 Orden del foco / 2.4.7 Foco visible | Cumple | `:focus-visible` con doble anillo; foco a «Resultado» tras cada respuesta; «Cancelar» al abrir el diálogo |
| W-04 | 1.4.3 Contraste de texto ≥ 4.5:1 | Cumple | 13 de 13 pares de texto (mínimo 6.58:1) |
| W-05 | 1.4.11 Contraste no textual ≥ 3:1 | Cumple con observación | Borde de campo 4.63:1; anillo amarillo solo 1.64:1, compensado por el contorno oscuro 14.76:1 (H-06) |
| W-06 | 1.3.1 / 3.3.2 Etiquetas e instrucciones | Cumple | 0 campos sin `<label for>`; ayuda con `aria-describedby` |
| W-07 | 1.3.1 Estructura de encabezados | **No cumple** | Episodios en `h3`, mismo nivel que «Episodios clínicos» (H-04) |
| W-08 | 3.3.1 Identificación de errores | Cumple | `aria-invalid`, mensaje en texto bajo el campo, foco en el campo |
| W-09 | 3.3.4 Prevención de errores (acciones con efecto) | Cumple | Diálogo de confirmación antes de abrir; apertura idempotente |
| W-10 | 4.1.3 Mensajes de estado | Cumple (sin verificar con lector) | Región `aria-live="polite"`; `role="alert"` en errores y denegaciones |
| W-11 | 1.4.1 Uso del color | Cumple | Estados con título en texto, borde e insignia, no solo color |
| W-12 | 3.1.1 Idioma | Cumple | `lang="es"` |
| W-13 | 1.4.10 Reflujo (320 px) | Cumple | Captura `m-05-consulta-320.png` sin desplazamiento horizontal |
| W-14 | 2.5.5 Tamaño del objetivo (AAA, guía interna 44 px) | Cumple | `min-height: 44px` en campos, botones y `summary` |

## Heurísticas de Nielsen

| # | Heurística | Resultado | Hallazgo |
|---|---|---|---|
| N-1 | Visibilidad del estado | Cumple | Carga, éxito, vacío, denegado y error diferenciados |
| N-2 | Coincidencia con el mundo real | **Parcial** | Fechas en hora local sin zona; nacimiento en ISO (H-02, H-03) |
| N-3 | Control y libertad | Cumple | Cancelar/Esc en el diálogo; «Ocultar datos» |
| N-4 | Consistencia | **Parcial** | Formatos de fecha distintos (H-03) |
| N-5 | Prevención de errores | **Parcial** | «Abrir expediente…» visible para roles que recibirán 403 (H-05) |
| N-6 | Reconocer antes que recordar | Cumple | Ejemplos de código en la ayuda |
| N-7 | Flexibilidad | Cumple | Enter para consultar; acción «Consultar expediente» tras abrir |
| N-8 | Diseño minimalista | Cumple | Motivos plegados; ficha mínima |
| N-9 | Recuperarse de errores | **Parcial** | «Sin vínculo» no dice a quién acudir (H-08) |
| N-10 | Ayuda | Cumple | Ayuda permanente bajo el campo |

## Protección de datos sensibles (criterios del módulo)

| # | Control | Resultado | Hallazgo |
|---|---|---|---|
| P-1 | Datos de otro paciente nunca junto a un código distinto | **No cumple** | H-01 |
| P-2 | Datos no quedan en pantalla sin supervisión | **No cumple** | H-07 |
| P-3 | Sin datos clínicos en mensajes de error | Cumple | N-04b, N-12 |
