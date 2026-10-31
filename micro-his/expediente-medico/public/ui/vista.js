// Componentes visuales del prototipo: construyen DOM con textContent (nunca innerHTML con datos del API, sin XSS).
// No hacen peticiones ni deciden reglas; reciben el estado ya interpretado por estados.js.
import { etiquetaEstado, etiquetaPaciente, etiquetaSexo, etiquetaTipo, formatearFecha, formatearNacimiento } from './estados.js';

function nodo(etiqueta, atributos = {}, ...hijos) {
    const el = document.createElement(etiqueta);
    for (const [clave, valor] of Object.entries(atributos)) {
        if (clave === 'texto') el.textContent = valor;
        else el.setAttribute(clave, valor);
    }
    el.append(...hijos.filter(Boolean));
    return el;
}

function ficha(pares) {
    return nodo('dl', { class: 'ficha' }, ...pares.map(([termino, valor]) => nodo('div', {}, nodo('dt', { texto: termino }), nodo('dd', { texto: valor || '—' }))));
}

/** Caja de estado: carga, vacío, éxito, error o acceso denegado. acciones = [{ texto, alPulsar }]. */
export function pintarEstado(contenedor, estado, acciones = []) {
    contenedor.className = `estado estado-${estado.tipo}`;
    contenedor.setAttribute('role', estado.tipo === 'error' || estado.tipo === 'denegado' ? 'alert' : 'status');
    // filter(Boolean): replaceChildren(null) insertaría el texto «null» (defecto DEF-EXP-01).
    contenedor.replaceChildren(...[
        nodo('p', { class: 'titulo', texto: estado.titulo }),
        nodo('p', { texto: estado.mensaje }),
        estado.traza ? nodo('p', { class: 'traza', texto: `Código de seguimiento: ${estado.traza}` }) : null,
        estado.aviso ? nodo('p', { texto: estado.aviso }) : null,
        acciones.length ? nodo('div', { class: 'acciones' }, ...acciones.map(({ texto, alPulsar }) => {
            const boton = nodo('button', { type: 'button', class: 'secundario', texto });
            boton.addEventListener('click', alPulsar);
            return boton;
        })) : null,
    ].filter(Boolean));
}

function episodio(e) {
    const fechas = e.ended_at ? `${formatearFecha(e.started_at)} – ${formatearFecha(e.ended_at)}` : `Desde ${formatearFecha(e.started_at)}`;
    return nodo('li', {}, nodo('article', { class: 'episodio', 'aria-label': `${etiquetaTipo(e.type)}, ${etiquetaEstado(e.status)}` },
        // h4: cada episodio cuelga del título «Episodios clínicos» (h3) (H-04).
        nodo('h4', {}, document.createTextNode(etiquetaTipo(e.type)), nodo('span', { class: 'insignia', texto: etiquetaEstado(e.status) })),
        nodo('p', { texto: fechas }),
        e.service_code ? nodo('p', { texto: `Servicio: ${e.service_code}` }) : null,
        // Dato sensible plegado por defecto: el motivo solo se ve si quien consulta lo despliega.
        nodo('details', {}, nodo('summary', { texto: 'Motivo de atención' }), nodo('p', { texto: e.reason })),
    ));
}

/** Expediente longitudinal: ficha del paciente, datos del expediente, resumen y línea de tiempo de episodios. */
export function pintarExpediente(contenedor, datos, alOcultar) {
    const { patient: p, record: r, summary: s, episodes } = datos;
    const ocultar = nodo('button', { type: 'button', class: 'secundario', texto: 'Ocultar datos del paciente' });
    ocultar.addEventListener('click', alOcultar);
    contenedor.replaceChildren(
        ficha([['Paciente', p.display_name], ['Código', p.code], ['Nacimiento', formatearNacimiento(p.birth_date)], ['Sexo', etiquetaSexo(p.sex)], ['Estado del paciente', etiquetaPaciente(p.status)],
            ['Expediente', r.record_number], ['Abierto el', formatearFecha(r.opened_at)], ['Estado del expediente', etiquetaEstado(r.status)]]),
        nodo('ul', { class: 'resumen', 'aria-label': 'Resumen' }, nodo('li', { texto: `${s.episodes} episodio(s)` }), nodo('li', { texto: `${s.open_episodes} abierto(s)` })),
        nodo('h3', { texto: 'Episodios clínicos (más reciente primero)' }),
        episodes.length ? nodo('ol', { class: 'linea-tiempo' }, ...episodes.map(episodio)) : nodo('p', { texto: 'Sin episodios registrados.' }),
        nodo('div', { class: 'acciones' }, ocultar),
    );
    contenedor.hidden = false;
}

export function limpiarExpediente(contenedor) {
    contenedor.replaceChildren();
    contenedor.hidden = true;
}
