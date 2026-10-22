# Declaración de uso de inteligencia artificial

**Herramienta:** opencode, modelo `opencode/minimax-m3.1-flash-free` (ejecutado sobre Windows; los dos diagramas se generaron con `@mermaid-js/mermaid-cli` 12).

**Propósito:** diseñar el flujo UX del módulo «registro de nota SOAP y asociación de diagnóstico clínico» por roles autorizados: user flow del médico tratante y de lectura, seis wireframes anotados con estados, mensajes, validaciones y reglas de interacción, ayuda contextual y las reglas de protección de datos.

**Revisado y ajustado:**

- Los mensajes y estados se mapearon desde los `code` reales del contrato de la Semana 5 (`SOAP_SECTION_INVALID`, `DIAGNOSIS_INVALID`, `DIAGNOSIS_CODE_INVALID`, `DIAGNOSIS_DUPLICATED`, `PRIMARY_DIAGNOSIS_EXISTS`, `NOTE_SIGNED`, `ICD10_UNAVAILABLE`, `AUTHORIZATION_UNAVAILABLE`, `SOAP_PERSISTENCE_ERROR`, `NOT_TREATING_PHYSICIAN`, `FORBIDDEN`, `PAYLOAD_INVALID`) y desde las validaciones del dominio (`SoapSections`: cuatro secciones no vacías y máximo 4000 caracteres; `Diagnosis`: código no vacío y tipo `primary`/`secondary`/`differential`).
- Los wireframes se dibujaron sobre el diseño de componentes de la Semana 7 (`SoapNoteEditorPage.vue`, `SoapSectionsForm.vue`, `DiagnosisPicker.vue`, `DiagnosisList.vue`, `SoapNoteStatusBadge.vue`, store Pinia y `api/notasSoapApi.js`) y sobre el patrón de estilos del frontend que ya existe en `develop` (clases BEM, `<script setup>`).
- La matriz de roles salió de la Semana 5: `medico` con `notas_soap.crear`, `notas_soap.asociar_diagnostico` y `notas_soap.ver`; `enfermeria` solo con `notas_soap.ver`; `admin_hospital` sin acceso a notas por ser dato clínico.
- La firma y la corrección aparecen **marcadas como propuestas** (contrato v1 sin código), igual que en las Semanas 5 y 7; no se diseñan como funciones disponibles.
- En esta entrega no se modificó código del micro-HIS ni del HIS: es diseño de experiencia.

**Validación humana:** realizada el 2026-10-19. Josué Fernando Hicho García revisó el user flow por rol, los seis wireframes con sus estados, mensajes y validaciones, la ayuda contextual y las reglas de protección de datos, y aprueba el documento.

**Responsable:** Josué Fernando Hicho García es responsable de esta entrega.
