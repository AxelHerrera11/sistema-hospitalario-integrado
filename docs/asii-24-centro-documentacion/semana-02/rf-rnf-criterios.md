# RF/RNF y criterios de aceptación — Semana 2 (ISP)

**Módulo:** `ASII-24 - Centro de documentación y manuales por rol` | **Issue:** `#208`
**Proceso:** publicación y consulta de documentación y manuales diferenciados por rol. **Principio aplicado:** Interface Segregation (ISP).

## 1. Requisitos funcionales (RF)

| ID | Requisito |
|---|---|
| `RF-01` | El sistema debe permitir publicar un documento o manual con sus roles destinatarios. |
| `RF-02` | El sistema debe listar el catálogo filtrado por el rol del usuario. |
| `RF-03` | El sistema debe permitir buscar por texto dentro del catálogo visible para el rol. |
| `RF-04` | El sistema debe permitir descargar un documento permitido al rol. |
| `RF-05` | El sistema debe gestionar versiones y vigencia sin afectar a los clientes de consulta. |
| `RF-06` | Un cliente de solo consulta **no debe** estar obligado a implementar operaciones de publicación o descarga. |

## 2. Requisitos no funcionales (RNF)

| ID | Requisito | Relación con ISP |
|---|---|---|
| `RNF-01` | Cada cliente debe depender solo de los métodos que usa. | Interfaces específicas por consumidor. |
| `RNF-02` | Cambiar la firma de una operación no debe afectar a clientes que no la usan. | Contratos segregados. |
| `RNF-03` | El catálogo debe poder probarse sin cubrir publicación ni descarga. | Interfaz `ConsultaDocumentos` mínima. |
| `RNF-04` | Un adaptador único debe poder implementar varias interfaces segregadas. | La segregación es del contrato, no del adaptador. |

## 3. Criterios de aceptación

- **`AC-01` (publicación):** un documento con roles válidos se publica y clasifica.
- **`AC-02` (catálogo por rol):** el listado devuelve solo documentos visibles para el rol.
- **`AC-03` (búsqueda):** la búsqueda filtra por texto dentro del catálogo del rol.
- **`AC-04` (descarga):** un documento permitido se descarga; uno fuera del rol no.
- **`AC-05` (segregación de consulta):** `CatalogoController` depende de `ConsultaDocumentos` (2 métodos) y **no** de la interfaz "gorda" de 7 (verificable en el diff del commit).
- **`AC-06` (sin métodos ajenos):** ningún cliente implementa métodos que no usa.

## 4. Trazabilidad con la evidencia

| Criterio | Evidencia |
|---|---|
| `AC-02` | `evidencia/demo-isp.php` → `listar para enfermeria` / `listar para admin` |
| `AC-03` | `evidencia/demo-isp.php` → `buscar "protocolo" como admin` |
| `AC-01` | `evidencia/demo-isp.php` → `publicar (otro contrato, 2 metodos): 1 documento` |
| `AC-05` | `evidencia/demo-isp.php` → `declara 7 metodos` frente a `declara 2 metodos` |
| `AC-06` | `evidencia/demo-isp.php` → `ningun cliente implementa metodos ajenos: OK` |
