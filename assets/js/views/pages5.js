// Landings de venta por solución y producto — orientadas a conversión, mobile-first.
// Estructura: promesa · contraste antes/después · beneficios · cómo funciona ·
// entregables · prueba (métricas) · objeciones (FAQ) · cierre + CTA sticky + modal.
import { t, tr, trLines, pageUrl, api, store, setMeta } from '../lib/core.js';
import { Icon, CountUp, ScrollProgress, inView } from '../lib/ui.js';
import { PageHero } from '../lib/layout.js';
import { LeadModal } from '../lib/forms.js';

async function fc(s){ const r=await api.get('/content/'+s); return r.ok?r.data:[]; }
export const slugify = (s)=> (s||'').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,80);
const tx = (key,fb)=>{ const v=t(key); return (v && v!==key)?v:fb; };
const L = (o)=> o ? (o[store.locale] || o.es || '') : '';

// El marco de venta de cada solución y producto (promesa, contraste, beneficios,
// pasos, entregables, métricas, FAQ…) vive 100% en la BD (columna `landing`),
// editable en ES/EN/PT desde el panel. Sin copy quemado en el código.

// ————————————————————— Componente de landing (compartido) —————————————————————
const Landing = {
  components:{ Icon, CountUp, ScrollProgress, PageHero, LeadModal },
  props:{ m:Object },
  data(){ return { modal:false }; },
  computed:{ t:()=>t, pageUrl:()=>pageUrl },
  methods:{ tx },
  template:`<div>
    <ScrollProgress/>
    <PageHero :eyebrow="m.eyebrow" :titulo="m.titulo" :sub="m.promesa">
      <div class="land-cta-row" v-reveal :style="{'--d':'.22s'}">
        <button class="btn btn-grad" @click="modal=true">{{ m.cta }}</button>
        <router-link :to="pageUrl(m.diag?'diagnostico':'agenda')" class="btn btn-ghost">{{ m.diag?tx('common.hacer_diagnostico','Hacer diagnóstico'):tx('landing.agendar','Agendar 1:1') }}</router-link></div>
      <p class="hero-trust small" v-reveal :style="{'--d':'.3s'}"><Icon name="check" :size="14"/> {{ tx('landing.trust','Respuesta en menos de 24 h · Sin compromiso') }}</p>
    </PageHero>

    <section class="section land-oferta-sec" style="padding-top:0"><div class="container"><div class="glass glass-lit card land-oferta" v-reveal>
      <div class="land-oferta-body"><span class="chip chip--cyan">{{ (m.oferta&&m.oferta.badge) || tx('landing.oferta_badge','Sin costo') }}</span>
        <h2 class="h3">{{ (m.oferta&&m.oferta.titulo) || tx('landing.oferta_t','Diagnóstico ejecutivo gratuito') }}</h2>
        <p>{{ (m.oferta&&m.oferta.texto) || tx('landing.oferta_s','En 30 minutos te entregamos un mapa de las 3 oportunidades de mayor retorno para tu empresa. Sin costo y sin compromiso.') }}</p></div>
      <div class="land-oferta-cta">
        <router-link :to="pageUrl('diagnostico')" class="btn btn-grad">{{ (m.oferta&&m.oferta.cta) || tx('landing.oferta_cta','Quiero mi diagnóstico gratis') }}</router-link>
        <p class="small garantia"><Icon name="shield" :size="13"/> {{ tx('landing.garantia','Si no vemos un caso claro, te lo decimos. Sin letra pequeña.') }}</p></div>
    </div></div></section>

    <section class="section land-contrast-sec"><div class="container land-contrast">
      <div class="glass card contrast-col contrast-antes" v-reveal><span class="contrast-tag">{{ tx('landing.antes','Hoy') }}</span><p>{{ m.antes }}</p></div>
      <div class="contrast-arrow" v-reveal :style="{'--d':'.1s'}"><Icon name="arrow" :size="26"/></div>
      <div class="glass glass-lit card contrast-col contrast-despues" v-reveal :style="{'--d':'.16s'}"><span class="contrast-tag contrast-tag--win">{{ tx('landing.despues','Con ExperientIA') }}</span><p>{{ m.despues }}</p></div>
    </div></section>

    <section class="section"><div class="container">
      <div class="section-head"><h2 class="h2" v-reveal>{{ tx('landing.benes_t','Lo que cambia para ti') }}</h2></div>
      <div class="grid grid-3 land-benes">
        <article v-for="(b,i) in m.beneficios" :key="i" class="glass card bene-card" v-reveal :style="{'--d':i*.09+'s'}">
          <span class="icon-chip"><Icon :name="b.icon"/></span><h3 class="h3">{{ b.t }}</h3><p>{{ b.x }}</p></article></div></div></section>

    <section class="section land-steps-sec"><div class="bg-atmos"><div class="halo halo-violet" style="width:460px;height:460px;top:-120px;left:-220px;opacity:.24"></div></div>
      <div class="container">
      <div class="section-head"><p class="eyebrow" v-reveal>{{ tx('landing.como_t','Cómo funciona') }}</p><h2 class="h2" v-reveal :style="{'--d':'.06s'}">{{ tx('landing.como_s','Un método claro, sin humo') }}</h2></div>
      <div class="land-steps">
        <div v-for="(s,i) in m.pasos" :key="i" class="glass card step" v-reveal :style="{'--d':i*.1+'s'}">
          <span class="step-num">{{ i+1 }}</span><div><h3 class="h3">{{ s.t }}</h3><p>{{ s.x }}</p></div></div></div></div></section>

    <section class="section"><div class="container land-grid">
      <div class="land-body">
        <div class="glass card" v-reveal><h2 class="lbl" style="margin-bottom:1rem">{{ tx('landing.entregables_t','Qué recibes') }}</h2>
          <ul class="check-list check-list--lg"><li v-for="(e,i) in m.entregables" :key="i" v-reveal :style="{'--d':i*.05+'s'}"><span class="check-chip"><Icon name="check" :size="13"/></span>{{ e }}</li></ul></div>
        <div class="glass glass-lit card land-metrics" v-reveal v-if="m.metricas.length">
          <div v-for="(mt,i) in m.metricas" :key="i" class="metric"><span class="metric-val grad-text"><CountUp :value="mt.valor" :suffix="mt.suf||''"/></span><span class="metric-lbl">{{ mt.label }}</span></div></div>
      </div>
      <aside class="land-aside"><div class="glass glass-lit card land-cta" v-reveal>
        <h3 class="h3">{{ tx('landing.aside_t','Da el primer paso') }}</h3><p>{{ tx('landing.aside_s','Cuéntanos tu caso y te mostramos cómo aplicarlo a tu negocio.') }}</p>
        <button class="btn btn-grad" style="width:100%" @click="modal=true">{{ m.cta }}</button>
        <router-link :to="pageUrl('agenda')" class="btn btn-ghost" style="width:100%">{{ tx('landing.agendar','Agendar 1:1') }}</router-link>
        <p class="small garantia" style="justify-content:center"><Icon name="shield" :size="12"/> {{ tx('landing.garantia_corta','Sin costo · sin compromiso') }}</p>
        <p class="small" style="text-align:center">{{ t('form.privacidad') }}</p></div></aside></div></section>

    <section class="section land-proof-sec" v-if="m.casos && m.casos.length"><div class="bg-atmos"><div class="halo halo-cyan" style="width:440px;height:440px;top:-120px;right:-200px;opacity:.22"></div></div>
      <div class="container"><div class="section-head"><p class="eyebrow" v-reveal>{{ tx('landing.proof_e','Resultados reales') }}</p><h2 class="h2" v-reveal :style="{'--d':'.06s'}">{{ tx('landing.proof_t','Lo que logran nuestros clientes') }}</h2></div>
      <div class="grid grid-3 land-proof">
        <router-link v-for="(c,i) in m.casos" :key="i" :to="c.url" class="glass glass-lit card proof-card" v-reveal :style="{'--d':i*.08+'s'}">
          <span class="proof-sector">{{ c.sector }}</span><h3 class="h3">{{ c.titulo }}</h3>
          <div class="proof-metric"><span class="grad-text proof-val">{{ c.valor }}</span><span class="proof-lbl">{{ c.label }}</span></div>
          <span class="link-arrow">{{ tx('landing.ver_caso','Ver el caso') }} <Icon name="arrow" :size="15"/></span></router-link></div></div></section>

    <section class="section" v-if="m.testimonio"><div class="container" style="max-width:820px">
      <figure class="glass glass-lit card land-quote" v-reveal>
        <Icon name="bulb" :size="22"/>
        <blockquote>“{{ m.testimonio.quote }}”</blockquote>
        <figcaption v-if="m.testimonio.autor||m.testimonio.cargo"><b>{{ m.testimonio.autor }}</b><span v-if="m.testimonio.cargo"> · {{ m.testimonio.cargo }}</span></figcaption>
      </figure></div></section>

    <section class="section" v-if="m.faqs && m.faqs.length"><div class="container land-faqs">
      <div class="section-head"><h2 class="h2" v-reveal>{{ tx('landing.faq_t','Antes de que preguntes') }}</h2></div>
      <div class="faq-list"><details v-for="(f,i) in m.faqs" :key="i" class="glass faq-item" v-reveal :style="{'--d':i*.06+'s'}" :open="i===0"><summary>{{ f.q }}</summary><p>{{ f.a }}</p></details></div></div></section>

    <section class="section"><div class="container"><div class="glass glass-lit card land-final" v-reveal>
      <h2 class="h2">{{ tx('landing.final_t','¿Listo para aplicarlo en tu empresa?') }}</h2>
      <p class="lead">{{ tx('landing.final_s','Agenda una sesión o déjanos tus datos y te contactamos para mostrarte cómo funciona en tu caso.') }}</p>
      <div class="land-cta-row"><button class="btn btn-grad" @click="modal=true">{{ m.cta }}</button>
        <router-link :to="pageUrl('diagnostico')" class="btn btn-ghost">{{ tx('landing.oferta_cta','Quiero mi diagnóstico gratis') }}</router-link></div>
      <p class="small garantia" style="justify-content:center"><Icon name="shield" :size="13"/> {{ tx('landing.garantia','Si no vemos un caso claro, te lo decimos. Sin letra pequeña.') }}</p></div></div></section>

    <div class="land-sticky"><span>{{ m.titulo }}</span><button class="btn btn-grad btn-sm" @click="modal=true">{{ tx('landing.cta_corto','Me interesa') }}</button></div>
    <LeadModal v-if="modal" :titulo="m.titulo" :origen="m.origen" @close="modal=false"/>
  </div>`,
};

