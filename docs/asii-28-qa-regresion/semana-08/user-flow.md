# User flow — propuesta completa, no flujo implementado

Fuente editable PlantUML; los WF corresponden a las seis secciones de wireframes.html.
Acceso/permisos son una frontera futura: el núcleo CLI no autentica ni tiene tenant.

```plantuml
@startuml
title ASII-28 - Recorrido UX PROPUESTO (no implementado)
start
:Solicitar acceso QA;
if (¿Identidad y permiso autorizados?) then (sí)
  :WF-01 Panel QA;
  :WF-02 Consultar/seleccionar campaña;
  if (¿Campaña tiene casos?) then (sí)
    :WF-04 Detalle y casos asociados;
  else (vacía)
    :Mostrar vacío y acción asociar caso diseñado;
    :WF-03 Diseñar caso o seleccionar uno existente;
    if (¿Cancelar sin guardar?) then (sí)
      :Volver al listado sin modificar datos;
      stop
    endif
    if (¿Propósito, pasos y esperado válidos?) then (sí)
      :Confirmar guardado y asociación futura;
      :WF-04 Detalle actualizado;
    else (no)
      :Mostrar errores por campo y conservar entrada;
      :Corregir en WF-03, sin guardar todavía;
      stop
    endif
  endif
  :Abrir caso y consultar pasos/esperado;
  :WF-05 Ejecutar manualmente y registrar observaciones;
  if (¿Cancelar registro?) then (sí)
    :Confirmar descarte y volver a WF-04;
    stop
  endif
  if (¿Comparación y evidencia válidas?) then (sí)
    if (¿Observado coincide con esperado?) then (sí)
      :Registrar nueva ejecución aprobada;
      :No ofrecer registrar defecto;
    else (no)
      :Registrar nueva ejecución fallida;
      :WF-06 Proponer defecto vinculado;
      if (¿Guardar defecto?) then (sí)
        if (¿Descripción válida?) then (sí)
          :Confirmar defecto y vínculo a ejecución;
        else (no)
          :Error en WF-06, conservar descripción sin guardar;
        endif
      else (cancelar)
        :Volver a ejecución; fallo sigue registrado;
      endif
    endif
    :Consultar resultado y trazabilidad en WF-04;
  else (sin evidencia/datos incorrectos)
    :Mostrar pendiente/error; no confirmar resultado;
    :Volver a WF-05 conservando entrada local;
  endif
else (no)
  :Acceso denegado; no revelar campañas/defectos;
endif
stop
@enduml
```

Los stop en cancelación/error cierran este intento, no bloquean reabrir la pantalla.
Ante error de transporte, conservar formulario y ofrecer reintento de lectura;
no reenviar automáticamente un guardado de resultado incierto. Asociación y
guardado del caso son pasos distintos del servidor futuro, sin transacción inventada.
Referencia de evidencia es precondición del contrato conceptual LSP; si la política
posterior permite omitirla debe acordarse para todos los sustitutos, no por adaptador.

## Validación de la entrega

PlantUML 1.2026.9 temporal: fuente extraída del bloque anterior, checkonly y render
PNG aprobados; representación inspeccionada. Edge headless renderizó el HTML con
perfil temporal aislado; imagen de escritorio revisada, sin datos ni cuenta reales.
Se verificaron seis secciones WF, IDs únicos, etiquetas y enlaces/fragmentos existentes,
controles disabled y ausencia de scripts/formularios/conexiones externas: PASS.
Los trece RF/RNF de Semana 2 están en la tabla de reglas-interaccion.md.
git diff --check y git diff --cached --check: PASS antes del commit.
No se modificó PHP ni se ejecutaron pruebas funcionales/usabilidad con personas.
Comandos de render (fuente/salida solo en carpeta temporal):

```text
java -jar <TEMP>/asii28-plantuml-v1.2026.9.jar -checkonly <TEMP>/user-flow.puml
java -jar <TEMP>/asii28-plantuml-v1.2026.9.jar -tpng <TEMP>/user-flow.puml
msedge.exe --headless --disable-gpu --no-first-run --user-data-dir=<perfil-temporal> --window-size=1440,3000 --screenshot=<TEMP>/wireframes-desktop.png <URL-file-de-wireframes.html>
```

No se versionan screenshots; los archivos HTML y el flujo son la fuente editable.
El render verifica presentación del diseño, no integración con API ni cumplimiento WCAG.
