const STATUS = {
  ISSUED: { label: "Emitida", icon: "●", cls: "st-issued" },
  COLLECTED: { label: "Tomada", icon: "●", cls: "st-collected" },
  IN_PROCESS: { label: "En proceso", icon: "●", cls: "st-process" },
  RESULTED: { label: "Con resultado", icon: "●", cls: "st-resulted" },
  VALIDATED: { label: "Validada", icon: "✓", cls: "st-validated" },
  CANCELLED: { label: "Cancelada", icon: "✕", cls: "st-cancelled" },
};

const NEXT = { ISSUED: "COLLECTED", COLLECTED: "IN_PROCESS", IN_PROCESS: "RESULTED", RESULTED: "VALIDATED" };

const catalog = [
  { id: "GLU", name: "Glucosa", area: "Química", active: true },
  { id: "HB", name: "Hemograma", area: "Hematología", active: true },
  { id: "TSH", name: "Tirotropina", area: "Tiroides", active: false },
  { id: "T4", name: "Tiroxina", area: "Tiroides", active: true },
  { id: "CREA", name: "Creatinina", area: "Química", active: true },
];

const orders = [
  {
    id: "ORD-1042", patient: "Ana Pérez", episode: "EPI-77", note: "NOTA-552",
    priority: "Urgente", status: "IN_PROCESS", orderedBy: "Dra. Méndez",
    at: "hoy 09:58", updated: "hoy 10:12",
    tests: [{ id: "GLU", name: "Glucosa" }, { id: "HB", name: "Hemograma" }],
    events: [
      { status: "ISSUED", actor: "Dra. Méndez", at: "hoy 09:58", note: "Orden emitida", corr: "a1b2c3d4" },
      { status: "COLLECTED", actor: "Enf. Solís", at: "hoy 10:05", note: "Muestra tomada", corr: "a1b2c3d4" },
      { status: "IN_PROCESS", actor: "Laboratorio", at: "hoy 10:12", note: "En análisis", corr: "a1b2c3d4" },
    ],
  },
  {
    id: "ORD-1041", patient: "Ana Pérez", episode: "EPI-77", note: "NOTA-552",
    priority: "Rutina", status: "VALIDATED", orderedBy: "Dra. Méndez",
    at: "21 oct 16:20", updated: "22 oct 08:40",
    tests: [{ id: "TSH", name: "Tirotropina" }],
    events: [
      { status: "ISSUED", actor: "Dra. Méndez", at: "21 oct 16:20", note: "Orden emitida", corr: "b2c3d4e5" },
      { status: "VALIDATED", actor: "Dra. Méndez", at: "22 oct 08:40", note: "Resultado validado", corr: "b2c3d4e5" },
    ],
  },
  {
    id: "ORD-1039", patient: "Carlos Ruiz", episode: "EPI-70", note: "NOTA-541",
    priority: "Rutina", status: "CANCELLED", orderedBy: "Dr. Lima",
    at: "20 oct 11:05", updated: "20 oct 11:30",
    tests: [{ id: "GLU", name: "Glucosa" }],
    events: [
      { status: "ISSUED", actor: "Dr. Lima", at: "20 oct 11:05", note: "Orden emitida", corr: "c3d4e5f6" },
      { status: "CANCELLED", actor: "Dr. Lima", at: "20 oct 11:30", note: "Estudio duplicado", corr: "c3d4e5f6" },
    ],
  },
  {
    id: "ORD-1035", patient: "María López", episode: "EPI-65", note: "NOTA-530",
    priority: "STAT", status: "ISSUED", orderedBy: "Dra. Méndez",
    at: "hoy 11:20", updated: "hoy 11:20",
    tests: [{ id: "HB", name: "Hemograma" }],
    events: [
      { status: "ISSUED", actor: "Dra. Méndez", at: "hoy 11:20", note: "Orden emitida", corr: "d4e5f6a7" },
    ],
  },
];

const state = {
  view: "lista",
  orderId: "ORD-1042",
  filters: { q: "", status: "" },
  testQuery: "",
  draft: freshDraft(),
  error: null,
  cancelTarget: null,
  issuing: false,
  lastCreated: null,
};

function freshDraft() {
  return {
    patient: "Ana Pérez", episode: "EPI-77", note: "NOTA-552",
    priority: "Rutina", context: "", tests: [],
  };
}

function esc(value) {
  return String(value)
    .replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;").replaceAll("'", "&#39;");
}

