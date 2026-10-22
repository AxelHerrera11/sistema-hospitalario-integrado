# Semana 12 - Segunda evaluación parcial

## Portada

- **Estudiante:** Javier Alexander Fajardo López (`Javifa2324`)
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Entrega:** defensa teórica y caso práctico de diseño

## Objetivo

Defender las decisiones de Semanas 7 a 11 y demostrar cómo un cambio de experiencia atraviesa componentes, estados, accesibilidad y movilidad sin duplicar reglas del dominio ni debilitar seguridad.

**Estado de la evaluación:** el guion, el caso y la evidencia técnica están preparados; la defensa oral, la resolución en vivo y la calificación requieren ejecución humana y permanecen pendientes.

- [Guion de presentación](./presentacion.md)
- [Prototipo navegable](../semana-11/prototipo/index.html)
- [Declaración de IA](./DECLARACION_IA.md)

## Tesis de defensa

El dashboard es una vista agregada, temporal y de solo lectura. El backend decide la clasificación de cada cama; el cliente presenta la fotografía, su antigüedad y cualquier discrepancia. Una falla nunca se traduce a ceros y una pérdida de autorización elimina los datos previos.

## Componentes defendidos

| Componente | Responsabilidad | Decisión comprobable |
|---|---|---|
| `OccupancyCalculator` | Clasificar y consolidar camas | Cada cama se cuenta exactamente una vez |
| `BedSnapshotReader` | Obtener fotografía coherente | El adaptador PDO y el doble en memoria sustituyen el mismo puerto |
| `ConsultOccupancy` | Orquestar lectura y cálculo | No conoce HTTP ni SQL |
| `OccupancyController` | Traducir solicitud y resultado | Una falla de persistencia produce `503` |
| `OccupancyPage.vue` | Implementado en la semana 16: presenta y permite filtrar | No recalcula ocupación ni modifica camas |
| `ocupacion.js` | Implementado en la semana 16: utilidades puras de presentación | Sin acceso directo a la red; la red vive en el cliente común del grupo |
| Utilidades de ordenación | **Propuesto**: priorizar salas con inconsistencias | No está implementado en la pantalla; es el cambio preparado de esta semana |

## Trazabilidad de diseño

| Requisito o riesgo | Decisión UX | Evidencia |
|---|---|---|
| RF-01 resumen por sala | Tabla desktop y tarjetas móviles equivalentes | Prototipo P-01/P-05 |
| RF-04 corte temporal | Hospital, corte y antigüedad antes de métricas | Prototipo y Semana 10 |
| RF-06 discrepancias | Icono, texto, cantidad y definición | Semanas 8-11 |
| RNF-01 aislamiento | Sesión, tenant y permiso antes de cargar | Flujo Semana 8 |
| RNF-03 exactitud | Cálculo permanece en Domain | 12 verificaciones del Micro-HIS |
| RNF-05 rendimiento | Resumen breve; medición aún pendiente | Plan de componentes Semana 7 |
| Falla de lectura | `503`, dato rotulado o métricas ocultas | Prototipo P-03 |
| Permiso revocado | Limpiar cifras y mover foco al mensaje | Prototipo P-04 |
| Accesibilidad | Orden, semántica, foco y mensajes de estado | Checklist Semana 9 |

## Recorrido navegable

Recorrido del prototipo que se ejecuta en la defensa, con la evidencia de cada paso. Las
capturas se generaron el 21 de octubre de 2026 con Chromium sobre el prototipo servido con
`php -S`.

