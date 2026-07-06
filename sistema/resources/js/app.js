import TomSelect from 'tom-select';
import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';

/* ------------------------------------------------------------
   Motion: reveals con IntersectionObserver (reduced-motion aware)
   ------------------------------------------------------------ */
document.documentElement.classList.add('js');
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const reveals = document.querySelectorAll('.reveal');
if (reduced || !('IntersectionObserver' in window)) {
  reveals.forEach((el) => el.classList.add('is-visible'));
} else {
  const io = new IntersectionObserver((entries) => {
    for (const e of entries) if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
  reveals.forEach((el) => io.observe(el));
}

/* ------------------------------------------------------------
   Header: scroll + menú móvil
   ------------------------------------------------------------ */
const header = document.querySelector('[data-header]');
if (header) {
  const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 24);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
  const toggle = header.querySelector('[data-nav-toggle]');
  toggle?.addEventListener('click', () => {
    const open = header.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(open));
  });
}

/* ------------------------------------------------------------
   Selects con buscador (país, industria, tamaño)
   ------------------------------------------------------------ */
document.querySelectorAll('select[data-combobox]').forEach((el) => {
  new TomSelect(el, {
    create: false,
    allowEmptyOption: false,
    maxOptions: 300,
    sortField: [{ field: '$order' }],
    placeholder: el.dataset.placeholder || undefined,
  });
});

/* ------------------------------------------------------------
   Teléfono WhatsApp: bandera + indicativo con buscador.
   Se muestra +57 al usuario; se envía E.164 sin "+" (wa.me).
   ------------------------------------------------------------ */
document.querySelectorAll('input[data-phone]').forEach((el) => {
  const form = el.closest('form');
  const waField = form?.querySelector('input[name=phone_wa]');
  const dialField = form?.querySelector('input[name=phone_dial]');
  const locale = document.documentElement.lang || 'es';

  const iti = intlTelInput(el, {
    initialCountry: el.dataset.country || 'co',
    preferredCountries: ['co', 'mx', 'us', 'br', 'ar', 'cl', 'pe', 'ec', 'es'],
    separateDialCode: true,
    countrySearch: true,
    i18n: undefined,
    autoPlaceholder: 'polite',
  });

  const sync = () => {
    const e164 = iti.getNumber(); // +573001234567
    if (waField) waField.value = e164 ? e164.replace(/\D/g, '') : '';
    if (dialField) dialField.value = iti.getSelectedCountry()?.dialCode || '';
  };
  el.addEventListener('change', sync);
  el.addEventListener('keyup', sync);
  el.addEventListener('countrychange', sync);

  form?.addEventListener('submit', (ev) => {
    sync();
    if (el.value.trim() && !iti.isValidNumber()) {
      ev.preventDefault();
      const err = form.querySelector('[data-phone-error]');
      if (err) err.hidden = false;
      el.focus();
    }
  });
});

/* ------------------------------------------------------------
   Diagnóstico: wizard por pasos
   ------------------------------------------------------------ */
const diag = document.querySelector('[data-diag]');
if (diag) {
  const steps = [...diag.querySelectorAll('.diag__step')];
  const bar = diag.querySelector('.diag__progress i');
  let current = 0;

  const show = (i) => {
    current = Math.max(0, Math.min(i, steps.length - 1));
    steps.forEach((s, j) => s.classList.toggle('is-active', j === current));
    if (bar) bar.style.setProperty('--p', `${(current / (steps.length - 1)) * 100}%`);
    diag.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
  };

  diag.querySelectorAll('[data-next]').forEach((btn) => btn.addEventListener('click', () => {
    const step = steps[current];
    const pending = step.querySelector('input[type=radio]:not(:checked)') && !step.querySelector('input[type=radio]:checked');
    if (pending) { step.querySelector('.diag__opts')?.animate([{ transform: 'translateX(0)' }, { transform: 'translateX(6px)' }, { transform: 'translateX(0)' }], { duration: 180 }); return; }
    show(current + 1);
  }));
  diag.querySelectorAll('[data-prev]').forEach((btn) => btn.addEventListener('click', () => show(current - 1)));

  // Avance automático al elegir una opción
  diag.querySelectorAll('.diag__step input[type=radio]').forEach((r) => {
    r.addEventListener('change', () => setTimeout(() => show(current + 1), 250));
  });

  show(0);
}

/* ------------------------------------------------------------
   Agenda: selector de día/hora en la zona horaria del visitante
   ------------------------------------------------------------ */
const agenda = document.querySelector('[data-agenda]');
if (agenda) {
  const slots = JSON.parse(agenda.dataset.slots || '[]').map((iso) => new Date(iso));
  const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
  const locale = document.documentElement.lang || 'es';
  const diasEl = agenda.querySelector('[data-dias]');
  const horasEl = agenda.querySelector('[data-horas]');
  const slotInput = agenda.querySelector('input[name=slot]');
  const tzInput = agenda.querySelector('input[name=timezone]');
  const resumen = agenda.querySelector('[data-resumen]');
  const zonaEl = agenda.querySelector('[data-zona]');
  if (tzInput) tzInput.value = tz;
  if (zonaEl) zonaEl.textContent += ` (${tz})`;

  const dayKey = (d) => d.toLocaleDateString('en-CA', { timeZone: tz }); // YYYY-MM-DD local
  const byDay = new Map();
  slots.forEach((d) => {
    const k = dayKey(d);
    if (!byDay.has(k)) byDay.set(k, []);
    byDay.get(k).push(d);
  });

  const fmtDia = new Intl.DateTimeFormat(locale, { weekday: 'short', timeZone: tz });
  const fmtNum = new Intl.DateTimeFormat(locale, { day: 'numeric', timeZone: tz });
  const fmtMes = new Intl.DateTimeFormat(locale, { month: 'short', timeZone: tz });
  const fmtHora = new Intl.DateTimeFormat(locale, { hour: '2-digit', minute: '2-digit', timeZone: tz });
  const fmtFull = new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit', timeZone: tz });

  const pickHora = (date, btn) => {
    horasEl.querySelectorAll('.agenda__hora').forEach((b) => b.classList.remove('is-active'));
    btn.classList.add('is-active');
    slotInput.value = date.toISOString();
    if (resumen) { resumen.hidden = false; resumen.textContent = fmtFull.format(date); }
  };

  const pickDia = (key, btn) => {
    diasEl.querySelectorAll('.agenda__dia').forEach((b) => b.classList.remove('is-active'));
    btn.classList.add('is-active');
    horasEl.innerHTML = '';
    slotInput.value = '';
    if (resumen) resumen.hidden = true;
    (byDay.get(key) || []).forEach((date) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'agenda__hora';
      b.textContent = fmtHora.format(date);
      b.addEventListener('click', () => pickHora(date, b));
      horasEl.appendChild(b);
    });
  };

  [...byDay.keys()].sort().forEach((key, i) => {
    const first = byDay.get(key)[0];
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'agenda__dia';
    b.innerHTML = `<span>${fmtDia.format(first)}</span><b>${fmtNum.format(first)}</b><span>${fmtMes.format(first)}</span>`;
    b.addEventListener('click', () => pickDia(key, b));
    diasEl.appendChild(b);
    if (i === 0) pickDia(key, b);
  });
}
