// Portal admin · Campañas (atribución UTM) y Segmentos (audiencias exportables).
import { api, exportCSV, toast, tr, loadMeta } from '../lib/core.js';
import { Icon, CountUp } from '../lib/ui.js';
import { SmartTable } from './table.js';

// Barras horizontales compactas (breakdowns), con conversión opcional.
const Bars = {
  props: { rows: { type: Array, default: () => [] }, showConv: Boolean },
  computed: { max() { return Math.max(1, ...this.rows.map(r => r.total)); } },
  template: `<div class="bars"><p v-if="!rows.length" class="empty">Sin datos aún.</p>
    <div v-for="r in rows" :key="r.clave" class="bar">
      <span class="bar-lbl" :title="r.clave">{{ r.clave }}</span>
      <div class="bar-track"><div class="bar-fill" :style="{width:(r.total/max*100)+'%'}"></div></div>
      <span class="bar-val">{{ r.total }}<i v-if="showConv && r.clientes"> · {{ r.clientes }}✓</i></span></div></div>`,
};

export const Campanas = {
  components: { Icon, CountUp, SmartTable, Bars },
  template: `<div><h1>Campañas <span class="grad-text">y atribución</span></h1>
    <p class="adm__sub">De dónde vienen tus leads: campaña, fuente, medio y landing · conversión a cliente</p>

    <div class="stats" v-if="d">
      <div class="glass stat"><b><CountUp :value="d.resumen.total"/></b><span>Leads totales</span></div>
      <div class="glass stat"><b><CountUp :value="d.resumen.atribuidos"/></b><span>Con atribución</span><span class="ctx">{{ d.resumen.tasa_atribucion }}% del total</span></div>
      <div class="glass stat"><b><CountUp :value="d.resumen.campanas"/></b><span>Campañas activas</span></div>
      <div class="glass stat"><b><CountUp :value="d.resumen.clientes"/></b><span>Clientes ganados</span></div>
    </div>

    <div class="adm__bar mt">
      <input class="inp" v-model="q" placeholder="Buscar campaña, fuente o medio…" style="max-width:340px">
      <button class="btn btn-ghost btn-sm" style="margin-left:auto" @click="exportar" :disabled="!rows.length"><Icon name="doc" :size="15"/> Exportar CSV</button>
    </div>
    <SmartTable v-if="d" :columns="cols" :rows="rows" :search="q" :searchKeys="['campaign','source','medium']" :clickable="false" :pageSize="12"/>

    <div class="grid-3 mt" v-if="d">
      <div class="glass panel"><h3>Por fuente</h3><Bars :rows="d.fuentes" :showConv="true"/></div>
      <div class="glass panel"><h3>Por medio</h3><Bars :rows="d.medios"/></div>
      <div class="glass panel"><h3>Landings de origen</h3><Bars :rows="d.landings" :showConv="true"/></div>
    </div>
  </div>`,
  data() {
    return {
      d: null, q: '',
      cols: [
        { key: 'campaign', label: 'Campaña', sortable: true },
        { key: 'source', label: 'Fuente', sortable: true },
        { key: 'medium', label: 'Medio', sortable: true },
        { key: 'leads', label: 'Leads', sortable: true, cls: 'num' },
        { key: 'calificados', label: 'Calificados', sortable: true, cls: 'num' },
        { key: 'clientes', label: 'Clientes', sortable: true, cls: 'num' },
        { key: 'tasa', label: 'Conversión', sortable: true, cls: 'num', render: (r) => r.tasa + '%' },
      ],
    };
  },
  computed: { rows() { return this.d ? this.d.campanas : []; } },
  async mounted() { const r = await api.get('/admin/campanas'); if (r.ok) this.d = r.data; },
  methods: {
    exportar() {
      exportCSV('campanas-experientia.csv', this.rows, ['campaign', 'source', 'medium', 'leads', 'calificados', 'clientes', 'tasa']);
      toast('CSV de campañas exportado.');
    },
  },
};

