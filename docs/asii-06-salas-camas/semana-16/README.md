# ASII-06 — Semana 16: Integración, validación y primera entrega funcional

Autora: Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #11.
Rama: `feature/asii-06-semana-16-salas-camas-madelinceron`; destino `develop`.
HEAD inicial: `3ea30faa44e6ece877f9141405374cb519279b45`, igual a origin/develop
tras fetch. Árbol inicialmente limpio, upstream inicial origin/develop; no se
necesitó pull, merge ni rebase. #1073, #1364 y #1365 siguen abiertos, no integrados.
Se consultaron sus ramas sin incorporarlas ni modificarlas. No hay dependencia
funcional de sus commits para ejecutar la nueva prueba de integración.

## Objetivo, alcance y evidencia

Validar la primera versión de consulta del catálogo por capas y corregir errores
comprobados. Vue `/wards` usa Axios compartido; Laravel valida tenant/JWT y
`camas.ver`, aplica guard temporal y consulta CatalogService → repositorios PDO
→ tablas wards/beds, relacionadas por beds.ward_id. No se añaden rooms ni CRUD.

- [Validación de integración](./validacion-integracion.md): recorridos y resultados.
- [Evidencia del entorno](./evidencia-entorno.md): ambiente, comandos y duraciones.
- [Errores corregidos](./errores-corregidos.md): reproducción, corrección y regresión.

La nueva prueba HTTP usa el kernel Laravel en proceso, login/JWT y middleware reales,
repositorios PDO efectivos y PostgreSQL hospitalario; no sustituye la API por
datos simulados. CENTRAL es SQLite aislada. La prueba frontend ejecuta el script
real de la vista con reactividad Vue y un doble HTTP; no es navegador ni E2E.
Se corrigió texto mal codificado en WardCatalogPage, con fallo reproducido antes.

Resultados nuevos del 28/10/2026: **28 pruebas PHP / 199 aserciones**, **6 Node**,
cuatro mensajes PASS standalone, build aprobado (142 módulos), 30 rutas API.
Incluye ejecución temporal de las 10 pruebas pendientes de integración de #1365;
no se copian resultados históricos ni se incorporan sus archivos al PR.

## Estado funcional y pendientes

Consulta autenticada HTTP→PDO validada localmente; carga, selección, vacío,
reintento, errores y protección contra respuestas tardías verificados en script Vue.
No se demuestra un recorrido único navegador→Axios→Laravel→PostgreSQL.
La estudiante autorizó solo pruebas aisladas: no se accedió a staging ni a una
cuenta real. Evidencia visual autenticada y responsive en navegador pendientes.
No se etiqueta esta ejecución como staging ni despliegue on-premise validado.

#1154 es contención temporal por instancia; selección dinámica por hospital
pendiente. Creación HTTP/UI, capacidad máxima y transacciones clínicas no se
implementan. Para Semana 17: disponer de entorno/cuenta autorizados, ejecutar
recorrido visual y tamaños móvil/escritorio, resolver hallazgos y revisar el PR.
Madelin Cerón mantiene corrección y evidencia; revisión técnica y verificación
independiente: Pendiente de asignación. La aprobación del PR se registra en GitHub
según `.github/BRANCH_PROTECTION.md`. Sin cambios de .env, migraciones u otros módulos.
