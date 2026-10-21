# Matriz endpoint → tablas → operación (Semana 5)

## 1. Matriz endpoint → tablas → operación

| Endpoint | Módulo | Base | Tablas | Operación | Transacción |
|---|---|---|---|---|---|
| `POST /api/v1/auth/register` | Auth (ASII-01) | CENTRAL | `central_users`, `hospital_memberships` | Escritura | Sí (usuario + membresía) |
| `POST /api/v1/auth/login` | Auth (ASII-01) | CENTRAL | `central_users`, `hospital_memberships` | Lectura + actualización de `last_authenticated_at` | No |
| `GET /api/v1/auth/me` | Auth (ASII-01) | CENTRAL | `central_users`, `hospital_memberships` | Lectura | No |
| `POST /api/v1/auth/refresh` | Auth (ASII-01) | CENTRAL | `central_users` | Escritura | No |
| `POST /api/v1/auth/logout` | Auth (ASII-01) | CENTRAL | `central_users` | Escritura (revocación) | No |
| `GET /api/v1/occupancy?cutoff_at=` | Ocupación (ASII-09) | HOSPITAL | `wards`, `beds`, `bed_stays` | Lectura | No (una lectura consistente) |
| Asignación de rol y permiso | RBAC (ASII-02) | HOSPITAL | `role_permissions`, `memberships`, bitácora de denegaciones | Escritura | Sí (permiso + denegación auditada) |
| Registro de profesional y especialidad | Médicos (ASII-04) | HOSPITAL | `doctor_profiles`, `doctor_specialties`, `specialties`, `local_user_refs` | Escritura | Sí (perfil + especialidad + vigencia) |
| Disponibilidad de médicos | Médicos (ASII-04) | HOSPITAL | `doctor_profiles`, `doctor_specialties`, `appointments` | Lectura | No |
| Asignación de cama en admisión | Admisión (ASII-07) | HOSPITAL | `care_episodes`, `bed_stays`, `care_assignments` | Escritura | **Sí** |
| Traslado, alta y liberación de cama | Traslados (ASII-08) | HOSPITAL | `bed_stays`, `wards`, `beds` | Escritura | **Sí** (cerrar intervalo y abrir el nuevo) |
| Firma y corrección de nota SOAP | Notas SOAP (ASII-11) | HOSPITAL | `clinical_notes`, `medical_records`, `diagnoses` | Escritura | **Sí** (nota + auditoría + evento) |
| Registro de signos vitales | Signos vitales (ASII-13) | HOSPITAL | `vital_signs`, `medical_records` | Escritura | No |
| Alergia clínica y alerta visual | Alergias (ASII-12) | HOSPITAL | `allergies` | Escritura | No |
| Creación de orden de laboratorio | Órdenes (ASII-16) | HOSPITAL | `lab_orders`, `lab_order_items`, `lab_tests` | Escritura | **Sí** (orden e ítems) |
| Recepción de muestra | Recepción (ASII-18) | HOSPITAL | `specimens`, `lab_orders` | Escritura | **Sí** (muestra + estado de la orden) |
| Ingreso de resultados | Resultados (ASII-19) | HOSPITAL | `lab_result_versions`, `lab_orders` | Escritura | **Sí** (versión + alerta) |
| Validación de resultado | Validación (ASII-20) | HOSPITAL | `lab_result_versions` | Escritura | **Sí** (decisión + outbox) |
| Alerta crítica | Alertas (ASII-21) | HOSPITAL | `critical_alerts`, `lab_result_versions` | Escritura | **Sí** (alerta + idempotencia) |
| Reportes y analítica | Reportes (ASII-23) | HOSPITAL | varias, solo lectura | Lectura | No |
| Sincronización hospital-red | Transversal | CENTRAL + HOSPITAL | `outbox_events`, `central_inbox_messages`, `sync_checkpoints` | Escritura | **Sí** (publicación idempotente) |

## 2. Unidades transaccionales

1. **Admisión.** Crear el episodio de cuidado, abrir la estancia y registrar la
   asignación del profesional en una sola transacción. Si la cama ya está ocupada, la
   exclusión GiST rechaza la estancia y nada queda a medias.
2. **Traslado o alta.** Cerrar el intervalo de `bed_stays` y abrir el nuevo en la misma
   transacción; de lo contrario hay una cama con dos estancias activas o ninguna.
3. **Orden de laboratorio.** La orden y todos sus ítems se insertan juntos; un ítem
   inválido revierte la orden completa.
4. **Resultado y alerta crítica.** La versión del resultado y la alerta se escriben
   juntas para que no exista un resultado crítico sin alerta.
5. **Firma de nota.** La nota firmada y su evento de auditoría no pueden separarse.
6. **Sincronización.** La publicación del evento es idempotente por clave; un reintento
   no duplica.
