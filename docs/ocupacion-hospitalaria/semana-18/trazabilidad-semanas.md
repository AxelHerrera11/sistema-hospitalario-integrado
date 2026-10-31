# Trazabilidad de las 18 semanas del módulo

Cada semana de Odoo corresponde a una entrega con su Pull Request. La columna «Estado» refleja
la situación verificada el 21 de octubre de 2026.

| Semana | Entregable | PR | Estado |
|---:|---|---:|---|
| 1 | Modelado UML del flujo de ocupación: casos de uso, actividad y secuencia | #45 | Fusionado |
| 2 | Diseño con ISP: RF/RNF, criterios de aceptación, diseño antes/después | #68 | Fusionado |
| 3 | Micro-HIS en PHP vanilla con capas Presentation, Application, Domain y Persistence | #171 | Fusionado |
| 4 | Arquitectura en capas y patrón Repository: MVC, puerto `BedSnapshotReader` y repositorio compartido | #872 | Fusionado |
| 5 | Cliente-servidor: contrato API, frontera de microservicio y migración razonada | #883 | Fusionado |
| 6 | Primera evaluación parcial: matriz decisión → evidencia, diagrama trazable y cambio práctico | #886 | Fusionado |
| 7 | Componentes backend/frontend, contratos de entrada/salida y comparación antes/después | #889 | Fusionado |
| 8 | Flujo UX por rol, seis wireframes y reglas de interacción | #875 | Fusionado |
| 9 | Usabilidad y accesibilidad: checklist, ocho hallazgos y backlog priorizado | #879 | Fusionado |
| 10 | Diseño responsive: jerarquía móvil, breakpoints y escenarios | #880 | Fusionado |
| 11 | Prototipo navegable desktop/móvil con capturas reales | #899 | Fusionado |
| 12 | Segunda evaluación parcial: recorrido navegable y respuesta al cambio solicitado | #901 | Fusionado |
| 13 | Revisión técnica formal: checklist de 30 ítems, roles y formato de acta | #881 | Fusionado |
| 14 | Plan SQA: métricas, riesgos, controles y calendario de seguimiento | #892 | Fusionado |
| 15 | Pruebas unitarias, de caja blanca y de caja negra trazadas a RF/RNF | #904 y #905 | Fusionado |
| 16 | Integración en Laravel, pantalla Vue y validación con datos reales | #574 y #575 | Fusionado |
| 17 | Seguridad y despliegue final: amenazas, permisos y bitácora | #579 | Fusionado |
| 18 | Evaluación final y defensa | Este PR | En revisión |

## Continuidad entre entregas

- **Semana 3 → 4:** el puerto `BedSnapshotReader` nace como sustituto de la lectura directa y sostiene
  todo lo demás.
- **Semana 5 → 16:** el contrato publicado en la semana 5 es el que implementa el endpoint; la
  versión preliminar de la semana 5 se conserva como trazabilidad.
- **Semana 8 → 10 → 11 → 16:** el flujo UX, la propuesta responsive y el prototipo anteceden a la
  pantalla Vue que los materializa.
- **Semana 9 → 13 → 14:** los ocho hallazgos de usabilidad alimentan el checklist de revisión
  técnica y las métricas del plan SQA.
- **Semana 13 → 17:** el plan de revisión técnica del módulo se aplicó a la entrega de seguridad
  y despliegue.

## Reglas que se mantuvieron en todas las entregas

1. Un PR por semana, con menos de 400 líneas modificadas.
2. Cada entrega se apoya en evidencia ejecutada, no declarada.
3. Nada se afirma como probado sin el comando y su salida.
4. Ninguna tabla nueva: el módulo lee `wards`, `beds` y `bed_stays`, propiedad de ASII-06/07/08.
5. Ningún PR se integra a mano: los revisa e integra Yamilet.
