# Presentación de defensa - ASII-09

## Diapositiva 1 - Problema y objetivo

- Consolidar ocupación por sala y estado de cama.
- Diferenciar ocupación clínica de estado operativo.
- Consultar sin asignar, trasladar, liberar ni identificar pacientes.

**Mensaje:** el dashboard informa; ASII-06/07/08 mantienen los hechos.

## Diapositiva 2 - Regla central

Una cama está ocupada en `T` si existe una estancia con:

```text
started_at <= T AND (ended_at IS NULL OR ended_at > T)
```

`beds.status` ayuda a operar, pero no sustituye el intervalo. Una diferencia se cuenta y se muestra como inconsistencia.

## Diapositiva 3 - Arquitectura defendida

```text
Cliente -> API HTTP -> Controller -> ConsultOccupancy
                                  -> OccupancyCalculator
                                  -> BedSnapshotReader -> PDO/PostgreSQL
                                                         -> InMemory
```

- Presentation traduce HTTP.
- Application coordina.
- Domain clasifica y consolida.
- Persistence obtiene una fotografía.

## Diapositiva 4 - Caso práctico

MED-01 figura disponible pero tiene estancia activa: se clasifica ocupada y genera inconsistencia. MED-04 figura ocupada sin estancia: se clasifica no verificada y genera otra inconsistencia.

La tasa es `1 ocupada / (1 ocupada + 1 disponible) = 50 %`; mantenimiento, inactiva y no verificada no inflan la capacidad operativa.

## Diapositiva 5 - Seguridad y fallos

| Situación | Respuesta |
|---|---|
| Sin token | `401` |
| Otro hospital | `403` |
| Corte inválido | `400` |
| PostgreSQL no disponible | `503`, nunca ceros |

El request ID permite correlación. El prototipo no envía datos clínicos identificables.

## Diapositiva 6 - Decisión de microservicio

No separar todavía:

- no existen métricas de carga o latencia;
- ASII-09 lee tablas de tres módulos;
- separar ahora agrega red sin autonomía de datos.

Primero se estabiliza la API y se mide. Una réplica de lectura por eventos solo se evalúa si se cumple un umbral verificable.

## Diapositiva 7 - Evidencia y cierre

- 12 verificaciones locales.
- PDO preparado e InMemory intercambiables.
- Contrato versionado y tres diagramas de Semana 5.
- Cambios semanales menores de 400 líneas.

**Cierre:** cada cama se cuenta exactamente una vez y toda inconsistencia permanece visible y trazable.
