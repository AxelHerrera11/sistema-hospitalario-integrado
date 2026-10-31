# Guion de defensa y preguntas

Duración orientativa 7–9 minutos, no medición de una defensa realizada.
Diapositivas 1–3 (2 min): presentar problema, actor y criterio RF-01; aclarar que
la propuesta completa no significa implementación completa de campañas/defectos.
Diapositivas 4–5 (2 min): recorrer capas y puerto desde el código; mostrar
CaseController, DesignCase, ManualCase y CaseRepository. La vista serializa,
el controlador valida formato, Domain rechaza vacíos y Application guarda.
Diapositiva 6 (2–3 min): ejecutar suite y comandos de evidencia; leer los errores,
no convertirlos en defectos del programa: son rechazos/fallos inducidos esperados.
Diapositivas 7–8 (1–2 min): diferenciar REST propuesto del CLI y reconocer límites.

| Pregunta probable | Respuesta basada en código/contrato |
|---|---|
| ¿Por qué cuatro capas? | Separan formato/transporte, coordinación, invariantes y almacenamiento; permiten cambiar PDO sin reescribir la regla de completitud. |
| ¿Qué significa MVC aquí? | CaseController recibe; ManualCase/DesignCase modelan regla y operación; CaseView produce data JSON. No requiere un framework o tres carpetas MVC adicionales. |
| ¿Por qué no hay SQL en el controlador? | Delega en DesignCase; este depende de CaseRepository. Solo PdoCaseRepository prepara INSERT/SELECT; la creación de tabla está en esquema demo/tests. |
| ¿Dónde está la regla de negocio? | ManualCase aplica trim y exige identidad, propósito, pasos y esperado no vacíos; CaseController solo valida presencia y tipos antes de llamar al caso de uso. |
| ¿Qué aporta Repository? | save y find ocultan la forma de almacenar; el consumidor recibe una entidad equivalente o null/error común, no filas SQL. |
| ¿InMemory y PDO son iguales? | Cumplen el mismo puerto; uno usa mapa de objetos readonly y otro sentencias preparadas sobre conexión inyectada. La equivalencia probada se limita a los casos del contrato actual. |
| ¿Cómo demuestras LSP? | La batería común pasa con ambos adaptadores: alta/lectura, segunda alta, rechazo, inexistente y duplicado sin pérdida. Ninguno exige formatos adicionales al consumidor. |
| ¿Eso certifica RNF-04 completo? | No: RNF-04 refiere al registro de ejecución y sus vínculos. Hoy solo hay evidencia de sustitución del repositorio de casos; ejecución/evidencias siguen pendientes. |
| ¿Por qué prepare/execute? | Los valores se pasan como parámetros, no se concatenan al SQL. El test guarda y recupera propósito con comillas/texto SQL como dato, sin cambiar la consulta. |
| ¿Qué ocurre si falla la base? | PDOException se traduce a StorageFailure; el controlador devuelve 503 CASE_STORAGE_UNAVAILABLE sin detalles SQL. Tests usan doble fallido y PDO sin tabla. |
| ¿Se preserva la historia? | El repositorio no sobrescribe una identidad existente y una segunda alta no altera la primera. No demuestra todavía historia de ejecuciones o atomicidad multirregistro. |
| ¿Ya funciona REST? | No. demo.php consume STDIN y produce un sobre status/body; #1400 propone dos rutas. Faltan host HTTP, lectura de aplicación, seguridad y almacenamiento durable. |
| ¿Cómo manejas acceso entre hospitales? | Aún no existe ámbito tenant en qa_manual_cases/CaseRepository. Debe acordarse y probarse antes de exponer rutas; un header por sí solo no aísla datos. |
| ¿Qué no se demuestra? | UI, navegador, staging, campañas, defectos, evidencia privada, autorización QA, disponibilidad o PostgreSQL; no se presentan como funcionales. |

No ingresar credenciales ni usar datos clínicos durante la defensa. Si PHP no
tiene pdo_sqlite, explicar el bloqueo y habilitarlo por el procedimiento autorizado;
no reemplazar la ejecución por resultados históricos ni modificar otros módulos.
Autora y responsable de evidencia: Madelin Cerón. Evaluación docente, revisión
y aprobación de PR permanecen pendientes; no se atribuye aceptación anticipada.
