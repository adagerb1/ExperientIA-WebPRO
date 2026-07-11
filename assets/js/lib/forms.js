// ExperientIA · Componentes de formulario (combobox buscable, teléfono WhatsApp, campos de lead)
import { t, tr, store, api, pageUrl, loadMeta } from './core.js';
import { Icon } from './ui.js';

let PAISES = null;
async function paises() {
  if (PAISES) return PAISES;
  const r = await fetch('/assets/js/lib/paises.json');
  PAISES = await r.json();
  return PAISES;
}

// Combobox buscable (país, industria, tamaño…)
export const Combo = {
  props: { modelValue: String, options: Array, placeholder: String },
  emits: ['update:modelValue'],
  template: `<div class="combo" @keydown.escape="open=false">
    <input class="field-el combo__input" readonly :value="label" :placeholder="placeholder" @mousedown.prevent="toggle" />
    <div class="combo__pop" v-if="open">
      <div style="padding:.5rem"><input ref="q" v-model="query" :placeholder="placeholder" style="width:100%;padding:.5rem .7rem;border-radius:8px;border:1px solid var(--line-soft);background:rgba(5,17,38,.6);color:var(--neutral-light)" @click.stop /></div>
      <div class="combo__opt" v-for="o in filtered" :key="o.value" @mousedown.prevent="pick(o)">{{ o.label }}</div>
      <div class="combo__opt" v-if="!filtered.length" style="opacity:.6">—</div>
    </div></div>`,
  data() { return { open: false, query: '' }; },
  computed: {
    label() { const o = (this.options || []).find(x => x.value === this.modelValue); return o ? o.label : ''; },
    filtered() { const q = this.query.toLowerCase(); return (this.options || []).filter(o => o.label.toLowerCase().includes(q)).slice(0, 60); },
  },
  methods: {
    toggle() { this.open = !this.open; if (this.open) this.$nextTick(() => this.$refs.q && this.$refs.q.focus()); },
    pick(o) { this.$emit('update:modelValue', o.value); this.open = false; this.query = ''; },
  },
  mounted() { this._h = (e) => { if (!this.$el.contains(e.target)) this.open = false; }; document.addEventListener('click', this._h); },
  beforeUnmount() { document.removeEventListener('click', this._h); },
};

// Indicativos telefónicos por país (ISO 3166-1 → código). Cobertura amplia,
// LatAm/Iberia priorizados. Sin dependencias externas.
const DIAL = {
  CO:'57',MX:'52',AR:'54',CL:'56',PE:'51',EC:'593',VE:'58',BO:'591',PY:'595',UY:'598',
  BR:'55',PA:'507',CR:'506',GT:'502',SV:'503',HN:'504',NI:'505',DO:'1',CU:'53',PR:'1',
  ES:'34',US:'1',CA:'1',PT:'351',
  GB:'44',FR:'33',DE:'49',IT:'39',NL:'31',BE:'32',CH:'41',AT:'43',IE:'353',SE:'46',
  NO:'47',DK:'45',FI:'358',PL:'48',CZ:'420',GR:'30',RO:'40',HU:'36',UA:'380',RU:'7',
  TR:'90',IL:'972',AE:'971',SA:'966',QA:'974',EG:'20',MA:'212',ZA:'27',NG:'234',KE:'254',
  IN:'91',CN:'86',JP:'81',KR:'82',ID:'62',PH:'63',TH:'66',VN:'84',MY:'60',SG:'65',
  AU:'61',NZ:'64',HK:'852',TW:'886',
};
const flagEmoji = (iso) => iso.replace(/./g, (c) => String.fromCodePoint(127397 + c.charCodeAt(0)));

