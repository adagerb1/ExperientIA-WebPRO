// Portal admin · Editor de diagnósticos dinámicos (varios tipos, resultados configurables).
import { api, toast } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

const LANGS = ['es', 'en', 'pt'];
const blankI18n = () => ({ es: '', en: '', pt: '' });
const ICONS = ['target', 'gear', 'analitica', 'growth', 'people', 'ia', 'bulb', 'shield', 'cube'];

// Entrada trilingüe compacta que respeta el idioma activo del editor.
const I18n = {
  props: { modelValue: Object, lang: String, ta: Boolean, ph: String },
  emits: ['update:modelValue'],
  template: `<textarea v-if="ta" class="inp" rows="2" :placeholder="ph" :value="(modelValue||{})[lang]" @input="set($event.target.value)"></textarea>
    <input v-else class="inp" :placeholder="ph" :value="(modelValue||{})[lang]" @input="set($event.target.value)">`,
  methods: { set(v) { const o = { ...(this.modelValue || {}) }; o[this.lang] = v; this.$emit('update:modelValue', o); } },
};

export const Diagnosticos = {
  components: { Icon, I18n },
  template: `<div><h1>Diagnósticos <span class="grad-text">dinámicos</span></h1>
    <p class="adm__sub">Habilita varios tipos de diagnóstico y configura preguntas, puntajes y resultados</p>

    <div v-if="!form" >
      <div class="adm__bar"><button class="btn btn-grad btn-sm" @click="nuevo"><Icon name="plus" :size="15"/> Nuevo diagnóstico</button></div>
      <div class="diag-adm-list">
        <div v-for="d in items" :key="d.id" class="glass panel diag-adm-row">
          <span class="icon-chip"><Icon :name="d.icon"/></span>
          <div class="diag-adm-meta"><b>{{ d.nombre?.es || d.dkey }}</b><span>{{ (d.preguntas||[]).length }} preguntas · {{ (d.resultados||[]).length }} resultados · <i :class="d.active==1?'on':'off'">{{ d.active==1?'activo':'inactivo' }}</i></span></div>
          <div class="diag-adm-act"><code>{{ d.dkey }}</code><button class="btn btn-ghost btn-sm" @click="editar(d)">Editar</button><button class="btn btn-ghost btn-sm danger" @click="borrar(d)"><Icon name="trash" :size="14"/></button></div>
        </div>
        <p v-if="!items.length" class="empty" style="padding:1.5rem">Aún no hay diagnósticos. Crea el primero.</p>
      </div>
    </div>

    <div v-else class="diag-editor">
      <div class="adm__bar"><button class="btn btn-ghost btn-sm" @click="form=null">‹ Volver</button>
        <div class="tabs" style="margin-left:auto"><button v-for="l in langs" :key="l" :class="{active:lang===l}" @click="lang=l">{{ l.toUpperCase() }}</button></div></div>

      <div class="glass panel">
        <div class="seg-grid">
          <label>Clave (URL, sin espacios)<input class="inp" v-model="form.dkey" placeholder="madurez-ia" :disabled="!!form.id"></label>
          <label>Ícono<select class="inp" v-model="form.icon"><option v-for="i in iconos" :key="i" :value="i">{{ i }}</option></select></label>
          <label>Resultado por defecto<select class="inp" v-model="form.default_result"><option value="">(el primero)</option><option v-for="r in form.resultados" :key="r.clave" :value="r.clave">{{ r.clave }}</option></select></label>
          <label>Orden<input class="inp" type="number" v-model.number="form.sort"></label>
          <label class="chk" style="align-self:end"><input type="checkbox" v-model="form.active"> Activo</label>
        </div>
        <div class="dg-field"><b>Nombre ({{ lang.toUpperCase() }})</b><I18n v-model="form.nombre" :lang="lang" ph="Diagnóstico de Madurez en IA"/></div>
        <div class="dg-field"><b>Introducción ({{ lang.toUpperCase() }})</b><I18n v-model="form.intro" :lang="lang" ta ph="Frase breve que invita a empezar"/></div>
      </div>

      <h3 class="dg-h">Resultados <span>· define primero los resultados; luego asigna puntajes en cada opción</span></h3>
      <div v-for="(r,ri) in form.resultados" :key="ri" class="glass panel dg-res">
        <div class="dg-res-head"><input class="inp dg-clave" v-model="r.clave" placeholder="clave (ej: datos)"><button class="btn btn-ghost btn-sm danger" @click="form.resultados.splice(ri,1)"><Icon name="trash" :size="14"/></button></div>
        <div class="seg-grid">
          <label>Enlazar solución (hereda su copy)<select class="inp" v-model="r.solucion_skey"><option value="">(ninguna · copy propio)</option><option v-for="s in soluciones" :key="s.skey" :value="s.skey">{{ s.nombre }}</option></select></label>
          <label>Ícono (opcional)<select class="inp" v-model="r.icon"><option value="">(de la solución)</option><option v-for="i in iconos" :key="i" :value="i">{{ i }}</option></select></label>
        </div>
        <div class="dg-field"><b>Título ({{ lang.toUpperCase() }}) <i>opcional si enlaza solución</i></b><I18n v-model="r.titulo" :lang="lang"/></div>
        <div class="dg-field"><b>Pilar / etiqueta ({{ lang.toUpperCase() }})</b><I18n v-model="r.pilar" :lang="lang"/></div>
        <div class="dg-field"><b>Descripción ({{ lang.toUpperCase() }})</b><I18n v-model="r.descripcion" :lang="lang" ta/></div>
      </div>
      <button class="btn btn-ghost btn-sm" @click="addResultado"><Icon name="plus" :size="14"/> Añadir resultado</button>

      <h3 class="dg-h">Preguntas</h3>
      <div v-for="(q,qi) in form.preguntas" :key="qi" class="glass panel dg-q">
        <div class="dg-res-head"><span class="dg-qn">{{ qi+1 }}</span><input class="inp dg-clave" v-model="q.id" placeholder="id (ej: decisiones)"><button class="btn btn-ghost btn-sm danger" @click="form.preguntas.splice(qi,1)"><Icon name="trash" :size="14"/></button></div>
        <div class="dg-field"><b>Pregunta ({{ lang.toUpperCase() }})</b><I18n v-model="q.texto" :lang="lang" ta/></div>
        <div class="dg-opts">
          <div v-for="(o,oi) in q.opciones" :key="oi" class="dg-opt">
            <div class="dg-opt-txt"><I18n v-model="o.texto" :lang="lang" :ph="'Opción '+(oi+1)"/></div>
            <div class="dg-scores"><span class="dg-scores-l">Puntos:</span>
              <label v-for="r in claves" :key="r" class="dg-score"><em>{{ r }}</em><input class="inp" type="number" min="0" :value="o.scores?.[r]||0" @input="setScore(o,r,$event.target.value)"></label></div>
            <button class="btn btn-ghost btn-sm danger dg-opt-x" @click="q.opciones.splice(oi,1)"><Icon name="trash" :size="13"/></button>
          </div>
          <button class="btn btn-ghost btn-sm" @click="q.opciones.push({texto:blankI18n(),scores:{}})"><Icon name="plus" :size="13"/> Opción</button>
        </div>
      </div>
      <button class="btn btn-ghost btn-sm" @click="addPregunta"><Icon name="plus" :size="14"/> Añadir pregunta</button>

      <div class="dg-save"><button class="btn btn-grad" @click="guardar" :disabled="saving">{{ saving?'Guardando…':'Guardar diagnóstico' }}</button><p class="err" v-if="err">{{ err }}</p></div>
    </div>
  </div>`,
  data() { return { items: [], form: null, lang: 'es', langs: LANGS, iconos: ICONS, soluciones: [], saving: false, err: '' }; },
  computed: { claves() { return (this.form?.resultados || []).map(r => r.clave).filter(Boolean); } },
  async mounted() { await this.load(); const s = await api.get('/content/soluciones'); this.soluciones = s.ok ? s.data.map(x => ({ skey: x.skey, nombre: (x.titulo?.es || x.skey) })) : []; },
  methods: {
    blankI18n,
    async load() { const r = await api.get('/admin/diagnostics/list'); if (r.ok) this.items = r.data; },
    nuevo() { this.form = { dkey: '', icon: 'target', active: true, sort: (this.items.length + 1), default_result: '', nombre: blankI18n(), intro: blankI18n(), preguntas: [], resultados: [] }; this.lang = 'es'; this.err = ''; },
    editar(d) {
      this.form = {
        id: d.id, dkey: d.dkey, icon: d.icon || 'target', active: d.active == 1, sort: d.sort || 0, default_result: d.default_result || '',
        nombre: { ...blankI18n(), ...(d.nombre || {}) }, intro: { ...blankI18n(), ...(d.intro || {}) },
        preguntas: (d.preguntas || []).map(q => ({ id: q.id, texto: { ...blankI18n(), ...(q.texto || {}) }, opciones: (q.opciones || []).map(o => ({ texto: { ...blankI18n(), ...(o.texto || {}) }, scores: { ...(o.scores || {}) } })) })),
        resultados: (d.resultados || []).map(r => ({ clave: r.clave, solucion_skey: r.solucion_skey || '', icon: r.icon || '', pilar: { ...blankI18n(), ...(r.pilar || {}) }, titulo: { ...blankI18n(), ...(r.titulo || {}) }, descripcion: { ...blankI18n(), ...(r.descripcion || {}) } })),
      };
      this.lang = 'es'; this.err = '';
    },
    addResultado() { this.form.resultados.push({ clave: '', solucion_skey: '', icon: '', pilar: blankI18n(), titulo: blankI18n(), descripcion: blankI18n() }); },
    addPregunta() { this.form.preguntas.push({ id: 'p' + (this.form.preguntas.length + 1), texto: blankI18n(), opciones: [{ texto: blankI18n(), scores: {} }, { texto: blankI18n(), scores: {} }] }); },
    setScore(o, clave, val) { const n = parseInt(val, 10) || 0; if (!o.scores) o.scores = {}; if (n > 0) o.scores[clave] = n; else delete o.scores[clave]; },
    limpiaI18n(o) { const out = {}; for (const l of LANGS) if (o && o[l]) out[l] = o[l]; return Object.keys(out).length ? out : null; },
    async guardar() {
      const f = this.form;
      if (!/^[a-z0-9-]{2,40}$/.test(f.dkey)) { this.err = 'La clave debe ser minúsculas, números y guiones.'; return; }
      if (!f.nombre.es) { this.err = 'El nombre en español es obligatorio.'; return; }
      if (!f.resultados.length || f.resultados.some(r => !r.clave)) { this.err = 'Cada resultado necesita una clave.'; return; }
      if (!f.preguntas.length) { this.err = 'Añade al menos una pregunta.'; return; }
      // Normaliza payload
      const payload = {
        dkey: f.dkey, icon: f.icon, active: f.active ? 1 : 0, sort: f.sort || 0, default_result: f.default_result || null,
        nombre: f.nombre, intro: this.limpiaI18n(f.intro),
        preguntas: f.preguntas.map(q => ({ id: q.id, texto: q.texto, opciones: q.opciones.map(o => ({ texto: o.texto, scores: o.scores || {} })) })),
        resultados: f.resultados.map(r => { const o = { clave: r.clave }; if (r.solucion_skey) o.solucion_skey = r.solucion_skey; if (r.icon) o.icon = r.icon; const p = this.limpiaI18n(r.pilar), t = this.limpiaI18n(r.titulo), d = this.limpiaI18n(r.descripcion); if (p) o.pilar = p; if (t) o.titulo = t; if (d) o.descripcion = d; return o; }),
      };
      this.saving = true; this.err = '';
      const r = f.id ? await api.put('/admin/diagnostics/' + f.id, payload) : await api.post('/admin/diagnostics', payload);
      this.saving = false;
      if (r.ok) { toast('Diagnóstico guardado.'); this.form = null; await this.load(); } else { this.err = r.error || 'No se pudo guardar.'; }
    },
    async borrar(d) { if (!confirm('¿Eliminar el diagnóstico "' + (d.nombre?.es || d.dkey) + '"?')) return; const r = await api.del('/admin/diagnostics/' + d.id); if (r.ok) { toast('Eliminado.'); await this.load(); } },
  },
};
