# Datos y servicios reales y simulados del prototipo

| Elemento | Real o simulado | Detalle |
|---|---|---|
| API HTTP (`public/index.php`) | **Real** (prototipo) | Las mismas operaciones y validaciones que el micro-HIS |
| Reglas, transacción y persistencia | **Real** | `PdoRegistroInternacion` sobre PostgreSQL con sus restricciones y triggers |
| Base HOSPITAL | **Real, desechable** | Migrada con `database/migrations/hospital`; no es una base compartida ni productiva |
| Pacientes, camas, episodios y estancias | **Simulados (ficticios)** | `database/demo.sql`: un paciente «Paciente Ficticio», la sala DEMO-TA y las camas A, B y C |
| Usuario que opera | **Simulado** | La «referencia de usuario» se escribe a mano y viaja en `X-Actor-Ref`; no hay inicio de sesión |
| Autorización | **Parcial** | Solo se exige una referencia activa en `local_user_refs`; los permisos por rol (RBAC) no existen |
| Tenant y hospital | **Ausente** | Una sola base; sin `X-Tenant-ID` ni selección de hospital |
| HOSPITAL caído | **Simulado** | El servidor apunta a un puerto sin base (`port=1`) |
| Red lenta o sin red | **Simulada** | Emulación de DevTools (Semana 10), no una red móvil real |
| Lista de camas libres | **Ausente** | La cama se escribe como uuid (hallazgo H5, pendiente de la integración) |

Lo simulado y lo ausente se integran con Laravel (JWT, tenant y RBAC) en las semanas 16–17.
