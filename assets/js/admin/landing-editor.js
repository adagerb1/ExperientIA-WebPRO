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
    // Solo casos: historia visual por capítulos, línea de tiempo y confidencialidad.
    historia: (L.historia || []).map(h => ({ tag: fillI18n(h.tag), t: fillI18n(h.t), x: fillI18n(h.x), viz: h.viz || 'bars', img: h.img || '' })),
    timeline: (L.timeline || []).map(s => ({ fase: fillI18n(s.fase), t: fillI18n(s.t), x: fillI18n(s.x) })),
    confidencial: !!L.confidencial,
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
    historia: f.historia.filter(h => CMS_CODES.some(c => (h.x[c] || '').trim())),
    timeline: f.timeline.filter(s => CMS_CODES.some(c => (s.t[c] || '').trim())),
    confidencial: !!f.confidencial,
  };
  if (f.oferta.on) out.oferta = { on: true, badge: f.oferta.badge, titulo: f.oferta.titulo, texto: f.oferta.texto, cta: f.oferta.cta };
  if (f.testimonio.on && CMS_CODES.some(c => (f.testimonio.quote[c] || '').trim())) {
    out.testimonio = { on: true, quote: f.testimonio.quote, autor: f.testimonio.autor, cargo: f.testimonio.cargo };
  }
  return out;
}

