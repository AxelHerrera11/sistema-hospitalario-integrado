# Matriz de amenazas del dashboard de ocupación (ASII-09, semana 17)

Amenazas del endpoint `GET /api/v1/occupancy` y de su pantalla, con el control que las mitiga y la
evidencia que lo demuestra. La autoridad de los datos y del permiso es del servidor; la pantalla
solo presenta lo que el backend autoriza.

| # | Amenaza | Vector | Control | Estado | Evidencia |
|---|---|---|---|---|---|
| T-01 | Suplantar a un usuario | Token JWT falso, vencido o de otro emisor | Middleware `jwt.auth` valida el token antes del controlador | `DEPENDIENTE` (ASII-25) | La ruta declara `jwt.auth`; los modelos y la emisión de sesión son de ASII-25 (#501–#505 abiertos) |
| T-02 | Cambiar de hospital | Manipular `X-Tenant-ID` | `RequirePermission` compara la cabecera con el tenant resuelto con `hash_equals` y responde `403 TENANT_MISMATCH` | `MITIGADO` | `OccupancySecurityTest::test_un_hospital_distinto_al_declarado_no_ve_ocupacion` |
| T-03 | Ver la ocupación sin permiso | Usuario sin `ocupacion.ver` | Middleware `rbac:ocupacion.ver`; sin permiso responde `403 RBAC_DENIED` y no ejecuta el caso de uso | `MITIGADO` | `OccupancyEndpointTest::test_sin_el_permiso_ocupacion_ver_responde_403` |
| T-04 | Concederse permisos a uno mismo | Alterar la matriz de roles | Matriz literal en `config/rbac.php`, sin comodines ni herencia, verificada por comparación exacta | `MITIGADO` | `RoleMatrixTest::test_role_matrix_matches_the_proposed_grants` |
| T-05 | Filtrar datos de pacientes | Exponer estancias o episodios en la respuesta | La respuesta solo contiene agregados de camas y salas; nunca identificadores de paciente ni de episodio | `MITIGADO` | `OccupancySecurityTest::test_la_respuesta_no_expone_datos_de_pacientes` |
| T-06 | Filtrar detalles internos | Devolver el error del motor de base de datos | Sobre de error con `error.code` y `error.message`; el detalle del `PDOException` no se propaga | `MITIGADO` | `OccupancySecurityTest::test_los_errores_no_exponen_detalles_internos` |
| T-07 | Interpretar un fallo como ocupación cero | Base no disponible | `503 OCCUPANCY_UNAVAILABLE`; nunca se devuelve una fotografía con ceros | `MITIGADO` | `OccupancyEndpointTest::test_la_falla_de_persistencia_responde_503_y_nunca_ocupacion_cero` |
| T-08 | Inyectar cabeceras o contenido en la traza | `X-Request-ID` con saltos de línea o longitud excesiva | Solo se propaga si cumple `^[A-Za-z0-9._-]{1,64}$`; en otro caso el servidor genera su identificador | `MITIGADO` | `OccupancySecurityTest::test_el_identificador_de_solicitud_se_sanitiza` |
| T-09 | Almacenar la respuesta en caché compartida | Caché de proxy o de navegador con datos de otro hospital | `Cache-Control: no-store, private` en éxito y en error | `MITIGADO` | `OccupancySecurityTest::test_la_respuesta_no_se_puede_guardar_en_cache_compartida` |
| T-10 | Escribir en la base | Uso del endpoint para modificar camas o estancias | El caso de uso es de solo lectura y los demás métodos responden `405` | `MITIGADO` | `OccupancySecurityTest::test_el_endpoint_es_de_solo_lectura` |
| T-11 | Alterar el corte para ver el estado de otro momento | `cutoff_at` con formato inválido o fuera de rango | Se exige ISO-8601 con zona horaria; si no, `400 INVALID_CUTOFF` | `MITIGADO` | `OccupancySecurityTest::test_un_corte_con_valores_invalidos_no_se_refleja` |
| T-12 | Agotar la base | Consultas repetidas sin límite | La consulta es una fotografía agregada sobre el inventario, sin filtros libres ni paginación costosa | `PARCIAL` | Sin rate limit propio: el control de tasa es del grupo (API Gateway) |
| T-13 | Perder trazabilidad | Denegación sin registro | Log estructurado con `request_id`, hospital, corte, inconsistencias y duración; sin datos de pacientes | `MITIGADO` | Registro `occupancy.snapshot` en `OccupancyController` |
| T-14 | Exponer la ruta sin sesión | Acceso directo a la URL de la pantalla | La pantalla exige sesión iniciada y el backend responde `401`/`403`; la ruta no entrega datos por sí sola | `MITIGADO` | `OccupancyEndpointTest::test_sin_token_responde_401` |
| T-15 | Depurar el entorno con datos sensibles | Depuración y trazas de Laravel en producción | El despliegue debe fijarse `APP_DEBUG=false` | `PENDIENTE` | Verificado `config('app.debug')` = `off` con la variable de entorno; falta confirmar en el entorno final |
| T-16 | Filtrar secretos | Credenciales en el repositorio | `.env` fuera del control de versiones; la documentación no incluye credenciales | `MITIGADO` | `.gitignore` incluye `.env`; la bitácora de despliegue no registra contraseñas |

## Amenazas aceptadas

1. **El corte por defecto es la hora del servidor.** No es una amenaza, pero sí un supuesto de
   negocio pendiente de acuerdo con ASII-06 (cierre de turno).
2. **La lista de hospitales permitidos no está filtrada en el endpoint.** La resolución del
   hospital la hace el middleware de tenant con el token; el filtro por lista blanca es
   responsabilidad de ASII-25.

## Datos sensibles que el módulo NO maneja

El módulo no lee ni expone identificadores de paciente, de episodio de cuidado ni de institución. La fotografía se construye sobre `wards`, `beds` y `bed_stays`, y el log de
observabilidad no incluye datos clínicos. La prueba
`test_la_respuesta_no_expone_datos_de_pacientes` comprueba que ninguna de esas palabras aparece
en la respuesta.
