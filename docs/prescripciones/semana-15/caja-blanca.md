# Diseño de caja blanca — `PROPUESTO`

## Decisiones y ramas a cubrir

- Sin conflicto: comprobar recorrido de éxito.
- `mild`: comprobar la rama de advertencia.
- `moderate` y `unknown`: comprobar la rama de rechazo con permiso indicado
  por el contrato, sin ejecutar persistencia.
- `severe` y `life_threatening`: comprobar rechazo sin excepción y sin
  persistencia.
- Error de lectura de alergias: comprobar que la rama de error termina el
  flujo y no se interpreta como lista vacía.
- Error de escritura: comprobar que el fallo no se convierte en éxito.

## Evidencia futura

Al implementar, registrar cada condición/ramificación que realmente se
ejercitó y la herramienta usada. No se declara cobertura alcanzada ni un
porcentaje objetivo en este diseño.