// Vista previa en vivo: refleja los bloques del idioma actual mientras se edita.
// Compacta y de marca (clases lp-*), sin router-links. Cae al ES si el idioma está vacío.
const LandingPreview = {
  components: { Icon },
  props: { f: { type: Object, required: true }, lang: { type: String, default: 'es' }, tabla: { type: String, required: true }, item: { type: Object, default: () => ({}) } },
  computed: {
    esCaso() { return this.tabla === 'case_studies'; },
    titulo() { return this.trItem(this.item.titulo || this.item.nombre) || 'Título de la landing'; },
    sector() { return this.trItem(this.item.sector || this.item.rol); },
    star() { const r = (this.item.resultados || [])[0]; return r ? { valor: r.valor, label: this.trItem(r.label) } : null; },
    promesa() { return this.L(this.f.promesa); },
    antes() { return this.L(this.f.antes); },
    despues() { return this.L(this.f.despues); },
    cta() { return this.L(this.f.cta) || 'Quiero más información'; },
    historia() { return (this.f.historia || []).map(h => ({ tag: this.L(h.tag), t: this.L(h.t), x: this.L(h.x), viz: h.viz || 'bars', img: h.img })).filter(h => h.t || h.x); },
    timeline() { return (this.f.timeline || []).map(s => ({ fase: this.L(s.fase), t: this.L(s.t), x: this.L(s.x) })).filter(s => s.t); },
    beneficios() { return (this.f.beneficios || []).map(b => ({ icon: b.icon, t: this.L(b.t), x: this.L(b.x) })).filter(b => b.t); },
    pasos() { return (this.f.pasos || []).map(p => ({ t: this.L(p.t), x: this.L(p.x) })).filter(p => p.t); },
    entregables() { return (this.f.entregables[this.lang] || this.f.entregables.es || '').split('\n').map(s => s.trim()).filter(Boolean); },
    metricas() { return (this.f.metricas || []).map(m => ({ valor: m.valor, suf: m.suf, label: this.L(m.label) })).filter(m => String(m.valor || '').trim()); },
    faqs() { return (this.f.faqs || []).map(x => ({ q: this.L(x.q), a: this.L(x.a) })).filter(x => x.q); },
    testimonio() { return (this.f.testimonio && this.f.testimonio.on && this.L(this.f.testimonio.quote)) ? { quote: this.L(this.f.testimonio.quote), autor: this.f.testimonio.autor, cargo: this.L(this.f.testimonio.cargo) } : null; },
    oferta() { return (this.f.oferta && this.f.oferta.on) ? { badge: this.L(this.f.oferta.badge), titulo: this.L(this.f.oferta.titulo), texto: this.L(this.f.oferta.texto), cta: this.L(this.f.oferta.cta) } : null; },
  },
  methods: {
    L(o) { return o ? (o[this.lang] || o.es || '') : ''; },
    trItem(v) { if (!v) return ''; if (typeof v !== 'object') return String(v); return v[this.lang] || v.es || Object.values(v)[0] || ''; },
    vizLabel(v) { return { radar: 'Visual · radar', flow: 'Visual · flujo', bars: 'Visual · barras' }[v] || 'Visual'; },
  },
  template: `<div class="lp">
    <div class="lp-badge">Vista previa · {{ lang.toUpperCase() }}</div>
    <!-- Hero -->
    <div class="lp-hero">
      <div class="lp-chips"><span v-if="sector" class="lp-chip">{{ sector }}</span><span v-if="esCaso && f.confidencial" class="lp-chip lp-chip--conf">Caso real · confidencial</span></div>
      <h3 class="lp-title">{{ titulo }}</h3>
      <p v-if="promesa" class="lp-lead">{{ promesa }}</p>
      <span class="lp-btn">{{ cta }}</span>
      <div v-if="esCaso && star" class="lp-star"><b>{{ star.valor }}</b><span>{{ star.label }}</span></div>
    </div>
    <!-- Contraste -->
    <div v-if="antes || despues" class="lp-contrast">
      <div class="lp-col"><span class="lp-tag">Hoy</span><p>{{ antes || '—' }}</p></div>
      <div class="lp-col lp-col--win"><span class="lp-tag">Con ExperientIA</span><p>{{ despues || '—' }}</p></div>
    </div>
    <!-- Historia (casos) -->
    <div v-if="esCaso && historia.length" class="lp-sec"><h4 class="lp-h">Historia</h4>
      <div v-for="(h,i) in historia" :key="i" class="lp-cap">
        <div class="lp-cap-media"><img v-if="h.img" :src="h.img" alt=""><span v-else class="lp-viz">{{ vizLabel(h.viz) }}</span></div>
        <div class="lp-cap-txt"><span class="lp-num">{{ ('0'+(i+1)).slice(-2) }}</span><span class="lp-cap-tag">{{ h.tag }}</span><b>{{ h.t }}</b><p>{{ h.x }}</p></div></div></div>
    <!-- Beneficios -->
    <div v-if="beneficios.length" class="lp-sec"><h4 class="lp-h">Lo que cambia</h4>
      <div class="lp-grid"><div v-for="(b,i) in beneficios" :key="i" class="lp-bene"><span class="lp-ic"><Icon :name="b.icon||'check'" :size="14"/></span><b>{{ b.t }}</b><p>{{ b.x }}</p></div></div></div>
    <!-- Pasos -->
    <div v-if="pasos.length" class="lp-sec"><h4 class="lp-h">Cómo funciona</h4>
      <div v-for="(p,i) in pasos" :key="i" class="lp-step"><span class="lp-num">{{ i+1 }}</span><div><b>{{ p.t }}</b><p>{{ p.x }}</p></div></div></div>
    <!-- Timeline (casos) -->
    <div v-if="esCaso && timeline.length" class="lp-sec"><h4 class="lp-h">El camino</h4>
      <div v-for="(s,i) in timeline" :key="i" class="lp-tl"><span class="lp-dot"></span><div><span class="lp-fase">{{ s.fase }}</span><b>{{ s.t }}</b><p>{{ s.x }}</p></div></div></div>
    <!-- Entregables + métricas -->
    <div v-if="entregables.length" class="lp-sec"><h4 class="lp-h">Qué recibes</h4>
      <ul class="lp-list"><li v-for="(e,i) in entregables" :key="i">{{ e }}</li></ul></div>
    <div v-if="metricas.length" class="lp-metrics"><div v-for="(m,i) in metricas" :key="i"><b>{{ m.valor }}{{ m.suf }}</b><span>{{ m.label }}</span></div></div>
    <!-- Oferta -->
    <div v-if="oferta" class="lp-oferta"><span class="lp-chip lp-chip--cyan">{{ oferta.badge||'Sin costo' }}</span><b>{{ oferta.titulo }}</b><p>{{ oferta.texto }}</p></div>
    <!-- Testimonio -->
    <div v-if="testimonio" class="lp-quote"><p>“{{ testimonio.quote }}”</p><span v-if="testimonio.autor||testimonio.cargo">{{ testimonio.autor }}<template v-if="testimonio.cargo"> · {{ testimonio.cargo }}</template></span></div>
    <!-- FAQs -->
    <div v-if="faqs.length" class="lp-sec"><h4 class="lp-h">Objeciones</h4>
      <div v-for="(x,i) in faqs" :key="i" class="lp-faq"><b>{{ x.q }}</b><p>{{ x.a }}</p></div></div>
  </div>`,
};

