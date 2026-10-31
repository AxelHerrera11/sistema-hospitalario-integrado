# Notas SOAP y diagnósticos — Semana 15: pruebas unitarias, de caja blanca y de caja negra

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-11 — Notas SOAP y diagnósticos · **Entrega:** 40 pruebas trazadas a RF/RNF, comandos, resultados, capturas y 3 defectos registrados.

## Ficha de la asignación

- **Semana:** Semana 15 — Pruebas unitarias, caja blanca y caja negra
- **Tarea individual:** Diseñe y ejecute pruebas de Notas SOAP y diagnósticos trazadas a RF/RNF: unitarias, caja blanca con ramas/condiciones del flujo «registro de nota SOAP y asociación de diagnóstico clínico» y caja negra con equivalencia, límites, permisos y errores, sin probar módulos ajenos.
- **Entregable / evidencia:** matriz RF/RNF → prueba, mínimo 3 unitarias, 2 de caja blanca y 4 de caja negra, comandos/resultados, capturas y defectos registrados.
- **Issue:** Refs #22
- **Alcance:** pruebas nuevas en `micro-his/notas-soap/tests/`. **No se corrige código**: los defectos se registran y se corrigen en PR aparte.

## Qué se prueba y qué no

Se prueban las clases del módulo (dominio, casos de uso, `ApiKernel`) con **dobles en memoria** de lo ajeno: asignaciones (Episodios), catálogo CIE-10 y RBAC. No se prueban esos módulos ni PostgreSQL. **Versionado y expediente** no tienen código todavía (firma, corrección y `medical_record_id`): solo se prueba lo que existe, la inmutabilidad de la nota firmada.

## Comandos y resultados

