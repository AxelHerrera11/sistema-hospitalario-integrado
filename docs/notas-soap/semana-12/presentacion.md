# Presentación breve — Notas SOAP y diagnósticos (ASII-11), Semana 12

Duración prevista: **8 minutos** más preguntas. Seis diapositivas; el recorrido en el prototipo ocupa el centro.

## Diapositivas

1. **El problema (45 s).** Una nota firmada es la evidencia oficial de una atención. Una pantalla que la deja editar, o que no dice a qué paciente pertenece, falla antes que cualquier servidor.
2. **Tesis (45 s).** *La nota es un documento con autoría, versión y firma, y la interfaz lo dice en cada pantalla.* Cuatro decisiones: paciente, estado y autoría siempre visibles (UX); errores anunciados y sin color como única señal (accesibilidad); un campo por pantalla y ningún texto clínico en el dispositivo (móvil); un error crítico con salida correcta (prototipo).
3. **Recorrido en vivo (3 min).** En el [prototipo](./prototipo.html): episodio vacío → editor con validación → confirmación con paciente → diagnóstico (tipo, «Principal» con razón) → **error `NOTE_SIGNED`** → versión corregida con motivo → misma nota como enfermería. En móvil, el mismo recorrido a 390 px.
4. **Matriz (1 min 30 s).** Catorce decisiones, cada una con su RF/RNF y su evidencia; doce se ven en el prototipo y dos solo en diseño. Ver la [matriz](./matriz.md).
5. **Cambio solicitado (1 min 30 s).** «Editar la nota firmada directamente»: se rechaza la edición, se acepta el objetivo. La corrección en tres pasos, con motivo, ya está en el prototipo. Ver el [detalle](./cambio-solicitado.md).
6. **Lo que no resuelvo (30 s).** Sin lector de pantalla ni axe; borrador en servidor pendiente de Javier; unicidad de versión por expediente (R-06); no hay RNF de accesibilidad (propongo RNF-SOAP-07).

## Preguntas que preparo

- **¿Por qué el botón «Registrar» no se deshabilita?** Un botón deshabilitado no dice qué falta ni puede enfocarse. Al pulsarlo, el error aparece junto al campo y el foco va a él (WCAG 3.3.1, B-06).
- **¿Por qué la confirmación repite al paciente?** Un código de episodio no identifica a una persona; con varias pestañas, la nota puede quedar en el expediente equivocado (B-01).
- **¿Por qué el diagnóstico se busca después de registrar la nota?** El servidor solo asocia a una nota existente (`201` primero); mostrar la búsqueda antes engaña al médico (B-02).
- **¿Por qué no guardar el borrador en el navegador?** Es texto clínico: RNF-SOAP-05 lo prohíbe. La defensa es el aviso al salir y, después, `clinical_note_drafts` en servidor.
- **¿Por qué el error de nota firmada no reintenta?** Reintentar asociaría un diagnóstico a una nota que ya no es del médico. La salida es una versión nueva.
- **¿Qué prueba el prototipo y qué no?** Los roles, estados, errores y el foco se probaron en Chrome. El control real de permisos lo hace el servidor; la UI solo lo refleja.
- **¿Qué falta del flujo en el prototipo?** `403 NOT_TREATING_PHYSICIAN` y «catálogo caído»: están en los wireframes de la Semana 8, no en el prototipo.

## Archivos de apoyo

- [README de la evaluación](./README.md) · [Matriz](./matriz.md) · [Cambio solicitado](./cambio-solicitado.md)
