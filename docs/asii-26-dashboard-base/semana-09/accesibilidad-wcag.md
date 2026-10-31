# Cumplimiento de accesibilidad (WCAG 2.2 AA) - ASII-26

| Criterio | Nivel | Nombre | Resultado | Evidencia |
|---|---|---|---|---|
| 1.1.1 | A | Contenido no textual | Cumple | Sin imagenes; iconos de estado acompanados de texto. |
| 1.3.1 | A | Informacion y relaciones | Cumple | Encabezados por pantalla, `th` en tablas, `fieldset/legend` en grupos. |
| 1.4.1 | A | Uso del color | Cumple | Estados con texto + icono + color. |
| 1.4.3 | AA | Contraste minimo | Cumple | `contraste.php`: todos los pares >= 4.5:1. |
| 1.4.10 | AA | Reflow | Cumple | Sin scroll horizontal a 375 px; tablas como tarjetas. |
| 1.4.11 | AA | Contraste no textual | Cumple | Borde de campos y foco >= 3:1. |
| 2.1.1 | A | Teclado | Cumple | Todos los controles son enlaces o campos nativos. |
| 2.4.1 | A | Evitar bloques | Cumple | Enlace "Saltar al contenido". |
| 2.4.3 | A | Orden del foco | Cumple | Orden del DOM = orden visual; el foco pasa a la pantalla abierta. |
| 2.4.7 | AA | Foco visible | Cumple | `:focus-visible` con contorno de 3 px. |
| 2.5.8 | AA | Tamano del objetivo | Cumple | Botones y enlaces de menu de 44 px de alto. |
| 3.1.1 | A | Idioma | Cumple | `<html lang="es">`. |
| 3.3.1 | A | Identificacion de errores | Cumple | Mensajes de error en texto junto al campo. |
| 3.3.2 | A | Etiquetas o instrucciones | Cumple | Etiqueta visible en todos los controles; `*` obligatorio. |
| 4.1.2 | A | Nombre, funcion, valor | Cumple | Controles nativos; dialogo con `role=alertdialog`. |
| 4.1.3 | AA | Mensajes de estado | Cumple | Alertas con `role=status` / `role=alert`. |

## Contraste de color (`php evidencia/contraste.php`)

```text
  [CUMPLE] Texto principal sobre fondo              #0f172a / #f8fafc  17.06:1 (min 4.5)
  [CUMPLE] Texto secundario sobre fondo             #475569 / #f8fafc   7.24:1 (min 4.5)
  [CUMPLE] Boton primario (blanco sobre azul)       #ffffff / #1d4ed8   6.70:1 (min 4.5)
  [CUMPLE] Encabezado (blanco sobre azul oscuro)    #ffffff / #1e3a8a  10.36:1 (min 4.5)
  [CUMPLE] Menu activo                              #1e3a8a / #dbeafe   8.49:1 (min 4.5)
  [CUMPLE] Enlace / badge informativo               #1d4ed8 / #ffffff   6.70:1 (min 4.5)
  [CUMPLE] Badge exito                              #15803d / #ffffff   5.02:1 (min 4.5)
  [CUMPLE] Badge aviso                              #b45309 / #ffffff   5.02:1 (min 4.5)
  [CUMPLE] Badge / texto de error                   #b91c1c / #ffffff   6.47:1 (min 4.5)
  [CUMPLE] Boton deshabilitado                      #475569 / #e2e8f0   6.15:1 (min 4.5)
  [CUMPLE] Borde de campo de formulario             #64748b / #ffffff   4.76:1 (min 3.0)
  [CUMPLE] Contorno de foco sobre fondo             #1d4ed8 / #f8fafc   6.41:1 (min 3.0)

12 pares evaluados, 0 por debajo del minimo.
```

## Auditoria automatica del prototipo (`php evidencia/auditoria-accesibilidad.php`)

```text
  [CUMPLE] 3.1.1 idioma de la pagina (lang)
  [CUMPLE] 2.4.2 titulo de la pagina
  [CUMPLE] 2.4.1 enlace para saltar al contenido
  [CUMPLE] 1.3.1 / 3.3.2 controles con etiqueta (1)
  [CUMPLE] 1.3.1 cada pantalla tiene encabezado (5)
  [CUMPLE] 1.3.1 tablas con encabezados th
  [CUMPLE] 1.1.1 imagenes con alt
  [CUMPLE] 4.1.3 alertas con role status/alert
  [CUMPLE] 2.4.7 estilo de foco visible definido
  [CUMPLE] 2.5.8 objetivo tactil >= 24px (usa 44px)
  [CUMPLE] 1.4.10 reflow: media query movil
  [CUMPLE] 2.1.1 controles deshabilitados explicados sin depender de title

12 chequeos, 0 a revisar.
```

La auditoria automatica no reemplaza la prueba manual con teclado y lector de pantalla (NVDA),
que se agenda junto con las sesiones de usuarios.
