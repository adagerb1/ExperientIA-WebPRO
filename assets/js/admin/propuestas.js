// Portal admin · Propuestas comerciales: el ciclo comercial después del marketing.
// Crear (ligada a un lead), redactar con AlexIA, editar secciones, enviar el
// mailing de marca con enlace confidencial (email+NIT) y seguir la trazabilidad
// (envío, aperturas, aceptación). Al enviar, el lead pasa a estado "propuesta";
// al aceptar el cliente, pasa a "cliente".
import { api, toast } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { PageHelp, HelpDot } from './help.js';

const LISTAS = [
  ['objetivos', 'Objetivos'], ['alcance', 'Alcance'], ['entregables', 'Entregables'],
  ['payment_terms', 'Forma de pago'], ['conditions', 'Consideraciones importantes'], ['next_steps', 'Próximos pasos'],
];

function normalizar(c) {
  c = (c && typeof c === 'object') ? c : {};
  const lista = (k, t) => ({ title: (c[k] && c[k].title) || t, items: ((c[k] && c[k].items) || []).join('\n') });
  const texto = (k, t) => ({ title: (c[k] && c[k].title) || t, text: (c[k] && c[k].text) || '' });
  return {
    eyebrow: c.eyebrow || 'Propuesta confidencial', title: c.title || '', subtitle: c.subtitle || '', hero_note: c.hero_note || '',
    resumen: texto('resumen', 'Resumen ejecutivo'), contexto: texto('contexto', 'Contexto'), tiempo: texto('tiempo', 'Tiempos'),
    objetivos: lista('objetivos', 'Objetivos'), alcance: lista('alcance', 'Alcance'), entregables: lista('entregables', 'Entregables'),
    payment_terms: lista('payment_terms', 'Forma de pago'), conditions: lista('conditions', 'Consideraciones importantes'), next_steps: lista('next_steps', 'Próximos pasos'),
    fases: (c.fases || []).map(f => ({ phase: f.phase || '', title: f.title || '', text: f.text || '' })),
    inversion: { title: (c.inversion && c.inversion.title) || 'Inversión', amount: (c.inversion && c.inversion.amount) || '', note: (c.inversion && c.inversion.note) || '' },
    cta: texto('cta', 'Demos el siguiente paso'),
  };
}

function serializar(f) {
  const lineas = (s) => s.split('\n').map(x => x.trim()).filter(Boolean);
  const out = {
    eyebrow: f.eyebrow, title: f.title, subtitle: f.subtitle, hero_note: f.hero_note,
    resumen: f.resumen, contexto: f.contexto, tiempo: f.tiempo,
    fases: f.fases.filter(x => x.title.trim()),
    inversion: f.inversion, cta: f.cta,
  };
  for (const [k] of LISTAS) { out[k] = { title: f[k].title, items: lineas(f[k].items) }; }
  return out;
}

