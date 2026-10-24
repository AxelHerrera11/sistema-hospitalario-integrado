const wards = [
  { id: 'MED', name: 'Medicina', occupied: 8, available: 4, maintenance: 1, unverified: 1, inconsistencies: 2 },
  { id: 'UCI', name: 'UCI', occupied: 4, available: 2, maintenance: 0, unverified: 0, inconsistencies: 0 },
];

const dashboard = document.querySelector('#dashboard');
const announcer = document.querySelector('#announcer');

function metric(label, value) {
  return `<div class="metric"><span>${label}</span><strong>${value}</strong></div>`;
}

function wardRate(ward) {
  const capacity = ward.occupied + ward.available;
  return capacity ? `${(ward.occupied * 100 / capacity).toFixed(1)} %` : 'N/A';
}

function renderData(selected = 'all', stale = false, focusTarget = null) {
  const fragment = document.querySelector('#success-template').content.cloneNode(true);
  dashboard.replaceChildren(fragment);
  const visible = selected === 'all' ? wards : wards.filter((ward) => ward.id === selected);
  const total = (key) => visible.reduce((sum, ward) => sum + ward[key], 0);
  const occupied = total('occupied');
  const available = total('available');
  const capacity = occupied + available;
  const rate = capacity ? `${(occupied * 100 / capacity).toFixed(1)} %` : 'N/A';

  document.querySelector('#metrics').innerHTML = metric('Ocupadas', occupied) + metric('Disponibles', available) + metric('Capacidad', capacity) + metric('Tasa', rate);
  document.querySelector('#issue-count').textContent = total('inconsistencies');
  document.querySelector('#ward').value = selected;
  document.querySelector('#ward-rows').innerHTML = visible.map((ward) => `<tr><th scope="row">${ward.name}</th><td>${ward.occupied}</td><td>${ward.available}</td><td>${ward.maintenance}</td><td>${ward.unverified}</td><td>${wardRate(ward)}</td></tr>`).join('');
  document.querySelector('#ward-cards').innerHTML = visible.map((ward) => `<article class="ward-card"><h3>${ward.name}</h3><dl><dt>Ocupadas</dt><dd>${ward.occupied}</dd><dt>Disponibles</dt><dd>${ward.available}</dd><dt>Mantenimiento</dt><dd>${ward.maintenance}</dd><dt>No verificadas</dt><dd>${ward.unverified}</dd><dt>Tasa</dt><dd>${wardRate(ward)}</dd><dt>Inconsistencias</dt><dd>${ward.inconsistencies}</dd></dl></article>`).join('');

  document.querySelector('#ward').addEventListener('change', (event) => renderData(event.target.value, stale, 'ward'));
  document.querySelector('#refresh').addEventListener('click', () => {
    document.querySelector('#refresh').disabled = true;
    announcer.textContent = 'Actualizando fotografía de ocupación.';
    setTimeout(() => {
      renderData(selected, false, 'refresh');
      announcer.textContent = 'Fotografía actualizada correctamente.';
    }, 300);
  });

  if (stale) {
    const banner = document.createElement('p');
    banner.className = 'stale-banner';
    banner.innerHTML = '<strong>Datos desactualizados.</strong> Última fotografía: 09:45, hace 15 minutos. Código: req-demo-503.';
    dashboard.querySelector('section').prepend(banner);
    const cutoff = document.querySelector('.cutoff time');
    cutoff.dateTime = '2026-10-16T09:45:00-06:00';
    cutoff.textContent = '16/10/2026 09:45 -06';
    document.querySelector('#freshness').textContent = 'No fue posible actualizar';
    announcer.textContent = 'No fue posible actualizar. Se muestran datos de las 09:45, hace 15 minutos.';
  }

  if (focusTarget === 'ward') document.querySelector('#ward').focus();
  if (focusTarget === 'refresh') document.querySelector('#refresh').focus();
}

function renderDenied() {
  dashboard.replaceChildren(document.querySelector('#denied-template').content.cloneNode(true));
  dashboard.querySelector('[tabindex="-1"]').focus();
  announcer.textContent = 'Acceso restringido. Se eliminaron los datos anteriores.';
}

document.addEventListener('click', (event) => {
  const state = event.target.dataset.state;
  if (state === 'success') renderData();
  if (state === 'stale') renderData('all', true);
  if (state === 'denied') renderDenied();
});

renderData();