const NoEncontrado = {
  template:`<section class="page-hero" style="min-height:50vh;display:grid;align-items:center"><div class="container" style="text-align:center;display:grid;gap:1rem;justify-items:center">
    <h1 class="h2">{{ tx('landing.no_t','Contenido no encontrado') }}</h1><router-link :to="pageUrl(volver)" class="btn btn-primary">{{ tx('landing.volver','Ver todo') }}</router-link></div></section>`,
  props:{ volver:String }, computed:{ pageUrl:()=>pageUrl }, methods:{ tx },
};

// Prueba social embebida: toma los casos y arma tarjetas con su métrica estelar.
async function proofCasos(){
  const casos = await fc('casos');
  return casos.slice(0,3).map(c=>{
    const res = (c.resultados||[])[0] || {};
    return { sector: tr(c.sector), titulo: tr(c.titulo), valor: res.valor || '', label: res.label ? tr(res.label) : '',
      url: pageUrl('casos', { slug: slugify(tr(c.titulo,'es')) }) };
  }).filter(c=>c.valor);
}

export const SolucionLanding = {
  components:{ Landing, NoEncontrado },
  template:`<Landing v-if="m" :m="m"/><NoEncontrado v-else-if="cargado" volver="soluciones"/>`,
  data(){ return { m:null, cargado:false }; },
  async mounted(){
    const items=await fc('soluciones'); const s=items.find(x=>x.skey===this.$route.params.slug);
    if(s){ const c=(s.landing&&typeof s.landing==='object')?s.landing:{}; const casos=await proofCasos(); this.m=build(s, c, tr(s.pilar), tr(s.titulo), 'solucion:'+s.skey, 'soluciones', trLines(s.como), true, tr(s.problema), tr(s.cambia), casos); setMeta(tr(s.titulo)+' · ExperientIA', L(c.promesa)||tr(s.cambia)); }
    this.cargado=true;
  },
};