PHP 8.3.33, ejecutado el 2026-10-20 sobre el código de la Semana 5 (PR #405).

```bash
cd micro-his/notas-soap
php tests/unit.php        # 14 pruebas
php tests/whitebox.php    # 9 pruebas
php tests/blackbox.php    # 17 pruebas
php tests/run.php && php tests/api.php   # regresión: 34 y 19 verificaciones
```

| Suite | Pruebas | OK | Fallos | Defectos abiertos | Captura |
|---|---|---|---|---|---|
| Unitarias (`unit.php`) | 14 | 12 | 0 | 2 | [unit](./capturas/unit.png) |
| Caja blanca (`whitebox.php`) | 9 | 8 | 0 | 1 | [whitebox](./capturas/whitebox.png) |
| Caja negra (`blackbox.php`) | 17 | 15 | 0 | 2 | [blackbox](./capturas/blackbox.png) |
| Regresión (`run.php`, `api.php`) | 53 | 53 | 0 | 0 | [regresion](./capturas/regresion.png) |

Una prueba de defecto («ABIERTO») no rompe la suite; pasa a «OK» cuando el defecto se corrige. Un fallo sí termina con código 1.

## Diseño de las pruebas

- **Unitarias (U-01 a U-14):** una clase por vez, sin adaptadores: `SoapSections`, `Diagnosis`, `DiagnosisPolicy`, `TreatingPhysicianGuard`, `ClinicalNote` y `Uuid`.
- **Caja blanca (W-01 a W-09):** `AssociateDiagnosis::execute` tiene seis salidas y se recorren **6 de 6 ramas** (W-01 a W-06), más el orden entre ramas (W-07). La condición del guardia, `(autor ≠ null ∧ autor ≠ usuario) ∨ ¬tratante`, se cubre por condición (W-08): cada término cambia el resultado por sí solo. La cobertura se contó leyendo el código, no con una herramienta (no hay Xdebug).
- **Caja negra (B-01 a B-17):** solo por `ApiKernel`, sin mirar el código. Clase válida y clases inválidas por cada una de las cuatro secciones (vacía, espacios, ausente), límites 4000 y 4001 (también con «ñ»), tipo de dato, identidad, permiso, médico tratante, RBAC caído, catálogo caído, duplicado, segundo principal, lectura, contrato y privacidad de la auditoría.

## Matriz RF/RNF → prueba

| Requisito | Pruebas | Estado |
|---|---|---|
| RF-SOAP-01 · registrar la nota | U-01, B-01 | Cubierto |
| RF-SOAP-02 · validar secciones y 4000 | U-02 a U-05, B-02 a B-04, U-13, U-14, B-16 | Cubierto; **D-01** |
| RF-SOAP-03 · solo el tratante y el autor | U-10, U-11, W-02, W-08, B-05, B-11, B-12 | Cubierto |
| RF-SOAP-04 · asociar con código y tipo | U-06, W-05, W-06, B-07, B-09 | Cubierto |
| RF-SOAP-05 · duplicado y segundo principal | U-07, U-08, W-07, B-08 | Cubierto |
| RF-SOAP-06 · catálogo caído | W-04, B-10 | Cubierto |
| RF-SOAP-07 · nota firmada no acepta diagnósticos | U-09, W-03, W-09 | Cubierto; **D-02** |
| RNF-SOAP-01 · seguridad | B-05, B-06, B-11 a B-13, B-17 | Cubierto; **D-03**; tenant sin código |
| RNF-SOAP-02 · integridad | U-12, W-09 | Parcial; la base se prueba en la regresión |
| RNF-SOAP-03 · falla segura | W-04, B-06, B-10 | Cubierto |
| RNF-SOAP-04 · rendimiento | — | **No probado**: exige medir con datos reales |
| RNF-SOAP-05 · privacidad de la auditoría | B-15 | Cubierto |
| RNF-SOAP-06 · una razón de cambio por clase | U-01 a U-12 | Cubierto: cada clase se prueba sola |

## Defectos registrados

| ID | Sev. | Pruebas | Defecto | Corrección propuesta |
|---|---|---|---|---|
| **D-01** | Mayor | U-13, U-14, B-16 | Una sección con solo espacio duro (U+00A0), de ancho cero (U+200B) o ideográfico (U+3000) se acepta como no vacía; RF-SOAP-02 pide rechazar «solo espacios». `trim()` no los reconoce | Validar con `preg_match('/^[\s\p{Z}\p{Cf}]*$/u', $texto)` en `SoapSections` |
| **D-02** | Crítico | W-09 | Si otra sesión firma la nota entre la lectura y la escritura, el diagnóstico se guarda en la nota firmada. Es el punto de atención P-1 de la revisión de la Semana 13 | Repetir la comprobación dentro de la transacción y rechazar en la base (trigger de `diagnoses`); coordinar con Javier |
| **D-03** | Menor | B-17 | Quien no atiende el episodio recibe `404` ante una nota inexistente y `403` ante una existente: puede saber qué identificadores existen (punto P-6) | Responder igual en ambos casos antes de revelar la existencia |

## Sensibilidad de las pruebas (mutaciones)

Se cambió una línea del código, se ejecutaron las tres suites y se restauró el archivo. Las seis mutaciones **se detectaron**:

| Mutación | Resultado |
|---|---|
| `MAX_LENGTH` de 4000 a 4001 | 2 fallos |
| Segundo principal sin comprobar el tipo del existente | 1 fallo |
| Condición del guardia invertida | 15 fallos y error fatal |
| Sin llamar a la política en `AssociateDiagnosis` | 4 fallos |
| `NOTE_SIGNED` con estado 200 | 1 fallo |
| `ApiKernel` sin capturar la caída de RBAC | error fatal en B-06 |

Una primera versión de U-08 **no** detectaba la mutación del segundo principal; se agregó el caso «existente secundario y nuevo principal» y desde entonces la detecta.

## Archivos

- [Capturas](./capturas/) · [Declaración de uso de IA](./DECLARACION_IA.md)
- Pruebas: `micro-his/notas-soap/tests/` (`support.php`, `unit.php`, `whitebox.php`, `blackbox.php`)

> Este PR añade pruebas sobre el código del PR #405 (Semana 5), aún sin integrar en `develop`.