| Paso | Acción en el prototipo | Evidencia | Qué se demuestra |
|---:|---|---|---|
| P-01 | Abrir el prototipo y pulsar «Resultado válido» | [`semana-11/capturas/escritorio-camino-feliz.png`](../semana-11/capturas/escritorio-camino-feliz.png) | Corte visible, antigüedad, resumen global, tasa e inconsistencias señaladas. |
| P-02 | Cambiar el filtro de sala a «Medicina» | Prototipo en vivo | Los totales y la tabla se recalculan para la sala elegida sin tocar el backend. |
| P-03 | Pulsar «Dato desactualizado» | Prototipo en vivo | La fotografía anterior se conserva con rótulo de obsolescencia y su antigüedad. |
| P-04 | Pulsar «Acceso restringido» | [`semana-11/capturas/escritorio-error-critico.png`](../semana-11/capturas/escritorio-error-critico.png) | El estado 403 borra las cifras anteriores y anuncia el motivo. |
| P-05 | Repetir P-01 y P-04 con viewport de 390 x 844 | [`semana-11/capturas/movil-camino-feliz.png`](../semana-11/capturas/movil-camino-feliz.png), [`semana-11/capturas/movil-error-critico.png`](../semana-11/capturas/movil-error-critico.png) | La misma información reorganizada en tarjetas, sin scroll horizontal, con el mismo comportamiento de error. |

En la pantalla real de la semana 16 el recorrido equivalente es: entrar a `/ocupacion`, ver el
estado de carga, la fotografía con su corte, filtrar por sala, pulsar «Actualizar» y, si el
permiso se revoca, comprobar que las cifras desaparecen.


## Caso práctico

**Solicitud:** «En móvil, coordinación necesita ver primero salas con inconsistencias y conocer si la última actualización falló».

### Análisis

1. El orden original del backend se conserva como fuente; la UI puede ofrecer `Priorizar inconsistencias` sin cambiar conteos.
2. El control se ubica después del filtro de sala y debe ser accesible por teclado.
3. El store guarda la preferencia de presentación, no una nueva regla clínica.
4. Una falla conserva el orden de la última fotografía, agrega rótulo y antigüedad, y no vuelve a ordenar datos inexistentes.
5. En móvil, las tarjetas con inconsistencia aparecen primero; cada una conserva todas sus métricas.
6. El mensaje `aria-live` anuncia «Se muestran primero las salas con inconsistencias» una sola vez.

### Cambio esperado

```text
Sala [Todas ▼]
[x] Priorizar inconsistencias

! Medicina · 1 no verificada
  8 ocupadas · 4 disponibles

UCI · sin inconsistencias
  4 ocupadas · 2 disponibles
```

### Pruebas para aceptar el cambio

- Activar/desactivar no cambia los totales ni la tasa.
- Dos salas con igual cantidad conservan un orden estable.
- El control funciona con teclado y tiene etiqueta programática.
- A 320 px no produce scroll horizontal ni oculta estados.
- Ante `503` se mantiene el rótulo de obsolescencia.
- Ante `403` el control y todas las cifras desaparecen.

## Respuesta documentada al cambio solicitado

**Cambio solicitado:** «En móvil, coordinación necesita ver primero las salas con inconsistencias
y conocer si la última actualización falló».

**Decisión:** se acepta el cambio como **prioridad de presentación**, no como regla nueva. El
backend sigue siendo la fuente autoritativa del orden y de los conteos; la interfaz puede
reordenar la lectura sin recalcular nada.

**Respuesta defendida y sus límites:**

1. El control se ubica después del filtro de sala, con etiqueta programática y operable por
   teclado.
2. Al activarlo, las salas con inconsistencias aparecen primero; cada tarjeta conserva todas sus
   métricas y ninguna sala desaparece.
3. La preferencia es de presentación y vive en el cliente; no se envía al backend ni altera la
   respuesta del contrato v1.
4. Ante `503`, se conserva el rótulo de obsolescencia con la antigüedad de la última fotografía y
   no se reordena información inexistente.
5. Ante `403`, el control y todas las cifras desaparecen: la autorización actual prevalece sobre
   cualquier dato previo.
6. El anuncio `aria-live` informa una sola vez que las salas con inconsistencias se muestran
   primero.

**Criterios de aceptación del cambio** (los mismos que se listan más abajo): activar y desactivar
no cambia totales ni tasa, el orden es estable entre salas con igual cantidad de
inconsistencias, a 320 px no hay scroll horizontal, y los estados de `503` y `403` se mantienen.

**Estado:** el cambio está **especificado y probado en diseño, pero no implementado** en la
pantalla integrada. Su construcción corresponde al cliente Vue y no forma parte de esta semana;
queda registrado como R-3 en la revisión técnica de la semana 13 y como control de calidad en el
plan SQA de la semana 14.


## Preguntas de defensa

