# Formato de hallazgos

## Plantilla

```markdown
### H-XX — Título corto y verificable

- **Artefacto / ubicación:** archivo, clase o ruta
- **Evidencia:** comando, prueba, captura o línea revisada
- **Esperado:** comportamiento o contrato acordado
- **Observado:** comportamiento real
- **Impacto:** qué riesgo introduce y a quién afecta
- **Severidad:** Bloqueante / Mayor / Menor / Observación
- **Recomendación:** cambio concreto y verificable
- **Responsable:** autor u owner
- **Fecha límite:** AAAA-MM-DD
- **Estado:** Nuevo / Confirmado / En corrección / Cerrado / Descartado
```

## Severidad

| Severidad | Criterio | Ejemplo |
|---|---|---|
| Bloqueante | Integridad rota, fuga entre tenants, cancelación de una orden validada o dato clínico expuesto | Un `UPDATE` permite `VALIDATED → CANCELLED` |
| Mayor | Contrato incumplido, permiso ausente, historial mutable o prueba crítica faltante | La API acepta motivos que el OpenAPI prohíbe |
| Menor | Documentación incompleta, mensaje confuso o deuda de mantenibilidad | Falta un ejemplo de error en el README |
| Observación | Mejora sin impacto inmediato | Renombrar una variable |

## Ciclo de vida

1. **Nuevo:** lo registra cualquier revisor con evidencia.
2. **Confirmado:** el moderador acepta la severidad y el alcance.
3. **En corrección:** el responsable tiene fecha y prueba de regresión.
4. **Cerrado:** la evidencia demuestra la corrección y el checklist se actualiza.
5. **Descartado:** se documenta por qué no aplica; no se borra el registro.

## Ejemplo de hallazgo (borrador de revisión)

### H-01 — La API acepta motivos de cancelación fuera del rango 5–250

- **Artefacto / ubicación:** `src/Application/CancelLabOrder.php`; `src/Domain/LabOrder.php`;
  `docs/ordenes-laboratorio/semana-05/openapi.yaml`.
- **Evidencia:** el contrato declara `reason` con `minLength: 5` y `maxLength: 250`; el dominio solo
  rechaza `null`/cadena vacía y no valida longitud. Una invocación directa con `"x"` o con 300
  caracteres no es rechazada por el caso de uso.
- **Esperado:** `422`/`CANCELLATION_REASON_INVALID` para longitudes fuera de rango, con prueba en
  límites 4/5/250/251.
- **Observado:** el caso de uso acepta cualquier cadena no vacía; la validación de longitud solo está
  en el contrato y en el diálogo del prototipo.
- **Impacto:** motivos pobres o excesivos debilitan la auditoría y generan deriva contrato–código.
- **Severidad:** Mayor (contrato y trazabilidad).
- **Recomendación:** validar longitud en dominio o aplicación, agregar pruebas de límites y alinear
  el OpenAPI.
- **Responsable:** autor.
- **Fecha límite:** acordar en la sesión.
- **Estado:** Nuevo.

> Este ejemplo proviene de una inspección preliminar; debe confirmarse en la sesión y no se presenta
> como corregido.
