# Recorrido navegable (guion de demostración)

Prototipo: `php -S 127.0.0.1:8096 -t micro-his/expediente-medico/public micro-his/expediente-medico/public/index.php`
→ `http://127.0.0.1:8096/ui/` con la base de demo recién creada. Solo teclado (Tab, Enter, Esc) para mostrar accesibilidad.

| Paso | Rol / entrada | Lo que se demuestra | Riesgo tratado | Captura |
|---|---|---|---|---|
| 1 | Dra. López, Hospital Demo, P-0001, Enter | Camino feliz; foco al resultado; fechas con zona; motivo plegado | Datos sensibles a la vista | `d-02`, `m-02` |
| 2 | Tab hasta «Motivo de atención», Enter | Despliegue explícito del dato sensible | Exposición accidental | `d-03` |
| 3 | Editar el código a P-0002 | Los datos de P-0001 desaparecen (cambio de la Semana 12) | Atribuir datos a otro paciente | `verificacion-despues.json` |
| 4 | Carla Ruiz, P-0001 | Error crítico: permiso sí, vínculo no → 403 sin datos, auditado | Acceso no autorizado | `d-04`, `m-03` |
| 5 | Elena Sol, P-0001 | 403 RBAC con código de seguimiento (también en la auditoría) | Rol sin permiso | — |
| 6 | Dra. López, P-0002 | Vacío «Sin expediente abierto» + «Solicitar apertura…» | Integridad: no inventar expediente | `d-06` |
| 7 | Diego Mora, P-0002, «Abrir expediente…», Esc | Prevención de errores: Esc cancela | Apertura accidental | `d-07`, `m-06` |
| 8 | Repetir y confirmar | 201 «Se abrió…»; repetir → «ya existía» | Duplicados | `d-08`, `m-07` |
| 9 | Diego Mora, P-0003 | 409 paciente no activo | Integridad | `curl-contrato.txt` (S5) |
| 10 | Dra. López, Hospital Norte | 503 + «Reintentar» (1 de 3 … salida a soporte) | Disponibilidad | `d-05` |
