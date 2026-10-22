'use strict';

/*
 * Prototipo navegable de la bitácora (Semana 11). Consume el mismo contrato
 * GET /api/v1/audit-events que la API real, servido por public/index.php con
 * datos ficticios. Todo dato del servidor se escribe con textContent (sin innerHTML).
 */
const API = '/api/v1/audit-events';
const TENANTS = { admin: '11111111-1111-4111-8111-111111111111', medico: '11111111-1111-4111-8111-111111111111', admin_h2: '22222222-2222-4222-8222-222222222222' };
const FIELDS = ['usuario', 'modulo', 'accion', 'desde', 'hasta', 'resultado', 'correlacion', 'por_pagina'];
const LABELS = { usuario: 'Usuario', modulo: 'Módulo', accion: 'Acción', desde: 'Desde', hasta: 'Hasta', resultado: 'Resultado', correlacion: 'Correlación', por_pagina: 'Resultados por página', pagina: 'Página' };
const ERRORS = {
  formato_invalido: 'tiene un formato no válido.',
  fecha_invalida: 'no es una fecha válida (AAAA-MM-DD).',
  anterior_a_desde: 'debe ser igual o posterior a «Desde».',
  fuera_de_rango: 'está fuera del rango permitido.',
  valor_no_permitido: 'no es un valor permitido.',
  parametro_no_permitido: 'no es un filtro reconocido.',
  tipo_invalido: 'tiene un tipo no válido.',
};
const OUTCOMES = { success: 'Ocurrió', failure: 'Intento fallido', denied: 'Intento denegado', unknown: 'Sin resultado registrado' };
const STATES = {
  401: ['denied', 'Sesión requerida', 'Inicie sesión para consultar la bitácora.'],
  403: ['denied', 'Acceso denegado', 'Su rol no tiene el permiso para consultar la bitácora de este hospital. Si lo necesita, solicítelo al administrador.'],
  400: ['error', 'Hospital no indicado', 'No se pudo determinar el hospital de la sesión.'],
  503: ['error', 'Servicio no disponible', 'La bitácora o el control de acceso no responden. Intente de nuevo en unos minutos.'],
  500: ['error', 'Error inesperado', 'No se pudo completar la consulta.'],
};

const $ = (id) => document.getElementById(id);
const form = $('filter-form');
let page = 1;
let lastPage = 0;
let controller = null;

function el(tag, text, attrs = {}) {
  const node = document.createElement(tag);
  if (text !== undefined && text !== null) node.textContent = String(text);
  Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, v));
  return node;
}

function formatDate(iso) {
  return new Intl.DateTimeFormat('es-GT', { dateStyle: 'medium', timeStyle: 'medium', timeZone: 'America/Guatemala' }).format(new Date(iso));
}

function clearErrors() {
  $('error-summary').hidden = true;
  $('error-summary').replaceChildren();
  FIELDS.forEach((name) => {
    const input = form.elements[name];
    const msg = $('e-' + name);
    if (input) input.removeAttribute('aria-invalid');
    if (msg) { msg.hidden = true; msg.textContent = ''; }
  });
}

function showFieldErrors(errors) {
  const summary = $('error-summary');
  const list = el('ul');
  Object.entries(errors).forEach(([name, code]) => {
    const text = `${LABELS[name] ?? name} ${ERRORS[code] ?? 'no es válido.'}`;
    const input = form.elements[name];
    const msg = $('e-' + name);
    if (input && msg) {
      input.setAttribute('aria-invalid', 'true');
      msg.textContent = text;
      msg.hidden = false;
      const item = el('li');
      item.append(el('a', text, { href: '#' + input.id }));
      list.append(item);
    } else {
      list.append(el('li', text));
    }
  });
  summary.replaceChildren(el('strong', 'Revise los filtros:'), list);
  summary.hidden = false;
  summary.focus();
}

function setState(kind, title, body) {
  const box = $('state');
  box.className = 'state ' + kind;
  box.replaceChildren(el('h3', title), el('p', body));
  box.hidden = false;
}

function query() {
  const params = new URLSearchParams();
  FIELDS.forEach((name) => {
    const value = (form.elements[name]?.value ?? '').trim();
    if (value !== '') params.set(name, value);
  });
  params.set('pagina', String(page));
  const failure = $('demo-failure').value;
  if (failure === 'almacen' || failure === 'rbac') params.set('demo_falla', failure);
  if (failure === 'lento') params.set('demo_latencia', '1500');
  return params;
}

