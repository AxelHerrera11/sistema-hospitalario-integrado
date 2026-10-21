# Declaración de uso de IA — recepción de muestras, semanas 1–15

## Herramienta y alcance

Se utilizó Codex, asistente de IA de OpenAI, para inspección del repositorio, propuesta de estructura,
redacción de Markdown/Mermaid, implementación PHP/Laravel/Vue y preparación/ejecución de pruebas.
Esta declaración cubre las quince semanas, el microproyecto y la implementación independiente del HIS.
La autoría de entrega indicada es Mar-03; no modifica la asignación oficial ASII-18 del README general.

## Instrucciones relevantes del usuario

- Revisar las quince semanas contra el README original y completar documentos, micro y código real.
- Separar la implementación Laravel del ejercicio Micro-HIS.
- Usar ramas feature/asii-XX-semana-XX-modulo-usuario y PR con destino develop.
- Dividir entregas mayores de 400 líneas en ramas complementarias.
- Corregir semanas demasiado resumidas y demostrar el cumplimiento de la matriz completa.

## Contenido aceptado, revisado o modificado

La IA generó borradores de requisitos, diagramas editables, casos de defensa, checklist, SQA y trazabilidad.
Generó código del micro, clases del HIS, rutas, adaptadores PDO, consultas, componentes Vue y pruebas.
La revisión automática detectó y corrigió documentación sin evidencia, dependencia del HIS hacia el micro,
exposición de metadatos internos, falta de búsqueda de órdenes y bloqueo incorrecto tras errores GET.
Las versiones resumidas se ampliaron al comprobar que no demostraban todos los entregables de la matriz.
Las afirmaciones de cobertura/producción/WCAG sin prueba se retiraron o identificaron como no verificadas.

## Validación técnica realizada con asistencia de IA

Lectura de fuentes, sintaxis PHP, micro sin framework, PDO/HTTP real contra PostgreSQL, PHPUnit del HIS,
compilación Vue y pruebas de navegador con capturas/axe. Logs y JUnit están en evidencias.
El navegador usa dobles HTTP explícitos; el backend se prueba por separado con JWT/RBAC y PostgreSQL reales.
La IA no puede sustituir la aceptación del docente, del personal clínico ni de responsables externos.

## Validación humana pendiente de registrar

El estudiante debe leer las fuentes, ejecutar la demo, explicar decisiones y realizar cambios en defensa.
Debe documentar qué contenido acepta o modifica, el commit evaluado y los resultados de su revisión.
No se declara defensa oral realizada, aprobación clínica, firma de pares ni despliegue por el solo uso de IA.
Las decisiones finales de entrega y la responsabilidad de explicar el trabajo corresponden al estudiante.
