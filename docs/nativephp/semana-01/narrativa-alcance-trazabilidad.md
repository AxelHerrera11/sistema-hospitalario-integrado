# Narrativa de alcance y trazabilidad - NativePHP

## Proposito

Evaluar de forma controlada si **NativePHP** puede usarse como una extension movil/escritorio del Sistema Hospitalario Integrado para ejecutar un flujo acotado sin comprometer seguridad, mantenibilidad ni consistencia con la aplicacion web principal.

## Alcance funcional

**Incluido**

- Seleccion de un flujo HIS pequeno y verificable para prototipo.
- Revision de prerequisitos tecnicos: PHP, Laravel, NativePHP, sistema operativo y empaquetado.
- Definicion de criterios de viabilidad: seguridad, rendimiento, experiencia de usuario, mantenimiento e integracion.
- Configuracion inicial del prototipo o simulacion documentada si el entorno no permite ejecucion completa.
- Consumo de datos ficticios o API controlada.
- Registro de hallazgos, limitaciones y riesgos.
- Recomendacion final: viable, viable con restricciones o no recomendado.

**Fuera de alcance**

- Publicar una aplicacion movil/escritorio de produccion.
- Reemplazar la aplicacion web Laravel/Vue.
- Guardar credenciales reales o datos clinicos sensibles en almacenamiento local.
- Implementar sincronizacion offline completa.
- Empaquetado formal para tiendas de aplicaciones.
- Cambios de infraestructura del HIS.

## Supuestos y restricciones

- El prototipo usa datos ficticios o anonimizados.
- La autenticacion puede simularse si el modulo real de autenticacion aun no esta estable.
- El flujo piloto debe ser pequeno: por ejemplo consulta de pendientes, dashboard simple o lectura de resultados.
- Cualquier almacenamiento local debe justificarse y evitar informacion sensible.
- La decision de viabilidad debe basarse en evidencia observable, no solo en opinion.

## Trazabilidad entre los diagramas

| UC | Objetivo | Actividad (decision o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-NP-01 | Definir flujo piloto | "Seleccionar flujo HIS acotado" | 1-2 |
| UC-NP-02 | Verificar entorno NativePHP | Decision "Entorno cumple prerequisitos" | 3-6 |
| UC-NP-03 | Configurar prototipo | "Crear estructura o simulacion del prototipo" | 7-9 |
| UC-NP-04 | Ejecutar flujo HIS | "Consumir datos ficticios/API controlada" | 10-13 |
| UC-NP-05 | Registrar hallazgos | "Documentar evidencia y limitaciones" | 14-15 |
| UC-NP-06 | Evaluar seguridad y riesgos | Decision "Riesgos aceptables" | 16-18 |
| UC-NP-07 | Emitir decision de viabilidad | "Clasificar resultado final" | 19-21 |
| UC-NP-08 | Reportar al lider tecnico | "Entregar informe y recomendacion" | 22-23 |

## Flujo principal

1. El investigador tecnico selecciona un flujo HIS pequeño para evaluar.
2. Define criterios de exito y riesgos esperados.
3. Verifica si el entorno local cumple prerequisitos de NativePHP.
4. Si el entorno no cumple, documenta bloqueo y alternativa de simulacion.
5. Configura un prototipo acotado o una maqueta tecnica.
6. Ejecuta el flujo con datos ficticios o API controlada.
7. Registra hallazgos de experiencia, seguridad, rendimiento e integracion.
8. Emite recomendacion de viabilidad para que el lider tecnico decida continuidad.

## Resultados y errores esperados

- `Viable`: el flujo se ejecuta con restricciones manejables.
- `Viable con restricciones`: requiere ajustes de seguridad, empaquetado, rendimiento o integracion.
- `No recomendado`: los riesgos o costos superan el beneficio para el HIS.
- `Bloqueado por entorno`: no se pudo ejecutar NativePHP, pero se documenta causa y alternativa.

## Dependencias con otros modulos

- **API Gateway o contratos API:** define como se consumen servicios desde la extension.
- **Gestion de usuarios y autenticacion:** provee identidad y tokens.
- **Dashboard base UI/UX:** aporta patrones visuales reutilizables.
- **QA y CI:** valida que el prototipo pueda probarse de forma repetible.
- **Seguridad:** revisa manejo de credenciales, cache local y datos sensibles.

## Archivos relacionados

- [Casos de uso](./01-casos-de-uso.svg)
- [Actividades](./02-actividad.svg)
- [Secuencia](./03-secuencia.svg)
- [README del modulo](./README.md)