async function search(focusResults) {
  controller?.abort();
  controller = new AbortController();
  clearErrors();
  $('table-wrap').hidden = true;
  $('pager').hidden = true;
  setState('loading', 'Cargando…', 'Consultando la bitácora.');
  $('status').textContent = 'Cargando resultados…';
  document.querySelector('.results').setAttribute('aria-busy', 'true');
  form.querySelector('button[type=submit]').disabled = true;

  const session = $('demo-session').value;
  const headers = { 'X-Request-ID': 'req-proto-' + Date.now().toString(36) };
  if (session) { headers['X-Demo-Session'] = session; headers['X-Tenant-ID'] = TENANTS[session]; }

  try {
    const response = await fetch(`${API}?${query()}`, { headers, signal: controller.signal });
    const body = await response.json().catch(() => ({}));
    // H-GOB-10 / F-02: sin sesión o sin permiso no se muestran los filtros (RV-01, W5).
    document.querySelector('.filters').hidden = response.status === 401 || response.status === 403;
    if (response.status === 200) {
      render(body);
    } else if (response.status === 422) {
      $('state').hidden = true;
      $('status').textContent = 'Hay filtros con errores.';
      showFieldErrors(body.errors ?? {});
      return;
    } else {
      const [kind, title, text] = STATES[response.status] ?? STATES[500];
      setState(kind, title, `${text} Referencia: ${body.trace_id ?? 'no disponible'}.`);
      $('status').textContent = title;
    }
  } catch (error) {
    if (error.name === 'AbortError') return;
    setState('error', 'Sin conexión', 'No se pudo contactar al servidor de demostración.');
    $('status').textContent = 'Sin conexión';
  } finally {
    document.querySelector('.results').removeAttribute('aria-busy');
    form.querySelector('button[type=submit]').disabled = false;
  }
  if (focusResults) $('resultados-titulo').focus();
}

function render(body) {
  const { data = [], meta = {} } = body;
  lastPage = meta.total_pages ?? 0;
  if (data.length === 0) {
    setState('empty', 'Sin resultados', meta.total > 0
      ? 'No hay eventos en esta página. Vuelva a la página anterior.'
      : 'Ningún evento coincide con los filtros. Amplíe el rango de fechas o quite filtros.');
    $('status').textContent = 'Sin resultados.';
    $('pager').hidden = !(meta.total > 0);
  } else {
    $('state').hidden = true;
    $('status').textContent = `${meta.total} eventos encontrados. Página ${meta.page} de ${meta.total_pages}.`;
    $('rows').replaceChildren(...data.map(row));
    $('table-wrap').hidden = false;
    $('pager').hidden = false;
  }
  $('page-label').textContent = `Página ${meta.page ?? page} de ${Math.max(lastPage, 1)}`;
  $('prev').disabled = page <= 1;
  $('next').disabled = page >= lastPage;
}

function row(event) {
  const tr = el('tr');
  const cell = (label, child) => { const td = el('td', null, { 'data-label': label }); td.append(child); return td; };
  const outcome = el('span', OUTCOMES[event.outcome] ?? event.outcome, { class: 'badge ' + event.outcome });
  const resource = event.resource ? `${event.resource.type} ${event.resource.id ?? ''}` : '—';
  const button = el('button', 'Ver detalle', { type: 'button', 'aria-label': `Ver detalle del evento ${event.event} del ${formatDate(event.occurred_at)}` });
  button.addEventListener('click', () => openDetail(event, button));
  tr.append(
    cell('Fecha y hora', el('time', formatDate(event.occurred_at), { datetime: event.occurred_at })),
    cell('Usuario', el('span', event.actor_id, { class: 'id' })),
    cell('Evento', el('span', event.event)),
    cell('Recurso', el('span', resource, { class: 'id' })),
    cell('Resultado', outcome),
    cell('Acciones', button),
  );
  return tr;
}

function openDetail(event, opener) {
  const dl = $('detail-body');
  const items = [
    ['Identificador', event.event_id],
    ['Fecha y hora', formatDate(event.occurred_at) + ' (UTC ' + event.occurred_at + ')'],
    ['Usuario', event.actor_id],
    ['Hospital', event.tenant_id],
    ['Módulo', event.module],
    ['Acción', event.action],
    ['Recurso', event.resource ? `${event.resource.type} / ${event.resource.id ?? '—'}` : '—'],
    ['Resultado', `${OUTCOMES[event.outcome] ?? event.outcome}${event.occurred ? '' : ' (no se aplicó ningún cambio)'}`],
    ['Criticidad', event.criticality === 'security' ? 'Seguridad (registro obligatorio)' : event.criticality === 'informational' ? 'Informativo' : 'No registrada'],
    ['Motivo', event.reason ?? '—'],
    ['Correlación', event.correlation_id ?? '—'],
    ['Contexto', Object.keys(event.context ?? {}).length ? JSON.stringify(event.context) : '—'],
    ['Claves omitidas', event.redacted_keys?.length ? event.redacted_keys.join(', ') + ' (eliminadas por seguridad)' : 'Ninguna'],
  ];
  dl.replaceChildren(...items.flatMap(([k, v]) => [el('dt', k), el('dd', v)]));
  const dialog = $('detail');
  dialog.showModal();
  $('detail-close').focus();
  dialog.addEventListener('close', () => opener.focus(), { once: true });
}

form.addEventListener('submit', (e) => { e.preventDefault(); page = 1; search(true); });
form.addEventListener('reset', () => { setTimeout(() => { page = 1; clearErrors(); search(false); }, 0); });
$('prev').addEventListener('click', () => { if (page > 1) { page--; search(true); } });
$('next').addEventListener('click', () => { if (page < lastPage) { page++; search(true); } });
$('detail-close').addEventListener('click', () => $('detail').close());
$('demo-session').addEventListener('change', () => { page = 1; search(false); });
$('demo-failure').addEventListener('change', () => { page = 1; search(false); });

search(false);
