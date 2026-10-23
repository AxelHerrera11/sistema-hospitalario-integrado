# Requisitos y criterios de aceptación - ASII-26 Semana 3

## Requisitos funcionales

| ID | Requisito |
|---|---|
| RF-01 | Construir el resumen del dashboard a partir de los indicadores registrados. |
| RF-02 | Rechazar indicadores con valor negativo. |
| RF-03 | Aceptar indicadores con valor cero. |
| RF-04 | Devolver un resumen vacío cuando no hay indicadores. |
| RF-05 | Exponer el resumen por CLI y adaptarlo a las pantallas del dashboard. |

## Requisitos no funcionales

| ID | Requisito |
|---|---|
| RNF-01 | PHP 8.2+ sin framework ni Composer. |
| RNF-02 | El Dominio no depende de la infraestructura ni de la entrada CLI. |
| RNF-03 | Cada capa tiene una única responsabilidad (SRP). |
| RNF-04 | Errores de persistencia traducidos a excepciones de Dominio. |
| RNF-05 | Sin credenciales ni datos reales en el repositorio. |

## Criterios de aceptación

- **AC-01**: un valor negativo lanza `IndicadorInvalidoException` (`422 indicador_valor_invalido`).
- **AC-02**: un valor cero es válido.
- **AC-03**: el resumen conserva el orden y los valores de los indicadores.
- **AC-04**: sin indicadores el resumen es una lista vacía.
- **AC-05**: un fallo de persistencia se propaga como `ErrorPersistenciaException`.
- **AC-06**: `src/Domain/` no contiene `PDO`, `$_GET`, `$_POST` ni `header(`.

## Trazabilidad

| Criterio | Artefacto | Prueba |
|---|---|---|
| AC-01 | `src/Domain/Indicador.php` | dominio: rechaza valor negativo |
| AC-02 | `src/Domain/Indicador.php` | dominio: acepta valor cero |
| AC-03 | `src/Application/ResumenDashboard.php` | aplicacion: camino feliz arma el resumen |
| AC-04 | `src/Application/ResumenDashboard.php` | aplicacion: resumen vacio sin indicadores |
| AC-05 | `src/Persistence/IndicadorRepositoryEnMemoria.php` | aplicacion: error de persistencia se propaga |
| AC-06 | `src/Domain/` | revisión manual |
