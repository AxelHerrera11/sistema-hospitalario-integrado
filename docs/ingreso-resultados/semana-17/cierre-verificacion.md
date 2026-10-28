# Cierre verificable ASII-19

| Riesgo | Control | Evidencia |
|---|---|---|
| Tenant ajeno | X-Tenant-ID y middleware JWT/RBAC | Pendiente de prueba con dos tenants |
| Rol sin permiso | permiso separado para corregir | Pendiente de matriz negativa |
| Auditoría incompleta | transacción de cambio y auditoría | Cubierto por Semana 16 |
| Secreto expuesto | configuración fuera del repositorio | Revisión local, sin secretos |

Comandos reproducibles:

    php micro-his/ingreso-resultados-lab/tests/run.php
    php -l app/Http/Controllers/Api/V1/LabModulesController.php

Resultado local: las pruebas del micro-HIS pasan. No se declara despliegue
staging/on-premise ni autorización de seguridad hasta ejecutar la matriz
negativa con identidad real y registrar el SHA, versión y resultado del entorno.
