# Frontera de microservicio y migración

## Propiedad de datos

| Datos | Propietario | ASII-09 |
|---|---|---|
| `wards`, `beds` | ASII-06 | Solo lectura |
| `bed_stays` abiertos por admisión | ASII-07 | Solo lectura |
| `bed_stays` cerrados por traslado/alta | ASII-08 | Solo lectura |
| Resumen calculado | ASII-09 | Propietario de la representación, no del hecho clínico |

## Evaluación medible

| Criterio para separar | Umbral propuesto | Estado actual |
|---|---:|---|
| Carga sostenida | > 50 consultas/s durante 4 semanas | No medida |
| Latencia | p95 > 500 ms durante 4 semanas | No medida |
| Despliegue independiente | > 1 despliegue semanal propio | No existe |
| Disponibilidad diferenciada | SLO propio >= 99.9 % | No acordado |
| Autonomía de datos | Sin lecturas directas de tablas ajenas | No: depende de 06/07/08 |

**Decisión actual: mantenerlo como módulo cliente-servidor del HIS.** Extraerlo hoy convertiría el esquema compartido en acoplamiento remoto sin dar autonomía. La frontera futura razonable incluye solo la lectura y materialización del resumen; ASII-09 nunca adquiere las escrituras de camas o estancias.

## Comunicación y fallos

El cliente consulta sincrónicamente porque necesita una respuesta inmediata. Si el servidor falla, muestra la última fotografía con su antigüedad y una advertencia. No se responde con ceros. Una futura réplica se alimentaría con eventos versionados `BedChanged` y `BedStayChanged`; eventos duplicados se procesan por `event_id` y el resumen conserva `source_updated_at` para exponer rezago.

## Migración razonada

1. **API modular actual:** estabilizar `/api/v1/occupancy`, JWT, tenant, logs y métricas.
2. **Medición:** observar cuatro semanas de p95, carga, errores, rezago e inconsistencias.
3. **Vista materializada local:** reducir costo de consulta sin introducir red; retorno inmediato a lectura directa si diverge.
4. **Eventos en paralelo:** construir réplica de lectura sin cambiar al consumidor y conciliarla diariamente.
5. **Extracción condicional:** solo si se cumple un umbral, enrutar mediante gateway con patrón estrangulador. Ante degradación se vuelve al módulo mientras la base HOSPITAL siga siendo la fuente.

Esta secuencia evita una migración irreversible y exige evidencia antes de añadir consistencia eventual, broker y operación independiente.
