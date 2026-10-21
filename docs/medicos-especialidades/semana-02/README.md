# Médicos, especialidades y disponibilidad — Semana 2

## Ficha de la asignación

- **Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`)
- **Módulo:** ASII-04 — Médicos, especialidades y disponibilidad
- **Semana:** Semana 2 — Proceso y modelo de diseño; principios SOLID
- **Principio aplicado:** ISP — *Interface Segregation Principle* (segregación de interfaces)
- **Tarea individual:** Aplique ISP al diseño del flujo «registro de médico, asociación de especialidad y consulta de disponibilidad». Defina RF/RNF y criterios de aceptación; muestre el diseño antes/después, justifique responsabilidades y dependencias y aporte evidencia verificable.
- **Entregable / evidencia:** RF/RNF, criterios de aceptación y ejemplo SOLID.
- **Fuente obligatoria:** Rodríguez González, L. M. *Principios básicos del diseño de software*. MVP Cluster. <https://mvpcluster.com/diseno-de-software-2/>

## Resumen

Se definen siete RF y cinco RNF medibles del alta, la asociación de especialidades y la consulta de disponibilidad, con escenarios de aceptación. ISP se aplica dividiendo una interfaz `DoctorDirectory` de ocho métodos en seis interfaces pequeñas, una por necesidad de cliente. Así, la disponibilidad (solo lectura) no depende de métodos de escritura y la vista de agenda del módulo *Citas* no tiene que implementar el alta. Una demo PHP lo mide.

## Archivos de evidencia

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Aplicación de ISP (diseño antes y después)](./solid-isp.md)
- [Evidencia verificable](./evidencia-verificable.md)
- [Demo ejecutable](./evidencia/demo-isp.php): `php docs/medicos-especialidades/semana-02/evidencia/demo-isp.php`
- [Declaración de uso de IA](./DECLARACION_IA.md)
- Base de esta semana: [UML y narrativa de la Semana 1](../semana-01/README.md)

## Alcance

**Incluido:** RF/RNF trazados a UC-MED-01 a UC-MED-07, criterios Gherkin, diseño antes/después con ISP (diagramas Mermaid dentro de [solid-isp.md](./solid-isp.md)) y demo PHP 8.2+ sin framework.

**Fuera de alcance:** tabla de horario (`doctor_schedules`, pendiente del acuerdo C1), reserva de citas (módulo *Citas*), capas del micro-HIS (Semana 3), Repository con PDO (Semana 4) e integración con Laravel, PostgreSQL o Vue.