function badge(code) {
  const s = STATUS[code] ?? { label: code, icon: "●", cls: "" };
  return `<span class="badge ${s.cls}"><span aria-hidden="true">${s.icon}</span>${esc(s.label)}</span>`;
}

function findOrder(id) {
  return orders.find((order) => order.id === id);
}

function testById(id) {
  return catalog.find((test) => test.id === id);
}

function toast(message) {
  const node = document.getElementById("toast");
  node.textContent = message;
  node.classList.add("show");
  clearTimeout(toast.timer);
  toast.timer = setTimeout(() => node.classList.remove("show"), 3600);
}

function requestId() {
  return Math.random().toString(16).slice(2, 10);
}

function go(view, options = {}) {
  state.view = view;
  if (options.id) state.orderId = options.id;
  state.error = options.error ?? state.error;
  render();
}

function showError(code, message, status, retry = null) {
  state.view = "error";
  state.error = { code, message, status, retry, requestId: requestId() };
  render();
}

function syncUrl() {
  const params = new URLSearchParams();
  params.set("vista", state.view);
  if (state.view === "detalle" || state.view === "error") params.set("orden", state.orderId);
  if (state.lastCreated) params.set("creada", state.lastCreated);
  try {
    history.replaceState(null, "", `?${params.toString()}`);
  } catch {
    /* file:// puede bloquear replaceState; el prototipo sigue funcionando sin URL sincronizada */
  }
}

function render() {
  const app = document.getElementById("app");
  if (state.view === "lista") app.innerHTML = listView();
  else if (state.view === "emision") app.innerHTML = formView();
  else if (state.view === "confirmar") app.innerHTML = confirmView();
  else if (state.view === "detalle") app.innerHTML = detailView();
  else if (state.view === "error") app.innerHTML = errorView();
  if (state.view === "lista") renderListBody();
  if (state.view === "emision") renderFormParts();
  syncUrl();
  requestAnimationFrame(() => {
    if (state.view === "error") document.getElementById("error-panel")?.focus();
    else app.focus({ preventScroll: true });
  });
}

function listView() {
  return `
    <div class="row" style="justify-content:space-between; margin-bottom:18px">
      <div>
        <p class="eyebrow">Seguimiento</p>
        <h1>Órdenes de laboratorio</h1>
        <p class="lead">Emite una orden desde el expediente y sigue su estado.</p>
      </div>
      <a class="btn" href="?vista=emision" data-nav="emision">Nueva orden</a>
    </div>
    <form class="filters" role="search" onsubmit="return false">
      <div class="field">
        <label for="filter-q">Buscar paciente, episodio u orden</label>
        <input id="filter-q" type="search" value="${esc(state.filters.q)}" placeholder="Ej. Ana, EPI-77 u ORD-1042">
      </div>
      <div class="field">
        <label for="filter-status">Estado</label>
        <select id="filter-status">
          <option value="">Todos los estados</option>
          ${Object.entries(STATUS).map(([code, s]) => `<option value="${code}" ${state.filters.status === code ? "selected" : ""}>${esc(s.label)}</option>`).join("")}
        </select>
      </div>
      <button type="button" class="btn secondary" id="clear-filters">Limpiar</button>
    </form>
    <p class="meta" id="orders-count" aria-live="polite"></p>
    <div id="orders-body"></div>
  `;
}

function filteredOrders() {
  const q = state.filters.q.trim().toLowerCase();
  return orders.filter((order) => {
    const matchesStatus = !state.filters.status || order.status === state.filters.status;
    const haystack = `${order.id} ${order.patient} ${order.episode}`.toLowerCase();
    return matchesStatus && (!q || haystack.includes(q));
  });
}

function renderListBody() {
  const body = document.getElementById("orders-body");
  const count = document.getElementById("orders-count");
  if (!body) return;
  const visible = filteredOrders();
  if (count) count.textContent = `${visible.length} orden${visible.length === 1 ? "" : "es"} · Hospital A`;
  if (visible.length === 0) {
    body.innerHTML = `<div class="card" role="status"><h2>Sin órdenes para este filtro</h2><p class="meta">Cambia el estado o emite una nueva orden desde el expediente.</p><a class="btn" href="?vista=emision" data-nav="emision">Nueva orden</a></div>`;
    return;
  }
  body.innerHTML = `<ul class="orders">${visible.map((order) => `
    <li class="card order-card">
      <a href="?vista=detalle&orden=${encodeURIComponent(order.id)}" data-nav="detalle" data-id="${esc(order.id)}">
        <div class="order-head">
          <span class="mono">${esc(order.id)}</span>
          ${badge(order.status)}
        </div>
        <div>
          <h2 style="margin:0">${esc(order.patient)} · ${esc(order.episode)}</h2>
          <p class="meta">${esc(order.tests.map((t) => t.id).join(" · "))} · ${order.tests.length} prueba${order.tests.length === 1 ? "" : "s"}</p>
        </div>
        <p class="meta">Actualizada ${esc(order.updated)} · Prioridad ${esc(order.priority)}</p>
      </a>
    </li>`).join("")}</ul>`;
}