export const ProductoLanding = {
  components:{ Landing, NoEncontrado },
  template:`<Landing v-if="m" :m="m"/><NoEncontrado v-else-if="cargado" volver="productos"/>`,
  data(){ return { m:null, cargado:false }; },
  async mounted(){
    const slug=this.$route.params.slug; const items=await fc('productos'); const p=items.find(x=>slugify(tr(x.nombre,'es'))===slug);
    if(p){ const c=(p.landing&&typeof p.landing==='object')?p.landing:{}; const casos=await proofCasos(); this.m=build(p, c, tr(p.rol), tr(p.nombre), 'producto:'+slug, 'productos', [], false, tr(p.texto), tr(p.rol), casos); setMeta(tr(p.nombre)+' · ExperientIA', L(c.promesa)||tr(p.texto)); }
    this.cargado=true;
  },
};

// Normaliza entidad CMS + copy de landing (BD o curado) en el modelo que consume <Landing>.
function build(ent, c, eyebrow, titulo, origen, volver, comoLines, diag, antesFb, despuesFb, casos){
  const beneficios = (c.beneficios||[]).map(b=>({ icon:b.icon, t:L(b.t), x:L(b.x) }));
  const pasos = (c.pasos||[]).map(p=>({ t:L(p.t), x:L(p.x) }));
  const metricas = (c.metricas||[]).map(mt=>({ valor:mt.valor, suf:mt.suf||'', label:L(mt.label) }));
  const faqs = (c.faqs||[]).map(f=>({ q:L(f.q), a:L(f.a) }));
  let entregables = c.entregables ? (c.entregables[store.locale]||c.entregables.es||[]) : [];
  if((!entregables || !entregables.length) && comoLines && comoLines.length) entregables = comoLines;
  const proofOn = c.proof_casos !== false;
  const oferta = (c.oferta && c.oferta.on) ? { badge:L(c.oferta.badge), titulo:L(c.oferta.titulo), texto:L(c.oferta.texto), cta:L(c.oferta.cta) } : null;
  const testimonio = (c.testimonio && c.testimonio.on && L(c.testimonio.quote)) ? { quote:L(c.testimonio.quote), autor:c.testimonio.autor||'', cargo:L(c.testimonio.cargo) } : null;
  return {
    eyebrow, titulo, origen, volver, diag,
    promesa: L(c.promesa) || despuesFb || '',
    antes: L(c.antes) || antesFb || '',
    despues: L(c.despues) || despuesFb || '',
    cta: L(c.cta) || tx('landing.cta','Quiero más información'),
    beneficios, pasos, entregables, metricas, faqs, oferta, testimonio,
    casos: proofOn ? (casos||[]) : [],
  };
}

