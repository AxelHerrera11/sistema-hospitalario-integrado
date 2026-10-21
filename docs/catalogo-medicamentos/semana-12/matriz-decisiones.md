# Matriz decisión → RF/RNF → evidencia y cambio solicitado

## Recorrido navegable para la defensa

1. Abrir `docs/catalogo-medicamentos/semana-11/prototipo/index.html` en Chrome, con datos simulados y sin servidor.
2. **Camino feliz (gestor):** elegir "Gestor", buscar "amox", pulsar "Inactivar" en AMX-500, comprobar que el foco está en Cancelar, confirmar y leer "Vigencia actualizada".
3. **Error crítico:** marcar "Simular catálogo no disponible" y buscar; aparece la alerta con Reintentar sin datos ni estado anterior.
4. **Riesgo de roles:** volver a "Usuario clínico"; desaparecen las acciones de escritura.
5. **Móvil:** repetir a 360 px (herramientas de desarrollo); resultados en tarjetas.
6. Reproducir todo de forma automática: `node docs/catalogo-medicamentos/semana-11/evidencia/verificar-prototipo.mjs` (24/24).

## Matriz

| Decisión | RF/RNF | Evidencia |
|---|---|---|
| Un único cliente HTTP y una página contenedora | RNF-04 (mantenibilidad) | `semana-07/componentes.md` |
| Acciones de escritura solo para el gestor; lectura con `medicamentos.ver` | RF-07, RNF-01 | `config/rbac.php` (lectura); D6/M6 del prototipo (escritura simulada) |
| Confirmación con el registro completo y foco en Cancelar | RF-06, RF-08 | D7, D8, M7 y M8 |
| Vigencia en texto, nunca solo color | RF-04, WCAG 1.4.1 | D4 y M4 |
| Nombres accesibles por código y `role="status"` | WCAG 2.4.6, 4.1.3 | D3, D6, M3 y M6 |
| Tarjetas por debajo de 768 px y sin desplazamiento horizontal | Semana 10 (RM1) | D5 y M5 |
| Fallo sin datos parciales ni éxito falso | RF-08, RNF-03 | D12 y M12 |
| Datos mostrados como texto | Seguridad | D11 y M11 |

## Riesgos demostrados

| Riesgo | Estado |
|---|---|
| La escritura depende de permisos que no existen en RBAC | Abierto: propuestos en Semana 5 |
| `can()` deniega siempre (sin `permissions` en la autenticación) | Abierto: la UI real no podría mostrar acciones |
| El prototipo no consume backend | Declarado: la integración es de Semanas 16 y 17 |

## Cambio solicitado (ejercicio simulado)

> No hubo un cambio real del docente; es un ejercicio propuesto para la defensa.

**Pedido:** "Que el usuario clínico vea por defecto solo medicamentos activos."

| Pregunta | Respuesta |
|---|---|
| ¿Dónde se implementa? | En `MedicationCatalogPage` (presentación): casilla "Incluir inactivos" desmarcada por defecto en la vista clínica. El API y el dominio no cambian |
| ¿Respeta los RF? | Sí: RF-04 sigue devolviendo la vigencia; RF-06 no cambia |
| ¿Qué riesgo introduce? | Ocultar que un medicamento fue inactivado. Mitigación: "Hay N medicamentos inactivos ocultos" en `role="status"` |
| ¿Accesibilidad y móvil? | Casilla con etiqueta visible, objetivo de 44 px, en una columna bajo la búsqueda |
| ¿Cómo se verificaría? | Comprobación nueva: con la casilla desmarcada no aparece AMX-250 y el estado anuncia "1 inactivo oculto" |