function formView() {
  return `
    <p class="eyebrow">Paso 2 de 3</p>
    <h1>Emitir orden de laboratorio</h1>
    <p class="lead">Paciente y episodio vienen del expediente; aquí solo eliges las pruebas.</p>
    <div class="grid">
      <section class="card context-card" aria-labelledby="contexto-title">
        <h2 id="contexto-title">Contexto del expediente</h2>
        <dl>
          <div><dt>Paciente</dt><dd>${esc(state.draft.patient)}</dd></div>
          <div><dt>Episodio</dt><dd>${esc(state.draft.episode)} · abierto</dd></div>
          <div><dt>Nota clínica</dt><dd>${esc(state.draft.note)}</dd></div>
        </dl>
        <p class="hint">Datos de solo lectura. No se editan en esta pantalla.</p>
      </section>
      <div class="stack">
        <section class="card" aria-labelledby="pruebas-title">
          <h2 id="pruebas-title">Pruebas solicitadas</h2>
          <div class="field">
            <label for="test-q">Buscar prueba activa</label>
            <input id="test-q" type="search" value="${esc(state.testQuery)}" placeholder="Nombre o código" autocomplete="off">
          </div>
          <p class="hint">Solo se muestran pruebas vigentes; las inactivas no se pueden agregar.</p>
          <ul class="search-results" id="test-results"></ul>
          <h3 style="margin:18px 0 6px;font-size:15px">Seleccionadas</h3>
          <ul class="chips" id="selected-tests"></ul>
        </section>
        <section class="card">
          <fieldset class="priority">
            <legend>Prioridad</legend>
            ${["Rutina", "Urgente", "STAT"].map((p) => `<label><input type="radio" name="priority" value="${p}" ${state.draft.priority === p ? "checked" : ""}>${p}</label>`).join("")}
          </fieldset>
          <div class="field" style="margin-top:14px">
            <label for="clinical-context">Contexto clínico (opcional)</label>
            <textarea id="clinical-context" maxlength="500" placeholder="Motivo de la solicitud">${esc(state.draft.context)}</textarea>
          </div>
        </section>
        <div class="form-footer">
          <a class="btn secondary" href="?vista=lista" data-nav="lista">Cancelar</a>
          <button class="btn" type="button" data-action="review" ${state.draft.tests.length === 0 ? "disabled" : ""}>Revisar y emitir</button>
        </div>
      </div>
    </div>
  `;
}

function renderFormParts() {
  const results = document.getElementById("test-results");
  const selected = document.getElementById("selected-tests");
  if (!results || !selected) return;
  const q = state.testQuery.trim().toLowerCase();
  const matches = catalog.filter((test) => !q || `${test.id} ${test.name} ${test.area}`.toLowerCase().includes(q));
  results.innerHTML = matches.length === 0
    ? `<li class="meta">No hay pruebas que coincidan. Revisa el nombre o el código.</li>`
    : matches.map((test) => {
        const chosen = state.draft.tests.includes(test.id);
        const disabled = !test.active || chosen;
        return `<li>
          <div><strong>${esc(test.id)} · ${esc(test.name)}</strong><p class="hint">${esc(test.area)} · ${test.active ? "vigente" : "inactiva"}</p></div>
          <button class="add" type="button" data-add="${esc(test.id)}" ${disabled ? "disabled" : ""} aria-label="Agregar ${esc(test.name)}${chosen ? " (ya seleccionada)" : ""}">${chosen ? "✓" : "+"}</button>
        </li>`;
      }).join("");
  selected.innerHTML = state.draft.tests.length === 0
    ? `<li class="hint">Ninguna prueba seleccionada.</li>`
    : state.draft.tests.map((id) => {
        const test = testById(id);
        return `<li class="chip">${esc(test.id)} · ${esc(test.name)}<button type="button" data-remove="${esc(id)}" aria-label="Quitar ${esc(test.name)}">✕</button></li>`;
      }).join("");
  const review = document.querySelector('[data-action="review"]');
  if (review) review.disabled = state.draft.tests.length === 0;
}

