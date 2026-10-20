# Evidencia verificable - Semana 2

## Qué demuestra

[`evidencia/isp-segregacion.php`](./evidencia/isp-segregacion.php) declara los contratos de [diseño antes y después](./diseno-isp.md) y los tres clientes. Cada cliente se ejecuta con un doble que registra las llamadas. Mediante reflexión, el script obtiene el contrato del que depende cada constructor y compara sus métodos con los invocados. No forma parte del Micro-HIS (Semana 3) ni usa base de datos.

## Ejecución

Entorno: Windows 11, `PHP 8.2.12 (cli) (ZTS Visual C++ 2019 x64)`, 20 de octubre de 2026.

```bash
php -l docs/catalogo-medicamentos/semana-02/evidencia/isp-segregacion.php   # exit 0
php docs/catalogo-medicamentos/semana-02/evidencia/isp-segregacion.php      # exit 0
```

```text
== Diseño Antes ==
AltaClient      -> MedicationCatalogService   depende de 4, usa 1, sin usar 3 [search, findById, changeValidity]
BusquedaClient  -> MedicationCatalogService   depende de 4, usa 2, sin usar 2 [register, changeValidity]
VigenciaClient  -> MedicationCatalogService   depende de 4, usa 1, sin usar 3 [register, search, findById]
== Diseño Despues ==
AltaClient      -> MedicationRegistration     depende de 1, usa 1, sin usar 0 []
BusquedaClient  -> MedicationLookup           depende de 2, usa 2, sin usar 0 []
VigenciaClient  -> MedicationValidityControl  depende de 1, usa 1, sin usar 0 []

PASS  antes: los clientes dependen de 8 métodos que no usan
PASS  después: 0 métodos sin usar en todos los clientes
PASS  después: el contrato de lectura no expone escrituras
PASS  después: un objeto solo de lectura no sirve para el alta (TypeError)
OK: 4 verificaciones aprobadas
```

**Control de la propia evidencia:** en una copia temporal fuera del repositorio se quitó la llamada `findById` del `BusquedaClient` posterior. El script respondió `FAIL después: 0 métodos sin usar` y terminó con código 1, porque el cliente seguía dependiendo de un método que ya no invocaba.

## Trazabilidad con los criterios

| Criterio | Evidencia |
|---|---|
| CA-11 / RNF-04 | "después: 0 métodos sin usar" |
| CA-09 / RNF-01 | El cliente de lectura no recibe `register` ni `changeValidity`, y el tipo rechaza un objeto solo de lectura para el alta. La autorización por rol sigue siendo un requisito propio de Semana 3. |
| RF-06 | `MedicationValidityControl` solo expone `changeValidity` con `active`/`inactive`; no hay borrado ni vencimiento de lotes. |

Los criterios CA-01 a CA-08 y CA-10 describen comportamiento ejecutable. Se verificarán con pruebas del Micro-HIS en Semana 3; esta semana no se afirma que estén implementados.

## Validaciones de la entrega

| Comando | Resultado | Salida |
|---|---|---|
| `java -jar plantuml.jar -tsvg -charset UTF-8 -failfast2 fuentes/*.puml` (PlantUML 1.2026.9, Java 24.0.2) | Renderiza ambos diagramas. Al regenerarlos en una carpeta temporal, los SVG son idénticos byte a byte a los versionados (`cmp`) | 0 |
| Carga de cada SVG con `DOMDocument` de PHP (script temporal) | 2 SVG válidos con raíz `<svg>` | 0 |
| Revisión de enlaces relativos de los `.md` de esta carpeta (script temporal) | 16 enlaces, 0 rotos | 0 |
| `git diff --check origin/develop...HEAD` | Sin errores de espacios | 0 |
