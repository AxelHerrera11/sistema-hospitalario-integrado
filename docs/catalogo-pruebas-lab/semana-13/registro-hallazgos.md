# Registro de hallazgos — ASII-17

Severidad: **Alta** (puede dañar datos clínicos o la seguridad del paciente), **Media** (defecto o incoherencia que afecta la integración), **Baja** (cosmético o de estilo).

| # | Severidad | Área | Hallazgo | Estado |
|---|---|---|---|---|
| RT-01 | Media | Documentación | La matriz de la Semana 12 citaba RNF-LAB-07, que no existe en la Semana 2 v2 | Cerrado (commit «alinear la matriz con los códigos RF/RNF») |
| RT-02 | Media | Código | El front controller calculaba «hoy» en UTC; un alta a las 23:00 en Guatemala quedaba como retroactiva | Cerrado (commit «usar la zona horaria del hospital») |
| RT-03 | Baja | Contrato | A `/health/ready` le faltaba una respuesta 4xx en `openapi.yaml` (aviso de redocly) | Cerrado antes de publicar la Semana 5 |
| RT-04 | Media | Datos | `lab_tests` no tiene migración en HOSPITAL; las columnas son la propuesta del Anexo A | Abierto: revisar con Javier |
| RT-05 | Baja | Persistencia | En SQLite el solapamiento concurrente solo lo cubre `UNIQUE (code, version_no)`; falta probar la exclusión GiST en PostgreSQL | Abierto |

Los hallazgos cerrados vienen de la autorrevisión de la reentrega y citan el commit que los corrigió. Los abiertos son el punto de partida de la revisión formal.
