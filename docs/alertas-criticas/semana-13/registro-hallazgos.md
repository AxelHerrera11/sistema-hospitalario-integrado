# Registro de hallazgos — ASII-21

Severidad: **Alta** (puede dañar datos clínicos o la seguridad del paciente), **Media** (defecto o incoherencia que afecta la integración), **Baja** (cosmético o de estilo).

| # | Severidad | Área | Hallazgo | Estado |
|---|---|---|---|---|
| RT-01 | Media | Prueba | La prueba de vencimiento dependía de la zona horaria de la máquina | Cerrado (reloj con `America/Guatemala`) |
| RT-02 | Media | Documentación | La matriz de la Semana 12 citaba un RNF de privacidad que la Semana 2 v2 ya no tiene | Cerrado (commit «alinear la matriz») |
| RT-03 | Media | Código | El front controller usaba la zona UTC de PHP para los plazos | Cerrado (commit «usar la zona horaria del hospital») |
| RT-04 | Alta | Integración | El formato del evento «resultado validado» no está confirmado por ASII-20 | Abierto: confirmar con Gerson |
| RT-05 | Media | Diseño | La notificación y el escalamiento siguen como propuesta de *worker* | Abierto |

Los hallazgos cerrados vienen de la autorrevisión de la reentrega y citan el commit que los corrigió. Los abiertos son el punto de partida de la revisión formal.
