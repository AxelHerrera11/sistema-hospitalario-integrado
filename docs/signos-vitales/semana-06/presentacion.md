# Presentación — Signos vitales y alertas por valores anormales (ASII-13), Semana 6

Ocho diapositivas. Cada una indica lo que se proyecta y lo que se dice. Duración total: unos 12 minutos.

---

## Diapositiva 1 — El problema y la tesis

**Se proyecta**

- «Captura de signos vitales, evaluación de umbrales y generación de alerta»
- Tesis: *una alerta clínica es una decisión derivada; el mismo valor produce la misma severidad, venga de donde venga.*

**Se dice.** No es guardar un formulario. Es decidir si un valor es anormal y avisar sin perder ni inventar alertas. Todo lo que sigue protege eso.

---

## Diapositiva 2 — Actores y UML

**Se proyecta.** Diagrama de casos de uso de la Semana 1: médico tratante; sistemas Expediente, Perfil de umbrales, Notificaciones, Auditoría y RBAC. UC-SV-01 incluye la evaluación (02) y la validación de acceso (06); UC-SV-03 extiende a 01; UC-SV-04 extiende a 02.

**Se dice.** Un solo actor humano, porque el trigger de `vital_signs` exige un médico con asignación activa. Modelar enfermería contradiría el esquema.

---

## Diapositiva 3 — De los casos de uso a RF/RNF

**Se proyecta.** Siete RF y cinco RNF, cada uno con su UC y un criterio medible. Ejemplo: RF-SV-02, 50 °C → 422 y no se guarda nada; RNF-SV-03, con la auditoría caída hay 0 valores anormales guardados.

**Se dice.** El criterio de aceptación es una prueba. Por eso hay 50 casos automáticos: 19 del flujo, 9 del Repository, 14 de la API y 8 del cliente.

---

## Diapositiva 4 — SOLID donde duele

**Se proyecta.** Antes/después de LSP: dos reglas lanzaban excepciones y el evaluador las esquivaba con `instanceof`. Después: `RangeRule::evaluate()` es `final`, un signo ausente da `None` y las seis reglas pasan la misma prueba de contrato. Apoyos: SRP (`VitalSigns` solo valida), ISP (puertos pequeños), DIP (puertos en Application).

**Se dice.** El defecto más grave posible es que una FC 138 crítica no genere alerta porque otra regla falló. LSP lo convierte en una propiedad probada y no en una intención.

---

## Diapositiva 5 — Capas y Repository

**Se proyecta.** Presentation (`ApiKernel`, `Router`, `VitalSignController`) → Application (`RecordVitalSigns`, `GetVitalSign`, puertos) → Domain ← Persistence (`Pdo…`, `InMemory…`).

**Se dice.** `VitalSignRepository` está en Application porque la aplicación define la garantía: medición y auditoría, todo o nada. La misma batería corre contra PDO e InMemory, y el controlador no tiene SQL ni reglas: una prueba lo comprueba leyendo su código.

---

## Diapositiva 6 — Contrato API y frontera

**Se proyecta.** `POST /api/v1/care-episodes/{id}/vital-signs` y `GET /api/v1/vital-signs/{id}`; permisos `signos_vitales.registrar` y `.ver`; errores `{code, trace_id}`; 401, 403 y 503 con falla cerrada. Decisión: **no dividir**; p95 de 0,087 ms con PDO en proceso, 3 FK y un trigger de integridad.

**Se dice.** El contrato es lo que ven los demás módulos; el SQL no. Dividir el módulo costaría tres FK, un trigger y la transacción única, sin una carga que lo justifique. Sí hay una frontera de eventos hacia Alertas críticas.

---

## Diapositiva 7 — Cambio práctico: unidades

**Se proyecta.** Glucosa 5,5 mmol/L (normal) hoy se lee como 5,5 mg/dL y genera una alerta **crítica falsa**. Diseño: `Unit` y `Measurement` en Domain, conversión a canónico antes de `VitalSigns`, contrato aditivo `{value, unit}`.

**Se dice.** El fallo no está en los umbrales sino en qué número llega a ellos. Se corrige en la frontera: las reglas, el evaluador, el Repository y la base no cambian. Una unidad desconocida se rechaza con 422; nunca se adivina.

---

## Diapositiva 8 — Evidencia, riesgos y cierre

**Se proyecta.** Matriz decisión → evidencia (15 decisiones). Riesgos: umbrales sin validación clínica, `local_access_audits` y `outbox_events` sin migración, RBAC como doble, enteros en FC/FR/presión.

**Se dice.** Lo implementado está probado; lo propuesto está marcado como propuesto. La arquitectura se resume en una frase: *ninguna alerta se pierde, ninguna se inventa y ningún valor anormal existe sin traza.*

---

## Preguntas que preparo

- **¿Por qué el permiso RBAC no basta para consultar?** El permiso es del rol; la asignación es del episodio. Enfermería tiene `signos_vitales.ver` y aun así recibe `403 NOT_TREATING_PHYSICIAN` al leer una medición de un episodio que no atiende.
- **¿Por qué no se guarda la alerta en una tabla?** La alerta se deriva del signo y del perfil vigente en `measured_at`; como el signo validado es inmutable, siempre se reconstruye. Guardarla pide una entidad que pertenece al slice de laboratorio (`critical_alert`).
- **¿Qué pasa si falla la notificación?** La medición ya está guardada y auditada. Se notifica después del `COMMIT`; un fallo del canal no deshace la medición.
- **¿Por qué se reintenta solo la lectura?** Registrar no es idempotente: reintentarlo duplica la medición y la alerta.
- **¿Y si los umbrales cambian?** Son configuración versionada detrás de las reglas; cambiar la banda de una regla no toca el evaluador ni el contrato.
