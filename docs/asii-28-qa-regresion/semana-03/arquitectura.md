# Arquitectura propuesta — campañas de regresión y defectos

## Decisión y alcance de implementación

Micro-HIS modular PHP 8.2+ vanilla, sin Laravel en su núcleo. No se necesita
microservicio, cola ni infraestructura distribuida para el flujo académico.
Los componentes de este documento y del diagrama **no existen aún como código**.
La composición futura conectará Presentation → Application → Domain y adaptadores
Persistence → contratos Domain. Domain nunca importa Persistence: el servicio
llama un puerto que un adaptador implementa, sin invertir la dependencia de código.

## Modelo conceptual y trazabilidad

| Objeto propuesto | Información y responsabilidad | Fuente Semana 2 |
|---|---|---|
| CasoManual | Identidad, propósito, pasos y resultado esperado; rechaza definición incompleta | RF-01 |
| CampaniaRegresion | Identidad y selección de casos mediante asociación explícita | RF-02 |
| CasoEnCampania | Vínculo entre campaña y caso diseñado, requerido al ejecutar | RF-02/03 |
| EjecucionManual | Identidad, asociación, observaciones, comparación registrada y resultado único | RF-03/04/08; RNF-02/05 |
| ReferenciaEvidencia | Identidad/referencia opaca ligada a ejecución; sin imponer formato de archivo | RF-05; RNF-01 |
| Defecto | Descripción y vínculo a ejecución fallida; caso/campaña se derivan de ella | RF-06/07; RNF-01 |
| ReferenciaRequisito | Identificador externo del requisito evaluado por un caso | Propuesta para trazabilidad solicitada; no es un RF nuevo |
| SeveridadDefecto | Valor por acordar con política QA, sin enumeración inventada | Extensión de diseño solicitada; Semana 2 no fija severidades |

Cadena: requisito → caso → asociación a campaña → ejecución → evidencia/defecto.
Cada reejecución añade identidad nueva; no actualiza observaciones, resultado ni
evidencias anteriores. Para preservar interpretación histórica se propone guardar
la versión/snapshot del resultado esperado usado; su representación se acordará
en Semana 4. No se prescriben tablas existentes ni estados de campaña no definidos.
No se usa una comparación literal automática para juzgar pasos manuales: el analista
registra observaciones y la comparación con lo esperado; el dominio exige coherencia
entre esa comparación y aprobado/fallido. La semántica concreta deberá acordarse.

## Casos de uso y separación

Presentation captura datos y devuelve un resultado legible, sin decidir si una
ejecución aprobada admite defecto ni consultar la base. El transporte (CLI/HTTP/UI)
no se fija en esta vista; cualquier ruta futura necesita contrato propio.
Application coordina los siguientes casos, sin SQL ni dependencia de adaptador:

| Caso de uso propuesto | Coordinación | Regla en Domain |
|---|---|---|
| DisenarCaso | Recibir definición, construir caso y guardarlo por puerto | Pasos y esperado presentes |
| CrearCampania / AsociarCaso | Recuperar identidades y conservar asociación | Caso diseñado y campaña identificados |
| RegistrarEjecucion | Recuperar asociación, capturar comparación, observaciones/evidencias y guardar | Resultado único coherente, origen y evidencia conservados |
| RegistrarDefecto | Recuperar ejecución y registrar descripción/vínculo | Ejecución fallida; aprobado no cumple precondición |
| ConsultarTrazabilidad | Recuperar grafo relacionado y presentar historial | No mezclar elementos sin vínculo ni reescribir historia |

La captura de evidencia durante la preparación de la ejecución se confirma junto
con el registro completo; no se inventa un ciclo persistente de estados intermedios.
Si se requiere edición en curso, deberá especificarse antes de implementarse.

## Contratos, patrones y LSP

Domain define conceptualmente `RegistroEjecucionManual` y `RepositorioQA`.
El primero expresa el contrato funcional de Semana 2; un servicio Application
lo implementará. El segundo agrupa operaciones de guardar/recuperar casos,
campañas, asociaciones, ejecuciones y defectos para esta vista inicial; se podrá
separar por agregado en Semana 4 si lo exige la implementación, sin añadir capas.

Precondición común: caso diseñado y asociado, observaciones/comparación y referencias
de evidencia válidas según el contrato acordado. Postcondición: registro identificado
con caso/campaña, resultado y evidencia preservados. Defectos se registran por
operación separada, con precondición de ejecución fallida, sin subtipos Aprobado y
Fallido que hereden operaciones incompatibles. RNF-03 impide sobrescribir historia.

Repository aísla almacenamiento; inyección por constructor permite sustitución.
DTO/resultado de aplicación mantiene Presentation independiente de filas SQL.
No son clases implementadas. InMemory y PDO deberán aceptar las mismas entradas,
conservar relaciones e historia y expresar rechazos/errores equivalentes.
Un sustituto no exigirá archivos/formatos exclusivos ni omitirá evidencias.
RNF-04 se verificará con la misma batería de contrato; no se acredita por diagramar.

## Persistencia y fallos previstos

El adaptador PDO futuro recibe la conexión de test desde composición; utiliza
`prepare` y `execute` con parámetros para valores, nunca concatenación de entradas.
Identificadores SQL serán constantes del adaptador. No se agregan SQL, tablas ni
migraciones en esta entrega. El esquema de test y el motor quedan por acordar.
Se propone integridad referencial para los vínculos y una transacción al guardar
ejecución con evidencias; ante error, rollback y error de almacenamiento común,
sin excepción PDO/detalles de conexión en Presentation. Nunca devolver éxito
si falta un vínculo o se perdió evidencia. El doble debe simular ese mismo fallo.

## Cobertura de requisitos y límites externos

RF-01/02: casos y campañas; RF-03/04: ejecución y resultado; RF-05: evidencia;
RF-06: defecto condicionado; RF-07: consulta del grafo; RF-08: historial append-only.
RNF-01: asociaciones conservadas; RNF-02: comparación coherente; RNF-03: historia
inmutable; RNF-04: contratos comunes; RNF-05: aprobado/fallido inequívocos.

El HIS es sistema bajo prueba, no una fuente de datos clínicos para esta propuesta.
Analista y responsable QA son actores conceptuales, no roles RBAC existentes.
Permisos, tenant, retención/almacenamiento de evidencias, severidad y contrato con
CI se acordarán explícitamente. No se accede a bases hospitalarias ni se alteran
otros módulos. Semana 4 deberá concretar MVC/repositorios y ejecutar los escenarios
de evidencia-validacion antes de declarar funcional el Micro-HIS.
