// Portal admin · Editor de landing de conversión (trilingüe) para soluciones, productos y casos.
// Edita el JSON `landing` de cada ítem: promesa, contraste, beneficios, pasos,
// entregables, métricas, oferta, testimonio, FAQs y prueba social.
import { api, toast, CMS_LANGS, CMS_CODES } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

const i18n = () => { const o = {}; for (const c of CMS_CODES) o[c] = ''; return o; };
const fillI18n = (v) => { const o = i18n(); if (v && typeof v === 'object') for (const c of CMS_CODES) o[c] = v[c] || ''; return o; };
const ICONS = ['target', 'shield', 'growth', 'gear', 'analitica', 'ia', 'people', 'bulb', 'cube', 'check', 'clock', 'plug'];

// Normaliza el landing cargado a una estructura completa y editable.
function normalizar(L) {
  L = (L && typeof L === 'object') ? L : {};
  const ent = L.entregables || {};
  return {
    promesa: fillI18n(L.promesa), antes: fillI18n(L.antes), despues: fillI18n(L.despues), cta: fillI18n(L.cta),
    oferta: {
      on: !!(L.oferta && L.oferta.on),
      badge: fillI18n(L.oferta && L.oferta.badge), titulo: fillI18n(L.oferta && L.oferta.titulo),
      texto: fillI18n(L.oferta && L.oferta.texto), cta: fillI18n(L.oferta && L.oferta.cta),
    },
    beneficios: (L.beneficios || []).map(b => ({ icon: b.icon || 'target', t: fillI18n(b.t), x: fillI18n(b.x) })),
    pasos: (L.pasos || []).map(p => ({ t: fillI18n(p.t), x: fillI18n(p.x) })),
    // entregables se editan como texto (una línea por ítem) por idioma.
    entregables: (() => { const o = {}; for (const c of CMS_CODES) o[c] = Array.isArray(ent[c]) ? ent[c].join('\n') : ''; return o; })(),
    metricas: (L.metricas || []).map(m => ({ valor: m.valor || '', suf: m.suf || '', label: fillI18n(m.label) })),
    testimonio: {
      on: !!(L.testimonio && L.testimonio.on),
      quote: fillI18n(L.testimonio && L.testimonio.quote), autor: (L.testimonio && L.testimonio.autor) || '', cargo: fillI18n(L.testimonio && L.testimonio.cargo),
    },
    faqs: (L.faqs || []).map(f => ({ q: fillI18n(f.q), a: fillI18n(f.a) })),
    proof_casos: L.proof_casos !== false,
  };
}

// Serializa el estado editable de vuelta al JSON de almacenamiento.
function serializar(f) {
  const limpiaI18n = (o) => { const has = CMS_CODES.some(c => (o[c] || '').trim()); return has ? o : null; };
  const out = {
    promesa: f.promesa, antes: f.antes, despues: f.despues, cta: f.cta,
    beneficios: f.beneficios.filter(b => CMS_CODES.some(c => (b.t[c] || '').trim())),
    pasos: f.pasos.filter(p => CMS_CODES.some(c => (p.t[c] || '').trim())),
    entregables: (() => { const o = {}; for (const c of CMS_CODES) o[c] = (f.entregables[c] || '').split('\n').map(s => s.trim()).filter(Boolean); return o; })(),
    metricas: f.metricas.filter(m => String(m.valor).trim()),
    faqs: f.faqs.filter(x => CMS_CODES.some(c => (x.q[c] || '').trim())),
    proof_casos: !!f.proof_casos,
  };
  if (f.oferta.on) out.oferta = { on: true, badge: f.oferta.badge, titulo: f.oferta.titulo, texto: f.oferta.texto, cta: f.oferta.cta };
  if (f.testimonio.on && CMS_CODES.some(c => (f.testimonio.quote[c] || '').trim())) {
    out.testimonio = { on: true, quote: f.testimonio.quote, autor: f.testimonio.autor, cargo: f.testimonio.cargo };
  }
  return out;
}

