# Semana 3 — Microproyecto por capas y vista arquitectónica

**Módulo:** ASII-18, recepción de muestras y códigos identificadores.
**Autor de la entrega:** María de los Ángeles López Fajardo, Mar-03.
**Issue:** [ASII-18 #6](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/6).
**Objetivo de la matriz:** Micro PHP 8.2 vanilla, Presentation/Application/Domain/Persistence, PDO preparado y tres caminos de prueba.

## Índice

1. Introducción y alcance.
2. Desarrollo del entregable y decisiones.
3. Aplicación al microproyecto y al HIS.
4. Verificación, límites y conclusión.
5. Fuentes y evidencia Git.

## Introducción y alcance

Se inicia un microproyecto ejecutable del mismo flujo de negocio de semana 1: recibir, identificar,
aceptar o rechazar una muestra. No se crea un formulario vacío ni se exige infraestructura distribuida.
El reloj se entrega al servicio para reproducir estabilidad sin depender de la hora del equipo.

## Vista de contexto y contenedores

~~~mermaid
flowchart LR
  Tecnico[Técnico] --> Presentation
  Presentation --> Application
  Application --> Domain
  Application --> Repository[ReceptionRepository]
  Repository --> InMemory[Adaptador InMemory]
  Repository --> PDO[Adaptador PDO preparado]
  PDO --> Hospital[(PostgreSQL del micro)]
  Ordenes[Órdenes y catálogo confiables] --> Hospital
~~~

Los actores externos aportan identidad esperada y requisitos. El cliente aporta únicamente lo observado.
Presentation valida forma y traduce respuestas; Application decide el flujo; Domain aplica invariantes;
Persistence representa el almacenamiento. Una falla de impresora queda fuera de la transacción de recepción.

## Estructura real

| Capa | Archivo bajo micro-his/recepcion-muestras | Responsabilidad |
|---|---|---|
| Composición | bootstrap.php y demo.php | Autoload del namespace Reception y construcción del caso de uso. |
| Presentation | src/Presentation/ReceptionController.php | Validar campos, llamar al servicio y traducir 201/404/409/422/500. |
| Application | src/Application/ReceiveSpecimen.php | Orden disponible, dos identificadores, validación, código y guardado. |
| Puerto | src/Application/ReceptionRepository.php | Transacción, lectura de orden, guardado y consulta por código. |
| Domain | src/Domain/SampleValidator.php | Reglas físicas comunes a los tres tipos. |
| Domain | src/Domain/SpecimenCode.php | Aleatoriedad y dígito de comprobación. |
| Persistence | src/Persistence/PdoReceptionRepository.php | SQL parametrizado, bloqueo, inserción y rollback. |
| Persistence | src/Persistence/InMemoryReceptionRepository.php | Doble ejecutable con fallo inducible y restauración del estado. |
| Datos | schema.sql | Integridad, unicidad de código y una aceptación por ítem. |

## Recorrido del camino feliz

1. El controlador recibe un ítem, código/nombre observados, contenedor, tipo, volumen, temperatura y fecha.
2. El servicio lee la orden del tenant y comprueba su disponibilidad.
3. Compara ambos identificadores y valida requisitos del catálogo proyectado.
4. Genera un código solo si no hay motivo de rechazo.
5. Guarda muestra y cambia ítem dentro de una transacción.
6. Responde 201; una etiqueta se imprime únicamente después de la persistencia.

## SQL, errores y consistencia

PdoReceptionRepository prepara las consultas y enlaza valores; no concatena el identificador del paciente.
order aplica tenant_id e item_id y FOR UPDATE en PostgreSQL.
save inserta muestra, actualiza orden y agrega outbox si rejected.
transaction confirma al terminar el callback y revierte ante cualquier Throwable.
InMemory reproduce el límite transaccional para probar el flujo sin una base instalada.

## Evidencia reproducible

Desde la raíz: php micro-his/recepcion-muestras/tests/run.php.
El runner cubre éxito, reglas de dominio, aislamiento, consulta, duplicidad, código y fallo de persistencia.
[Salida conservada](../evidencias/micro-php.txt): 39 comprobaciones.
La prueba adicional tests/postgres.php usa PostgreSQL real y un esquema rx_micro_* desechable.
[Desarrollo de la persistencia](persistencia-verificada.md) detalla setup, rollback y límites de esa prueba.
No se sustituye PostgreSQL por SQLite ni se presenta InMemory como evidencia de comportamiento de SQL.

## Diferencia con la implementación real

El ejercicio no se importa desde el HIS. Laravel tiene clases propias en app/Modules/Reception,
migración en database y controlador API autenticado. El micro mantiene un alcance académico menor:
la recuperación por request_key y el reintento selectivo robusto pertenecen a la implementación del HIS.
La evolución HTTP vanilla de semana 5 continúa exactamente esta carpeta y este esquema.

## Conclusión

La separación permite demostrar un cambio de almacenamiento sin mover las reglas al controlador.
El micro aporta fuente ejecutable y pruebas; el diagrama explica dependencias que se pueden verificar en archivos.

## Fuentes y evidencia Git

- [README original: plan, matriz ASII-18 y Definition of Done](../../../README.md).
- [Declaración de uso de IA y validación humana](../DECLARACION_IA.md).
- [Índice de semanas y archivos](../README.md).
- [Ramas, PR y destino develop](../semana-15/ramas-prs.md).
- Rama de entrega: feature/asii-18-semana-03-recepcion-muestras-mar-03.
- La referencia corregida es el worktree local recepcion-integracion-javier, integrado desde develop y las 18 ramas.

## Corrección de integración — 21 de octubre de 2026

El micro vanilla es un ejercicio académico aislado en un esquema temporal; Laravel usa su propio dominio y el esquema canónico HOSPITAL.
La tabla propia del HIS es specimens (migración 000601, propuesta de Javier); lab_order_item_id es UUID lógico sin FK a Órdenes. Las FK locales son indexadas y RESTRICT. Se reutiliza outbox_events.
El módulo finalizado de Gerson se conserva; esta entrega no cambia Resultados/Validación. El contrato de lectura con ASII-16 debe verificarse con su proveedor; si no está disponible se responde 503 sin guardar muestras.
La evidencia vigente y sus límites están en [semana 15](../semana-15/README.md). Esta corrección sigue en local, sin push ni cambio de estado de PR.
