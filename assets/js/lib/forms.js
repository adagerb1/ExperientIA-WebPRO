// ExperientIA · Componentes de formulario (combobox buscable, teléfono WhatsApp, campos de lead)
import { t, store } from './core.js';

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
    <input class="field-el combo__input" readonly :value="label" :placeholder="placeholder" @click="toggle" @focus="toggle" />
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

// Input de teléfono WhatsApp con bandera + indicativo (usa intl-tel-input global)
export const PhoneInput = {
  props: { modelValue: String },
  emits: ['update:modelValue', 'dial'],
  template: `<input ref="el" type="tel" class="field-el" style="width:100%;padding:.85rem 1rem;border-radius:var(--r-sm);border:1px solid var(--line-strong);background:rgba(5,17,38,.5);color:var(--neutral-light);font-family:var(--font);font-size:.95rem" autocomplete="tel" />`,
  mounted() {
    const load = () => {
      if (!window.intlTelInput) { setTimeout(load, 100); return; }
      this.iti = window.intlTelInput(this.$refs.el, {
        initialCountry: 'co', separateDialCode: true, countrySearch: true,
        preferredCountries: ['co','mx','us','br','ar','cl','pe','ec','es'],
      });
      const sync = () => {
        const num = (this.iti.getNumber() || '').replace(/\D/g, '');
        this.$emit('update:modelValue', num);
        this.$emit('dial', (this.iti.getSelectedCountry() || {}).dialCode || '');
      };
      this.$refs.el.addEventListener('change', sync);
      this.$refs.el.addEventListener('keyup', sync);
      this.$refs.el.addEventListener('countrychange', sync);
    };
    load();
  },
  methods: { valid() { return !this.$refs.el.value.trim() || (this.iti && this.iti.isValidNumber()); } },
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
    <div class="form-row">
      <div class="field"><label>{{ t('form.telefono') }}</label><PhoneInput ref="phone" v-model="d.phone_wa" @dial="d.phone_dial=$event" /><p class="hint">{{ t('form.telefono_hint') }}</p><p class="err" v-if="phoneError">{{ t('form.error_telefono') }}</p></div>
      <div class="field"><label>{{ t('form.pais') }} *</label><Combo v-model="d.country" :options="opcPais" :placeholder="t('form.pais_placeholder')" /><p class="err" v-if="errors.country">{{ t('form.error_validacion') }}</p></div>
    </div>
    <div class="form-row" v-if="company">
      <div class="field"><label>{{ t('form.empresa') }} <span v-if="full">*</span></label><input class="field-el" style="width:100%;padding:.85rem 1rem;border-radius:var(--r-sm);border:1px solid var(--line-strong);background:rgba(5,17,38,.5);color:var(--neutral-light);font-family:var(--font);font-size:.95rem" v-model="d.company" autocomplete="organization"></div>
      <div class="field" v-if="full"><label>{{ t('form.rol') }}</label><input class="field-el" style="width:100%;padding:.85rem 1rem;border-radius:var(--r-sm);border:1px solid var(--line-strong);background:rgba(5,17,38,.5);color:var(--neutral-light);font-family:var(--font);font-size:.95rem" v-model="d.role"></div>
    </div>
    <div class="form-row" v-if="company">
      <div class="field"><label>{{ t('form.industria') }} <span v-if="full">*</span></label><Combo v-model="d.industry" :options="opcInd" :placeholder="t('form.industria_placeholder')" /></div>
      <div class="field"><label>{{ t('form.empleados') }} <span v-if="full">*</span></label><Combo v-model="d.company_size" :options="opcTam" :placeholder="t('form.empleados')" /></div>
    </div>
  </div>`,
  data() { return { d: this.modelValue, errors: {}, phoneError: false, opcPais: [] }; },
  computed: {
    t: () => t,
    opcInd() { const m = this.industrias; return Object.keys(m).map(k => ({ value: k, label: m[k] })); },
    opcTam() { const m = t('form.empleados_opciones') || {}; return Object.keys(m).map(k => ({ value: k, label: m[k] })); },
    industrias() { return { tecnologia:'Tecnología y software',retail:'Retail y comercio',financiero:'Servicios financieros',salud:'Salud',manufactura:'Manufactura',educacion:'Educación',logistica:'Logística y transporte',agroindustria:'Agroindustria',turismo:'Turismo y hospitalidad',profesionales:'Servicios profesionales',construccion:'Construcción e inmobiliario',energia:'Energía',gobierno:'Gobierno y ONG',medios:'Medios y marketing',otro:'Otra industria' }; },
  },
  async mounted() {
    const p = await paises();
    const lista = p[store.locale] || p.es;
    this.opcPais = Object.keys(lista).map(k => ({ value: k, label: lista[k] })).sort((a, b) => a.label.localeCompare(b.label));
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