// Campo WhatsApp compuesto: select con banderas + indicativo e input de número.
// Si el usuario escribe el número con indicativo (+57 / 0057), lo detecta y ubica.
export const PhoneInput = {
  props: { modelValue: String },
  emits: ['update:modelValue', 'dial'],
  template: `<div class="phone-field">
    <select class="phone-cc" v-model="iso" @change="emit" :title="nombre(iso)">
      <option v-for="c in lista" :key="c.iso" :value="c.iso">{{ c.flag }} +{{ c.dial }}</option></select>
    <input class="phone-num" type="tel" inputmode="tel" v-model="num" @input="onInput" :placeholder="ph" autocomplete="tel"></div>`,
  data() { return { iso: 'CO', num: '', nombres: {} }; },
  computed: {
    ph() { return t('form.telefono_placeholder') || 'Número de WhatsApp'; },
    lista() {
      const pref = ['CO','MX','AR','CL','PE','EC','VE','BR','ES','US'];
      const keys = Object.keys(DIAL);
      const ordenadas = [...pref.filter(k => DIAL[k]), ...keys.filter(k => !pref.includes(k)).sort((a, b) => this.nombre(a).localeCompare(this.nombre(b)))];
      return ordenadas.map((iso) => ({ iso, dial: DIAL[iso], flag: flagEmoji(iso) }));
    },
    selected() { return { iso: this.iso, dial: DIAL[this.iso] || '' }; },
  },
  methods: {
    nombre(iso) { return this.nombres[iso] || iso; },
    onInput() {
      const raw = this.num.trim();
      if (raw.startsWith('+') || raw.startsWith('00')) {
        const digits = raw.replace(/^00/, '').replace(/\D/g, '');
        const match = this.lista.slice().sort((a, b) => b.dial.length - a.dial.length).find((c) => digits.startsWith(c.dial));
        if (match) { this.iso = match.iso; this.num = digits.slice(match.dial.length); }
      }
      this.emit();
    },
    emit() {
      const nat = this.num.replace(/\D/g, '');
      this.$emit('update:modelValue', nat ? this.selected.dial + nat : '');
      this.$emit('dial', this.selected.dial);
    },
    valid() { const nat = this.num.replace(/\D/g, ''); return nat === '' || nat.length >= 6; },
    parse(v) {
      const digits = (v || '').replace(/\D/g, '');
      if (!digits) { this.num = ''; return; }
      const match = this.lista.slice().sort((a, b) => b.dial.length - a.dial.length).find((c) => digits.startsWith(c.dial));
      if (match) { this.iso = match.iso; this.num = digits.slice(match.dial.length); } else { this.num = digits; }
    },
  },
  async mounted() {
    try { const p = await paises(); this.nombres = p[store.locale] || p.es || {}; } catch (e) { /* nombres opcionales */ }
    if (this.modelValue) { this.parse(this.modelValue); }
    this.emit();
  },
};

// Campos de captura de lead compartidos
export const LeadFields = {
  components: { Combo, PhoneInput },
  props: { modelValue: Object, full: Boolean, company: { type: Boolean, default: true } },
  emits: ['update:modelValue'],
  template: `<div style="display:grid;gap:1.2rem">
    <div class="honeypot"><input type="text" v-model="d.website" tabindex="-1" autocomplete="off"></div>
    <div class="form-row">
      <div class="field"><label>{{ t('form.nombre') }} *</label><input class="field-el" style="width:100%;padding:.85rem 1rem;border-radius:var(--r-sm);border:1px solid var(--line-strong);background:rgba(5,17,38,.5);color:var(--neutral-light);font-family:var(--font);font-size:.95rem" v-model="d.name" autocomplete="name"><p class="err" v-if="errors.name">{{ t('form.error_validacion') }}</p></div>
      <div class="field"><label>{{ t('form.email') }} *</label><input class="field-el" style="width:100%;padding:.85rem 1rem;border-radius:var(--r-sm);border:1px solid var(--line-strong);background:rgba(5,17,38,.5);color:var(--neutral-light);font-family:var(--font);font-size:.95rem" type="email" v-model="d.email" autocomplete="email"><p class="err" v-if="errors.email">{{ t('form.error_validacion') }}</p></div>
    </div>
    <div class="field"><label>{{ t('form.telefono') }}</label><PhoneInput ref="phone" v-model="d.phone_wa" @dial="d.phone_dial=$event" /><p class="hint">{{ t('form.telefono_hint') }}</p><p class="err" v-if="phoneError">{{ t('form.error_telefono') }}</p></div>
    <div class="field"><label>{{ t('form.pais') }} *</label><Combo v-model="d.country" :options="opcPais" :placeholder="t('form.pais_placeholder')" /><p class="err" v-if="errors.country">{{ t('form.error_validacion') }}</p></div>
    <div class="form-row" v-if="company">
      <div class="field"><label>{{ t('form.empresa') }} <span v-if="full">*</span></label><input class="field-el" style="width:100%;padding:.85rem 1rem;border-radius:var(--r-sm);border:1px solid var(--line-strong);background:rgba(5,17,38,.5);color:var(--neutral-light);font-family:var(--font);font-size:.95rem" v-model="d.company" autocomplete="organization"></div>
      <div class="field" v-if="full"><label>{{ t('form.rol') }}</label><input class="field-el" style="width:100%;padding:.85rem 1rem;border-radius:var(--r-sm);border:1px solid var(--line-strong);background:rgba(5,17,38,.5);color:var(--neutral-light);font-family:var(--font);font-size:.95rem" v-model="d.role"></div>
    </div>
    <div class="form-row" v-if="company">
      <div class="field"><label>{{ t('form.industria') }} <span v-if="full">*</span></label><Combo v-model="d.industry" :options="opcInd" :placeholder="t('form.industria_placeholder')" /></div>
      <div class="field"><label>{{ t('form.empleados') }} <span v-if="full">*</span></label><Combo v-model="d.company_size" :options="opcTam" :placeholder="t('form.empleados')" /></div>
    </div>
  </div>`,
  data() { return { d: this.modelValue, errors: {}, phoneError: false, opcPais: [], metaInd: [] }; },
  computed: {
    t: () => t,
    opcInd() { return this.metaInd.map(i => ({ value: i.key, label: tr(i.nombre) })); },
    opcTam() { const m = t('form.empleados_opciones') || {}; return Object.keys(m).map(k => ({ value: k, label: m[k] })); },
  },
  async mounted() {
    const meta = await loadMeta();
    this.metaInd = meta.industries || [];
    this.opcPais = (meta.countries || []).map(c => ({ value: c.iso, label: tr(c.nombre) })).sort((a, b) => a.label.localeCompare(b.label));
  },
  watch: { d: { deep: true, handler(v) { this.$emit('update:modelValue', v); } } },
  methods: {
    validate(required) {
      this.errors = {};
      for (const f of required) { if (!this.d[f]) this.errors[f] = true; }
      this.phoneError = this.$refs.phone && !this.$refs.phone.valid();
      return Object.keys(this.errors).length === 0 && !this.phoneError;
    },
    setErrors(campos) { this.errors = {}; (campos || []).forEach(c => this.errors[c] = true); },
  },
};

