# Errores corregidos y pendientes

## S16-01 — Texto mal codificado en la vista (corregido)

Componente: WardCatalogPage.vue. Mensajes, título y ayudas contenían mojibake:
`catÃ¡logo`, `sesiÃ³n`, `Verificandoâ€¦`. Esperado: español legible en UTF-8.
Reproducción: `node --test tests/Frontend/WardCatalogFlowTest.mjs` antes de la
corrección dio **3 PASS y 1 FAIL**, 451.3893 ms. El caso 503 esperaba
`No se pudo consultar el catálogo de wards. Intenta nuevamente.` y recibió
`No se pudo consultar el catÃ¡logo de wards. Intenta nuevamente.`.

Se corrigieron únicamente los literales mal codificados de la vista, conservando
contrato y lógica. La misma prueba después dio **4 PASS, 0 FAIL**, 401.6255 ms;
la ejecución final con navegación dio **6 PASS**, 417.5981 ms. Reintento conserva
su comportamiento y limpia error. El build también pasó. Responsable: Madelin
Cerón; verificación automatizada registrada, revisión independiente pendiente.
No se atribuye una captura o inspección visual de usuario a esta prueba de script.

## Riesgos y brechas que no se cierran

- REV-01 de #1073: contención temporal #1154 probada nuevamente, sin resolución
  dinámica definitiva por hospital. Corrección técnica/verificación: Pendiente de asignación.
- Creación disponible en servicio/PDO, no HTTP/UI; sin endpoints de escritura nuevos.
- Capacidad máxima, paridad completa fake/PDO y atomicidad multirregistro no acreditadas.
- Staging/on-premise, recorrido autenticado en navegador y responsive: pendientes
  de entorno/cuenta autorizados; por instrucción se usaron solo pruebas aisladas.

No hubo otros defectos confirmados en las ejecuciones dirigidas. La nueva prueba
HTTP→PDO amplía evidencia de integración; no se presenta como corrección de un
fallo inexistente. Los PR #1073/#1364/#1365 conservan su estado y contenido.
Madelin mantiene seguimiento; revisión técnica, aprobación y cierre independiente
requieren asignación autorizada antes de aceptar la entrega.
