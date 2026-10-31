# ASII-23 Semana 12 — Segunda evaluación parcial

**Issue:** `Refs #152` · **Estudiante:** María Yamilet Lindo Pablo (`Yamilet1235`).

## Entregables

| Requisito | Evidencia |
|---|---|
| Presentación breve | [`presentacion.pdf`](./presentacion.pdf) (6 diapositivas). Fuente: [`fuentes/presentacion.md`](./fuentes/presentacion.md) |
| Recorrido navegable | Pantalla real en `/reportes`; pasos en `../semana-11/README.md` |
| Matriz decisión → RF/RNF → evidencia | [`matriz-decisiones.md`](./matriz-decisiones.md) |
| Respuesta documentada a un cambio | [`cambio-practico.md`](./cambio-practico.md): **ejercicio simulado** |

**No realizado:** la exposición, la evaluación docente y la prueba con usuarios.

**Retirado:** la tabla «Rubro de la semana» de la versión anterior. Sus pesos (25, 30, 25 y 20 %) no provienen de ninguna rúbrica publicada. Si el docente publica una, se agrega con su fuente.

## Guion de 7 minutos

1. **Regla (40 s):** IND-RA-01 y estados.
2. **Recorrido (2 min):** formulario, ayuda y resultado de enero.
3. **Errores (2 min):** periodo invertido sin cifra y N/A con su razón.
4. **Móvil (1 min):** captura a 375 px o emulador.
5. **Matriz (1 min):** tres decisiones con su prueba.
6. **Cierre (20 s):** qué falta probar.

## Preguntas probables

- **¿Evita el 0 % inventado?** Sí: sin datos publica un estado; el cero real se muestra.
- **¿Puede quedar una cifra vieja?** No: `limpiar()` corre antes de validar y al cambiar un filtro.
- **¿Aislamiento entre hospitales?** Hay una base por hospital y un guard que rechaza al que no es dueño. Elegir la base según el hospital sigue pendiente en Plataforma/Tenants.

Regenerar el PDF: `npx @marp-team/marp-cli@4.1.2 docs/reportes-analytics/semana-12/fuentes/presentacion.md --pdf --allow-local-files -o docs/reportes-analytics/semana-12/presentacion.pdf`
