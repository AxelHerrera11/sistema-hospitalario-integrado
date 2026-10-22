# Semana 06 — Primera evaluación parcial

## Módulo
ASII-03 — Pacientes

## Responsable
Madelin Jazmín Cerón Molina

## Objetivo
Presentar y defender las decisiones tomadas durante las primeras cinco semanas del módulo Pacientes, mostrando trazabilidad entre requisitos, diseño, arquitectura, implementación y contrato de integración.

## Alcance evaluado
La evaluación comprende el registro, consulta y actualización segura de pacientes dentro del hospital.

Se consideran como base:
- actores y alcance del módulo;
- requisitos funcionales y no funcionales;
- reglas de dominio;
- arquitectura por capas;
- patrón Repository;
- separación MVC;
- persistencia mediante PDO;
- contrato API REST;
- integración con autenticación, tenant y RBAC.

## Evolución del módulo

### Semana 1
Se definieron actores, alcance y diagramas UML del flujo de pacientes.

### Semana 2
Se establecieron requisitos funcionales, requisitos no funcionales, criterios de aceptación y decisiones relacionadas con principios SOLID.

### Semana 3
Se implementó el micro-HIS en PHP con separación entre Presentation, Application, Domain y Persistence.

### Semana 4
Se incorporó Repository con adaptadores PDO e InMemory y se reforzó la separación MVC.

### Semana 5
Se documentó el contrato REST del módulo, sus endpoints, datos públicos, errores y estrategia de integración.

## Arquitectura defendida
El controlador recibe la solicitud y delega el caso de uso a la capa Application.

Application contiene la coordinación de los casos de uso y depende de una interfaz PatientRepository.

Domain concentra las reglas del paciente sin depender de HTTP ni de la base de datos.

Persistence implementa PatientRepository utilizando PDO y sentencias preparadas.

## Integración con el HIS
El módulo no implementa autenticación ni autorización propias.

Las rutas deben ejecutarse detrás de la cadena transversal del sistema:

tenant -> jwt.auth -> rbac:permiso -> controlador de Pacientes

Los permisos conciliados en el RBAC del proyecto son:
- pacientes.ver
- pacientes.registrar
- pacientes.editar

## Identidad del paciente
El identificador público del paciente es patient_id y corresponde internamente a local_patient_id.

El código del paciente y global_id son identificadores inmutables después del registro.

## Decisión sobre microservicio
Pacientes permanece como módulo lógico del HIS y no se separa actualmente como microservicio físico.

La decisión evita aumentar la complejidad de comunicación y consistencia mientras existen dependencias clínicas directas con otras tablas del hospital.

## Resultado de la evaluación
El módulo presenta una evolución trazable desde requisitos hasta contrato de integración y mantiene separadas las responsabilidades de dominio, aplicación, persistencia y presentación.
