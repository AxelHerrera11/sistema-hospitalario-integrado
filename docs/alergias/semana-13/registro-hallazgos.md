# Registro de hallazgos — ASII-12

Severidad: **Alta** (puede dañar datos clínicos o la seguridad del paciente), **Media** (defecto o incoherencia que afecta la integración), **Baja** (cosmético o de estilo).

| # | Severidad | Área | Hallazgo | Estado |
|---|---|---|---|---|
| RT-01 | Media | Código | El kernel no tenía título para el 405 que devuelve el controller (índice indefinido) | Cerrado antes de publicar la Semana 5 |
| RT-02 | Baja | Diagrama | En la vista de componentes de la Semana 3, la flecha hacia el dominio cruzaba la base y parecía salir de ella | Cerrado (commit «ubicar la base dentro de Persistence») |
| RT-03 | Media | Código | El front controller usaba la zona UTC de PHP | Cerrado (commit «usar la zona horaria del hospital») |
| RT-04 | Alta | Integración | Prescripciones debe tratar `unavailable` como bloqueo o confirmación manual; hoy solo está acordado por escrito | Abierto: verificar con María |
| RT-05 | Media | Datos | El código de alérgeno no está unificado con el catálogo de medicamentos | Abierto: acordar con Yamilet |

Los hallazgos cerrados vienen de la autorrevisión de la reentrega y citan el commit que los corrigió. Los abiertos son el punto de partida de la revisión formal.
