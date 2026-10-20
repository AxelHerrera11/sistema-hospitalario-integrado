# ASII-03 Pacientes - Semana 04

## Arquitectura en capas y patrón Repository

La Semana 04 evoluciona el Micro-HIS de Pacientes iniciado en la Semana 03 sin modificar el esquema de base de datos.

El flujo continúa cubriendo:

- registro de paciente;
- búsqueda por código;
- actualización segura.

## Arquitectura

Se mantiene la separación entre Presentation, Application, Domain y Persistence.

La entrada se organiza mediante MVC.

PatientRepository funciona como puerto de persistencia utilizado por Application.

Se implementan dos adaptadores:

- PdoPatientRepository: persistencia real mediante PDO.
- InMemoryPatientRepository: persistencia desacoplada para pruebas y desarrollo.

PatientController no contiene SQL ni reglas de dominio.

PatientView transforma el resultado del dominio a una estructura de salida.

## Base de datos

No se modifican migraciones.

El módulo continúa utilizando la tabla real local_patients.

La persistencia PDO mantiene sentencias preparadas.

## Validación

php -l micro-his/pacientes/src/Persistence/InMemoryPatientRepository.php
php -l micro-his/pacientes/src/Presentation/PatientView.php
php -l micro-his/pacientes/src/Presentation/PatientController.php
php -l micro-his/pacientes/tests/run.php
php micro-his/pacientes/tests/run.php

## Alcance

El contrato REST/HTTP se trabajará en la Semana 05.
