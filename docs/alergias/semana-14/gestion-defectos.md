# Gestión de defectos

1. **Reporte:** un issue en GitHub con el título `[ASII-NN] defecto: …`, los pasos, lo esperado, lo obtenido y la evidencia (salida o captura). Sin datos clínicos reales.
2. **Clasificación:**
   - **Alta:** puede dañar datos clínicos o la seguridad del paciente; se corrige el mismo día.
   - **Media:** rompe la integración o una regla; se corrige antes del siguiente merge.
   - **Baja:** cosmética; se agenda.
3. **Corrección:** un commit nuevo que cita el issue (`Refs #`), con una prueba de regresión que fallaba antes y pasa después. Nunca con force push.
4. **Verificación:** quien reportó confirma; se cierra el issue con el enlace al commit.
5. **Registro:** los defectos del módulo se suman al registro de la Semana 13 para medir la tendencia.
