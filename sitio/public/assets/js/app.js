/* ExperientIA · Frontend vanilla. Interactúa con la API PHP (/api/*) vía fetch. */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var CSRF = (document.querySelector('meta[name=csrf]') || {}).content || '';
  var LOCALE = document.documentElement.lang || 'es';

  /* ---------- Reveals ---------- */
  var reveals = document.querySelectorAll('.reveal');
  if (reduced || !('IntersectionObserver' in window)) {
    reveals.forEach(function (el) { el.classList.add('is-visible'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('is-visible'); io.unobserve(en.target); }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });
    reveals.forEach(function (el) { io.observe(el); });
  }

  /* ---------- Header ---------- */
  var header = document.querySelector('[data-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 24); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    var toggle = header.querySelector('[data-nav-toggle]');
    if (toggle) toggle.addEventListener('click', function () {
      var open = header.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
  }

  /* ---------- Comboboxes con buscador ---------- */
  document.querySelectorAll('select[data-combobox]').forEach(function (el) {
    if (window.TomSelect) new TomSelect(el, {
      create: false,
      maxOptions: 300,
      sortField: [{ field: '$order' }],
      placeholder: el.dataset.placeholder || undefined
    });
  });

  /* ---------- Teléfono WhatsApp con bandera e indicativo ---------- */
  var itis = [];
  document.querySelectorAll('input[data-phone]').forEach(function (el) {
    if (!window.intlTelInput) return;
    var form = el.closest('form');
    var iti = window.intlTelInput(el, {
      initialCountry: 'co',
      preferredCountries: ['co', 'mx', 'us', 'br', 'ar', 'cl', 'pe', 'ec', 'es'],
      separateDialCode: true,
      countrySearch: true
    });
    itis.push({ el: el, iti: iti, form: form });
    var sync = function () {
      var wa = form && form.querySelector('input[name=phone_wa]');
      var dial = form && form.querySelector('input[name=phone_dial]');
      var num = iti.getNumber() || '';
      if (wa) wa.value = num.replace(/\D/g, '');
      if (dial) dial.value = (iti.getSelectedCountry() || {}).dialCode || '';
    };
    ['change', 'keyup', 'countrychange'].forEach(function (ev) { el.addEventListener(ev, sync); });
  });

  function telefonoValido(form) {
    var item = itis.find(function (x) { return x.form === form; });
    if (!item || !item.el.value.trim()) return true;
    var ok = item.iti.isValidNumber();
    var err = form.querySelector('[data-phone-error]');
    if (err) err.hidden = ok;
    return ok;
  }

  /* ---------- Formularios → API (fetch JSON) ---------- */
  function datosFormulario(form) {
    var data = {};
    new FormData(form).forEach(function (valor, nombre) {
      var m = nombre.match(/^(\w+)\[([\w-]+)\]$/);
      if (m) {
        data[m[1]] = data[m[1]] || {};
        data[m[1]][m[2]] = valor;
      } else {
        data[nombre] = valor;
      }
    });
    data.locale = LOCALE;
    return data;
  }

  document.querySelectorAll('form[data-api]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      if (!form.reportValidity() || !telefonoValido(form)) return;

      var boton = form.querySelector('[type=submit]');
      var textoOriginal = boton ? boton.textContent : '';
      if (boton) { boton.disabled = true; boton.textContent = boton.dataset.loading || '…'; }
      var errorBox = form.querySelector('[data-form-error]');
      if (errorBox) errorBox.hidden = true;
      form.querySelectorAll('[data-error]').forEach(function (e) { e.hidden = true; });

      fetch('/api/' + form.dataset.api, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
        body: JSON.stringify(datosFormulario(form))
      })
        .then(function (r) { return r.json().then(function (j) { return { status: r.status, json: j }; }); })
        .then(function (res) {
          if (res.json && res.json.ok) { exito(form, res.json); return; }
          var msj = (res.json && res.json.error) || 'Error';
          if (errorBox) { errorBox.textContent = msj; errorBox.hidden = false; }
          (res.json && res.json.campos || []).forEach(function (campo) {
            var e = form.querySelector('[data-error="' + campo + '"]');
            if (e) e.hidden = false;
          });
        })
        .catch(function () {
          if (errorBox) { errorBox.textContent = 'Error de conexión'; errorBox.hidden = false; }
        })
        .finally(function () {
          if (boton) { boton.disabled = false; boton.textContent = textoOriginal; }
        });
    });
  });

  function exito(form, json) {
    var api = form.dataset.api;

    if (api === 'diagnostico') {
      var panel = document.querySelector('[data-resultado]');
      if (panel && json.solucion) {
        panel.querySelector('[data-r-titulo]').textContent = json.solucion.titulo;
        panel.querySelector('[data-r-pilar]').textContent = json.solucion.pilar;
        panel.querySelector('[data-r-cambia]').textContent = json.solucion.cambia;
        panel.querySelector('[data-r-icono]').innerHTML =
          '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none"/></svg>';
        form.hidden = true;
        panel.hidden = false;
        panel.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' });
      }
      return;
    }

    if (api === 'newsletter') {
      var aviso = document.createElement('p');
      aviso.className = 'form-ok';
      aviso.textContent = form.dataset.ok || 'OK';
      form.replaceWith(aviso);
      return;
    }

    var plantilla = document.querySelector('template[data-plantilla-ok]');
    if (plantilla) {
      var nodo = plantilla.content.cloneNode(true);
      var enlace = nodo.querySelector('[data-descarga-url]');
      if (enlace && json.url) enlace.href = json.url;
      form.replaceWith(nodo);
      window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' });
    }
  }

  /* ---------- Diagnóstico: wizard por pasos ---------- */
  var diag = document.querySelector('[data-diag]');
  if (diag) {
    var pasos = Array.prototype.slice.call(diag.querySelectorAll('.diag__step'));
    var barra = diag.querySelector('.diag__progress i');
    var actual = 0;

    var mostrar = function (i) {
      actual = Math.max(0, Math.min(i, pasos.length - 1));
      pasos.forEach(function (s, j) { s.classList.toggle('is-active', j === actual); });
      if (barra) barra.style.setProperty('--p', (actual / (pasos.length - 1)) * 100 + '%');
      diag.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'start' });
    };

    diag.querySelectorAll('[data-next]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var paso = pasos[actual];
        var sinResponder = paso.querySelector('input[type=radio]') && !paso.querySelector('input[type=radio]:checked');
        if (sinResponder) return;
        mostrar(actual + 1);
      });
    });
    diag.querySelectorAll('[data-prev]').forEach(function (btn) {
      btn.addEventListener('click', function () { mostrar(actual - 1); });
    });
    diag.querySelectorAll('.diag__step input[type=radio]').forEach(function (r) {
      r.addEventListener('change', function () { setTimeout(function () { mostrar(actual + 1); }, 250); });
    });

    mostrar(0);
  }

  /* ---------- Agenda: selector día/hora en zona horaria del visitante ---------- */
  var agenda = document.querySelector('[data-agenda]');
  if (agenda) {
    var slots = JSON.parse(agenda.dataset.slots || '[]').map(function (iso) { return new Date(iso); });
    var tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    var diasEl = agenda.querySelector('[data-dias]');
    var horasEl = agenda.querySelector('[data-horas]');
    var slotInput = agenda.querySelector('input[name=slot]');
    var tzInput = agenda.querySelector('input[name=timezone]');
    var resumen = agenda.querySelector('[data-resumen]');
    var zonaEl = agenda.querySelector('[data-zona]');
    if (tzInput) tzInput.value = tz;
    if (zonaEl) zonaEl.textContent += ' (' + tz + ')';

    var clave = function (d) { return d.toLocaleDateString('en-CA', { timeZone: tz }); };
    var porDia = new Map();
    slots.forEach(function (d) {
      var k = clave(d);
      if (!porDia.has(k)) porDia.set(k, []);
      porDia.get(k).push(d);
    });

    var fDia = new Intl.DateTimeFormat(LOCALE, { weekday: 'short', timeZone: tz });
    var fNum = new Intl.DateTimeFormat(LOCALE, { day: 'numeric', timeZone: tz });
    var fMes = new Intl.DateTimeFormat(LOCALE, { month: 'short', timeZone: tz });
    var fHora = new Intl.DateTimeFormat(LOCALE, { hour: '2-digit', minute: '2-digit', timeZone: tz });
    var fFull = new Intl.DateTimeFormat(LOCALE, { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit', timeZone: tz });

    var elegirHora = function (fecha, btn) {
      horasEl.querySelectorAll('.agenda__hora').forEach(function (b) { b.classList.remove('is-active'); });
      btn.classList.add('is-active');
      slotInput.value = fecha.toISOString();
      if (resumen) { resumen.hidden = false; resumen.textContent = fFull.format(fecha); }
    };

    var elegirDia = function (k, btn) {
      diasEl.querySelectorAll('.agenda__dia').forEach(function (b) { b.classList.remove('is-active'); });
      btn.classList.add('is-active');
      horasEl.innerHTML = '';
      slotInput.value = '';
      if (resumen) resumen.hidden = true;
      (porDia.get(k) || []).forEach(function (fecha) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'agenda__hora';
        b.textContent = fHora.format(fecha);
        b.addEventListener('click', function () { elegirHora(fecha, b); });
        horasEl.appendChild(b);
      });
    };

    Array.from(porDia.keys()).sort().forEach(function (k, i) {
      var primera = porDia.get(k)[0];
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'agenda__dia';
      b.innerHTML = '<span>' + fDia.format(primera) + '</span><b>' + fNum.format(primera) + '</b><span>' + fMes.format(primera) + '</span>';
      b.addEventListener('click', function () { elegirDia(k, b); });
      diasEl.appendChild(b);
      if (i === 0) elegirDia(k, b);
    });
  }
})();
