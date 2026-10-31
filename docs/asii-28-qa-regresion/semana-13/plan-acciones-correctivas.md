# Plan de acciones correctivas

Propuesta de seguimiento, no ejecución completada. Refs #12.
Fuente de cada acción: [hallazgos](./hallazgos-tecnicos.md); estado inicial **Pendiente**.
P1 = condición previa de integración/integridad, P2 = habilitación/fiabilidad/validación,
P3 = mantenibilidad acotada. No son fechas ni asignaciones aprobadas.
Todos los roles correctores y verificadores siguientes: **Pendiente de asignación**.
Madelin Cerón mantiene documentación/evidencia propia y actualizará estados con respaldo;
no sustituye al revisor independiente ni asigna compromisos a otros estudiantes.

| Acción / hallazgo | Prioridad / acción propuesta | Responsable de corregir (rol propuesto) | Criterio de cierre y evidencia requerida | Quién verifica el cierre | Estado / seguimiento sugerido |
|---|---|---|---|---|---|
| AC-01 / REV-01 | P1: acordar propiedad tenant y aplicar aislamiento antes de persistencia compartida | Seguridad + backend QA, Pendiente de asignación | Modelo de ámbito revisado; consultas/escrituras con contexto autorizado; pruebas aisladas tenant A/B impiden lectura/escritura cruzada. Adjuntar diff y resultados, no basta diagrama | Revisor técnico independiente + revisión de seguridad, Pendiente de asignación | Pendiente; Semana 14, previo a exposición HTTP |
| AC-02 / REV-02 | P1: especificar y conectar frontera de autenticación/autorización del host | Seguridad + backend QA, Pendiente de asignación | Política/permisos aprobados y tests con identidad ausente/inválida/sin permiso/con permiso; datos protegidos, errores sin detalles privados; evidencia de ejecución | Revisor técnico independiente, Pendiente de asignación | Pendiente; Semana 14, coordinar AC-01 |
| AC-03 / REV-03 | P1: planificar contratos y dominio de campaña/ejecución/defecto; implementar incrementos autorizados | Backend QA + pruebas, Pendiente de asignación | RF-02–08 y RNF con vínculos, comparación declarada coherente, rechazo defecto desde aprobado, historia intacta y batería común de registro; commits/tests reales y matriz actualizada | Revisor técnico independiente, Pendiente de asignación | Pendiente; Semana 14 definir secuencia, implementación posterior según alcance aprobado |
| AC-04 / REV-04 | P2: revisar contrato REST y adaptar alta/consulta tras AC-01/02 | Backend QA + seguridad, Pendiente de asignación | Rutas verificables, status/payload/errores y límites de entrada contractuales; tests HTTP positivos/negativos y seguridad; no aceptar solo resultados CLI | Revisor técnico independiente, Pendiente de asignación | Pendiente; Semana 14 revisión, exposición bloqueada hasta AC-01/02 |
| AC-05 / REV-05 | P3: revisar proyección explícita propuesta en #1402 sin incorporarla automáticamente | Backend QA, Pendiente de asignación | Cuatro claves públicas intencionales, compatibilidad del sobre/valores comprobada; prueba de caracterización y diff revisados; integración solo mediante proceso autorizado | Revisor técnico independiente, Pendiente de asignación | Pendiente; Semana 14 revisar PR existente |
| AC-06 / REV-06 | P2: normalizar fallos de composición CLI sin filtrar detalle interno | Backend QA + pruebas, Pendiente de asignación | Fallos de conexión/esquema inducidos solo en entorno aislado con salida/código seguro acordado; sin SQL/path/stack sensible; suite normal continúa pasando | Revisor técnico independiente, Pendiente de asignación | Pendiente; Semana 14 diseñar casos de fallo |
| AC-07 / REV-07 | P2: definir almacenamiento durable de pruebas y recuperación/evidencia privada | Backend QA + pruebas, Pendiente de asignación | Registros sobreviven reinicio, relaciones/historia se conservan y recuperan con acceso controlado; prueba entre procesos y recuperación documentada sin datos clínicos | Revisor técnico independiente, Pendiente de asignación | Pendiente; Semana 14 diseño condicionado a AC-01; implementación según planificación |
| AC-08 / REV-08 | P2: revisión de tareas/accesibilidad con protocolo autorizado | Frontend QA + pruebas, Pendiente de asignación | Evidencia de teclado completo, lector, zoom, errores/anuncios y descarte con cambios; registrar navegador/herramienta/resultados. UX-06 real exige contrato/recuperación, no simular garantía | Revisor técnico independiente, Pendiente de asignación | Pendiente; Semana 14 planificar; participantes solo con autorización |

## Reglas de cierre

1. Madelin registra evidencia/versiones y estado propuesto en su documentación; el corrector entrega resultados y limitaciones.
2. Revisor independiente por asignar repite el criterio y documenta dictamen; no basta escribir “corregido”.
3. Si falta asignación, evidencia o verificación, la acción permanece pendiente y el hallazgo abierto.
4. Revisar primero AC-01/02/03; no publicar endpoints ni conectar datos compartidos para aparentar avance.
5. Una revisión documental no demuestra seguridad, integridad o accesibilidad funcionales; aprobación del PR documental tampoco cierra automáticamente estos hallazgos.

No se fija fecha de aprobación ni se asignan tareas a personas reales sin respaldo.
