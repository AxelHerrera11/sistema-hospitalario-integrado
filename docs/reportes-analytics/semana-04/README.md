# ASII-23 Semana 4 — MVC y repositorios en el módulo de reportes

**Issue:** `Refs #152` · **Estudiante:** `Yamilet1235`.

La Semana 4 pide identificar y construir Model-View-Controller y el patrón
Repository. El módulo `micro-his/reportes-analytics` entregado en la Semana 3
isla esas piezas como evidencias concretas que se muestran en clase.

## Función de cada pieza implementada

| Pieza (Semana 4) | Donde está evidenciada | Responsabilidad |
|---|---|---|
| Vista `View` | `micro-his/reportes-analytics/src/Presentation/JsonView.php` | Renderizar siempre un documento JSON homogéneo; no decide reglas ni estados. |
| Controlador `Controller` | `micro-his/reportes-analytics/src/Presentation/{Router,IndicatorController}.php` | Interpretar la petición HTTP (ruta+método+payload), vigilar estados/turnstile y responder estados 200/404/422/503. |
| Modelo/regla dominio | `micro-his/reportes-analytics/src/Domain/{Period,IndicatorReading}.php` | Regla de negocio pura (periodo medio abierto [desde,hasta), offset explícito) sin SQL, ni HTTP, ni frameworks. |
| Repositorio `Repository` | `micro-his/reportes-analytics/src/Persistence/{PdoEpisodeSummaryReader,InMemoryEpisodeSummaryReader}.php` | Abstraer la lectura agregada detrás del puerto `EpisodeSummaryReader` y traducir a SQL preparado (PostgreSQL/SQLite), sin filtrar reglas. |
| Composición | `micro-his/reportes-analytics/app.php` | Ensamblar todas las piezas sin que los objetos se conozcan entre sí (inyección por constructor). |

## Cómo somos estrictos con la separación

- `IndicatorController` no conoce SQL ni PDO ni planos de consulta: solo
  decisiones de estados del contrato (SIN_DATOS_EN_PERIODO,
  LECTURA_NO_DISPONIBLE, periodo_invalido).
- `SqliteInMemory`/`Pdo` no conocen HTTP: responden lectura agregada o
  RuntimeException del contrato.
- `app.php` es única raíz de composición; los registros en `src` no
  importan nada del API más allá de clases del porte.
- Nota: los únicos "if" de negocio están en la regla de dominio del
  periodo (Semana 1) y en la decisión "denominador 0" del caso de uso.

## Cómo reproducir la evidencia

```bash
php micro-his/reportes-analytics/tests/run.php
# run.php OK: 11 escenarios del indicador IND-RA-01.
```

Además, mirar `git log --oneline micro-his/reportes-analytics`:
cada capa se introdujo en commits separados (dominio y caso de uso;
persistencia y esquema; presentación y composición).

## Puente a la Semana 5

La Semana 5 trasladará esta arquitectura al contenedor: la ruta
`/api/{Version REST}/v1/indicators/IND-RA-01/calculate` del Laravel del
cuerpo del sistema invocará `app.php` (a través del mismo puerto) y la
Vista de texto pasará a JSON exacto con cabeceras homogéneas.
