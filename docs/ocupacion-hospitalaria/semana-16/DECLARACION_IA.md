# Declaración de uso de IA — Semana 16 (ASII-09)

- **Herramienta:** OpenCode (modelo `minimax-m3.1-flash-free`), con la indicación expresa de Javier
  Fajardo López de implementar la semana 16 a partir del contrato de ocupación publicado.
- **Propósito:** generar la pantalla Vue del dashboard de ocupación, las utilidades puras que la
  sostienen y su prueba con `node`; redactar esta documentación de la entrega.
- **Contenido aceptado:** la estructura de la pantalla (estados de carga, error, acceso denegado,
  vacío y fotografía desactualizada), la tabla de dependencias con la de `README.md` y las
  afirmaciones verificables del contrato.
- **Contenido modificado:** la tasa de ocupación del dominio se corrigió para seguir el contrato
  vigente (denominador de camas operativas) y su prueba del Micro-HIS se ajustó; la matriz
  compartida de RBAC se actualizó con el permiso `ocupacion.ver`.
- **Verificación humana pendiente:** Javier revisa esta declaración, el texto de la pantalla y la
  tabla de dependencias antes de publicar.
- **Datos usados:** únicamente datos ficticios de prueba; no hay datos reales de pacientes,
  credenciales ni tokens en el repositorio.
