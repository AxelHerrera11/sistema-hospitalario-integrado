# Traslados, altas y liberación de camas — Semana 8

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo · **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Destino:** `develop`
- **Asignación:** flujo de usuario por roles y 4–6 wireframes anotados. Deben cubrir inicio, decisión, confirmación, carga, vacío, éxito, errores recuperables, ayuda, validaciones y protección de datos.

## Contenido y cumplimiento

| Pedido | Evidencia | Estado |
|---|---|---|
| Flujo de usuario por roles | [flujo-usuario.md](./flujo-usuario.md) | Cumplido; los roles son una **propuesta** pendiente con RBAC |
| 4–6 wireframes anotados | [wireframes.md](./wireframes.md): 6 capturas reales del prototipo | Cumplido |
| Estados pedidos | Inicio, decisión, validaciones, confirmación, error recuperable y éxito con captura; vacío, carga e incierto descritos | Cumplido |
| Ayuda, validaciones y protección de datos | Ayuda junto a los campos, validación local y solo identificadores en pantalla | Cumplido |
| Prototipo | `micro-his/traslados-altas/public/ui/` (#859) | Prototipo funcional contra la API real |
| [Declaración de uso de IA](./DECLARACION_IA.md) | — | — |

## Cómo reproducir

```bash
# Base HOSPITAL desechable migrada y sembrada con micro-his/traslados-altas/database/demo.sql
TRASLADOS_PG_DSN="pgsql:host=127.0.0.1;port=55433;dbname=<base>" TRASLADOS_PG_PASSWORD=<clave local> \
  php -S 127.0.0.1:8792 -t micro-his/traslados-altas/public micro-his/traslados-altas/public/index.php
# Abrir http://127.0.0.1:8792/ui/index.html con el usuario 00000000-0000-4000-8000-0000000000f1 y la estancia …0000000000e1.
# Recorrido automático (requiere puppeteer-core y Chrome): node evidencia/recorrido-ui.mjs http://127.0.0.1:8792 <carpeta-capturas> 1024
```

Resultado registrado: 9/9 comprobaciones en Chrome 155 sin interfaz contra PostgreSQL 17.10.
