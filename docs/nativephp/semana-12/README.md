# Semana 12 | ASII-29: Defensa de arquitectura y UX NativePHP

## Recorrido

`Bandeja local -> Captura -> Cola -> Sincronización -> HIS -> Auditoría`

El prototipo demuestra el flujo HIS acotado, sin reemplazar reglas clínicas.
La aplicación conserva roles, estado, sincronización y riesgos de plataforma.

## Matriz decisión -> RF/RNF -> evidencia

| Decisión | Requisito | Evidencia |
|---|---|---|
| Separar UI y sincronización | RF: captura local y envío idempotente | Flujo y Semana 9 |
| Informar offline | RNF: recuperación sin pérdida | Responsive y Semana 10 |
| Mostrar estados de cola | RF: pendiente, enviando, error, sincronizado | Prototipo y Semana 11 |
| Minimizar caché | RNF: protección de datos clínicos | Reglas de privacidad |
| Condicionar viabilidad | RNF: validar permisos por plataforma | Riesgos y soporte |

## Cambio solicitado y respuesta

**Cambio:** demostrar captura offline sin confirmar viabilidad de NativePHP en un
equipo cuyo almacenamiento seguro no está habilitado.

**Respuesta:** la demostración usa una simulación claramente rotulada para el
estado `En cola`; no se presenta como ejecución real. En plataforma no habilitada
se bloquea persistencia clínica, se informa la restricción y se ofrece continuar
en HIS web. Con entorno válido, el reintento usa la misma clave idempotente.

## Criterios de defensa

- El recorrido muestra camino feliz, error y recuperación en desktop/móvil.
- La cola no duplica envíos y conserva trazabilidad del resultado.
- Roles, tenant, minimización y expiración de caché están documentados.
- Se distingue evidencia simulada de ejecución real y se mantienen restricciones.

## Evidencia

- Requisito Odoo: `Semana 12: Segunda evaluación parcial`.
- Entregable: recorrido navegable, matriz decisión→RF/RNF→evidencia y cambio respondido.
- La defensa conecta NativePHP, flujo HIS, capacidades nativas, sincronización y riesgo.
