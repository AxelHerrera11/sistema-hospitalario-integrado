# Presentación de defensa - Segundo parcial ASII-09

## Diapositiva 1 - Problema

- Decidir con ocupación agregada por sala y corte temporal.
- Hacer visibles discrepancias entre estancia y estado operativo.
- Nunca identificar pacientes ni modificar camas.

**Mensaje:** informar con contexto es más seguro que mostrar cifras aisladas.

## Diapositiva 2 - Componentes

```text
Vista -> Store -> Cliente API -> Controller -> Caso de uso
                                          -> Domain
                                          -> Repository -> PostgreSQL
```

La UI presenta; Domain clasifica; Repository toma la fotografía.

## Diapositiva 3 - Estados UX

- Carga sin cifras engañosas.
- Éxito con corte y antigüedad.
- Vacío confirmado por respuesta `200`.
- Dato obsoleto claramente rotulado.
- Falla sin fotografía, sin ceros.
- `401/403` sin datos residuales.

## Diapositiva 4 - Evaluación

- Ocho hallazgos priorizados.
- Principales brechas: teclado, contraste, reflow y semántica.
- La inspección fue documental; no equivale a certificación WCAG.

## Diapositiva 5 - Movilidad

- Desde 320 px, una columna y tarjetas por sala.
- Objetivos táctiles de 44x44 px.
- Misma jerarquía y significado en desktop/móvil.
- Sin scroll horizontal para datos críticos.

## Diapositiva 6 - Demostración

1. Filtrar UCI.
2. Simular `503` con fotografía previa.
3. Simular `403` y comprobar limpieza.
4. Cambiar a 320 px y recorrer con teclado.

## Diapositiva 7 - Cambio solicitado

Agregar `Priorizar inconsistencias` como preferencia de presentación. No cambia cálculo, contrato API ni totales. Debe operar con teclado, conservar orden estable y desaparecer ante denegación.

## Diapositiva 8 - Cierre

- 12 verificaciones del Micro-HIS aprobadas.
- Prototipo local con datos ficticios y sintaxis JS validada.
- Evidencia pendiente declarada: filtro/priorización en Vue, limpieza del estado tras `401/403`, lector de pantalla y navegador.

**Cierre:** una interfaz confiable no oculta la incertidumbre; la explica sin alterar la fuente de verdad.
