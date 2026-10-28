# Cierre verificable ASII-29

| Riesgo | Control | Evidencia |
|---|---|---|
| Acción sin permiso | RBAC antes de preparar o sincronizar | Pendiente de prueba negativa |
| Datos de otro hospital | tenant autenticado | Pendiente de prueba con dos tenants |
| Falsa sincronización | estado simulado sin gateway | Verificado en el micro; integración HTTP pendiente |
| Secretos locales | configuración fuera del repositorio | Revisión local, sin secretos |

Comandos reproducibles:

    php micro-his/nativephp-extension-prototype/tests/run.php
    php -l routes/api.php

Resultado local: las pruebas del micro-HIS pasan. La aplicación NativePHP
distribuida, la sesión real y el despliegue autorizado siguen pendientes.
