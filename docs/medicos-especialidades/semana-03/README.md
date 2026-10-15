# Médicos, especialidades y disponibilidad — Semana 3

## Ficha de la asignación

- **Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`)
- **Módulo:** ASII-04 — Médicos, especialidades y disponibilidad
- **Semana:** Semana 3 — Diseño arquitectónico, vistas y patrones
- **Tarea individual:** Inicie «Micro-HIS Médicos, especialidades y disponibilidad» en PHP 8.2+ vanilla para ejecutar «registro de médico, asociación de especialidad y consulta de disponibilidad». Separe Presentation, Application, Domain y Persistence; use PDO con sentencias preparadas, sin framework, y pruebe al menos camino feliz, regla de dominio y error de persistencia mediante dobles o una base de prueba.
- **Entregable / evidencia:** Vista arquitectónica C4/UML del módulo y dependencias. Código en [`micro-his/medicos-especialidades/`](../../../micro-his/medicos-especialidades/).

## Resumen

El micro-HIS ejecuta los tres pasos del flujo con cuatro capas. `DoctorHttpHandler` (Presentation) traduce la petición a los casos de uso `RegisterDoctor`, `AssociateSpecialty` y `QueryAvailability` (Application). Las reglas de vigencia, especialidad duplicada, principal única y franjas libres viven en Domain sin dependencias. `PdoDoctorStore` (Persistence) implementa los puertos con PDO y sentencias preparadas. Los puertos son los seis que salieron de aplicar ISP en la [Semana 2](../semana-02/solid-isp.md).

## Archivos de evidencia

- [Vista arquitectónica C4/UML](./arquitectura-c4.md): contexto, contenedores, componentes y puertos.
- [Dependencias](./dependencias.md): regla entre capas, matriz caso de uso → puertos y supuestos externos.
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Cómo verificar

```bash
php micro-his/medicos-especialidades/tests/run.php
```

Base de prueba: SQLite en memoria (`pdo_sqlite`) con `database/schema.sql` y datos ficticios. Resultado con PHP 8.3.33 (código de salida 0):

```text
OK: camino feliz: alta 201 con CARD principal y auditoría
OK: camino feliz: asociar NEURO secundaria -> 201
OK: camino feliz: disponibilidad del lunes -> Ana libre 08:00 y 09:00 (08:30 confirmada)
OK: RF-MED-07: médico sin horario aparece con SCHEDULE_NOT_CONFIGURED
OK: RF-MED-06: especialidad sin médicos -> 200 NO_DOCTORS_FOR_SPECIALTY
OK: regla de dominio: misma especialidad en periodo solapado -> 409
OK: regla de dominio: otra principal en el mismo periodo -> 409
OK: regla de dominio: vigencia fuera del perfil -> 422
OK: regla de dominio: especialidad inactiva -> 422
OK: regla de dominio: licencia repetida -> 409
OK: validación: fecha inválida -> 422
OK: regla de dominio pura (sin BD): la política rechaza dos principales
OK: error de persistencia: auditoría caída -> 500 y rollback del perfil
OK: ISP: la disponibilidad no depende de puertos de escritura
Todas las pruebas pasaron.
```

## Organización de la entrega

Para respetar el límite de 400 líneas por PR, la semana se entrega en tres ramas creadas desde `develop`, con archivos que no se solapan:

| Rama | Contenido | Orden de integración |
|---|---|---|
| `feature/asii-04-semana-03-medicos-especialidades-core-jhos-hgnu` | `bootstrap.php`, Domain y Application | 1 |
| `feature/asii-04-semana-03-medicos-especialidades-adapters-tests-jhos-hgnu` | Persistence, Presentation, `schema.sql` y pruebas | 2 |
| `feature/asii-04-semana-03-medicos-especialidades-arquitectura-jhos-hgnu` | Esta documentación | 3 |

Las pruebas se ejecutan cuando las ramas 1 y 2 están integradas.
