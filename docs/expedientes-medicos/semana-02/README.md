# Semana 02 — Proceso y modelo de diseño; principios SOLID

**Tarea:** aplicar DIP al diseño del flujo «apertura y consulta autorizada del expediente longitudinal»; definir
RF/RNF y criterios de aceptación; mostrar el diseño antes/después y justificar responsabilidades y dependencias con
evidencia verificable.

## Entregables

| Archivo | Contenido |
|---|---|
| [`requisitos.md`](requisitos.md) | RF-EXP-01…06, RNF-EXP-01…08 y criterios de aceptación CA-01…CA-10 enlazados a pruebas |
| [`dip-antes-despues.md`](dip-antes-despues.md) | Diseño antes/después, tabla de puertos y adaptadores, justificación por capa |
| [`01-dip-antes.svg`](01-dip-antes.svg) · [`fuentes/01-dip-antes.mmd`](fuentes/01-dip-antes.mmd) | Diseño inicial descartado (no implementado; se marca así) |
| [`02-dip-despues.svg`](02-dip-despues.svg) · [`fuentes/02-dip-despues.mmd`](fuentes/02-dip-despues.mmd) | Diseño implementado con las interfaces reales de `src/Application` |
| [`evidencias/arquitectura-y-dip.txt`](evidencias/arquitectura-y-dip.txt) | Salida real de las pruebas de arquitectura y del mismo flujo con dos adaptadores |

## Ubicación del código

`micro-his/expediente-medico/src/Application/*.php` (interfaces y casos de uso) y
`micro-his/expediente-medico/src/{Persistence,Integration}` (implementaciones). Ver Semana 3 para la estructura completa.

## Cómo verificar

```bash
php micro-his/expediente-medico/tests/run.php | grep -E "R-0|N-01|Resumen"
```

Debe mostrar R-01…R-07 en `OK`, N-01 en `OK` para `[pdo]` y `[memoria]`, y `0 FALLO`.

## Estado

- **Implementado:** el diseño «después» completo y sus pruebas.
- **Supuestos:** S-01 (vínculo asistencial en la consulta) y S-02 (permiso de apertura) — ver Semana 1.
