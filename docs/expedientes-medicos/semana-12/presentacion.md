---
marp: true
title: Expediente médico — Segunda evaluación
---

# Expediente médico electrónico base

**Componentes, UX, accesibilidad y movilidad** · apertura y consulta autorizada del expediente longitudinal

---

## Componentes (Semana 7)

- Backend: Presentation → Application (casos de uso + `AccessGuard`) → Domain; Persistence e Integration por puertos
- Frontend: `app.js` (aplicación) · `estados.js` (lógica pura, probada con node) · `vista.js` (componentes con `textContent`)
- Refactor: la consulta solo orquesta; acceso, integridad, auditoría y minimización tienen dueño

---

## UX y roles (Semana 8)

- Médico: consulta y (propuesta) abre · Recepción: abre · Enfermería y Administración: 403 sin datos
- Estados: inicial, carga, éxito, vacío, denegado, error recuperable con máx. 3 reintentos
- Confirmación modal con foco en «Cancelar»; motivo plegado; «Ocultar datos»

---

## Accesibilidad y movilidad (Semanas 9–10)

- 14 criterios WCAG: 13 cumplen; contraste mínimo de texto 6.58:1
- 8 hallazgos priorizados por impacto en datos sensibles; 4 corregidos
- Móvil primero: una columna < 768 px, objetivos de 44 px, sin datos guardados en el dispositivo

---

## Demostración (recorrido)

1. Dra. López + P-0001 → expediente con 3 episodios (zona horaria visible)
2. Carla Ruiz + P-0001 → error crítico «Sin vínculo asistencial» (auditado)
3. Diego Mora + P-0002 → confirmación → «Se abrió el expediente EXP-P-0002»
4. Hospital Norte → 503 con reintentos y salida

---

## Cambio solicitado y respuesta

- Pedido: «que la pantalla nunca muestre datos de un paciente junto al código de otro y que las horas no sean ambiguas»
- Respuesta: H-01 (ocultar al editar o fallar la validación), H-02 (zona horaria), H-03, H-04
- Evidencia: `verificacion-despues.json`, pruebas V-10 y V-11
