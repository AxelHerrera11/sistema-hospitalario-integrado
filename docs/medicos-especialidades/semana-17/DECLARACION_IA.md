# Declaración de uso de inteligencia artificial

**Herramienta:** Claude Code con el modelo Claude Sonnet 5.5 (`claude-sonnet-5-5`).

**Uso:** apoyo para diseñar las pruebas de seguridad (`DoctorSecurityTest`), redactar la matriz de amenazas STRIDE y la bitácora de despliegue local.

**Revisado y ajustado:** los resultados salen de ejecutar las pruebas sobre el código de las ramas del módulo y de levantar PostgreSQL 16 en contenedores efímeros. Las amenazas sin prueba (MED-12 y MED-13) se declaran como verificada por lectura o abierta, no como mitigadas, y salen de leer el código y la plataforma (`TenantMiddleware`, `DoctorsPage.vue`); MED-14 y MED-15 se cerraron con límite de tasa y longitudes máximas, y tienen prueba. No se desplegó nada fuera del equipo local.

**Validación humana:** realizada el 2026-10-22. Josué Fernando Hicho García revisó esta entrega y la aprueba.