function confirmView() {
  const tests = state.draft.tests.map(testById);
  return `
    <p class="eyebrow">Paso 3 de 3</p>
    <h1>Confirmar emisión</h1>
    <p class="lead">Revisa los datos antes de crear la orden.</p>
    <div class="stack" style="max-width:760px">
      <section class="card summary">
        <h2>Resumen</h2>
        <dl>
          <div><dt>Paciente / episodio</dt><dd>${esc(state.draft.patient)} · ${esc(state.draft.episode)} · ${esc(state.draft.note)}</dd></div>
          <div><dt>Prioridad</dt><dd>${esc(state.draft.priority)}</dd></div>
          <div><dt>Pruebas</dt><dd>${tests.map((t) => `${esc(t.id)} · ${esc(t.name)}`).join("<br>")}</dd></div>
          <div><dt>Contexto</dt><dd>${state.draft.context ? esc(state.draft.context) : "Sin contexto adicional"}</dd></div>
        </dl>
      </section>
      <div class="warning">Se creará una orden <strong>EMITIDA</strong> y un evento inicial con tu usuario, fecha y correlación. No se puede editar después de la toma.</div>
      <div class="form-footer">
        <a class="btn secondary" href="?vista=emision" data-nav="emision">Volver</a>
        <button class="btn" type="button" data-action="issue">Emitir orden</button>
      </div>
      <p class="hint" id="issuing-status" aria-live="polite"></p>
    </div>
  `;
}

function detailView() {
  const order = findOrder(state.orderId) ?? orders[0];
  state.orderId = order.id;
  const next = NEXT[order.status];
  const cancelDisabled = order.status === "VALIDATED" || order.status === "CANCELLED";
  return `
    <p class="eyebrow">Seguimiento</p>
    <div class="row" style="justify-content:space-between;align-items:flex-start;margin-bottom:14px">
      <div>
        <h1>${esc(order.id)}</h1>
        <div class="row">${badge(order.status)}<span class="meta">${esc(order.patient)} · ${esc(order.episode)}</span></div>
      </div>
      <a class="btn secondary" href="?vista=lista" data-nav="lista">Volver a la bandeja</a>
    </div>
    <div class="grid">
      <div class="stack">
        <section class="card">
          <h2>Datos de la orden</h2>
          <dl>
            <div><dt>Paciente / episodio</dt><dd>${esc(order.patient)} · ${esc(order.episode)}</dd></div>
            <div><dt>Nota clínica</dt><dd>${esc(order.note)}</dd></div>
            <div><dt>Solicitada por</dt><dd>${esc(order.orderedBy)} · ${esc(order.at)}</dd></div>
            <div><dt>Prioridad</dt><dd>${esc(order.priority)}</dd></div>
          </dl>
        </section>
        <section class="card">
          <h2>Pruebas</h2>
          <div class="table-wrap">
            <table>
              <caption>Pruebas solicitadas en la orden ${esc(order.id)}</caption>
              <thead><tr><th scope="col">Código</th><th scope="col">Prueba</th></tr></thead>
              <tbody>${order.tests.map((t) => `<tr><td>${esc(t.id)}</td><td>${esc(t.name)}</td></tr>`).join("")}</tbody>
            </table>
          </div>
        </section>
        <section class="card">
          <h2>Acciones</h2>
          ${next ? `<button class="btn" type="button" data-action="advance">Avanzar a ${esc(STATUS[next].label)}</button>` : `<p class="meta">La orden está en su estado final; no hay más transiciones.</p>`}
          <button class="btn danger" type="button" data-action="open-cancel" ${cancelDisabled ? "disabled" : ""} style="margin-top:10px">Cancelar orden</button>
          ${cancelDisabled ? `<p class="hint">No se puede cancelar una orden ${order.status === "VALIDATED" ? "validada" : "ya cancelada"}.</p>` : `<p class="hint">Cancelar exige motivo y conserva el historial.</p>`}
        </section>
      </div>
      <aside class="stack">
        <section class="card">
          <h2>Historial</h2>
          <ol class="timeline">
            ${order.events.slice().reverse().map((event) => `<li><strong>${esc(STATUS[event.status]?.label ?? event.status)}</strong><span>${esc(event.actor)} · ${esc(event.at)}</span><span>${esc(event.note)} · corr. ${esc(event.corr)}</span></li>`).join("")}
          </ol>
        </section>
        <section class="card">
          <h2>Trazabilidad</h2>
          <p class="meta">Cada evento guarda estado anterior, nuevo, actor, fecha, motivo y correlation_id. El historial no se edita.</p>
          <p class="mono">ID de correlación: ${esc(order.events[0]?.corr ?? "—")}</p>
        </section>
      </aside>
    </div>
  `;
}

