# ASII-23 Semana 18 — Evaluación final

**Issue:** `Refs #152` · **Estudiante:** María Yamilet Lindo Pablo (`Yamilet1235`).

**Estado:** preparada. La demostración final y la defensa individual **no han ocurrido** y no se registran hasta que ocurran. El detalle del requisito en Odoo no está disponible: por confirmar.

## Guion de la demostración (8 minutos)

| # | Acción | Resultado esperado |
|---|---|---|
| 1 | Abrir `/login` con el UUID del hospital y un usuario ficticio | Entra al sistema |
| 2 | Menú «Reportes» | Formulario con ayuda del intervalo |
| 3 | Del 01/01/2026 al 01/02/2026, sin sala | `25.00 %` con leyenda y trazabilidad |
| 4 | Agregar la sala `HOSP-UCI` | Desaparece la cifra anterior; al calcular, `33.33 %` |
| 5 | Invertir las fechas | «Desde debe ser anterior a hasta.», sin cifra y sin llamada a la API |
| 6 | Del 01/01/2019 al 01/02/2019 | `N/A` con su razón |
| 7 | Entrar con recepción | Sin entrada «Reportes» |
| 8 | Entrar con un miembro del hospital B | «Este hospital no está disponible en esta instalación.» |
| 9 | Opcional, por terminal: `DELETE` a la ruta | 405 |

## Matriz de evidencia

| Tema | Evidencia |
|---|---|
| Regla y requisitos | `semana-01/`, `semana-02/` |
| Arquitectura | `semana-03/`, `semana-04/`, `semana-05/`, `semana-07/` |
| Experiencia | `semana-08/` a `semana-11/` |
| Parciales preparados | `semana-06/presentacion.pdf`, `semana-12/presentacion.pdf` |
| Calidad | `semana-13/`, `semana-14/`, `semana-15/` |
| Integración y seguridad | `semana-16/`, `semana-17/` |

## Preguntas de defensa

- **¿Por qué no se guarda el resultado?** Cada cálculo lee lo confirmado; no hay copia que pueda quedar desactualizada.
- **¿Qué defecto grave se encontró?** Un miembro válido de otro hospital podía leer el indicador; se corrigió con el guard de base.
- **¿Qué queda abierto?** Staging, enrutamiento por hospital, escritura de `cancelled`, tamaño mínimo de muestra, auditoría de lectura y límite de tasa.
- **¿Qué hizo la IA y qué hizo usted?** Responder según la declaración vigente y lo que usted haya revisado.

## Pendiente de la estudiante

1. Ejecutar el guion en el navegador y registrar qué revisó.
2. Probar con lector de pantalla y con un teléfono, si el docente lo pide.
3. Realizar la defensa.