| Pregunta | Respuesta breve esperada |
|---|---|
| ¿Por qué no usar `beds.status` como ocupación? | La estancia activa define ocupación clínica; el estado operativo puede discrepar |
| ¿Por qué Repository? | Aísla persistencia y permite probar el mismo caso con InMemory |
| ¿Por qué no calcular en Vue? | Duplicaría dominio y podría divergir de la respuesta autoritativa |
| ¿Por qué conservar dato antiguo? | Ayuda a operar solo si se rotula con corte y antigüedad inequívocos |
| ¿Por qué ocultar en `403`? | La autorización actual prevalece sobre cualquier caché anterior |
| ¿Qué validó Semana 9? | Una inspección documental, no usuarios ni certificación WCAG |
| ¿Qué es real en Semana 11? | La navegación local; datos, API y autorización son simulados |
| ¿Cuándo extraer microservicio? | Solo con métricas que justifiquen autonomía y costo de red |

## Riesgos y límites

- Falta integrar el cliente Vue y probarlo contra la API real.
- Falta medir contraste y probar lector de pantalla y navegadores objetivo.
- El prototipo no implementa autenticación ni tenant reales.
- La prueba PostgreSQL es opcional mientras `MICROHIS_PG_DSN` no esté configurado.
- Los componentes frontend siguen siendo una propuesta arquitectónica.

## Evidencia

```bash
$ node --check docs/ocupacion-hospitalaria/semana-11/prototipo/app.js
(sin salida)

$ php micro-his/ocupacion-hospitalaria/tests/run.php
OK: 12 verificaciones aprobadas

$ php artisan test tests/Feature/Ocupacion tests/Database/Hospital/OccupancySnapshotIntegrityTest.php
Tests:    22 passed (125 assertions)

$ git diff --check
(sin salida)
```

## Criterios de evaluación

- Explicar el flujo completo sin confundir diseño, prototipo e implementación.
- Relacionar cada decisión con un RF, RNF, riesgo o hallazgo.
- Ejecutar P-01, P-03 y P-04 en desktop y móvil.
- Resolver el caso práctico preservando exactitud, privacidad y accesibilidad.
- Reconocer límites y evidencia pendiente sin inventar resultados.

El docente o evaluador debe registrar el resultado de estos criterios después de la defensa; este documento no se autoatribuye una aprobación.

## Conclusión

La defensa conecta arquitectura y experiencia mediante contratos claros: el backend mantiene la verdad, el cliente comunica contexto y fallas, y cualquier mejora visual conserva las garantías de seguridad y exactitud.

## Verificación de la respuesta defendida

Cada punto de la respuesta al cambio solicitado tiene una comprobación automatizada en el
código integrado:

| Punto de la respuesta | Comprobación |
|---|---|
| El corte y la antigüedad viajan en la respuesta | `test_devuelve_la_fotografia_de_ocupacion_con_el_corte_y_el_request_id` |
| El corte por defecto es la hora del servidor | `test_el_corte_por_defecto_es_la_hora_del_servidor` |
| Una fecha inválida se rechaza | `test_rechaza_un_corte_invalido` |
| Sin token no hay datos | `test_sin_token_responde_` |
| Sin el permiso `ocupacion.ver` no hay datos | `test_sin_el_permiso_ocupacion_ver_responde_` |
| Otro hospital declarado no ve ocupación | `test_un_hospital_distinto_al_declarado_responde_` y `test_un_hospital_distinto_al_declarado_no_ve_ocupacion` |
| Una falla de persistencia responde `503`, nunca ocupación cero | `test_la_falla_de_persistencia_responde_` y `test_una_base_hospital_inexistente_responde_` |
| La respuesta no expone datos de pacientes | `test_la_respuesta_no_expone_datos_de_pacientes` |
| El endpoint es de solo lectura | `test_el_endpoint_es_de_solo_lectura` |

Las pruebas viven en `tests/Feature/Ocupacion/` y `tests/Database/Hospital/OccupancySnapshotIntegrityTest.php`,
y se ejecutan con `php artisan test tests/Feature/Ocupacion tests/Database/Hospital/OccupancySnapshotIntegrityTest.php`.