function errorView() {
  const error = state.error ?? { code: "INTERNAL_ERROR", message: "No fue posible completar la operación.", status: 500, requestId: requestId(), retry: null };
  return `
    <p class="eyebrow">Error recuperable</p>
    <h1>No se pudo completar la acción</h1>
    <div class="error-panel" id="error-panel" role="alert" tabindex="-1">
      <h2>${esc(error.message)}</h2>
      <p><strong>Código:</strong> <span class="mono">${esc(error.code)}</span> · <strong>HTTP:</strong> ${esc(error.status)}</p>
      <p><strong>request_id:</strong> <span class="mono">${esc(error.requestId)}</span></p>
      <p>Tus datos siguen en el formulario; no se creó ni modificó ninguna orden.</p>
    </div>
    <div class="row" style="margin-top:16px">
      ${error.retry ? `<a class="btn" href="?vista=detalle&orden=${encodeURIComponent(error.retry.id)}" data-nav="detalle" data-id="${esc(error.retry.id)}">Volver al detalle</a>` : `<a class="btn" href="?vista=emision" data-nav="emision">Corregir pruebas</a>`}
      <a class="btn secondary" href="?vista=lista" data-nav="lista">Ir a la bandeja</a>
      <button class="btn ghost" type="button" data-action="copy-request">Copiar request_id</button>
    </div>
  `;
}

function advanceCurrent() {
  const order = findOrder(state.orderId);
  if (!order) return;
  const next = NEXT[order.status];
  if (!next) {
    showError("INVALID_STATUS_TRANSITION", "La orden ya está en su estado final.", 409, { id: order.id });
    return;
  }
  order.status = next;
  order.updated = "ahora";
  order.events.push({ status: next, actor: "Laboratorio", at: "ahora", note: "Transición registrada", corr: order.events[0].corr });
  render();
  toast(`Estado actualizado a ${STATUS[next].label}`);
}

function tryCancel(order) {
  if (order.status === "VALIDATED") {
    showError("ORDER_ALREADY_VALIDATED", "La orden ya fue validada y no se puede cancelar.", 409, { id: order.id });
    return;
  }
  if (order.status === "CANCELLED") {
    showError("INVALID_STATUS_TRANSITION", "La orden ya está cancelada.", 409, { id: order.id });
    return;
  }
  openCancelDialog(order);
}

function openCancelDialog(order) {
  state.cancelTarget = order.id;
  const dialog = document.getElementById("cancel-dialog");
  document.getElementById("cancel-reason").value = "";
  document.getElementById("cancel-error").hidden = true;
  dialog.showModal();
  document.getElementById("cancel-reason").focus();
}

function issueOrder(button) {
  if (state.issuing) return;
  state.issuing = true;
  button.disabled = true;
  button.textContent = "Emitiendo…";
  button.setAttribute("aria-busy", "true");
  document.getElementById("issuing-status").textContent = "Enviando la orden de forma segura…";
  setTimeout(() => {
    const id = `ORD-${1043 + orders.length}`;
    const now = new Date().toLocaleTimeString("es-GT", { hour: "2-digit", minute: "2-digit" });
    const tests = state.draft.tests.map((testId) => {
      const test = testById(testId);
      return { id: test.id, name: test.name };
    });
    const order = {
      id, patient: state.draft.patient, episode: state.draft.episode, note: state.draft.note,
      priority: state.draft.priority, status: "ISSUED", orderedBy: "Dra. Méndez",
      at: `hoy ${now}`, updated: `hoy ${now}`, tests,
      events: [{ status: "ISSUED", actor: "Dra. Méndez", at: `hoy ${now}`, note: "Orden emitida", corr: requestId() }],
    };
    orders.unshift(order);
    state.issuing = false;
    state.lastCreated = id;
    state.orderId = id;
    state.draft = freshDraft();
    state.view = "detalle";
    render();
    toast(`Orden ${id} emitida`);
  }, 800);
}

