# Alergias clínicas y alerta visual en expediente: Semana 18, evaluación final y defensa

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-12 · **Issue:** #38

**Estado:** lista para la evaluación final. La demostración y la defensa oral se hacen en la evaluación, con el guion de abajo.

- [Trazabilidad de las 18 semanas](./trazabilidad-semanas.md)
- [Recorrido final sobre `develop`](./evidencia/salida-recorrido-final.txt)
- [Declaración de uso de IA](./DECLARACION_IA.md)

## Qué entrega el módulo

Registra las alergias de un paciente y muestra, al consultarlo, un banner con tres niveles: **sin alergias registradas**, **alergias registradas** y **ALERGIA GRAVE**. Lo usan medicina y enfermería antes de prescribir o administrar.

| Pieza | Dónde | PR |
|---|---|---|
| Micro-HIS por capas | `micro-his/alergias/` | #200, #1006 y #1021 |
| API v1 (`POST …/allergies`, `GET …/allergy-alert`) y su provider | `app/Http/Controllers/Api/V1/AllergyController.php` | #1232 |
| Pantalla `/alergias` | `resources/js/modules/alergias/` | #1235 |
| Prueba contra PostgreSQL y recorrido con login real | `tests/Database/Hospital/AlergiasPostgresTest.php` | #1323 |
| Límites de texto, pruebas de permisos y matriz de amenazas | `tests/Feature/Alergias/AllergySecurityTest.php` | #1359 |

## Evidencia final sobre `develop` (a24bfb36, 30/10/2026)

Entorno local, no staging: PHP 8.4.21, PostgreSQL 18.4, Node 24.14.1 y Chrome headless. Las bases `*_e2e` se recrearon vacías con las migraciones de `develop`, el seed de CI y datos ficticios, con `APP_DEBUG=false` (pasos en la [bitácora de la Semana 17](../semana-17/bitacora-despliegue.md)).

| Verificación | Resultado |
|---|---|
| Micro-HIS: `run`, `whitebox`, `blackbox` y `api` | 30, 15, 14 y 15 comprobaciones OK |
| `tests/Feature/Alergias` y `AlergiasPostgresTest` | OK (15 tests, 151 assertions) |
| `node resources/js/modules/alergias/alergias.test.mjs` | 21 aserciones OK |
| Recorrido HTTP con login real (Semana 16) | **9/9** |
| Recorrido de la pantalla en Chrome (Semana 16) | **7/7** |
| Recorrido de seguridad, con la base caída en un segundo servidor (Semana 17) | **10/10** |

La salida completa está en [`evidencia/salida-recorrido-final.txt`](./evidencia/salida-recorrido-final.txt).

### Defectos encontrados en el recorrido final

| Dónde | Qué pasaba | Corrección en esta entrega |
|---|---|---|
| Recorrido de seguridad (Semana 17) | El paso «token con la firma alterada» cambiaba el **último** carácter por «x». En una firma HS256 ese carácter solo lleva 4 bits de datos: si el token terminaba en «w», el cambio dejaba la misma firma y la API respondía 200 porque el token seguía siendo válido. Pasa en 1 de cada 16 tokens; apareció en el recorrido final de admisión (ASII-07) | El script cambia un carácter del **medio** de la firma. Se comprobó que la API rechaza (401) cualquier firma realmente distinta |

## Guion de la demostración (8 minutos)

Con el entorno anterior y los usuarios ficticios del seed. Cada paso coincide con uno de los recorridos automatizados.

| # | Actor | Acción | Resultado esperado |
|---|---|---|---|
| 1 | Administración del hospital | Entrar | Sin «Alergias» en el menú |
| 2 | Medicina | Abrir «Alergias» y buscar `PAC-E2E-01` | Banner rojo «ALERGIA GRAVE» con la penicilina, anunciado como alerta |
| 3 | Medicina | Buscar `PAC-E2E-02` | «Sin alergias registradas» |
| 4 | Medicina | Registrar sin alérgeno | Error junto al campo; no se envía |
| 5 | Medicina | Registrar «Látex», leve | «Alergia registrada.» y banner amarillo |
| 6 | Medicina | Registrar «látex» otra vez | «El paciente ya tiene esa alergia activa.» junto al campo |
| 7 | Medicina | Repetir la consulta a 375 px | Sin desplazamiento horizontal |
| 8 | Medicina | Consultar con la base HOSPITAL caída (servidor 8096) | 503 `ALLERGIES_UNAVAILABLE`; la pantalla muestra «Alergias no disponibles: verificar con el paciente…», nunca «sin alergias» |

## Reglas que la defensa sostiene

1. «Sin alergias registradas» solo aparece si la consulta funcionó. Si la base falla, la respuesta es 503 y la pantalla pide verificar con el paciente.
2. Una alergia grave o que amenaza la vida eleva el banner a crítico; el banner crítico se anuncia como alerta.
3. No se repite una alergia activa equivalente: lo impide el dominio y el índice único de PostgreSQL (409).
4. Quien registra sale del JWT y de `local_user_refs`; el cuerpo no elige paciente, autor, estado ni hospital.
5. Cada alta deja auditoría H8 en la misma transacción, y una alergia no se puede borrar (trigger `allergies_retention`).
6. Un paciente inexistente responde 404; nunca «sin alergias».

## Preguntas de defensa

- **¿Por qué no mostrar «sin alergias» si la base no responde?** Porque se leería como seguro prescribir. Una falla responde 503 y la pantalla pide verificar con el paciente.
- **¿Cómo se evita duplicar una alergia?** Con la regla del dominio y con el índice único real; `AlergiasPostgresTest` lo comprueba contra PostgreSQL.
- **¿Puede alguien registrar a nombre de otra persona?** No. El autor sale del token; el recorrido A04 manda otro autor en el cuerpo y se ignora.
- **¿Qué defectos reales se encontraron?** Base caída que habría dado 500, paciente inexistente que decía «sin alergias» (Semana 16) y textos sin límite en la API (Semana 17).
- **¿Qué falta para producción?** Elegir la base por hospital, sincronizar `local_user_refs` y un staging autorizado.

## Cambio solicitado en la Semana 12

Farmacia pidió mostrar **cuándo se verificó** cada alergia. No está implementado. Entraría como el campo compatible `verified_at` en el contrato, sin cambiar el nivel del banner.

## Pendientes al cierre

| Pendiente | Responsable |
|---|---|
| Elegir la base HOSPITAL por hospital; hoy aplica el guard de #1154 | Integración y Tenants (ASII-25) |
| Sincronizar `local_user_refs` desde CENTRAL | Tenants (ASII-25) |
| Consumir el banner desde Prescripciones (`AllergyChecker`) | ASII-15 |
| Caso de uso «resolver alergia» (`alergias.resolver` ya existe) | ASII-12, siguiente iteración |
| Límite de tasa común de `/api/v1` | API Gateway |
| Staging u on-premise autorizado | Coordinación del proyecto |