// ————————————————————— Caso como landing narrativa (story landing) —————————————————————
// Espíritu: contar el caso como una historia visual (texto + imagen/visual + motion),
// con la identidad de ExperientIA y respetando la confidencialidad del cliente.

// Visual de marca animado para capítulos sin imagen: bars (resultado),
// flow (intervención/proceso) o radar (reto/diagnóstico). Se anima al entrar en viewport.
const CaseViz = {
  components:{ Icon },
  props:{ variant:{ type:String, default:'bars' } },
  template:`<div class="caso-viz glass glass-lit" :class="'caso-viz--'+variant" ref="root" aria-hidden="true">
    <template v-if="variant==='bars'">
      <div class="vz-bars"><span v-for="(h,i) in [30,46,38,58,52,74,68,92]" :key="i" class="vz-bar" :style="{'--h':h+'%','--i':i}"></span></div>
      <span class="vz-trend"></span>
    </template>
    <template v-else-if="variant==='flow'">
      <div class="vz-flow">
        <span v-for="(ic,i) in ['analitica','ia','gear','growth']" :key="i" class="vz-node" :style="{'--i':i}"><Icon :name="ic" :size="20"/></span>
        <span class="vz-link"></span><span class="vz-pulse"></span></div>
    </template>
    <template v-else>
      <div class="vz-radar">
        <span class="vz-ring" v-for="i in 3" :key="i" :style="{'--i':i}"></span>
        <span class="vz-core"></span>
        <span v-for="(b,i) in [[22,30],[68,24],[58,70]]" :key="'b'+i" class="vz-blip" :style="{left:b[0]+'%',top:b[1]+'%','--i':i}"></span></div>
    </template>
  </div>`,
  mounted(){ this._stop = inView(this.$refs.root, ()=> this.$refs.root.classList.add('on'), { amount:.35 }); },
  unmounted(){ this._stop && this._stop(); },
};