export const LandingEditor = {
  components: { Icon },
  props: { item: { type: Object, required: true }, tabla: { type: String, required: true }, titulo: { type: String, default: '' } },
  emits: ['close', 'saved'],
  template: `<div class="modal-bg" @click.self="$emit('close')">
   <div class="glass modal modal-lg">
    <div class="rec-editor-head">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <h2>Landing de conversión<span v-if="titulo"> · {{ titulo }}</span></h2>
        <button class="btn btn-ghost btn-sm" @click="$emit('close')">✕</button></div>
      <div class="rec-langbar">
        <div class="lang-tabs" style="margin:0;border:0;padding:0">
          <button type="button" v-for="l in langs" :key="l.code" :class="{active:lang===l.code}" @click="lang=l.code">{{ l.label }}<span v-if="l.code==='es'" class="lang-req">·oblig</span></button></div>
      </div>
      <p class="rec-lang-note">Editando <b>{{ lang.toUpperCase() }}</b>. Las secciones vacías no se muestran en la landing pública. ES es el respaldo si un idioma queda sin texto.</p>
    </div>

    <div class="sec-divider"><span>Promesa (héroe)</span></div>
    <div class="field"><label>Promesa · subtítulo del héroe · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="2" v-model="f.promesa[lang]" placeholder="La transformación en una frase potente"></textarea></div>
    <div class="field"><label>Texto del botón principal (CTA) · {{ lang.toUpperCase() }}</label><input class="inp" v-model="f.cta[lang]" placeholder="Quiero más información"></div>

    <div class="sec-divider"><span>Contraste antes / después</span></div>
    <div class="form-grid two">
      <div class="field"><label>Hoy (el problema) · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="3" v-model="f.antes[lang]"></textarea></div>
      <div class="field"><label>Con ExperientIA · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="3" v-model="f.despues[lang]"></textarea></div></div>

    <div class="sec-divider"><span>Beneficios <button type="button" class="btn btn-ghost btn-sm" @click="add('beneficios',{icon:'target',t:blank(),x:blank()})"><Icon name="plug" :size="12"/> Añadir</button></span></div>
    <div v-for="(b,i) in f.beneficios" :key="'b'+i" class="glass panel land-block">
      <div class="form-grid" style="grid-template-columns:auto 1fr auto;align-items:end">
        <div class="field" style="min-width:120px"><label>Ícono</label><select class="inp" v-model="b.icon"><option v-for="ic in icons" :key="ic" :value="ic">{{ ic }}</option></select></div>
        <div class="field"><label>Título · {{ lang.toUpperCase() }}</label><input class="inp" v-model="b.t[lang]"></div>
        <button class="btn btn-danger btn-sm" @click="del('beneficios',i)">✕</button></div>
      <div class="field"><label>Descripción · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="2" v-model="b.x[lang]"></textarea></div>
    </div>

    <div class="sec-divider"><span>Cómo funciona (pasos) <button type="button" class="btn btn-ghost btn-sm" @click="add('pasos',{t:blank(),x:blank()})"><Icon name="plug" :size="12"/> Añadir</button></span></div>
    <div v-for="(p,i) in f.pasos" :key="'p'+i" class="glass panel land-block">
      <div class="form-grid" style="grid-template-columns:1fr auto;align-items:end">
        <div class="field"><label>Paso {{ i+1 }} · título · {{ lang.toUpperCase() }}</label><input class="inp" v-model="p.t[lang]"></div>
        <button class="btn btn-danger btn-sm" @click="del('pasos',i)">✕</button></div>
      <div class="field"><label>Descripción · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="2" v-model="p.x[lang]"></textarea></div>
    </div>

    <div class="sec-divider"><span>Qué recibes (entregables)</span></div>
    <div class="field"><label>Un ítem por línea · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="5" v-model="f.entregables[lang]" placeholder="Diagnóstico ejecutivo&#10;Roadmap a 12 meses&#10;Métricas de seguimiento"></textarea></div>

    <div class="sec-divider"><span>Métricas / prueba <button type="button" class="btn btn-ghost btn-sm" @click="add('metricas',{valor:'',suf:'',label:blank()})"><Icon name="plug" :size="12"/> Añadir</button></span></div>
    <div v-for="(m,i) in f.metricas" :key="'m'+i" class="glass panel land-block">
      <div class="form-grid" style="grid-template-columns:120px 120px 1fr auto;align-items:end">
        <div class="field"><label>Valor</label><input class="inp" v-model="m.valor" placeholder="24/7"></div>
        <div class="field"><label>Sufijo</label><input class="inp" v-model="m.suf" placeholder="%"></div>
        <div class="field"><label>Etiqueta · {{ lang.toUpperCase() }}</label><input class="inp" v-model="m.label[lang]"></div>
        <button class="btn btn-danger btn-sm" @click="del('metricas',i)">✕</button></div>
    </div>

    <div class="sec-divider"><span>Oferta / lead magnet <label class="switch" style="margin-left:.5rem"><input type="checkbox" v-model="f.oferta.on"><span></span></label></span></div>
    <div v-if="f.oferta.on" class="glass panel land-block">
      <div class="form-grid two">
        <div class="field"><label>Insignia · {{ lang.toUpperCase() }}</label><input class="inp" v-model="f.oferta.badge[lang]" placeholder="Sin costo"></div>
        <div class="field"><label>Título · {{ lang.toUpperCase() }}</label><input class="inp" v-model="f.oferta.titulo[lang]"></div></div>
      <div class="field"><label>Texto · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="2" v-model="f.oferta.texto[lang]"></textarea></div>
      <div class="field"><label>Texto del botón de la oferta · {{ lang.toUpperCase() }}</label><input class="inp" v-model="f.oferta.cta[lang]"></div>
    </div>

    <div class="sec-divider"><span>Testimonio <label class="switch" style="margin-left:.5rem"><input type="checkbox" v-model="f.testimonio.on"><span></span></label></span></div>
    <div v-if="f.testimonio.on" class="glass panel land-block">
      <div class="field"><label>Cita · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="2" v-model="f.testimonio.quote[lang]"></textarea></div>
      <div class="form-grid two">
        <div class="field"><label>Autor</label><input class="inp" v-model="f.testimonio.autor" placeholder="Nombre Apellido"></div>
        <div class="field"><label>Cargo / empresa · {{ lang.toUpperCase() }}</label><input class="inp" v-model="f.testimonio.cargo[lang]"></div></div>
    </div>

    <div class="sec-divider"><span>Objeciones (FAQs) <button type="button" class="btn btn-ghost btn-sm" @click="add('faqs',{q:blank(),a:blank()})"><Icon name="plug" :size="12"/> Añadir</button></span></div>
    <div v-for="(x,i) in f.faqs" :key="'q'+i" class="glass panel land-block">
      <div class="form-grid" style="grid-template-columns:1fr auto;align-items:end">
        <div class="field"><label>Pregunta · {{ lang.toUpperCase() }}</label><input class="inp" v-model="x.q[lang]"></div>
        <button class="btn btn-danger btn-sm" @click="del('faqs',i)">✕</button></div>
      <div class="field"><label>Respuesta · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="2" v-model="x.a[lang]"></textarea></div>
    </div>

    <div class="field" style="margin-top:1rem"><label class="switch-row"><label class="switch"><input type="checkbox" v-model="f.proof_casos"><span></span></label> Mostrar franja de casos reales como prueba social</label></div>

    <div style="display:flex;justify-content:flex-end;gap:.7rem;margin-top:1rem">
      <button class="btn btn-ghost btn-sm" @click="$emit('close')">Cancelar</button>
      <button class="btn btn-primary btn-sm" @click="guardar" :disabled="busy">{{ busy?'Guardando…':'Guardar landing' }}</button></div>
   </div></div>`,
  data() { return { f: normalizar(this.item.landing), langs: CMS_LANGS, lang: 'es', icons: ICONS, busy: false }; },
  methods: {
    blank() { return i18n(); },
    add(k, obj) { this.f[k].push(obj); },
    del(k, i) { this.f[k].splice(i, 1); },
    async guardar() {
      this.busy = true;
      const r = await api.put('/admin/' + this.tabla + '/' + this.item.id, { landing: serializar(this.f) });
      this.busy = false;
      if (r.ok) { toast('Landing guardada.'); this.$emit('saved'); } else toast(r.error || 'Error al guardar.', 'err');
    },
  },
};
