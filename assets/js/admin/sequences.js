// Portal admin · Automatizaciones (secuencias de nurturing por etapa del pipeline).
import { api, toast } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

export const Secuencias = {
  components: { Icon },
  template: `<div><h1>Automatizaciones <span class="grad-text">de nurturing</span></h1>
    <p class="adm__sub">Cuando un lead entra a una etapa, se le envían mensajes automáticos según tus plantillas</p>

    <div v-if="!form">
      <div class="adm__bar">
        <button class="btn btn-grad btn-sm" @click="nuevo"><Icon name="plus" :size="15"/> Nueva secuencia</button>
        <button class="btn btn-ghost btn-sm" style="margin-left:auto" @click="procesar" :disabled="procesando"><Icon name="play" :size="15"/> {{ procesando?'Procesando…':'Procesar ahora' }}</button>
      </div>
      <p class="seq-note"><Icon name="clock" :size="13"/> El envío automático corre con un cron. Añade en cPanel: <code>curl -s "https://TU-DOMINIO/api/cron/run?key=TU_CRON_KEY"</code> cada 15 min. Mientras, usa “Procesar ahora”.</p>
      <div class="seq-list">
        <div v-for="s in items" :key="s.id" class="glass panel seq-row">
          <div class="seq-meta"><b>{{ s.nombre }}</b><span>Dispara al entrar a <em>{{ estados[s.trigger_status]||s.trigger_status }}</em> · {{ s.steps.length }} paso(s) · {{ s.activas }} activo(s) / {{ s.total }} total · <i :class="s.active==1?'on':'off'">{{ s.active==1?'activa':'pausada' }}</i></span></div>
          <div class="seq-act"><button class="btn btn-ghost btn-sm" @click="editar(s)">Editar</button><button class="btn btn-ghost btn-sm danger" @click="borrar(s)"><Icon name="trash" :size="14"/></button></div>
        </div>
        <p v-if="!items.length" class="empty" style="padding:1.5rem">Aún no hay secuencias. Crea la primera.</p>
      </div>
    </div>

    <div v-else class="diag-editor">
      <div class="adm__bar"><button class="btn btn-ghost btn-sm" @click="form=null">‹ Volver</button></div>
      <div class="glass panel">
        <div class="seg-grid">
          <label>Nombre<input class="inp" v-model="form.nombre" placeholder="Onboarding lead nuevo"></label>
          <label>Etapa que la dispara<select class="inp" v-model="form.trigger_status"><option v-for="(v,k) in estados" :key="k" :value="k">{{ v }}</option></select></label>
          <label class="chk" style="align-self:end"><input type="checkbox" v-model="form.active"> Activa</label>
        </div>
      </div>

      <h3 class="dg-h">Pasos <span>· el retraso cuenta desde que el lead entra a la etapa (o desde el paso anterior)</span></h3>
      <div v-if="!templates.length" class="seq-note danger">No tienes plantillas de campaña activas. Crea alguna en “Plantillas de campaña” antes de armar pasos.</div>
      <div v-for="(st,i) in form.steps" :key="i" class="glass panel seq-step">
        <span class="dg-qn">{{ i+1 }}</span>
        <label class="seq-delay">Esperar<input class="inp" type="number" min="0" v-model.number="st.delay_hours"> horas</label>
        <label class="seq-tpl">Enviar plantilla<select class="inp" v-model.number="st.template_id"><option :value="0">— elige —</option><option v-for="t in templates" :key="t.id" :value="t.id">{{ t.nombre }} ({{ t.canal }})</option></select></label>
        <button class="btn btn-ghost btn-sm danger" @click="form.steps.splice(i,1)"><Icon name="trash" :size="14"/></button>
      </div>
      <button class="btn btn-ghost btn-sm" @click="form.steps.push({delay_hours:24,template_id:0})"><Icon name="plus" :size="14"/> Añadir paso</button>

      <div class="dg-save"><button class="btn btn-grad" @click="guardar" :disabled="saving">{{ saving?'Guardando…':'Guardar secuencia' }}</button><p class="err" v-if="err">{{ err }}</p></div>
    </div>
  </div>`,
  data() { return { items: [], templates: [], estados: {}, form: null, saving: false, procesando: false, err: '' }; },
  async mounted() { await this.load(); },
  methods: {
    async load() { const r = await api.get('/admin/secuencias'); if (r.ok) { this.items = r.data.sequences; this.templates = r.data.templates; this.estados = r.data.estados; } },
    nuevo() { this.form = { id: 0, nombre: '', trigger_status: 'nuevo', active: true, steps: [{ delay_hours: 0, template_id: 0 }] }; this.err = ''; },
    editar(s) { this.form = { id: s.id, nombre: s.nombre, trigger_status: s.trigger_status, active: s.active == 1, steps: s.steps.map(x => ({ delay_hours: x.delay_hours, template_id: x.template_id || 0 })) }; this.err = ''; },
    async guardar() {
      const f = this.form;
      if (!f.nombre.trim()) { this.err = 'Ponle un nombre.'; return; }
      const steps = f.steps.filter(s => s.template_id > 0);
      if (!steps.length) { this.err = 'Añade al menos un paso con plantilla.'; return; }
      this.saving = true; this.err = '';
      const r = await api.post('/admin/secuencias', { id: f.id || undefined, nombre: f.nombre, trigger_status: f.trigger_status, active: f.active ? 1 : 0, steps });
      this.saving = false;
      if (r.ok) { toast('Secuencia guardada.'); this.form = null; await this.load(); } else { this.err = r.error || 'No se pudo guardar.'; }
    },
    async borrar(s) { if (!confirm('¿Eliminar la secuencia "' + s.nombre + '"? Se cancelan sus inscripciones.')) return; const r = await api.del('/admin/secuencias/' + s.id); if (r.ok) { toast('Eliminada.'); await this.load(); } },
    async procesar() { this.procesando = true; const r = await api.post('/admin/secuencias/procesar', {}); this.procesando = false; if (r.ok) { const d = r.data; toast(`Procesados ${d.procesados}: ${d.enviados} enviado(s), ${d.completados} completado(s).`); await this.load(); } },
  },
};