// Modal de captación para landings (formulario ligero, mobile-first)
const blankInteres = () => ({ name:'', email:'', phone_wa:'', phone_dial:'', country:'', website:'' });
export const LeadModal = {
  components: { LeadFields, Icon },
  props: { titulo: String, origen: String },
  emits: ['close'],
  template: `<div class="lm-bg" @click.self="$emit('close')"><div class="glass glass-lit lm">
    <button class="lm-x" @click="$emit('close')" aria-label="Cerrar">✕</button>
    <div v-if="done" class="lm-ok">
      <span class="icon-chip"><Icon name="check"/></span>
      <h3 class="h3">{{ tx('landing.gracias_t','¡Gracias!') }}</h3>
      <p>{{ tx('landing.gracias_s','Recibimos tus datos. Te contactaremos muy pronto para mostrarte cómo aplicarlo a tu negocio.') }}</p>
      <router-link :to="pageUrl('agenda')" class="btn btn-primary" @click="$emit('close')">{{ tx('landing.agendar','Agendar ahora') }}</router-link></div>
    <form v-else @submit.prevent="enviar">
      <h3 class="h3">{{ tx('landing.form_t','Hablemos de tu caso') }}</h3>
      <p class="small" v-if="titulo">{{ titulo }}</p>
      <div style="margin-top:1.1rem"><LeadFields ref="lf" v-model="lead" :full="false" :company="false"/></div>
      <button class="btn btn-grad" style="width:100%;margin-top:1.2rem" :disabled="loading">{{ loading?t('form.enviando'):tx('landing.cta','Quiero más información') }}</button>
      <p class="err" v-if="error">{{ error }}</p>
      <p class="small" style="margin-top:.7rem">{{ t('form.privacidad') }}</p></form>
  </div></div>`,
  data() { return { lead: blankInteres(), loading: false, done: false, error: '' }; },
  computed: { t: () => t, pageUrl: () => pageUrl },
  methods: {
    tx(key, fb) { const v = t(key); return (v && v !== key) ? v : fb; },
    async enviar() {
      if (!this.$refs.lf.validate(['name', 'email'])) { return; }
      this.loading = true; this.error = '';
      const r = await api.post('/interes', { ...this.lead, origen: this.origen, titulo: this.titulo, locale: store.locale });
      this.loading = false;
      if (r.ok) { this.done = true; } else { this.error = r.error || 'Error'; this.$refs.lf.setErrors(r.campos); }
    },
  },
};
