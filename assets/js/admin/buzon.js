// Portal admin · Buzón comercial: correos del Google Workspace con triage de
// AlexIA (clase, resumen, acción sugerida y borrador de respuesta). El trámite
// se hace aquí: editar/regenerar la respuesta y enviarla en el mismo hilo con
// la identidad corporativa. Todo queda trazado en el email y en el CRM.
import { api, toast } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { PageHelp, HelpDot } from './help.js';

export const Buzon = {
  components: { Icon, PageHelp, HelpDot },
  template: `<div><h1>Buzón comercial
      <PageHelp titulo="Buzón comercial"
        que="AlexIA lee la bandeja de tu correo corporativo (Google Workspace) y clasifica cada mensaje: cliente, prospecto, proveedor, otro o spam. Para los importantes te avisa por Telegram, resume el correo, sugiere la acción y deja un borrador de respuesta. Tú lo revisas y lo envías: la respuesta sale en el mismo hilo, con tu identidad corporativa, y queda registrada en el timeline del lead."
        :pasos="['Configura y activa el conector «Correo corporativo (Google Workspace)» en Conectores (botón ? del conector para la guía).','La bandeja se lee sola cada vez que corre el cron; también puedes pulsar «Leer bandeja ahora».','Revisa los correos clasificados: los de clientes y prospectos llegan con resumen y acción sugerida.','Abre un correo, ajusta el borrador de AlexIA si hace falta y pulsa «Enviar respuesta».','Si lo gestionaste por otro canal, usa «Marcar tramitado» para cerrar el pendiente.']"/></h1>
    <p class="adm__sub">AlexIA lee, clasifica y sugiere; tú decides y respondes con tu identidad corporativa</p>

    <div v-if="!conectado" class="rev-warn">⚠️ El conector de <b>Correo corporativo (Google Workspace)</b> no está configurado. Ve a Plataforma → Conectores, ábrelo y sigue la guía del botón «?».</div>

    <div class="conn-kpis">
      <div class="glass stat"><b class="grad-text">{{ resumen.total||0 }}</b><span>Correos leídos</span></div>
      <div class="glass stat"><b class="grad-text">{{ resumen.nuevos||0 }}</b><span>Por tramitar</span></div>
      <div class="glass stat"><b class="grad-text">{{ resumen.clientes||0 }}</b><span>De clientes/prospectos</span></div></div>

    <div class="rev-toolbar">
      <button class="btn btn-ghost btn-sm" @click="sincronizar" :disabled="busy"><Icon name="mail" :size="15"/> {{ busy?'Leyendo…':'Leer bandeja ahora' }}</button>
      <span v-if="msg" class="small" :style="{color:ok?'#4be3a0':'#ff7d9d'}">{{ msg }}</span>
      <label class="switch-row" style="border:0;padding:0;margin-left:auto"><label class="switch"><input type="checkbox" v-model="soloNuevos"><span></span></label> Solo por tramitar</label></div>

    <div v-if="!filtrados.length" class="glass panel" style="text-align:center;padding:2.5rem">
      <p>{{ items.length? 'Nada pendiente por tramitar. 👌' : 'Aún no hay correos leídos.' }}</p>
      <p class="small" v-if="!items.length">Configura el conector y pulsa «Leer bandeja ahora», o espera la próxima pasada del cron.</p></div>

    <div class="rev-list">
      <div v-for="e in filtrados" :key="e.id" class="glass rev-card" :class="e.estado==='tramitado'?'st-publicada':(esImportante(e)?'st-sugerida':'st-ninguna')">
        <div class="rev-head">
          <div class="rev-who"><span class="rev-avatar">{{ (e.from_name||'?').charAt(0).toUpperCase() }}</span>
            <div><b>{{ e.from_name||e.from_email }}</b>
              <div class="rev-stars"><em>{{ e.from_email }} · {{ fecha(e.received_at) }}{{ e.lead_name? ' · CRM: '+e.lead_name+' ('+e.lead_status+')':'' }}</em></div></div></div>
          <div class="rev-flags">
            <span class="badge" :class="claseBadge(e.ai_clase)">{{ labelClase(e.ai_clase) }}</span>
            <span class="badge" :class="e.estado==='tramitado'?'ok':''">{{ e.estado==='tramitado'?'Tramitado':'Por tramitar' }}</span></div></div>

        <p class="rev-comment" style="font-weight:600">{{ e.subject }}</p>
        <p v-if="e.ai_resumen" class="small" style="color:var(--text-secondary)"><b style="color:var(--cyan)">Resumen:</b> {{ e.ai_resumen }}</p>
        <p v-if="e.ai_accion" class="small" style="color:var(--text-secondary)"><b style="color:var(--violet)">Acción sugerida:</b> {{ e.ai_accion }}</p>
        <details v-if="e.body"><summary class="small" style="cursor:pointer;color:var(--text-tertiary)">Ver correo completo</summary>
          <p class="small" style="white-space:pre-wrap;max-height:220px;overflow:auto;color:var(--text-secondary);margin-top:.5rem">{{ e.body }}</p></details>

        <div v-if="e.estado!=='tramitado'" class="rev-reply-box">
          <label class="rev-lbl">Respuesta (borrador de AlexIA, editable) <HelpDot titulo="Respuesta" texto="AlexIA redacta un borrador según el correo y el contexto del CRM. Edítalo a tu gusto: al pulsar «Enviar respuesta» sale en el MISMO hilo del correo, con tu identidad corporativa, y queda en tus Enviados."/></label>
          <textarea class="inp" rows="4" v-model="e.ai_respuesta" placeholder="AlexIA sugerirá aquí la respuesta. También puedes escribirla tú."></textarea>
          <div class="rev-actions">
            <button class="btn btn-ghost btn-sm" @click="sugerir(e)" :disabled="e._busy"><Icon name="sparkle" :size="14"/> {{ e._busy?'Analizando…':(e.ai_respuesta?'Regenerar análisis':'Analizar con AlexIA') }}</button>
            <button class="btn btn-ghost btn-sm" @click="tramitar(e)" :disabled="e._busy">Marcar tramitado</button>
            <button class="btn btn-primary btn-sm" @click="responder(e)" :disabled="e._busy||!(e.ai_respuesta&&e.ai_respuesta.trim())">Enviar respuesta</button></div>
          <p v-if="e._msg" class="small" :style="{color:e._ok?'#4be3a0':'#ff7d9d'}">{{ e._msg }}</p></div>
        <p v-else-if="e.replied_at" class="small" style="color:#4be3a0">✓ Respondido {{ fecha(e.replied_at) }} en el mismo hilo.</p>
      </div></div></div>`,
  data() { return { items: [], resumen: {}, conectado: true, busy: false, msg: '', ok: false, soloNuevos: true }; },
  computed: {
    filtrados() { return this.soloNuevos ? this.items.filter(e => e.estado !== 'tramitado') : this.items; },
  },
  methods: {
    async load() { const r = await api.get('/admin/buzon'); if (!r.ok) { toast(r.error || 'Error', 'err'); return; }
      this.resumen = r.data.resumen; this.conectado = !!r.data.conectado;
      this.items = r.data.items.map(x => ({ ...x, _busy: false, _msg: '', _ok: false })); },
    fecha(x) { return x ? new Date(x.replace(' ', 'T') + 'Z').toLocaleString('es', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }) : ''; },
    esImportante(e) { return ['cliente', 'prospecto'].includes(e.ai_clase); },
    labelClase(c) { return { cliente: 'Cliente', prospecto: 'Prospecto', proveedor: 'Proveedor', otro: 'Otro', spam: 'Spam', pendiente: 'Sin analizar' }[c] || c || '—'; },
    claseBadge(c) { return { cliente: 'ok', prospecto: 'cliente', spam: 'descartado' }[c] || ''; },
    async sincronizar() { this.busy = true; this.msg = '';
      const r = await api.post('/admin/buzon/sync', {});
      this.busy = false; this.ok = !!(r.data && r.data.ok !== false) && r.ok;
      this.msg = (r.data && (r.data.message || r.data.error)) || r.error || '—';
      if (r.ok) this.load(); },
    async sugerir(e) { e._busy = true; e._msg = '';
      const r = await api.post('/admin/buzon/' + e.id + '/sugerir', {});
      e._busy = false;
      if (r.ok) { Object.assign(e, r.data); e._ok = true; e._msg = 'Análisis listo. Revisa la respuesta sugerida.'; }
      else { e._ok = false; e._msg = r.error || 'AlexIA no disponible.'; } },
    async responder(e) { if (!confirm('Se enviará la respuesta en el mismo hilo a ' + e.from_email + ' con tu identidad corporativa. ¿Continuar?')) return;
      e._busy = true; e._msg = '';
      const r = await api.post('/admin/buzon/' + e.id + '/responder', { respuesta: e.ai_respuesta });
      e._busy = false;
      if (r.ok) { toast('Respuesta enviada.'); this.load(); } else { e._ok = false; e._msg = r.error || 'No se pudo enviar.'; } },
    async tramitar(e) { const r = await api.post('/admin/buzon/' + e.id + '/tramitar', {});
      if (r.ok) { this.load(); } else toast(r.error || 'Error', 'err'); },
  },
  mounted() { this.load(); },
};
