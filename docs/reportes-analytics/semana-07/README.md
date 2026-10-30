# ASII-23 Semana 7 — Componentes y protocolos de comunicación

**Issue:** `Refs #152`. Componentes que participan en un cálculo del
indicador y el protocolo que usan; el "mercado" se llama una vez y todo lo
demás queda demostrado por el código integrado.

## Componentes internos

| Componente | Archivo | Protocolo con sus pares |
|---|---|---|
| Pantalla Vue "Reportes" | `resources/js/modules/reportes/**` | HTTPS + JSON hacia la API; error visible por roles `alert`/`status`. |
| Router guard de rutas | `resources/js/router/index.js` | Coordinación con el almacén de sesión; falla segura sin `permissions`. |
| API Laravel | `routes/api.php` + `ReportesIndicatorController` | REST protegida; estados 200/401/403/404/405/422/503. |
| Caso de uso Micro-HIS | `micro-his/reportes-analytics/src/Application/*` | Llamada en proceso con el puerto `EpisodeSummaryReader`. |
| Adaptador de lectura | `micro-his/reportes-analytics/src/Persistence/PdoEpisodeSummaryReader.php` | SQL preparado sobre PostgreSQL HOSPITAL; mapeo a agregado. |

## Protocolos clave y decisiones

- **HTTPS REST + JWT Bearer:** token inmutable en el SPA, `Authorization`
  en cada petición del interceptor de axios; `X-Tenant-ID` por hospital.
- **JSON uniforme:** `data`/`meta` y `error`/`meta`; sin detalles internos.
- **Sin caché compartida:** `Cache-Control: no-store, private` (los datos
  del indicador pueden cambiar al cerrar/cancelar un episodio).
- **Dominio sin framework:** los componentes del dominio no importan
  Laravel ni Vue; el vínculo entra al final por composición.

## Cómo ensayar la comunicación (cuatro llamadas resumen)

1. Sin sesión: 401 `AUTH_REQUIRED`.
2. Con sesión sin `reportes.ver`: 403 `RBAC_DENIED`.
3. Con permiso y periodo invertido: 422 `periodo_invalido`.
4. Camino feliz: 200 + porcentaje con dos decimales.