export const Segmentos = {
  components: { Icon, CountUp, Bars },
  template: `<div><h1>Segmentos <span class="grad-text">de audiencia</span></h1>
    <p class="adm__sub">Arma audiencias filtrando tu CRM y expórtalas para tus campañas y pautas</p>

    <div class="glass panel seg-builder">
      <div class="seg-grid">
        <label>Industria<select class="inp" v-model="f.industry"><option value="">Todas</option><option v-for="(v,k) in industrias" :key="k" :value="k">{{ v }}</option></select></label>
        <label>Tamaño<select class="inp" v-model="f.company_size"><option value="">Todos</option><option v-for="s in tamanos" :key="s" :value="s">{{ s }}</option></select></label>
        <label>País (ISO)<input class="inp" v-model="f.country" placeholder="CO, MX, US…" maxlength="2" style="text-transform:uppercase"></label>
        <label>Origen<select class="inp" v-model="f.source"><option value="">Todos</option><option v-for="s in origenes" :key="s" :value="s">{{ s }}</option></select></label>
        <label>Canal<select class="inp" v-model="f.channel"><option value="">Todos</option><option value="web">Web</option><option value="telegram">Telegram</option><option value="whatsapp">WhatsApp</option></select></label>
        <label>Estado<select class="inp" v-model="f.status"><option value="">Todos</option><option v-for="s in estados" :key="s" :value="s">{{ s }}</option></select></label>
        <label>Idioma<select class="inp" v-model="f.locale"><option value="">Todos</option><option value="es">ES</option><option value="en">EN</option><option value="pt">PT</option></select></label>
        <label>Fuente UTM<input class="inp" v-model="f.utm_source" placeholder="google, meta, linkedin…"></label>
        <label>Campaña UTM<input class="inp" v-model="f.utm_campaign" placeholder="nombre de campaña"></label>
      </div>
      <div class="seg-flags">
        <label class="chk"><input type="checkbox" v-model="f.has_email"> Con correo</label>
        <label class="chk"><input type="checkbox" v-model="f.has_phone"> Con WhatsApp</label>
        <input class="inp" v-model="f.q" placeholder="Buscar nombre/empresa…" style="max-width:240px;margin-left:auto">
        <button class="btn btn-ghost btn-sm" @click="limpiar">Limpiar</button>
      </div>
    </div>

    <div class="stats mt" v-if="p">
      <div class="glass stat"><b><CountUp :value="p.total"/></b><span>En el segmento</span></div>
      <div class="glass stat"><b><CountUp :value="p.con_email"/></b><span>Con correo</span></div>
      <div class="glass stat"><b><CountUp :value="p.con_whatsapp"/></b><span>Con WhatsApp</span></div>
      <div class="glass stat seg-cta"><button class="btn btn-grad" @click="exportar" :disabled="!p.total"><Icon name="doc" :size="15"/> Exportar audiencia</button><span class="ctx">{{ p.total }} contactos · CSV</span></div>
    </div>

    <div class="glass panel seg-send mt" v-if="p">
      <div class="seg-send-head"><h3>Enviar al segmento</h3>
        <select v-if="plantillas.length" class="inp" @change="usarPlantilla($event.target.value)"><option value="">Usar una plantilla…</option><option v-for="t in plantillas" :key="t.id" :value="t.id">{{ t.nombre }} ({{ t.canal }})</option></select></div>
      <div class="seg-send-tabs">
        <button type="button" :class="{active:envio.canal==='email'}" @click="envio.canal='email'"><Icon name="mail" :size="15"/> Correo · {{ p.con_email }}</button>
        <button type="button" :class="{active:envio.canal==='whatsapp'}" :disabled="!p.canales.whatsapp" @click="envio.canal='whatsapp'"><Icon name="send" :size="15"/> WhatsApp · {{ p.con_whatsapp }}<em v-if="!p.canales.whatsapp"> (no configurado)</em></button>
      </div>
      <input v-if="envio.canal==='email'" class="inp" v-model="envio.asunto" placeholder="Asunto del correo (usa {nombre} si quieres)">
      <textarea class="inp" rows="4" v-model="envio.mensaje" placeholder="Escribe tu mensaje… puedes usar {nombre} y {empresa} para personalizar."></textarea>
      <div class="seg-send-foot">
        <span class="small">Se enviará a <b>{{ destinatarios }}</b> contacto(s) con {{ envio.canal==='email'?'correo':'WhatsApp' }}. Máx. 500 por envío.</span>
        <button class="btn btn-grad" :disabled="enviando || !destinatarios || envio.mensaje.length<3" @click="enviar">{{ enviando?'Enviando…':'Enviar a '+destinatarios }}</button></div>
    </div>

    <div class="grid-2 mt" v-if="p">
      <div class="glass panel"><h3>Por industria</h3><Bars :rows="p.por_industria"/></div>
      <div class="glass panel"><h3>Por origen</h3><Bars :rows="p.por_origen"/></div>
    </div>
    <div class="glass panel mt" v-if="p && p.muestra.length" style="padding:0;overflow:hidden">
      <table><thead><tr><th>Nombre</th><th>Empresa</th><th>Industria</th><th>Origen</th><th>Campaña</th><th>Estado</th></tr></thead>
      <tbody><tr v-for="(m,i) in p.muestra" :key="i"><td>{{ m.name }}</td><td>{{ m.company||'—' }}</td><td>{{ m.industry||'—' }}</td><td>{{ m.source||'—' }}</td><td>{{ m.utm_campaign||'—' }}</td><td>{{ m.status }}</td></tr></tbody></table>
      <p class="seg-note">Muestra de los últimos {{ p.muestra.length }} · exporta para ver todos.</p>
    </div>
  </div>`,
  data() {
    return {
      p: null, timer: null, enviando: false, plantillas: [],
      envio: { canal: 'email', asunto: '', mensaje: '' },
      f: { industry: '', company_size: '', country: '', source: '', channel: '', status: '', locale: '', utm_source: '', utm_campaign: '', has_email: false, has_phone: false, q: '' },
      industrias: {},
      tamanos: ['1-10', '11-50', '51-200', '201-1000', '1000+'],
      origenes: ['contacto', 'interes', 'diagnostico', 'reserva', 'descarga', 'newsletter', 'telegram', 'whatsapp'],
      estados: ['nuevo', 'contactado', 'calificado', 'propuesta', 'cliente', 'descartado'],
    };
  },
  computed: { destinatarios() { return this.p ? (this.envio.canal === 'email' ? this.p.con_email : this.p.con_whatsapp) : 0; } },
  watch: { f: { deep: true, handler() { this.debounced(); } } },
  async mounted() { const meta = await loadMeta(); this.industrias = Object.fromEntries((meta.industries || []).map(i => [i.key, tr(i.nombre)])); const tp = await api.get('/admin/campaign_templates/list'); if (tp.ok) this.plantillas = tp.data; await this.load(); },
  methods: {
    qs() {
      const p = new URLSearchParams();
      for (const [k, v] of Object.entries(this.f)) { if (v === true) p.set(k, '1'); else if (v) p.set(k, v); }
      return p.toString();
    },
    async load() { const r = await api.get('/admin/segmentos/preview?' + this.qs()); if (r.ok) this.p = r.data; },
    debounced() { clearTimeout(this.timer); this.timer = setTimeout(() => this.load(), 350); },
    limpiar() { for (const k in this.f) { this.f[k] = (typeof this.f[k] === 'boolean') ? false : ''; } },
    async exportar() {
      const r = await api.get('/admin/segmentos/export?' + this.qs());
      if (r.ok && r.data.rows.length) {
        exportCSV('segmento-experientia.csv', r.data.rows, ['name', 'email', 'phone_wa', 'phone_dial', 'country', 'company', 'role', 'industry', 'company_size', 'status', 'source', 'channel', 'locale', 'utm_source', 'utm_medium', 'utm_campaign', 'landing_page', 'created_at']);
        toast(r.data.total + ' contactos exportados.');
      } else { toast('El segmento no tiene contactos.'); }
    },
    filtrosObj() { const o = {}; for (const [k, v] of Object.entries(this.f)) { if (v === true) o[k] = '1'; else if (v) o[k] = v; } return o; },
    usarPlantilla(id) { const t = this.plantillas.find(x => String(x.id) === String(id)); if (!t) return; this.envio.canal = t.canal || 'email'; this.envio.asunto = tr(t.asunto) || ''; this.envio.mensaje = tr(t.cuerpo) || ''; },
    async enviar() {
      const canalTxt = this.envio.canal === 'email' ? 'correo' : 'WhatsApp';
      if (!confirm(`¿Enviar este ${canalTxt} a ${this.destinatarios} contacto(s) del segmento? Esta acción envía mensajes reales.`)) return;
      this.enviando = true;
      const r = await api.post('/admin/segmentos/enviar', { ...this.filtrosObj(), canal: this.envio.canal, asunto: this.envio.asunto, mensaje: this.envio.mensaje });
      this.enviando = false;
      if (r.ok) { toast(`${r.data.enviados} enviado(s)` + (r.data.fallidos ? `, ${r.data.fallidos} fallido(s)` : '') + (r.data.tope ? ' · se alcanzó el tope de 500' : '') + '.'); this.envio.mensaje = ''; this.envio.asunto = ''; }
      else { toast(r.error || 'No se pudo enviar.'); }
    },
  },
};
