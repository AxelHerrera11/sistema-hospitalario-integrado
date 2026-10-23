# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code con el modelo Claude Sonnet 5.5 (`claude-sonnet-5-5`).

**Uso:** apoyo para diseñar las pruebas de seguridad (`DoctorSecurityTest`), redactar la matriz de amenazas STRIDE y la bitácora de despliegue local.

**Revisado y ajustado:** los resultados salen de ejecutar las pruebas sobre el código de las ramas del módulo y de levantar PostgreSQL 16 en contenedores efímeros. Las amenazas sin prueba (MED-12, MED-13, MED-14 y MED-15) se declaran como verificadas por lectura o abiertas, no como mitigadas, y salen de leer el código y la plataforma (`TenantMiddleware`, rutas, `RegisterDoctor`). No se desplegó nada fuera del equipo local.

**Validación humana:** pendiente. Josué Fernando Hicho García debe revisar y aprobar esta entrega antes de solicitar revisión.
