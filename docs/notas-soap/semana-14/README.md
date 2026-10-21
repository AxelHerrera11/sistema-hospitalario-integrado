# Notas SOAP y diagnósticos — Semana 14: plan de aseguramiento de la calidad (SQA)

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-11 — Notas SOAP y diagnósticos · **Entrega:** plan SQA con siete métricas, matriz de riesgos, controles preventivos y detectivos, responsables y calendario de seguimiento.

## Ficha de la asignación

- **Semana:** Semana 14 — Aseguramiento de la calidad del software (SQA)
- **Tarea individual:** Defina el plan SQA de Notas SOAP y diagnósticos: calidad de «registro de nota SOAP y asociación de diagnóstico clínico», confiabilidad, seguridad, trazabilidad y riesgos de notas SOAP, diagnósticos, autoría clínica, versionado y expediente. Asigne responsable, umbral, fuente y acción para cada métrica y control.
- **Entregable / evidencia:** plan SQA con 5–7 métricas, matriz de riesgos, controles preventivos/detectivos, responsables y calendario de seguimiento.
- **Issue:** Refs #22
- **Alcance:** plan. **No se implementa código ni se modifica la base de datos**: lo que requiere una migración queda como acción para quien coordina ASII-09.

## Objetivo de calidad

Una nota SOAP es la evidencia clínica de una atención. El plan SQA busca que **ninguna nota firmada cambie sin dejar una versión, ningún diagnóstico quede sin autor ni traza, y ninguna persona sin relación con el episodio lea o escriba**. Se mide con siete indicadores que salen de fuentes que ya existen (pruebas, bitácora de acceso, tablas del módulo) y no de impresiones.

## Atributos de calidad y su evidencia

| Atributo | Qué se asegura | Requisito | Métrica |
|---|---|---|---|
| **Corrección funcional** | Las reglas de la nota y del diagnóstico se cumplen siempre | RF-SOAP-01 a 07 | Q-01 |
| **Calidad del código** | Los defectos se encuentran antes de integrar | Revisión formal de la Semana 13 | Q-02 |
| **Confiabilidad** | Ante una caída se falla seguro y no queda una nota a medias | RNF-SOAP-03 | Q-03 |
| **Rendimiento** | Asociar un diagnóstico es rápido durante la consulta | RNF-SOAP-04 | Q-04 |
| **Seguridad** | Solo accede quien tiene permiso, tenant y asignación | RNF-SOAP-01 | Q-05 |
| **Integridad clínica** | Nota firmada inmutable, un solo principal, sin duplicados | RNF-SOAP-02 | Q-06 |
| **Trazabilidad y privacidad** | Cada escritura tiene su auditoría, sin texto clínico | RNF-SOAP-05 | Q-07 |

Las métricas, con su fórmula, umbral, fuente, responsable y acción, están en [metricas.md](./metricas.md). Los riesgos, los controles y el calendario, en [riesgos-controles.md](./riesgos-controles.md).

## Roles

| Rol | Responsabilidad en el plan | Quién |
|---|---|---|
| **Responsable del módulo (autor)** | Ejecuta las métricas Q-01 a Q-04, mantiene el plan y reporta | Josué Fernando Hicho García |
| **Revisora de PR** | Verifica tamaño (menos de 400 líneas), pruebas y checklist en cada PR | Yamilet |
| **Moderador de revisiones** | Convoca y cierra las revisiones formales (Semana 13) | Por designar |
| **Base de datos (ASII-09)** | Migraciones de triggers e índices; ejecuta las consultas de integridad | Javier |
| **Seguridad y permisos (ASII-02)** | Catálogo `notas_soap.*`, identidad, tenant | María |
| **QA y regresión (ASII-28)** | Pruebas negativas, accesibilidad y regresión | Equipo de QA |

Los nombres de Javier, María, Yamilet y el equipo de QA vienen de las dependencias ya documentadas en las semanas anteriores; **nadie ha aceptado todavía las tareas de este plan**, así que cada asignación queda «por confirmar» hasta que respondan.

## Reglas del proceso

1. **Un PR, menos de 400 líneas propias**, sale de `develop` y lleva revisora. Es el control preventivo más barato del plan.
2. **No se integra** con una métrica en rojo: se anota la acción de [metricas.md](./metricas.md) y se corrige primero.
3. **Todo hallazgo** crítico de una revisión se cierra con una prueba que antes fallaba.
4. **Las consultas de integridad** se ejecutan sobre una copia o con un usuario de solo lectura: el plan nunca escribe en datos clínicos.
5. **Cada cambio al plan** pasa por PR y deja constancia del motivo.

## Qué no cubre este plan

- La corrección de los hallazgos de la revisión formal: se hacen en PR aparte.
- Firma y corrección de notas: están en el contrato y no en el código; sus métricas se añaden cuando existan.
- Rendimiento de otros módulos y disponibilidad de la infraestructura.

## Archivos

- [Métricas de calidad (Q-01 a Q-07)](./metricas.md)
- [Matriz de riesgos, controles y calendario](./riesgos-controles.md)
- [Declaración de uso de IA](./DECLARACION_IA.md)

> Este PR sale de `develop` y no enlaza a las semanas anteriores: las menciona por número de semana y de PR, porque aún están sin integrar. Los documentos de referencia son la Semana 2 (RF/RNF, PR #65), la Semana 5 (contrato, PR #374), la Semana 6 (arquitectura, PR #411), la Semana 9 (accesibilidad, PR #441) y la Semana 13 (revisión técnica, PR #533).
