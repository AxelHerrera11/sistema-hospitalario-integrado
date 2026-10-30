# Prototipo navegable — Semana 11

Prototipo: `micro-his/traslados-altas/public/ui/` (#859 y #866), servido con `php -S` junto a la API real. Las capturas se tomaron el 21/10/2026 en Chrome 155 sin interfaz contra PostgreSQL 17.10 desechable sembrado con `database/demo.sql`.

## Mapa de navegación

```mermaid
flowchart LR
    P1["1. Paciente internado<br/>(actor y estancia)"] -->|Consultar 200| P2["2. Situación actual"]
    P1 -->|"datos inválidos"| P1
    P1 -->|"404 / 503"| AV["Aviso en la región de resultado"]
    P2 -->|Trasladar| P3T["3. Datos del traslado"]
    P2 -->|Dar de alta| P3A["3. Alta hospitalaria"]
    P3T -->|Revisar| P4["4. Confirmar"]
    P3A -->|Revisar| P4
    P3T -->|Cancelar| P2
    P4 -->|Volver| P3T
    P4 -->|Confirmar| R["Resultado"]
    R -->|"éxito de traslado"| SEG["Operar sobre la nueva estancia"] --> P2
    R -->|"incierto"| CON["Consultar estado"] --> P2
    R -->|"rechazo o 503"| P1
```

## Camino feliz: traslado (escritorio 1024 px y móvil 375 px)

| Paso | Escritorio | Móvil |
|---|---|---|
| 1. Inicio | ![](./capturas/01-inicio-1024.png) | ![](./capturas/01-inicio-375.png) |
| 2. Situación | ![](./capturas/02-situacion-1024.png) | ![](./capturas/02-situacion-375.png) |
| 4. Confirmación | ![](./capturas/04-confirmacion-1024.png) | ![](./capturas/04-confirmacion-375.png) |
| Resultado | ![](./capturas/06-exito-1024.png) | ![](./capturas/06-exito-375.png) |

El mismo recorrido (`../semana-10/evidencia/recorrido-ui.mjs`) siguió con el alta de la nueva estancia: **9/9 en cada formato**.

## Error crítico: HOSPITAL inaccesible

| Escritorio | Móvil |
|---|---|
| ![](./capturas/error-critico-1024.png) | ![](./capturas/error-critico-375.png) |

[`evidencia/error-critico.mjs`](./evidencia/error-critico.mjs) (2/2): la consulta responde **503**. La interfaz no muestra datos ni acciones e indica que no se guardó ningún cambio y que se puede reintentar.
