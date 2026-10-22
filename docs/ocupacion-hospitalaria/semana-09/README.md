# Semana 9 - Evaluación de usabilidad y accesibilidad

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Entrega:** checklist, hallazgos y mejoras priorizadas

## Alcance y método

Se evaluaron los seis wireframes y el flujo por rol de Semana 8 mediante una inspección heurística. La revisión usa las diez heurísticas de Nielsen y criterios aplicables de WCAG 2.1 nivel AA. No se afirma haber realizado pruebas con usuarios, navegador, lector de pantalla ni frontend implementado.

- [Diseño evaluado](../semana-08/README.md)
- [Declaración de IA](./DECLARACION_IA.md)

Escala de severidad:

| Nivel | Definición | Tratamiento |
|---|---|---|
| Crítica | Impide una decisión segura o expone información no autorizada | Resolver antes del prototipo |
| Alta | Bloquea una tarea principal o excluye a un grupo de usuarios | Resolver en Semanas 10-11 |
| Media | Genera duda o esfuerzo evitable, pero existe alternativa | Incorporar al prototipo |
| Baja | Mejora de consistencia o comprensión | Mantener en backlog trazable |

## Checklist de usabilidad

| Criterio | Estado | Evidencia o brecha |
|---|---|---|
| Estado del sistema visible | Cumple | Carga, corte, antigüedad y actualización están definidos |
| Lenguaje del dominio | Parcial | `No verificada` requiere explicación cercana |
| Control y libertad | Cumple | Reintento y restablecimiento del filtro son explícitos |
| Consistencia | Parcial | Falta fijar formato único para fecha y antigüedad |
| Prevención de errores | Cumple | Actualizar se deshabilita durante la solicitud |
| Reconocer antes que recordar | Parcial | La regla de discrepancia no acompaña todos los estados |
| Eficiencia | Parcial | El flujo no define selección de sala por teclado |
| Diseño mínimo | Cumple | Solo muestra agregados necesarios para decidir |
| Recuperación ante errores | Cumple | `503`, dato obsoleto y reintento no se confunden con cero |
| Ayuda contextual | Parcial | Falta acceso persistente a definiciones y soporte por request ID |

## Checklist de accesibilidad

| Criterio WCAG 2.1 AA | Estado | Evidencia o brecha |
|---|---|---|
| 1.1.1 Alternativas textuales | Parcial | Iconos tienen texto en wireframes; faltan nombres accesibles finales |
| 1.3.1 Información y relaciones | Pendiente | El prototipo debe usar encabezados, tabla o listas semánticas |
| 1.3.2 Secuencia significativa | Pendiente | Debe comprobarse el orden al adaptar tarjetas en móvil |
| 1.4.1 Uso del color | Cumple | Advertencias combinan icono, texto y cantidad |
| 1.4.3 Contraste mínimo | Pendiente | Debe medirse texto normal `>= 4.5:1` |
| 1.4.10 Reflow | Pendiente | Semana 10 debe evitar scroll horizontal a 320 px |
| 1.4.11 Contraste no textual | Pendiente | Controles y foco requieren `>= 3:1` |
| 2.1.1 Teclado | Pendiente | Filtro, recarga y retorno deben operar sin puntero |
| 2.4.3 Orden del foco | Pendiente | Debe seguir título, estado, filtros, resumen y salas |
| 2.4.7 Foco visible | Parcial | La regla existe, pero aún no hay componente medible |
| 3.3.1 Identificación de errores | Cumple | Falla, vacío y acceso denegado tienen mensajes distintos |
| 4.1.2 Nombre, función y valor | Pendiente | Controles finales necesitan etiqueta y estado programáticos |
| 4.1.3 Mensajes de estado | Parcial | Se propone `aria-live`, falta definir mensajes exactos |

## Hallazgos

| ID | Severidad | Hallazgo | Riesgo |
|---|---|---|---|
| H-01 | Alta | No existe orden de foco ni patrón de teclado documentado | Una persona que usa teclado puede perder el filtro o la recarga |
| H-02 | Alta | Contraste y foco solo están declarados, no medidos | Texto o selección pueden resultar imperceptibles |
| H-03 | Alta | El diseño de escritorio no garantiza reflow a 320 px | Totales o discrepancias pueden quedar fuera de pantalla |
| H-04 | Media | `No verificada` carece de definición inmediata | Puede interpretarse como cama disponible o dato inválido |
| H-05 | Media | No se especifican mensajes anunciados por `aria-live` | La actualización puede pasar inadvertida sin visión |
| H-06 | Media | La tabla no define caption ni encabezados asociados | La relación sala-métrica puede perderse con tecnología asistiva |
| H-07 | Media | Fecha de corte y antigüedad usan formatos variables | Aumenta el esfuerzo para decidir si el dato sigue vigente |
| H-08 | Baja | El request ID no tiene ruta de soporte definida | El usuario recibe un dato técnico sin saber cómo usarlo |

No se halló una brecha crítica: los wireframes ya separan falla, vacío, dato obsoleto y denegación, y no muestran pacientes. Esta conclusión es documental y deberá revalidarse en el prototipo.

## Mejoras priorizadas

| Prioridad | Mejora | Criterio de aceptación |
|---:|---|---|
| 1 | Definir reflow y jerarquía móvil | A 320 px se consultan corte, totales, discrepancias y reintento sin scroll horizontal |
| 2 | Diseñar navegación completa por teclado | Tab sigue un orden lógico, Enter/Espacio activan controles y el foco nunca se pierde |
| 3 | Establecer tokens de contraste y foco | Texto normal alcanza 4.5:1; foco y controles, 3:1; foco visible de al menos 2 px |
| 4 | Añadir semántica de resumen y salas | Hay un `h1`, regiones con nombre, tabla con caption/headers o tarjetas equivalentes |
| 5 | Precisar mensajes de estado | Carga, éxito, obsolescencia y error se anuncian una vez sin interrumpir la lectura |
| 6 | Explicar estados operativos | `No verificada`, `N/A` e inconsistencia tienen texto breve disponible junto al dato |
| 7 | Unificar temporalidad | Todas las vistas muestran corte absoluto y antigüedad relativa con zona horaria |
| 8 | Hacer accionable el request ID | El error indica conservar el código y el canal de soporte, sin revelar detalles internos |

## Escenarios de reevaluación

1. Coordinación entra solo con teclado, filtra UCI, identifica dos discrepancias y actualiza.
2. Dirección amplía al 200 % y compara salas sin perder información ni usar scroll en dos ejes.
3. Una respuesta `503` conserva una fotografía previa rotulada y anuncia su antigüedad.
4. Sin fotografía previa, la falla oculta métricas y mantiene reintento accesible.
5. Un `403` limpia datos anteriores y mueve el foco al título de acceso restringido.
6. Capacidad operativa cero presenta `N/A` con explicación, nunca `0 %` engañoso.

## Criterios de salida

- Los ocho hallazgos tienen severidad, riesgo y mejora trazable.
- Semanas 10 y 11 incorporan las prioridades 1 a 6.
- Ningún resultado documental se presenta como prueba con usuarios o conformidad WCAG certificada.
- Se conserva solo lectura, aislamiento por tenant y ausencia de identificadores clínicos.

## Evidencia

```bash
php micro-his/ocupacion-hospitalaria/tests/run.php
git diff --check
git diff --numstat HEAD^
```

## Conclusión

El flujo tiene una base segura, pero necesita evidencia concreta de teclado, contraste, semántica y reflow. Las mejoras convierten esas brechas en criterios observables para la propuesta móvil y el prototipo navegable.
