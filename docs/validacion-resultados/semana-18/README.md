# ASII-20 Validación bioquímica - Semana 18

## Evaluación final

La demo funcional permite validar un resultado y deja visible el estado
`validado`. La evaluación final debe incluir el caso autorizado, el rechazo y
la denegación de un rol sin permiso.

### Criterio de cierre

- [x] Validación integrada comprobada sobre una captura real (caso autorizado, rechazo posterior → 422 y denegación de rol sin permiso → 403).
- [x] Regla de decisión única comprobada en la suite del micro-HIS.
- [x] Pruebas del micro-HIS ejecutadas.
- [x] Build frontend ejecutado sobre develop `6a81fb9a`.
- [ ] Revisión humana y aceptación del PR.
- [ ] Evidencia de despliegue autorizado.

Evidencia sobre develop `6a81fb9a` (2026-10-29, PostgreSQL 17 desechable): `phpunit --filter 'LabResults|LabValidation|NativePhp|TrasladosAltasPostgresTest' tests/Database/Hospital` → 17 pruebas, 214 aserciones, OK; `npm run build` OK; pruebas de los micros OK. Matrices de permisos y bitácora local en la semana 17.