export const Propuestas = {
  components: { Icon, PageHelp, HelpDot },
  template: `<div><h1>Propuestas
      <PageHelp titulo="Propuestas comerciales"
        que="El ciclo comercial completo después del marketing: creas una propuesta para un lead (o cliente), AlexIA redacta el borrador con los datos del CRM y tu brief, la ajustas, y la envías como página confidencial en línea. El cliente la abre con su correo autorizado + NIT, cada apertura queda registrada, y puede aceptarla con un clic — al aceptar, el lead pasa automáticamente a estado cliente."
        :pasos="['Pulsa «Nueva propuesta»: elige el lead (opcional), escribe cliente, NIT y el correo del contacto.','Abre la propuesta y usa «Redactar con AlexIA» con un brief corto (qué vas a proponer, monto si ya lo tienes).','Revisa y ajusta cada sección: resumen, objetivos, alcance, fases, inversión, condiciones…','Pulsa «Enviar»: el cliente recibe el correo de marca con el botón a su página confidencial.','Sigue la trazabilidad aquí y en el timeline del lead: enviada → vista (N aperturas) → aceptada.']"/></h1>
    <p class="adm__sub">Del lead al cierre: propuestas confidenciales en línea, redactadas con AlexIA y con trazabilidad completa</p>

    <div class="conn-kpis rev-kpis">
      <div class="glass stat"><b class="grad-text">{{ resumen.total||0 }}</b><span>Propuestas</span></div>
      <div class="glass stat"><b class="grad-text">{{ resumen.borrador||0 }}</b><span>En borrador</span></div>
      <div class="glass stat"><b class="grad-text">{{ resumen.enviada||0 }}</b><span>Enviadas</span></div>
      <div class="glass stat"><b class="grad-text">{{ resumen.aceptada||0 }}</b><span>Aceptadas</span></div></div>

    <div class="rev-toolbar">
      <button class="btn btn-primary btn-sm" @click="abrirNueva"><Icon name="plus" :size="14"/> Nueva propuesta</button></div>

    <div v-if="!items.length" class="glass panel" style="text-align:center;padding:2.5rem">
      <p>Aún no hay propuestas.</p><p class="small">Crea la primera y deja que AlexIA redacte el borrador.</p></div>

    <div class="rev-list">
      <div v-for="p in items" :key="p.id" class="glass rev-card" :class="'st-'+cssEstado(p.estado)">
        <div class="rev-head">
          <div class="rev-who"><span class="rev-avatar">{{ (p.cliente||'?').charAt(0).toUpperCase() }}</span>
            <div><b>{{ p.cliente }}</b>
              <div class="rev-stars"><em>{{ p.contenido.title || p.title || 'Sin título aún' }}{{ p.lead_name ? ' · lead: '+p.lead_name : '' }}</em></div></div></div>
          <div class="rev-flags">
            <span class="badge" :class="claseEstado(p.estado)">{{ labelEstado(p.estado) }}</span>
            <span v-if="p.views>0" class="badge cliente" title="Aperturas registradas">👁 {{ p.views }}</span></div></div>
        <p class="small" style="color:var(--text-tertiary)">
          {{ p.sent_at ? 'Enviada: '+fecha(p.sent_at)+' · ' : '' }}{{ p.last_view ? 'Última apertura: '+fecha(p.last_view) : (p.estado==='borrador'?'Sin enviar':'Sin aperturas aún') }}</p>
        <div class="conn-actions">
          <button class="btn btn-ghost btn-sm" @click="editar(p)">Abrir / editar</button>
          <button class="btn btn-ghost btn-sm" @click="copiar(p)">Copiar enlace</button>
          <button class="btn btn-primary btn-sm" v-if="p.estado!=='aceptada'" @click="mandar(p)" :disabled="p._busy">{{ p._busy?'Enviando…':'Enviar al cliente' }}</button>
          <button class="btn btn-danger btn-sm" @click="eliminar(p)">Eliminar</button></div>
        <p v-if="p._msg" class="small" :style="{color:p._ok?'#4be3a0':'#ff7d9d'}">{{ p._msg }}</p></div></div>

    <!-- Nueva propuesta -->
    <div v-if="nueva" class="modal-bg" @click.self="nueva=false"><div class="glass modal">
      <h2>Nueva propuesta <HelpDot titulo="Nueva propuesta" texto="Los datos de esta pantalla protegen el documento: el cliente solo podrá abrirlo con uno de los correos autorizados y el NIT (solo números). Si la ligas a un lead, toda la trazabilidad queda en su timeline y su estado avanza solo (propuesta → cliente)."/></h2>
      <div class="field"><label>Lead del CRM (opcional) <HelpDot titulo="Lead" texto="Si eliges un lead, AlexIA usará su ficha e historial para redactar mejor, y las aperturas/aceptación quedarán en su timeline."/></label>
        <select class="inp" v-model="nv.lead_id"><option :value="0">— Sin lead —</option><option v-for="l in leads" :key="l.id" :value="l.id">{{ l.name }}{{ l.company?' · '+l.company:'' }}</option></select></div>
      <div class="field"><label>Cliente / empresa * <HelpDot titulo="Cliente" texto="Nombre de la empresa como aparecerá en el documento (ej: JCP Company S.A.S.)."/></label><input class="inp" v-model="nv.cliente"></div>
      <div class="form-grid two" style="display:grid;gap:1rem;grid-template-columns:1fr 1fr">
        <div class="field"><label>NIT (solo números) <HelpDot titulo="NIT" texto="Segunda llave de la compuerta. El cliente deberá digitarlo para abrir la propuesta. Déjalo vacío si no quieres exigirlo."/></label><input class="inp" v-model="nv.nit" inputmode="numeric"></div>
        <div class="field"><label>Idioma</label><select class="inp" v-model="nv.locale"><option value="es">Español</option><option value="en">English</option><option value="pt">Português</option></select></div></div>
      <div class="form-grid two" style="display:grid;gap:1rem;grid-template-columns:1fr 1fr">
        <div class="field"><label>Contacto</label><input class="inp" v-model="nv.contact" placeholder="Nombre de quien recibe"></div>
        <div class="field"><label>Correo del contacto <HelpDot titulo="Correo del contacto" texto="A este correo se envía el mailing con el enlace, y queda autorizado para abrir la propuesta."/></label><input class="inp" v-model="nv.contact_email" placeholder="contacto@cliente.com"></div></div>
      <div class="field"><label>Otros correos autorizados (uno por línea) <HelpDot titulo="Correos autorizados" texto="Si más personas del cliente deben poder abrir la propuesta (gerencia, compras…), agrega sus correos aquí, uno por línea."/></label>
        <textarea class="inp" rows="2" v-model="nv.extra_emails"></textarea></div>
      <div style="display:flex;justify-content:flex-end;gap:.7rem">
        <button class="btn btn-ghost btn-sm" @click="nueva=false">Cancelar</button>
        <button class="btn btn-primary btn-sm" @click="crear" :disabled="busy">{{ busy?'Creando…':'Crear propuesta' }}</button></div>
    </div></div>

    <!-- Editor -->
    <div v-if="ed" class="modal-bg" @click.self="cerrarEditor"><div class="glass modal modal-xl" style="display:grid;gap:1.1rem">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem">
        <h2>{{ ed.cliente }} <span class="badge" :class="claseEstado(ed.estado)" style="margin-left:.4rem">{{ labelEstado(ed.estado) }}</span></h2>
        <button class="btn btn-ghost btn-sm" @click="cerrarEditor">✕</button></div>

      <div class="glass panel land-ai open">
        <div class="land-ai__head"><b><Icon name="ia" :size="15"/> Redactar con AlexIA</b></div>
        <div class="land-ai__body">
          <p class="rec-lang-note" style="margin:0">AlexIA usa la ficha del lead (si existe) + tu brief para redactar todas las secciones. Incluye el monto en el brief si ya está definido; si no, dejará la inversión "Por definir". Tú revisas y guardas: nada se envía solo.</p>
          <textarea class="inp" rows="2" v-model="brief" placeholder="Ej: Proponer programa de automatización de facturación, 3 fases, arranque en agosto, inversión COP $28.000.000 + IVA…"></textarea>
          <div style="display:flex;gap:.6rem;align-items:center">
            <button class="btn btn-grad btn-sm" @click="generarIA" :disabled="aiBusy"><Icon name="ia" :size="14"/> {{ aiBusy?'Redactando…':'Redactar borrador' }}</button>
            <span v-if="aiMsg" class="small" :style="{color:aiOk?'#4be3a0':'#ff7d9d'}">{{ aiMsg }}</span></div></div></div>

      <div class="sec-divider"><span>Portada</span></div>
      <div class="field"><label>Título del documento <HelpDot titulo="Título" texto="El titular grande de la propuesta. Ej: «Propuesta estratégica y tecnológica para la evolución operativa de JCP Company»."/></label><input class="inp" v-model="f.title"></div>
      <div class="field"><label>Subtítulo</label><textarea class="inp" rows="2" v-model="f.subtitle"></textarea></div>

      <div class="sec-divider"><span>Resumen y contexto</span></div>
      <div class="field"><label>{{ f.resumen.title }}</label><textarea class="inp" rows="3" v-model="f.resumen.text"></textarea></div>
      <div class="field"><label>{{ f.contexto.title }}</label><textarea class="inp" rows="3" v-model="f.contexto.text"></textarea></div>

      <div class="sec-divider"><span>Objetivos y alcance <HelpDot titulo="Listas" texto="Escribe un punto por línea. Las líneas vacías se ignoran."/></span></div>
      <div class="form-grid two" style="display:grid;gap:1rem;grid-template-columns:1fr 1fr">
        <div class="field"><label>Objetivos (uno por línea)</label><textarea class="inp" rows="4" v-model="f.objetivos.items"></textarea></div>
        <div class="field"><label>Alcance (uno por línea)</label><textarea class="inp" rows="4" v-model="f.alcance.items"></textarea></div></div>

      <div class="sec-divider"><span>Fases <button type="button" class="btn btn-ghost btn-sm" @click="f.fases.push({phase:'Fase '+(f.fases.length+1),title:'',text:''})"><Icon name="plus" :size="12"/> Añadir fase</button></span></div>
      <div v-for="(fs,i) in f.fases" :key="'f'+i" class="glass panel land-block">
        <div class="form-grid" style="display:grid;grid-template-columns:140px 1fr auto;gap:.8rem;align-items:end">
          <div class="field"><label>Etiqueta</label><input class="inp" v-model="fs.phase" placeholder="Fase 1"></div>
          <div class="field"><label>Título</label><input class="inp" v-model="fs.title"></div>
          <button class="btn btn-danger btn-sm" @click="f.fases.splice(i,1)">✕</button></div>
        <div class="field"><label>Descripción</label><textarea class="inp" rows="2" v-model="fs.text"></textarea></div></div>

      <div class="sec-divider"><span>Entregables y tiempos</span></div>
      <div class="form-grid two" style="display:grid;gap:1rem;grid-template-columns:1fr 1fr">
        <div class="field"><label>Entregables (uno por línea)</label><textarea class="inp" rows="4" v-model="f.entregables.items"></textarea></div>
        <div class="field"><label>Tiempos</label><textarea class="inp" rows="4" v-model="f.tiempo.text"></textarea></div></div>

      <div class="sec-divider"><span>Inversión <HelpDot titulo="Inversión" texto="El monto tal como debe verse (ej: COP $28.000.000 + IVA). En la nota puedes aclarar equivalencia en USD o vigencia de la oferta."/></span></div>
      <div class="form-grid two" style="display:grid;gap:1rem;grid-template-columns:1fr 1fr">
        <div class="field"><label>Monto</label><input class="inp" v-model="f.inversion.amount" placeholder="COP $0 + IVA"></div>
        <div class="field"><label>Nota</label><input class="inp" v-model="f.inversion.note"></div></div>
      <div class="form-grid two" style="display:grid;gap:1rem;grid-template-columns:1fr 1fr">
        <div class="field"><label>Forma de pago (uno por línea)</label><textarea class="inp" rows="3" v-model="f.payment_terms.items"></textarea></div>
        <div class="field"><label>Consideraciones (uno por línea)</label><textarea class="inp" rows="3" v-model="f.conditions.items"></textarea></div></div>

      <div class="sec-divider"><span>Cierre</span></div>
      <div class="field"><label>Próximos pasos (uno por línea)</label><textarea class="inp" rows="3" v-model="f.next_steps.items"></textarea></div>
      <div class="form-grid two" style="display:grid;gap:1rem;grid-template-columns:1fr 1fr">
        <div class="field"><label>Título del cierre</label><input class="inp" v-model="f.cta.title"></div>
        <div class="field"><label>Texto del cierre</label><input class="inp" v-model="f.cta.text"></div></div>

      <div style="display:flex;justify-content:space-between;gap:.7rem;align-items:center;flex-wrap:wrap">
        <a :href="ed.url" target="_blank" class="small" style="color:var(--cyan)">Ver como cliente ↗ <HelpDot titulo="Ver como cliente" texto="Abre la página pública de la propuesta. Necesitarás pasar la compuerta con un correo autorizado y el NIT, igual que el cliente."/></a>
        <div style="display:flex;gap:.7rem">
          <button class="btn btn-ghost btn-sm" @click="cerrarEditor">Cancelar</button>
          <button class="btn btn-primary btn-sm" @click="guardar" :disabled="busy">{{ busy?'Guardando…':'Guardar propuesta' }}</button></div></div>
    </div></div></div>`,
  data() { return { items: [], resumen: {}, leads: [], nueva: false, busy: false,
    nv: { lead_id: 0, cliente: '', nit: '', locale: 'es', contact: '', contact_email: '', extra_emails: '' },
    ed: null, f: normalizar(null), brief: '', aiBusy: false, aiMsg: '', aiOk: false }; },
  methods: {
    async load() { const r = await api.get('/admin/propuestas'); if (!r.ok) { toast(r.error || 'Error', 'err'); return; }
      this.resumen = r.data.resumen; this.items = r.data.items.map(x => ({ ...x, _busy: false, _msg: '', _ok: false })); },
    fecha(x) { return x ? new Date(x.replace(' ', 'T') + 'Z').toLocaleString('es', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : ''; },
    labelEstado(s) { return { borrador: 'Borrador', enviada: 'Enviada', aceptada: 'Aceptada ✓', rechazada: 'Rechazada' }[s] || s; },
    claseEstado(s) { return { aceptada: 'ok', enviada: 'cliente' }[s] || ''; },
    cssEstado(s) { return { borrador: 'ninguna', enviada: 'sugerida', aceptada: 'publicada' }[s] || 'ninguna'; },
    async abrirNueva() { this.nueva = true;
      if (!this.leads.length) { const r = await api.get('/admin/leads?per_page=100'); if (r.ok) this.leads = (r.data.items || r.data || []); } },
    async crear() {
      if (!this.nv.cliente.trim()) { toast('Escribe el nombre del cliente.', 'err'); return; }
      this.busy = true;
      const payload = { ...this.nv, auth_emails: this.nv.extra_emails.split('\n').map(s => s.trim()).filter(Boolean) };
      const r = await api.post('/admin/propuestas', payload);
      this.busy = false;
      if (r.ok) { toast('Propuesta creada. Redáctala con AlexIA o a mano.'); this.nueva = false;
        this.nv = { lead_id: 0, cliente: '', nit: '', locale: 'es', contact: '', contact_email: '', extra_emails: '' };
        await this.load(); const nueva = this.items.find(x => x.code === r.data.code); if (nueva) this.editar(nueva); }
      else toast(r.error || 'Error', 'err');
    },
    editar(p) { this.ed = p; this.f = normalizar(p.contenido); this.brief = ''; this.aiMsg = ''; },
    cerrarEditor() { this.ed = null; },
    async generarIA() {
      this.aiBusy = true; this.aiMsg = '';
      const r = await api.post('/admin/propuestas/' + this.ed.id + '/generar', { brief: this.brief });
      this.aiBusy = false;
      if (r.ok && r.data.contenido) { this.f = normalizar(r.data.contenido); this.aiOk = true; this.aiMsg = 'Borrador listo. Revísalo sección por sección y guarda.'; }
      else { this.aiOk = false; this.aiMsg = r.error || 'No se pudo generar. Revisa OpenAI/Claude en Conectores.'; }
    },
    async guardar() {
      this.busy = true;
      const r = await api.put('/admin/propuestas/' + this.ed.id, { contenido: serializar(this.f), title: this.f.title });
      this.busy = false;
      if (r.ok) { toast('Propuesta guardada.'); this.load(); } else toast(r.error || 'Error', 'err');
    },
    async mandar(p) {
      const to = p.contact_email || prompt('Correo del destinatario:');
      if (!to) return;
      if (!confirm('Se enviará el correo de marca con el enlace confidencial a ' + to + '. ¿Continuar?')) return;
      p._busy = true; p._msg = '';
      const r = await api.post('/admin/propuestas/' + p.id + '/enviar', { to });
      p._busy = false;
      if (r.ok) { p._ok = true; p._msg = r.data.message; this.load(); } else { p._ok = false; p._msg = r.error || 'No se pudo enviar.'; }
    },
    async copiar(p) { try { await navigator.clipboard.writeText(p.url); toast('Enlace copiado.'); } catch (e) { prompt('Copia el enlace:', p.url); } },
    async eliminar(p) { if (!confirm('¿Eliminar esta propuesta? El enlace dejará de funcionar.')) return;
      const r = await api.del('/admin/propuestas/' + p.id); if (r.ok) { toast('Eliminada.'); this.load(); } else toast(r.error || 'Error', 'err'); },
  },
  mounted() { this.load(); },
};