// La landing de cada caso (historia por capítulos, línea de tiempo, testimonio,
// confidencialidad) vive 100% en la BD (columna `landing`), editable desde el panel
// en ES/EN/PT. Si un caso aún no tiene landing, se arma un mínimo con su contexto e
// intervención. Sin copy quemado en el código.

export const CasoDetalle = {
  components:{ Icon, CountUp, ScrollProgress, LeadModal, NoEncontrado, CaseViz },
  template:`<div v-if="c" class="caso-landing">
    <ScrollProgress/>

    <section class="caso-hero"><div class="bg-atmos">
      <div class="halo halo-cyan anim-pulse" style="width:640px;height:640px;top:-280px;right:-160px"></div>
      <div class="halo halo-violet anim-pulse" style="width:520px;height:520px;bottom:-220px;left:-200px;animation-delay:2.4s"></div>
      <span class="caso-orb" style="top:18%;left:6%;--s:10px;--d:0s"></span>
      <span class="caso-orb" style="top:64%;left:12%;--s:6px;--d:1.6s"></span>
      <span class="caso-orb" style="top:30%;right:8%;--s:8px;--d:.8s"></span></div>
      <div class="container caso-hero__in">
        <div class="caso-hero__copy">
          <div class="caso-chips" v-reveal>
            <span class="chip">{{ tr(c.sector) }}</span>
            <span v-if="ld.confidencial" class="chip chip--conf"><Icon name="shield" :size="12"/> {{ tx('caso.conf','Caso real · cliente en confidencialidad') }}</span></div>
          <h1 class="display" v-reveal :style="{'--d':'.08s'}">{{ tr(c.titulo) }}</h1>
          <p class="lead" v-reveal :style="{'--d':'.16s'}">{{ sub }}</p>
          <div class="land-cta-row" v-reveal :style="{'--d':'.24s'}">
            <button class="btn btn-grad" @click="modal=true">{{ tx('caso.cta','Quiero resultados así') }}</button>
            <router-link :to="pageUrl('diagnostico')" class="btn btn-ghost">{{ tx('landing.oferta_cta','Quiero mi diagnóstico gratis') }}</router-link></div></div>
        <div class="glass glass-lit caso-hero__star" v-reveal :style="{'--d':'.2s'}" v-if="star">
          <span class="caso-star-val grad-text"><CountUp :value="star.valor"/></span>
          <span class="caso-star-lbl">{{ tr(star.label) }}</span>
          <div class="caso-star-mini"><div v-for="(r,i) in resto" :key="i"><b>{{ r.valor }}</b><span>{{ tr(r.label) }}</span></div></div></div></div>
      <div class="scroll-cue" aria-hidden="true"><span></span></div></section>

    <section class="section caso-historia-sec"><div class="container">
      <div class="section-head"><p class="eyebrow" v-reveal>{{ tx('caso.historia_e','La historia') }}</p>
        <h2 class="h2" v-reveal :style="{'--d':'.06s'}">{{ tx('caso.historia_t','Del problema al resultado, jugada a jugada') }}</h2></div>
      <article v-for="(h,i) in historia" :key="i" class="caso-cap" :class="{'caso-cap--rev': i%2===1}">
        <div class="caso-cap__txt" v-reveal>
          <div class="caso-cap__head"><span class="caso-cap__num grad-text">{{ ('0'+(i+1)).slice(-2) }}</span><span class="caso-cap__tag">{{ h.tag }}</span></div>
          <h3 class="h2">{{ h.t }}</h3><p class="lead">{{ h.x }}</p></div>
        <div class="caso-cap__media" v-reveal :style="{'--d':'.12s'}">
          <img v-if="h.img" :src="h.img" alt="" loading="lazy" class="glass caso-cap__img">
          <CaseViz v-else :variant="h.viz||'bars'"/></div></article></div></section>

    <section class="section caso-cifras-sec"><div class="bg-atmos"><div class="halo halo-cyan" style="width:460px;height:460px;top:-160px;right:-220px;opacity:.24"></div></div>
      <div class="container"><div class="glass glass-lit card land-metrics caso-metrics" v-reveal>
        <div v-for="(r,i) in c.resultados" :key="i" class="metric"><span class="metric-val grad-text"><CountUp :value="r.valor"/></span><span class="metric-lbl">{{ tr(r.label) }}</span></div></div></div></section>

    <section class="section caso-tl-sec" v-if="timeline.length"><div class="container">
      <div class="section-head"><p class="eyebrow" v-reveal>{{ tx('caso.tl_e','El camino') }}</p>
        <h2 class="h2" v-reveal :style="{'--d':'.06s'}">{{ tx('caso.tl_t','Así se construyó, semana a semana') }}</h2></div>
      <ol class="caso-tl">
        <li v-for="(s,i) in timeline" :key="i" class="caso-tl__item" v-reveal :style="{'--d':i*.1+'s'}">
          <span class="caso-tl__dot"></span>
          <span class="caso-tl__fase">{{ s.fase }}</span>
          <h3 class="h3">{{ s.t }}</h3><p>{{ s.x }}</p></li></ol></div></section>

    <section class="section" v-if="ld.beneficios && ld.beneficios.length"><div class="container">
      <div class="section-head"><h2 class="h2" v-reveal>{{ tx('landing.benes_t','Lo que cambia para ti') }}</h2></div>
      <div class="grid grid-3 land-benes">
        <article v-for="(b,i) in ld.beneficios" :key="i" class="glass card bene-card" v-reveal :style="{'--d':i*.09+'s'}">
          <span class="icon-chip"><Icon :name="b.icon||'check'"/></span><h3 class="h3">{{ b.t }}</h3><p>{{ b.x }}</p></article></div></div></section>

    <section class="section" v-if="ld.testimonio"><div class="container" style="max-width:820px">
      <figure class="glass glass-lit card land-quote" v-reveal><Icon name="bulb" :size="22"/>
        <blockquote>“{{ ld.testimonio.quote }}”</blockquote>
        <figcaption v-if="ld.testimonio.autor||ld.testimonio.cargo"><b>{{ ld.testimonio.autor }}</b><span v-if="ld.testimonio.cargo"><template v-if="ld.testimonio.autor"> · </template>{{ ld.testimonio.cargo }}</span></figcaption></figure></div></section>

    <section class="section" v-if="ld.faqs && ld.faqs.length"><div class="container land-faqs">
      <div class="section-head"><h2 class="h2" v-reveal>{{ tx('landing.faq_t','Antes de que preguntes') }}</h2></div>
      <div class="faq-list"><details v-for="(f,i) in ld.faqs" :key="i" class="glass faq-item" v-reveal :open="i===0"><summary>{{ f.q }}</summary><p>{{ f.a }}</p></details></div></div></section>

    <section class="section"><div class="container"><div class="glass glass-lit card land-final" v-reveal>
      <h2 class="h2">{{ tx('caso.final_t','Los resultados no son suerte: son método') }}</h2>
      <p class="lead">{{ tx('caso.final_s','Apliquémoslo a tu empresa. Empieza con un diagnóstico ejecutivo gratuito.') }}</p>
      <div class="land-cta-row"><button class="btn btn-grad" @click="modal=true">{{ tx('caso.cta','Quiero resultados así') }}</button>
        <router-link :to="pageUrl('casos')" class="btn btn-ghost">{{ tx('caso.ver_mas','Ver más casos') }}</router-link></div></div></div></section>

    <div class="land-sticky"><span>{{ tr(c.titulo) }}</span><button class="btn btn-grad btn-sm" @click="modal=true">{{ tx('caso.cta_corto','Quiero esto') }}</button></div>
    <LeadModal v-if="modal" :titulo="tr(c.titulo)" :origen="'caso:'+slug" @close="modal=false"/>
  </div><NoEncontrado v-else-if="cargado" volver="casos"/>`,
  data(){ return { c:null, cargado:false, modal:false, slug:'' }; },
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl,
    // Fuente única: el JSON `landing` de la BD (editable en el panel). Sin copy quemado.
    base(){ return (this.c&&this.c.landing&&typeof this.c.landing==='object')?this.c.landing:{}; },
    ld(){ const c=this.base;
      return { promesa:L(c.promesa), confidencial:!!c.confidencial,
        beneficios:(c.beneficios||[]).map(b=>({ icon:b.icon, t:L(b.t), x:L(b.x) })).filter(b=>b.t),
        faqs:(c.faqs||[]).map(f=>({ q:L(f.q), a:L(f.a) })).filter(f=>f.q),
        testimonio:(c.testimonio&&c.testimonio.on&&L(c.testimonio.quote))?{ quote:L(c.testimonio.quote), autor:c.testimonio.autor||'', cargo:L(c.testimonio.cargo) }:null }; },
    // Capítulos de la historia; si no hay curados ni editados, se arman del contexto/intervención del CMS.
    historia(){ const hs=(this.base.historia||[]).map(h=>({ tag:L(h.tag), t:L(h.t), x:L(h.x), viz:h.viz||'bars', img:h.img||'' })).filter(h=>h.x);
      if(hs.length) return hs;
      return [
        { tag:tx('caso.reto','El reto'), t:tx('caso.contexto','El contexto'), x:tr(this.c.contexto), viz:'radar', img:'' },
        { tag:tx('caso.jugada','La jugada'), t:tx('caso.intervencion','Qué hicimos'), x:tr(this.c.intervencion), viz:'flow', img:'' },
      ].filter(h=>h.x); },
    timeline(){ return (this.base.timeline||[]).map(s=>({ fase:L(s.fase), t:L(s.t), x:L(s.x) })).filter(s=>s.t); },
    star(){ return (this.c.resultados||[])[0]||null; },
    resto(){ return (this.c.resultados||[]).slice(1,3); },
    sub(){ if(!this.c) return ''; return this.ld.promesa || (tr(this.c.contexto).split('.')[0]+'.'); } },
  methods:{ tx },
  async mounted(){ this.slug=this.$route.params.slug; const items=await fc('casos');
    this.c=items.find(x=>slugify(tr(x.titulo,'es'))===this.slug)||null; this.cargado=true;
    if(this.c) setMeta(tr(this.c.titulo)+' · ExperientIA', tr(this.c.contexto).slice(0,150)); },
};
