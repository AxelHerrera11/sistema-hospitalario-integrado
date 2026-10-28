# Cierre verificable ASII-20

| Riesgo | Control | Evidencia |
|---|---|---|
| Rol no bioquímico | permiso y membresía activa | Pendiente de prueba negativa |
| Tenant ajeno | tenant autenticado en API | Pendiente de prueba con dos tenants |
| Decisión repetida | estado persistido y auditoría | Cubierto por Semana 16 |
| Secreto expuesto | configuración fuera del repositorio | Revisión local, sin secretos |

Comandos reproducibles:

    php micro-his/validacion-resultados-bioquimico/tests/run.php
    php -l routes/api.php

Resultado local: las pruebas del micro-HIS pasan. Falta ejecutar y adjuntar la
denegación con rol no autorizado y una bitácora de despliegue autorizada con
SHA, versión y resultado.
