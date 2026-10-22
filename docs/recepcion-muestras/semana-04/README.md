# Semana 4 — MVC, Repository y datos compartidos

**Módulo:** ASII-18, recepción de muestras y códigos identificadores.
**Autor de la entrega:** María de los Ángeles López Fajardo, Mar-03.
**Issue:** [ASII-18 #6](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/6).
**Objetivo de la matriz:** Continuar el mismo microproyecto, organizar entrada MVC y analizar con diagrama el repositorio compartido.

## Índice

1. Introducción y alcance.
2. Desarrollo del entregable y decisiones.
3. Aplicación al microproyecto y al HIS.
4. Verificación, límites y conclusión.
5. Fuentes y evidencia Git.

## Introducción y continuidad

Se conserva la carpeta micro-his/recepcion-muestras de semana 3, sus reglas y su esquema.
La evolución modifica la organización de entrada y la sustituibilidad del almacenamiento.
No se entrega un segundo micro incompatible ni un controlador que mezcla SQL con reglas clínicas.

## MVC aplicado al flujo

| Elemento | Implementación | Límite |
|---|---|---|
| Vista | JSON generado por demo.php y, en semana 5, respuesta HTTP | No decide aceptación ni consulta tablas directamente. |
| Controlador | Presentation/ReceptionController::store | Valida tipos/campos, invoca un caso de uso y traduce errores. |
| Modelo de aplicación | Application/ReceiveSpecimen | Coordina orden, identidad, validador y persistencia. |
| Modelo de dominio | SampleValidator y SpecimenCode | Mantiene reglas y representación del identificador. |
| Repository | Application/ReceptionRepository | Oculta mecanismos de almacenamiento al caso de uso. |

El controlador no contiene sentencias SQL ni umbrales de volumen o temperatura.
El nombre de un archivo no basta para probar MVC: las llamadas y responsabilidades anteriores
son el criterio verificable. demo.php es la raíz de composición y serializa la respuesta.

## Contrato Repository y adaptadores

~~~mermaid
classDiagram
  class ReceptionRepository {
    <<interface>>
    +transaction(operation)
    +order(tenant,item)
    +save(sample)
    +find(tenant,code)
  }
  ReceptionRepository <|.. InMemoryReceptionRepository
  ReceptionRepository <|.. PdoReceptionRepository
  ReceiveSpecimen --> ReceptionRepository
  ReceptionController --> ReceiveSpecimen
~~~

InMemory usa colecciones y copia del estado al entrar a transaction.
PDO utiliza tablas PostgreSQL, sentencias preparadas y commit/rollback.
Ambos conservan el significado de ausencia de orden y muestra, y propagan fallos de escritura.
No se agregan métodos SQL al contrato solo porque un adaptador los necesita.

## Análisis de un repositorio compartido

~~~mermaid
flowchart TD
  Ordenes[Órdenes] --> Maestros[Datos maestros de orden]
  Catalogo[Catálogo] --> Requisitos[Requisitos de prueba]
  Maestros --> Gateway[Lectura de órdenes]
  Requisitos --> Gateway
  Recepcion[Recepción] --> Muestras[Registro inmutable de muestras]
  Gateway --> Hospital[(HOSPITAL)]
  Muestras --> Hospital
  Resultados[Ingreso de resultados] -->|consulta por código| Recepcion
~~~

Compartir una base física permite una transacción local y reduce latencia.
Compartir escrituras de todas las tablas, en cambio, acopla reglas y permite saltarse al propietario.
Por eso recepción escribe solo specimens y eventos en outbox_events; consulta los requisitos de Órdenes/Catálogo mediante un adaptador de lectura validado.
Ingreso de resultados consume una API de lectura y no muta el estado de recepción.

## Implementación equivalente en Laravel 12

ReceptionController consume un caso de uso propio en app/Modules/Reception/Application.
PdoReceptionRepository se ubica en Infrastructure y obtiene la conexión hospital de Laravel.
La migración del HIS carga database/sql/reception/schema.sql, sin leer el SQL del micro.
El frontend Vue es la vista real y Axios transporta los DTO JSON del contrato API.

## Caso práctico y verificación

Ejecutar demo.php muestra una recepción con datos ficticios y un código persistible en el doble.
Cambiar patient_name por otra persona debe producir rejected y no un código.
Activar fail en InMemory provoca error de persistencia y restaura el estado.
La prueba PostgreSQL del micro demuestra que el mismo límite transaccional existe con SQL real.
La prueba de integridad del HIS induce un fallo del outbox para evitar una aceptación parcial.

## Conclusión

MVC organiza entrada y presentación; Repository protege al caso de uso de detalles de almacenamiento.
La integración de datos se diseña por propiedad y contrato, sin conceder escrituras cruzadas indiscriminadas.

## Fuentes y evidencia Git

- [README original: plan, matriz ASII-18 y Definition of Done](../../../README.md).
- [Declaración de uso de IA y validación humana](../DECLARACION_IA.md).
- [Índice de semanas y archivos](../README.md).
- [Ramas, PR y destino develop](../semana-15/ramas-prs.md).
- Rama de entrega: feature/asii-18-semana-04-recepcion-muestras-mar-03.
- La referencia reunida y probada es la rama semana-15-recepcion-muestras-integracion-mar-03.

## Corrección de integración — 21 de octubre de 2026

El micro vanilla es un ejercicio académico aislado en un esquema temporal; Laravel usa su propio dominio y el esquema canónico HOSPITAL.
La tabla propia del HIS es specimens (migración 000601, propuesta de Javier); lab_order_item_id es UUID lógico sin FK a Órdenes. Las FK locales son indexadas y RESTRICT. Se reutiliza outbox_events.
El módulo finalizado de Gerson se conserva; esta entrega no cambia Resultados/Validación. El contrato de lectura con ASII-16 debe verificarse con su proveedor; si no está disponible se responde 503 sin guardar muestras.
La evidencia vigente y sus límites están en [semana 15](../semana-15/README.md). Esta corrección sigue en local, sin push ni cambio de estado de PR.