document.addEventListener("click", (event) => {
  const nav = event.target.closest("[data-nav]");
  if (nav) {
    event.preventDefault();
    const view = nav.dataset.nav;
    go(view, { id: nav.dataset.id || state.orderId });
    return;
  }
  const add = event.target.closest("[data-add]");
  if (add) {
    const id = add.dataset.add;
    const test = testById(id);
    if (test?.active && !state.draft.tests.includes(id)) {
      state.draft.tests.push(id);
      renderFormParts();
      toast(`${test.name} agregada`);
    }
    return;
  }
  const remove = event.target.closest("[data-remove]");
  if (remove) {
    state.draft.tests = state.draft.tests.filter((id) => id !== remove.dataset.remove);
    renderFormParts();
    return;
  }
  const action = event.target.closest("[data-action]")?.dataset.action;
  if (action === "review") {
    if (state.draft.tests.length === 0) return;
    go("confirmar");
  } else if (action === "issue") {
    issueOrder(event.target.closest("[data-action]"));
  } else if (action === "advance") {
    advanceCurrent();
  } else if (action === "open-cancel") {
    const order = findOrder(state.orderId);
    if (order) tryCancel(order);
  } else if (action === "copy-request") {
    const code = state.error?.requestId ?? "";
    navigator.clipboard?.writeText(code).then(() => toast("request_id copiado")).catch(() => toast("Copia manual: " + code));
  } else if (action === "close-dialog") {
    document.getElementById("cancel-dialog").close();
  } else if (event.target.id === "clear-filters") {
    state.filters = { q: "", status: "" };
    render();
  }
});

document.addEventListener("input", (event) => {
  if (event.target.id === "filter-q") {
    state.filters.q = event.target.value;
    renderListBody();
  } else if (event.target.id === "filter-status") {
    state.filters.status = event.target.value;
    renderListBody();
  } else if (event.target.id === "test-q") {
    state.testQuery = event.target.value;
    renderFormParts();
  } else if (event.target.id === "clinical-context") {
    state.draft.context = event.target.value;
  }
});

document.addEventListener("change", (event) => {
  if (event.target.name === "priority") {
    state.draft.priority = event.target.value;
  } else if (event.target.id === "filter-status") {
    state.filters.status = event.target.value;
    renderListBody();
  }
});

document.getElementById("cancel-form").addEventListener("submit", (event) => {
  event.preventDefault();
  const reason = document.getElementById("cancel-reason").value.trim();
  const error = document.getElementById("cancel-error");
  if (reason.length < 5) {
    error.hidden = false;
    document.getElementById("cancel-reason").focus();
    return;
  }
  const order = findOrder(state.cancelTarget);
  const dialog = document.getElementById("cancel-dialog");
  if (!order) { dialog.close(); return; }
  if (order.status === "VALIDATED" || order.status === "CANCELLED") {
    dialog.close();
    showError("ORDER_ALREADY_VALIDATED", "La orden ya fue validada y no se puede cancelar.", 409, { id: order.id });
    return;
  }
  order.status = "CANCELLED";
  order.updated = "ahora";
  order.events.push({ status: "CANCELLED", actor: "Dra. Méndez", at: "ahora", note: reason, corr: order.events[0].corr });
  dialog.close();
  state.view = "detalle";
  state.orderId = order.id;
  render();
  toast("Orden cancelada con motivo");
});

document.getElementById("cancel-dialog").addEventListener("close", () => {
  state.cancelTarget = null;
  document.querySelector('[data-action="open-cancel"]')?.focus();
});

(function init() {
  const params = new URLSearchParams(location.search);
  const view = params.get("vista");
  if (["lista", "emision", "confirmar", "detalle", "error"].includes(view)) state.view = view;
  if (params.get("orden")) state.orderId = params.get("orden");
  if (state.view === "emision" && state.draft.tests.length === 0) state.draft.tests = ["GLU"];
  if (state.view === "confirmar" && state.draft.tests.length === 0) state.draft.tests = ["GLU", "HB"];
  if (state.view === "error") {
    const code = params.get("code") || "LAB_TEST_NOT_ACTIVE";
    state.error = {
      code,
      message: code === "ORDER_ALREADY_VALIDATED" ? "La orden ya fue validada y no se puede cancelar." : "La prueba seleccionada ya no está activa en el catálogo.",
      status: 409,
      requestId: requestId(),
      retry: { id: state.orderId },
    };
  }
  render();
  if (state.view === "detalle" && params.get("accion") === "cancelar") {
    const order = findOrder(state.orderId);
    if (order) setTimeout(() => tryCancel(order), 50);
  }
})();
