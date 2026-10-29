# ASII-29 NativePHP - Semana 18

## Evaluación final

La integración demuestra el flujo local y una respuesta de sincronización
simulada en la interfaz del HIS. La defensa debe distinguirla de una
distribución NativePHP para escritorio o móvil.

### Criterio de cierre

- [x] Flujo integrado y sincronización simulada comprobados (la acción queda en `pendiente_sincronizar`; la respuesta es `simulado`).
- [x] Regla offline y error de persistencia comprobados.
- [x] Build frontend ejecutado sobre develop `6a81fb9a`.
- [ ] Ejecutar la aplicación NativePHP real.
- [ ] Revisión humana y aceptación del PR.
- [ ] Evidencia de despliegue autorizado.

Evidencia sobre develop `6a81fb9a` (2026-10-29, PostgreSQL 17 desechable): `phpunit --filter 'LabResults|LabValidation|NativePhp|TrasladosAltasPostgresTest' tests/Database/Hospital` → 17 pruebas, 214 aserciones, OK; `npm run build` OK; pruebas de los micros OK. Matrices de permisos y bitácora local en la semana 17.
