/* Portal administrativo ExperientIA — SPA vanilla sobre /api/admin/*. */
(function () {
  'use strict';

  var CSRF = document.querySelector('meta[name=csrf]').content;
  var vista = document.getElementById('vista');
  var ICONOS = ['target', 'gear', 'analitica', 'growth', 'ia', 'bulb', 'cube', 'people', 'shield', 'impulso'];
  var DIAS = { 1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves', 5: 'Viernes', 6: 'Sábado', 7: 'Domingo' };
  var ESTADOS_LEAD = { nuevo: 'Nuevo', contactado: 'Contactado', calificado: 'Calificado', propuesta: 'Propuesta', cliente: 'Cliente', descartado: 'Descartado' };
  var TIPOS_TP = { contacto: 'Contacto', descarga: 'Descarga', diagnostico: 'Diagnóstico', reserva: 'Reserva', newsletter: 'Newsletter' };

  /* ---------- API ---------- */
  function api(metodo, ruta, cuerpo) {
    var opts = { method: metodo, headers: { 'X-CSRF': CSRF } };
    if (cuerpo instanceof FormData) { opts.body = cuerpo; }
    else if (cuerpo) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(cuerpo); }
    return fetch('/api/admin/' + ruta, opts).then(function (r) { return r.json(); });
  }

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function trAdmin(campo) {
    if (typeof campo === 'string') { try { var j = JSON.parse(campo); if (j && typeof j === 'object') campo = j; } catch (e) {} }
    if (campo && typeof campo === 'object') return campo.es || Object.values(campo)[0] || '';
    return campo == null ? '' : String(campo);
  }

  /* ---------- Esquemas del CRUD de contenido ---------- */
  var MODULOS = {
    solutions: {
      titulo: 'Soluciones', listar: ['titulo', 'skey', 'active'],
      campos: [
        { n: 'skey', l: 'Clave interna (no cambiar después de crear)', t: 'text' },
        { n: 'icon', l: 'Ícono', t: 'select', opciones: ICONOS },
        { n: 'titulo', l: 'Título', t: 'i18n' },
        { n: 'pilar', l: 'Pilar (etiqueta corta)', t: 'i18n' },
        { n: 'problema', l: 'El problema', t: 'i18n-area' },
        { n: 'como', l: 'Cómo lo hacemos (un punto por línea)', t: 'i18n-area' },
        { n: 'cambia', l: 'Qué cambia en el negocio', t: 'i18n-area' },
        { n: 'sort', l: 'Orden', t: 'number' },
        { n: 'active', l: 'Activa', t: 'check' }
      ]
    },
    products: {
      titulo: 'Productos', listar: ['nombre', 'rol', 'active'],
      campos: [
        { n: 'icon', l: 'Ícono', t: 'select', opciones: ICONOS },
        { n: 'nombre', l: 'Nombre', t: 'i18n' },
        { n: 'rol', l: 'Rol (etiqueta corta)', t: 'i18n' },
        { n: 'texto', l: 'Descripción', t: 'i18n-area' },
        { n: 'destacado', l: 'Destacado', t: 'check' },
        { n: 'sort', l: 'Orden', t: 'number' },
        { n: 'active', l: 'Activo', t: 'check' }
      ]
    },
    case_studies: {
      titulo: 'Casos', listar: ['titulo', 'sector', 'active'],
      campos: [
        { n: 'sector', l: 'Sector', t: 'i18n' },
        { n: 'titulo', l: 'Título', t: 'i18n' },
        { n: 'contexto', l: 'Contexto', t: 'i18n-area' },
        { n: 'intervencion', l: 'Intervención', t: 'i18n-area' },
        { n: 'resultados', l: 'Resultados (JSON: [{"valor":"+31%","label":{"es":"…","en":"…","pt":"…"}}])', t: 'area-json' },
        { n: 'sort', l: 'Orden', t: 'number' },
        { n: 'active', l: 'Activo', t: 'check' }
      ]
    },
    faqs: {
      titulo: 'FAQs', listar: ['pregunta', 'active'],
      campos: [
        { n: 'pregunta', l: 'Pregunta', t: 'i18n' },
        { n: 'respuesta', l: 'Respuesta', t: 'i18n-area' },
        { n: 'sort', l: 'Orden', t: 'number' },
        { n: 'active', l: 'Activa', t: 'check' }
      ]
    },
    resources: {
      titulo: 'Recursos', listar: ['titulo', 'type', 'downloads', 'active'],
      campos: [
        { n: 'slug', l: 'Slug (URL, sin espacios)', t: 'text' },
        { n: 'type', l: 'Tipo', t: 'select', opciones: ['article', 'download'], etiquetas: { article: 'Artículo (lectura libre)', download: 'Descargable (con formulario)' } },
        { n: 'tipo_label', l: 'Etiqueta (Guía, Playbook…)', t: 'i18n' },
        { n: 'titulo', l: 'Título', t: 'i18n' },
        { n: 'extracto', l: 'Extracto', t: 'i18n-area' },
        { n: 'cuerpo', l: 'Contenido del artículo (HTML permitido)', t: 'i18n-area' },
        { n: 'file_path', l: 'Archivo PDF (solo descargables)', t: 'archivo' },
        { n: 'published_at', l: 'Publicado desde (YYYY-MM-DD HH:MM:SS, vacío = borrador)', t: 'text' },
        { n: 'sort', l: 'Orden', t: 'number' },
        { n: 'active', l: 'Activo', t: 'check' }
      ]
    },
    availability_rules: {
      titulo: 'Disponibilidad', listar: ['weekday', 'start_time', 'end_time', 'active'],
      campos: [
        { n: 'weekday', l: 'Día de la semana', t: 'select', opciones: [1, 2, 3, 4, 5, 6, 7], etiquetas: DIAS },
        { n: 'start_time', l: 'Desde (HH:MM)', t: 'text' },
        { n: 'end_time', l: 'Hasta (HH:MM)', t: 'text' },
        { n: 'active', l: 'Activa', t: 'check' }
      ]
    }
  };

  /* ---------- Navegación ---------- */
  var RUTAS = [
    { g: 'CRM' },
    { id: 'resumen', titulo: 'Resumen' },
    { id: 'leads', titulo: 'Leads' },
    { id: 'reservas', titulo: 'Reservas' },
    { id: 'availability_rules', titulo: 'Disponibilidad' },
    { g: 'Contenido' },
    { id: 'solutions', titulo: 'Soluciones' },
    { id: 'products', titulo: 'Productos' },
    { id: 'case_studies', titulo: 'Casos' },
    { id: 'faqs', titulo: 'FAQs' },
    { id: 'resources', titulo: 'Recursos' }
  ];

  var nav = document.getElementById('nav');
  nav.innerHTML = RUTAS.map(function (r) {
    return r.g ? '<p class="admin__grupo">' + r.g + '</p>'
      : '<a href="#' + r.id + '" data-ruta="' + r.id + '">' + r.titulo + '</a>';
  }).join('');

  document.getElementById('salir').addEventListener('click', function () {
    api('POST', 'logout').then(function () { location.reload(); });
  });

  function navegar() {
    var ruta = location.hash.replace('#', '') || 'resumen';
    nav.querySelectorAll('a').forEach(function (a) {
      a.classList.toggle('is-active', a.dataset.ruta === ruta.split('/')[0]);
    });
    var partes = ruta.split('/');
    if (partes[0] === 'resumen') return vResumen();
    if (partes[0] === 'leads') return partes[1] ? vLeadDetalle(partes[1]) : vLeads();
    if (partes[0] === 'reservas') return vReservas();
    if (MODULOS[partes[0]]) return partes[1] ? vEditor(partes[0], partes[1]) : vLista(partes[0]);
    vResumen();
  }
  window.addEventListener('hashchange', navegar);

  /* ---------- Vistas ---------- */
  function vResumen() {
    api('GET', 'resumen').then(function (j) {
      var r = j.resumen;
      vista.innerHTML = '<h1>Resumen</h1><div class="admin__stats">'
        + '<div class="card"><b class="grad-text">' + r.leads + '</b><span>Leads totales</span></div>'
        + '<div class="card"><b class="grad-text">' + r.leads_nuevos + '</b><span>Leads nuevos</span></div>'
        + '<div class="card"><b class="grad-text">' + r.interacciones + '</b><span>Interacciones</span></div>'
        + '<div class="card"><b class="grad-text">' + r.reservas_proximas + '</b><span>Sesiones próximas</span></div>'
        + '<div class="card"><b class="grad-text">' + r.descargas + '</b><span>Descargas</span></div>'
        + '</div><p class="small">Seleccione un módulo en el menú para gestionar leads, contenido y agenda.</p>';
    });
  }

  function vLeads() {
    vista.innerHTML = '<h1>Leads</h1><div class="admin__toolbar">'
      + '<input id="q" placeholder="Buscar nombre, correo o empresa…">'
      + '<select id="fEstado"><option value="">Todos los estados</option>' + Object.keys(ESTADOS_LEAD).map(function (k) { return '<option value="' + k + '">' + ESTADOS_LEAD[k] + '</option>'; }).join('') + '</select>'
      + '</div><div id="tabla"></div>';

    var cargar = function () {
      var q = document.getElementById('q').value;
      var st = document.getElementById('fEstado').value;
      api('GET', 'leads?q=' + encodeURIComponent(q) + '&status=' + st).then(function (j) {
        document.getElementById('tabla').innerHTML = '<table><thead><tr><th>Lead</th><th>Contacto</th><th>Industria</th><th>Estado</th><th>Interacciones</th><th>Actualizado</th></tr></thead><tbody>'
          + j.items.map(function (l) {
            return '<tr data-id="' + l.id + '"><td><b style="color:var(--neutral-light)">' + esc(l.name) + '</b><br><span class="small">' + esc(l.company || '') + '</span></td>'
              + '<td>' + esc(l.email || '') + (l.phone_wa ? '<br><a href="https://wa.me/' + esc(l.phone_wa) + '" target="_blank" onclick="event.stopPropagation()">+' + esc(l.phone_wa) + '</a>' : '') + '</td>'
              + '<td>' + esc(l.industry || '—') + '</td>'
              + '<td><span class="badge" data-v="' + esc(l.status) + '">' + (ESTADOS_LEAD[l.status] || l.status) + '</span></td>'
              + '<td>' + l.touchpoints + '</td><td class="small">' + esc((l.updated_at || '').slice(0, 16)) + '</td></tr>';
          }).join('') + '</tbody></table>';
        document.getElementById('tabla').querySelectorAll('tr[data-id]').forEach(function (tr) {
          tr.addEventListener('click', function () { location.hash = 'leads/' + tr.dataset.id; });
        });
      });
    };
    document.getElementById('q').addEventListener('input', debounce(cargar, 350));
    document.getElementById('fEstado').addEventListener('change', cargar);
    cargar();
  }

  function vLeadDetalle(id) {
    api('GET', 'leads/' + id).then(function (j) {
      var l = j.lead;
      vista.innerHTML = '<a href="#leads" class="link-arrow admin__volver">← Volver a leads</a>'
        + '<h1>' + esc(l.name) + (l.company ? ' · ' + esc(l.company) : '') + '</h1>'
        + '<div id="aviso"></div>'
        + '<div class="admin__form card form-panel">'
        + '<div class="form-row"><div class="field"><label>Correo<input value="' + esc(l.email || '') + '" readonly></label></div>'
        + '<div class="field"><label>WhatsApp<input value="' + (l.phone_wa ? '+' + esc(l.phone_wa) : '') + '" readonly></label></div></div>'
        + '<div class="form-row"><div class="field"><label>País<input value="' + esc(l.country || '') + '" readonly></label></div>'
        + '<div class="field"><label>Industria / Tamaño<input value="' + esc((l.industry || '—') + ' · ' + (l.company_size || '—')) + '" readonly></label></div></div>'
        + '<div class="form-row"><div class="field"><label>Estado<select id="estado">'
        + Object.keys(ESTADOS_LEAD).map(function (k) { return '<option value="' + k + '"' + (k === l.status ? ' selected' : '') + '>' + ESTADOS_LEAD[k] + '</option>'; }).join('')
        + '</select></label></div>'
        + '<div class="field"><label>Origen / Idioma<input value="' + esc((l.source || '—') + ' · ' + (l.locale || 'es').toUpperCase()) + '" readonly></label></div></div>'
        + '<div class="field"><label>Notas internas<textarea id="notas" rows="4">' + esc(l.notes || '') + '</textarea></label></div>'
        + '<div class="admin__acciones"><button class="btn btn-primary" id="guardar">Guardar</button>'
        + (l.phone_wa ? '<a class="btn btn-ghost" target="_blank" href="https://wa.me/' + esc(l.phone_wa) + '">WhatsApp</a>' : '')
        + '<button class="btn btn-ghost" id="borrar" style="margin-left:auto;color:#ff7d9d">Eliminar</button></div>'
        + '</div>'
        + '<h2 class="h3" style="margin:1.6rem 0 .4rem">Historial (' + j.touchpoints.length + ')</h2>'
        + '<div class="admin__timeline">'
        + j.touchpoints.map(function (tp) {
          var pl = {};
          try { pl = JSON.parse(tp.payload) || {}; } catch (e) {}
          var det = Object.keys(pl).filter(function (k) { return pl[k]; }).map(function (k) { return '<b>' + esc(k) + ':</b> ' + esc(String(pl[k]).slice(0, 300)); }).join(' · ');
          return '<div class="card"><div><span class="badge" data-v="nuevo">' + (TIPOS_TP[tp.type] || tp.type) + '</span> <b style="color:var(--neutral-light)">' + esc(tp.title) + '</b></div>'
            + (det ? '<p class="small">' + det + '</p>' : '') + '<time>' + esc(tp.created_at) + '</time></div>';
        }).join('')
        + '</div>'
        + (j.reservas.length ? '<h2 class="h3" style="margin:1.6rem 0 .4rem">Sesiones</h2><div class="admin__timeline">'
          + j.reservas.map(function (b) { return '<div class="card"><div><span class="badge" data-v="' + esc(b.status) + '">' + esc(b.status) + '</span> <b style="color:var(--neutral-light)">' + esc(b.starts_at) + ' UTC</b></div>' + (b.tema ? '<p class="small">' + esc(b.tema) + '</p>' : '') + '</div>'; }).join('') + '</div>' : '');

      document.getElementById('guardar').addEventListener('click', function () {
        api('PATCH', 'leads/' + id, { status: document.getElementById('estado').value, notes: document.getElementById('notas').value })
          .then(function (r) { aviso(r.ok ? 'Guardado.' : (r.error || 'Error'), !r.ok); });
      });
      document.getElementById('borrar').addEventListener('click', function () {
        if (! confirm('¿Eliminar este lead y todo su historial?')) return;
        api('DELETE', 'leads/' + id).then(function () { location.hash = 'leads'; });
      });
    });
  }

  function vReservas() {
    api('GET', 'reservas').then(function (j) {
      vista.innerHTML = '<h1>Reservas</h1><div id="aviso"></div><table><thead><tr><th>Fecha (UTC)</th><th>Lead</th><th>Tema</th><th>Estado</th></tr></thead><tbody>'
        + j.items.map(function (b) {
          return '<tr><td>' + esc(b.starts_at) + '</td>'
            + '<td><b style="color:var(--neutral-light)">' + esc(b.lead_name) + '</b><br><span class="small">' + esc(b.lead_email || '') + '</span></td>'
            + '<td class="small">' + esc((b.tema || '').slice(0, 140)) + '</td>'
            + '<td><select data-reserva="' + b.id + '">'
            + ['confirmada', 'realizada', 'cancelada'].map(function (s) { return '<option' + (s === b.status ? ' selected' : '') + '>' + s + '</option>'; }).join('')
            + '</select></td></tr>';
        }).join('') + '</tbody></table>';
      vista.querySelectorAll('select[data-reserva]').forEach(function (sel) {
        sel.addEventListener('change', function () {
          api('PATCH', 'reservas/' + sel.dataset.reserva, { status: sel.value }).then(function (r) { aviso(r.ok ? 'Actualizado.' : r.error, !r.ok); });
        });
      });
    });
  }

  function vLista(mod) {
    var M = MODULOS[mod];
    api('GET', mod).then(function (j) {
      vista.innerHTML = '<h1>' + M.titulo + '</h1><div class="admin__toolbar"><span class="small">' + j.items.length + ' registros</span>'
        + '<a class="btn btn-primary" href="#' + mod + '/nuevo">+ Crear</a></div>'
        + '<table><thead><tr>' + M.listar.map(function (c) { return '<th>' + c + '</th>'; }).join('') + '</tr></thead><tbody>'
        + j.items.map(function (fila) {
          return '<tr data-id="' + fila.id + '">' + M.listar.map(function (c) {
            var v = fila[c];
            if (c === 'active') return '<td>' + (Number(v) ? '✓' : '—') + '</td>';
            if (c === 'weekday') return '<td>' + (DIAS[v] || v) + '</td>';
            return '<td>' + esc(trAdmin(v)).slice(0, 90) + '</td>';
          }).join('') + '</tr>';
        }).join('') + '</tbody></table>';
      vista.querySelectorAll('tr[data-id]').forEach(function (tr) {
        tr.addEventListener('click', function () { location.hash = mod + '/' + tr.dataset.id; });
      });
    });
  }

  function vEditor(mod, id) {
    var M = MODULOS[mod];
    var esNuevo = id === 'nuevo';
    var cargar = esNuevo ? Promise.resolve({}) : api('GET', mod).then(function (j) {
      return j.items.find(function (x) { return String(x.id) === String(id); }) || {};
    });

    cargar.then(function (fila) {
      var html = '<a href="#' + mod + '" class="link-arrow admin__volver">← Volver</a>'
        + '<h1>' + (esNuevo ? 'Crear' : 'Editar') + ' · ' + M.titulo + '</h1><div id="aviso"></div><div class="admin__form">';

      M.campos.forEach(function (c) {
        var v = fila[c.n];
        if (c.t === 'i18n' || c.t === 'i18n-area') {
          var val = {};
          try { val = typeof v === 'string' ? JSON.parse(v) : (v || {}); } catch (e) {}
          html += '<div class="admin__i18n"><b>' + esc(c.l) + '</b>';
          ['es', 'en', 'pt'].forEach(function (l) {
            var campoHtml = c.t === 'i18n'
              ? '<input data-campo="' + c.n + '" data-idioma="' + l + '" value="' + esc(val[l] || '') + '">'
              : '<textarea data-campo="' + c.n + '" data-idioma="' + l + '" rows="3">' + esc(val[l] || '') + '</textarea>';
            html += '<div class="field"><label>' + l.toUpperCase() + (l === 'es' ? ' <span>(obligatorio; EN/PT vacíos muestran ES)</span>' : '') + campoHtml + '</label></div>';
          });
          html += '</div>';
        } else if (c.t === 'select') {
          html += '<div class="field"><label>' + esc(c.l) + '<select data-campo="' + c.n + '">'
            + c.opciones.map(function (o) { return '<option value="' + o + '"' + (String(v) === String(o) ? ' selected' : '') + '>' + ((c.etiquetas || {})[o] || o) + '</option>'; }).join('')
            + '</select></label></div>';
        } else if (c.t === 'check') {
          html += '<div class="field"><label><input type="checkbox" data-campo="' + c.n + '"' + (esNuevo || Number(v) ? ' checked' : '') + '> ' + esc(c.l) + '</label></div>';
        } else if (c.t === 'archivo') {
          html += '<div class="field"><label>' + esc(c.l) + '<input type="file" accept="application/pdf" data-archivo></label>'
            + '<input type="hidden" data-campo="file_path" value="' + esc(v || '') + '">'
            + (v ? '<p class="small">Actual: ' + esc(v) + '</p>' : '') + '</div>';
        } else if (c.t === 'area-json') {
          var vjson = v;
          try { vjson = JSON.stringify(typeof v === 'string' ? JSON.parse(v) : v, null, 1); } catch (e) {}
          html += '<div class="field"><label>' + esc(c.l) + '<textarea data-campo="' + c.n + '" data-json rows="6">' + esc(vjson || '') + '</textarea></label></div>';
        } else {
          html += '<div class="field"><label>' + esc(c.l) + '<input type="' + (c.t === 'number' ? 'number' : 'text') + '" data-campo="' + c.n + '" value="' + esc(v == null ? (c.t === 'number' ? '0' : '') : v) + '"></label></div>';
        }
      });

      html += '<div class="admin__acciones"><button class="btn btn-primary" id="guardar">Guardar</button>'
        + (esNuevo ? '' : '<button class="btn btn-ghost" id="borrar" style="margin-left:auto;color:#ff7d9d">Eliminar</button>')
        + '</div></div>';
      vista.innerHTML = html;

      document.getElementById('guardar').addEventListener('click', function () {
        var datos = {};
        var invalido = false;
        M.campos.forEach(function (c) {
          if (c.t === 'i18n' || c.t === 'i18n-area') {
            var obj = {};
            vista.querySelectorAll('[data-campo="' + c.n + '"]').forEach(function (el) { obj[el.dataset.idioma] = el.value; });
            datos[c.n] = obj;
          } else if (c.t === 'check') {
            datos[c.n] = vista.querySelector('[data-campo="' + c.n + '"]').checked ? 1 : 0;
          } else if (c.t === 'area-json') {
            var crudo = vista.querySelector('[data-campo="' + c.n + '"]').value;
            try { datos[c.n] = JSON.parse(crudo || '[]'); }
            catch (e) { aviso('JSON inválido en "' + c.l + '"', true); invalido = true; }
          } else {
            var el = vista.querySelector('[data-campo="' + c.n + '"]');
            if (el) datos[c.n] = el.value === '' ? null : el.value;
          }
        });
        if (invalido) return;

        var archivo = vista.querySelector('[data-archivo]');
        var subir = archivo && archivo.files.length
          ? (function () { var fd = new FormData(); fd.append('archivo', archivo.files[0]); return api('POST', 'archivo', fd); })()
          : Promise.resolve(null);

        subir.then(function (r) {
          if (r) {
            if (! r.ok) { aviso(r.error || 'Error subiendo archivo', true); throw new Error(); }
            datos.file_path = r.file_path;
          }
          return esNuevo ? api('POST', mod, datos) : api('PUT', mod + '/' + id, datos);
        }).then(function (r) {
          if (r.ok) { location.hash = mod; } else { aviso(r.error || 'Error', true); }
        }).catch(function () {});
      });

      var borrar = document.getElementById('borrar');
      if (borrar) borrar.addEventListener('click', function () {
        if (! confirm('¿Eliminar este registro?')) return;
        api('DELETE', mod + '/' + id).then(function () { location.hash = mod; });
      });
    });
  }

  /* ---------- Utilidades ---------- */
  function aviso(texto, esError) {
    var box = document.getElementById('aviso');
    if (! box) return;
    box.innerHTML = '<div class="aviso' + (esError ? ' err' : '') + '">' + esc(texto) + '</div>';
    setTimeout(function () { box.innerHTML = ''; }, 3500);
  }

  function debounce(fn, ms) {
    var t;
    return function () { clearTimeout(t); t = setTimeout(fn, ms); };
  }

  navegar();
})();