export const LandingEditor = {
  components: { Icon, LandingPreview },
  props: { item: { type: Object, required: true }, tabla: { type: String, required: true }, titulo: { type: String, default: '' } },
  emits: ['close', 'saved'],
  template: `<div class="modal-bg" @click.self="$emit('close')">
   <div class="glass modal modal-xl land-editor" :class="{'has-preview':showPreview}">
    <div class="rec-editor-head">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem">
        <h2>Landing de conversión<span v-if="titulo"> · {{ titulo }}</span></h2>
        <div style="display:flex;gap:.5rem;align-items:center">
          <button class="btn btn-ghost btn-sm" @click="showPreview=!showPreview"><Icon name="eye" :size="14"/> {{ showPreview?'Ocultar preview':'Ver preview' }}</button>
          <button class="btn btn-ghost btn-sm" @click="$emit('close')">✕</button></div></div>
      <div class="rec-langbar">
        <div class="lang-tabs" style="margin:0;border:0;padding:0">
          <button type="button" v-for="l in langs" :key="l.code" :class="{active:lang===l.code}" @click="lang=l.code">{{ l.label }}<span v-if="l.code==='es'" class="lang-req">·oblig</span></button></div>
      </div>
      <p class="rec-lang-note">Editando <b>{{ lang.toUpperCase() }}</b>. Las secciones vacías no se muestran en la landing pública. ES es el respaldo si un idioma queda sin texto.</p>
    </div>

    <div class="land-editor__body">
     <div class="land-editor__form">
    <div class="glass panel land-ai" :class="{open:aiOpen}">
      <div class="land-ai__head" @click="aiOpen=!aiOpen">
        <b><Icon name="ia" :size="15"/> Generar con IA</b>
        <span class="land-ai__chev" :class="{on:aiOpen}">▾</span></div>
      <div v-if="aiOpen" class="land-ai__body">
        <p class="rec-lang-note" style="margin:0">AlexIA orquesta a su equipo (estratega · copywriter · traductor) y arma un borrador trilingüe desde la ficha + tu enfoque. Lo revisas aquí y lo guardas. No se publica solo.</p>
        <textarea class="inp" rows="2" v-model="brief" placeholder="Enfoque opcional: público objetivo, ángulo, tono, algo a resaltar… (2-3 líneas)"></textarea>
        <div style="display:flex;gap:.6rem;align-items:center">
          <button class="btn btn-grad btn-sm" @click="generar" :disabled="aiBusy"><Icon name="ia" :size="14"/> {{ aiBusy?'Generando…':'Generar borrador' }}</button>
          <span v-if="aiBusy" class="rec-lang-note" style="margin:0">AlexIA está escribiendo y traduciendo; puede tardar unos segundos.</span></div>
        <p v-if="aiMsg" class="small" :style="{color:aiOk?'#4be3a0':'#ff7d9d',margin:0}">{{ aiMsg }}</p>
      </div>
    </div>

    <div class="sec-divider"><span>Promesa (héroe)</span></div>
    <div class="field"><label>Promesa · subtítulo del héroe · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="2" v-model="f.promesa[lang]" placeholder="La transformación en una frase potente"></textarea></div>
    <div class="field"><label>Texto del botón principal (CTA) · {{ lang.toUpperCase() }}</label><input class="inp" v-model="f.cta[lang]" placeholder="Quiero más información"></div>

    <div class="sec-divider"><span>Contraste antes / después</span></div>
    <div class="form-grid two">
      <div class="field"><label>Hoy (el problema) · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="3" v-model="f.antes[lang]"></textarea></div>
      <div class="field"><label>Con ExperientIA · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="3" v-model="f.despues[lang]"></textarea></div></div>

    <template v-if="tabla==='case_studies'">
      <div class="sec-divider"><span>Historia visual por capítulos <button type="button" class="btn btn-ghost btn-sm" @click="add('historia',{tag:blank(),t:blank(),x:blank(),viz:'bars',img:''})"><Icon name="plug" :size="12"/> Añadir capítulo</button></span></div>
      <p class="rec-lang-note">Cada capítulo alterna texto y visual (reto → jugada → resultado). Sin imagen, se muestra una visual animada de marca. Si no defines capítulos, la landing usa el contexto e intervención del caso.</p>
      <div v-for="(h,i) in f.historia" :key="'h'+i" class="glass panel land-block">
        <div class="form-grid" style="grid-template-columns:1fr 1fr auto;align-items:end">
          <div class="field"><label>Etiqueta (p. ej. El reto) · {{ lang.toUpperCase() }}</label><input class="inp" v-model="h.tag[lang]" placeholder="El reto"></div>
          <div class="field"><label>Título del capítulo · {{ lang.toUpperCase() }}</label><input class="inp" v-model="h.t[lang]"></div>
          <button class="btn btn-danger btn-sm" @click="del('historia',i)">✕</button></div>
        <div class="field"><label>Relato · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="3" v-model="h.x[lang]"></textarea></div>
        <div class="form-grid two" style="align-items:end">
          <div class="field"><label>Visual de marca (si no hay imagen)</label>
            <select class="inp" v-model="h.viz"><option value="radar">radar · diagnóstico / reto</option><option value="flow">flow · proceso / intervención</option><option value="bars">bars · crecimiento / resultado</option></select></div>
          <div class="field"><label>Imagen del capítulo (opcional · JPG/PNG/WebP)</label>
            <div style="display:flex;gap:.6rem;align-items:center">
              <label class="btn btn-ghost btn-sm" style="cursor:pointer">{{ h.img?'Cambiar':'Subir imagen' }}<input type="file" accept="image/jpeg,image/png,image/webp" hidden @change="subirImg($event,h)"></label>
              <button v-if="h.img" class="btn btn-danger btn-sm" @click="h.img=''">Quitar</button>
              <img v-if="h.img" :src="h.img" alt="" style="height:44px;border-radius:8px;object-fit:cover;aspect-ratio:4/3"></div></div></div>
      </div>

      <div class="sec-divider"><span>Línea de tiempo <button type="button" class="btn btn-ghost btn-sm" @click="add('timeline',{fase:blank(),t:blank(),x:blank()})"><Icon name="plug" :size="12"/> Añadir hito</button></span></div>
      <div v-for="(s,i) in f.timeline" :key="'tl'+i" class="glass panel land-block">
        <div class="form-grid" style="grid-template-columns:160px 1fr auto;align-items:end">
          <div class="field"><label>Fase · {{ lang.toUpperCase() }}</label><input class="inp" v-model="s.fase[lang]" placeholder="Semanas 1–2"></div>
          <div class="field"><label>Hito · {{ lang.toUpperCase() }}</label><input class="inp" v-model="s.t[lang]"></div>
          <button class="btn btn-danger btn-sm" @click="del('timeline',i)">✕</button></div>
        <div class="field"><label>Detalle · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="2" v-model="s.x[lang]"></textarea></div>
      </div>

      <div class="field"><label class="switch-row"><label class="switch"><input type="checkbox" v-model="f.confidencial"><span></span></label> Mostrar sello «Caso real · cliente en confidencialidad»</label></div>
    </template>

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
     </div>
     <aside class="land-editor__preview" v-if="showPreview"><LandingPreview :f="f" :lang="lang" :tabla="tabla" :item="item"/></aside>
    </div>
   </div></div>`,
  data() { return { f: normalizar(this.item.landing), langs: CMS_LANGS, lang: 'es', icons: ICONS, busy: false, showPreview: window.innerWidth >= 1100, aiOpen: false, aiBusy: false, aiMsg: '', aiOk: false, brief: '' }; },
  methods: {
    blank() { return i18n(); },
    add(k, obj) { this.f[k].push(obj); },
    del(k, i) { this.f[k].splice(i, 1); },
    async generar() {
      this.aiBusy = true; this.aiMsg = ''; this.aiOk = false;
      const r = await api.post('/admin/landing/' + this.tabla + '/' + this.item.id + '/generar', { brief: this.brief });
      this.aiBusy = false;
      if (r.ok && r.data && r.data.landing) {
        this.f = normalizar(r.data.landing);
        this.aiOk = true; this.aiMsg = 'Borrador generado. Revísalo en la vista previa, ajústalo y guarda.';
        this.showPreview = true;
      } else {
        this.aiMsg = r.error || 'No se pudo generar. Revisa que OpenAI o Claude estén activos en Conectores.';
      }
    },
    async subirImg(e, h) {
      const file = e.target.files[0]; if (!file) return;
      const fd = new FormData(); fd.append('archivo', file);
      const r = await api.upload('/admin/recursos/subir-imagen', fd);
      if (r.ok) { h.img = r.data.cover_image; toast('Imagen subida.'); } else toast(r.error || 'Error al subir.', 'err');
      e.target.value = '';
    },
    async guardar() {
      this.busy = true;
      const r = await api.put('/admin/' + this.tabla + '/' + this.item.id, { landing: serializar(this.f) });
      this.busy = false;
      if (r.ok) { toast('Landing guardada.'); this.$emit('saved'); } else toast(r.error || 'Error al guardar.', 'err');
    },
  },
};
