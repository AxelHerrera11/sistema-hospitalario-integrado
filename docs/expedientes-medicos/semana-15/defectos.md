# Registro de defectos

Defectos reales encontrados el 2026-10-23 mientras se construía y probaba el módulo. Ninguno se inventó para la
entrega; los que siguen abiertos lo dicen.

| Id | Encontrado por | Descripción | Severidad | Causa | Corrección | Verificación | Estado |
|---|---|---|---|---|---|---|---|
| DEF-EXP-01 | Prueba manual en el navegador | El estado mostraba «nullnullnull» bajo el mensaje | Menor | `replaceChildren(null)` inserta el texto «null» | `filter(Boolean)` en `pintarEstado` (`vista.js`) | Recorrido manual; capturas de la Semana 11 | Cerrado |
| DEF-EXP-02 | Prueba manual | Sexo y estado del paciente en inglés (`female`, `active`) | Menor | Se pintaba el código de la tabla | `etiquetaSexo`, `etiquetaPaciente` | V-09 | Cerrado |
| DEF-EXP-03 | Prueba P-06 | `DROP TABLE care_episodes` fallaba por FK al preparar el escenario | Menor (prueba) | `PRAGMA foreign_keys = ON` en el esquema de prueba | Desactivar FK solo en ese escenario | P-06 | Cerrado |
| DEF-EXP-04 | Pruebas R-02, R-05, R-06 | Falsos positivos: la regla leía comentarios y un método privado `query()` | Menor (prueba) | Regex sobre el archivo completo | La regla quita comentarios con `token_get_all`; método renombrado a `select()` | R-01…R-07 | Cerrado |
| DEF-EXP-05 | Integración Laravel | El vínculo asistencial se buscaba con el id CENTRAL del JWT y nunca coincidía | **Mayor** | Una sola identidad para RBAC, auditoría y `care_assignments` | `RequestContext::localUserRef()` + `ResolutorActorLocal` | C8, F-1, F-3 | Cerrado |
| DEF-EXP-06 | Prueba Feature | La ruta del expediente respondía 200 con la SPA (comodín de `routes/web.php`) | Mayor (prueba) | Provider registrado después del arranque: su ruta quedaba detrás del comodín | Registrar el provider antes de `BootProviders`, como hace `bootstrap/providers.php`; desde el 2026-10-23 el provider está registrado allí y la prueba ya no lo registra por su cuenta | F-1…F-4 | Cerrado |
| DEF-EXP-07 | Evaluación Semana 9 (H-01) | Expediente de un paciente visible junto al código de otro | **Mayor** | No se limpiaba al editar/validar | `debeOcultar` + limpieza en `app.js` | V-10; `semana-12/evidencias/verificacion-despues.json` | Cerrado |
| DEF-EXP-08 | Evaluación Semana 9 (H-02) | Horas sin zona horaria | Menor | `toLocaleString` sin `timeZoneName` | `formatearFecha(iso, zona)` | V-11 | Cerrado |
| DEF-EXP-09 | `PublishedContractsTest` | El nombre anterior de la captura `d-06` (la palabra «expediente» seguida de punto y la extensión png) se leía como permiso no catalogado | Menor | La prueba compartida busca `expediente.<texto>` en los documentos | Renombrado a `d-06-vacio-sin-apertura.png` | `evidencias/05-contratos-rbac.txt` | Cerrado |
| DEF-EXP-10 | Verificación manual | Tras cambiar `app.js`, el navegador siguió ejecutando la versión anterior de los módulos JS | Menor | `php -S` sirve los archivos estáticos sin cabeceras de caché/versión | Propuesta: versionar los módulos (`?v=`) o servir la UI desde Vite al integrarla a la SPA | — | **Abierto** |

## Pendientes que no son defectos

- Pruebas contra PostgreSQL: no hay servidor en el entorno (riesgo R-04).
- Prueba con lector de pantalla y en teléfono físico con red limitada: manuales.
