# Checklist de heurísticas y WCAG — Semana 9

Objeto: el prototipo `micro-his/traslados-altas/public/ui/` de #859. Método: sondas automáticas en Chrome ([evidencia/sondas-ux.mjs](./evidencia/sondas-ux.mjs), [salida](./evidencia/salida-sondas.txt)) e inspección del código. **No hubo pruebas con usuarios.**

| # | Criterio | Fuente | Resultado | Evidencia |
|---|---|---|---|---|
| 1 | Visibilidad del estado del sistema | Nielsen 1 | No cumple | S8: el éxito no muestra la nueva estancia ni la hora (H6) |
| 2 | Lenguaje del usuario | Nielsen 2 | Cumple | Los motivos de rechazo se traducen a mensajes claros (`mensajes.js`) |
| 3 | Control y libertad | Nielsen 3 | Cumple | «Cancelar» y «Volver» en el formulario y en la confirmación |
| 4 | Prevención de errores | Nielsen 5 | Parcial | Hay validación local y confirmación obligatoria, pero el motivo queda cargado (S9, H7) |
| 5 | Reconocer antes que recordar | Nielsen 6 | No cumple | S7: hay que teclear el uuid de la cama (H5) |
| 6 | Ayudar a reconocer errores | Nielsen 9 | No cumple | S2: un actor faltante se informa como «La estancia no existe» (H1) |
| 7 | Teclado y orden del foco | WCAG 2.1.1, 2.4.3 | Parcial | S1 cumple el orden; S3: tras consultar, el foco no va a la situación (H2) |
| 8 | Foco visible | WCAG 2.4.7 | Cumple | `:focus-visible` con contorno de 3 px (inspección de `estilos.css`) |
| 9 | Contraste mínimo | WCAG 1.4.3 | Cumple | S6: ayuda 8,81; error 6,47; primario 6,70; secundario 11,89 |
| 10 | Etiquetas e instrucciones | WCAG 3.3.2 | Parcial | Todos los campos tienen `label`; S5: cama y motivo no se declaran obligatorios (H4) |
| 11 | Identificación de errores | WCAG 3.3.1, 4.1.2 | No cumple | S4: sin `aria-invalid` ni `role=alert` (H3) |
| 12 | Mensajes de estado | WCAG 4.1.3 | Parcial | El resultado está en `aria-live`; S3: la consulta exitosa no anuncia nada (H2) |
| 13 | Título de página | WCAG 2.4.2 | Cumple | S10 |
| 14 | Idioma de la página | WCAG 3.1.1 | Cumple | `<html lang="es">` (inspección) |
