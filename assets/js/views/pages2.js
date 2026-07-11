// Vistas: Tablero, Productos, Casos, Nosotros, FAQ
import { t, tr, pageUrl, api, setMeta } from '../lib/core.js';
import { Icon, DashMock, BrandSymbol } from '../lib/ui.js';
import { PageHero, SectionCTA } from '../lib/layout.js';
import { slugify } from './pages5.js';
async function fc(s){ const r=await api.get('/content/'+s); return r.ok?r.data:[]; }
const tx = (key,fb)=>{ const v=t(key); return (v && v!==key)?v:fb; };

export const Tablero = {
  components: { Icon, DashMock, PageHero, SectionCTA },
  template: `<div>
    <PageHero :eyebrow="t('tablero.eyebrow')" :titulo="t('tablero.titulo')" :sub="t('tablero.sub')">
      <div class="hero__actions" v-reveal :style="{'--d':'.24s'}"><router-link :to="pageUrl('agenda')" class="btn btn-primary">{{ t('tablero.cta') }}</router-link></div></PageHero>
    <section class="section" style="padding-block:clamp(1rem,3vw,2rem)"><div class="container"><div class="glass" v-reveal style="position:relative;padding:clamp(1.5rem,4vw,3.5rem);overflow:hidden">
      <div class="bg-atmos"><div class="halo halo-cyan anim-pulse" style="width:560px;height:560px;top:-180px;left:8%"></div><div class="halo halo-violet anim-pulse" style="width:520px;height:520px;bottom:-220px;right:4%;animation-delay:2.2s"></div></div>
      <div style="position:relative;z-index:1;max-width:760px;margin-inline:auto"><DashMock/></div></div></div></section>
    <section class="section"><div class="container"><div class="grid grid-3">
      <article v-for="(p,i) in pilares" :key="i" class="glass glass-lit card" v-reveal :style="{'--d':i*.1+'s',display:'grid',gap:'.9rem',alignContent:'start'}">
        <span class="icon-chip"><Icon :name="p.i"/></span><h2 class="h3">{{ tr(p.t) }}</h2><p>{{ tr(p.x) }}</p></article></div></div></section>
    <section class="section"><div class="bg-atmos"><div class="halo halo-violet" style="width:460px;height:460px;top:0;left:-240px;opacity:.28"></div></div>
      <div class="container"><div class="section-head"><p class="eyebrow" v-reveal>{{ t('tablero.features_eyebrow') }}</p><h2 class="h2" v-reveal :style="{'--d':'.08s'}">{{ t('tablero.features_titulo') }}</h2></div>
      <div class="grid grid-4"><article v-for="(f,i) in features" :key="i" class="glass card" v-reveal :style="{'--d':i*.08+'s',display:'grid',gap:'.8rem',alignContent:'start'}">
        <span class="icon-chip"><Icon :name="f.i"/></span><h3 class="h3" style="font-size:1.05rem">{{ tr(f.t) }}</h3><p class="small">{{ tr(f.x) }}</p></article></div></div></section>
    <SectionCTA :titulo="t('tablero.cta_titulo')" :sub="t('tablero.cta_sub')" :primary="pageUrl('agenda')" :primaryLabel="t('tablero.cta')" :secondary="pageUrl('soluciones')" :secondaryLabel="t('common.ver_soluciones')"/>
  </div>`,
  data(){ return { pilares:[
    {i:'eye',t:{es:'Visibilidad total',en:'Total visibility',pt:'Visibilidade total'},x:{es:'Ingresos, eficiencia, clientes y adopción de IA en una sola vista. Sin esperar al cierre de mes.',en:'Revenue, efficiency and AI adoption in one view.',pt:'Receita, eficiência e adoção de IA em uma visão.'}},
    {i:'alert',t:{es:'Alertas con criterio',en:'Alerts with judgment',pt:'Alertas com critério'},x:{es:'El tablero no grita datos: señala qué cambió, por qué importa y qué decisión requiere.',en:'It signals what changed, why it matters and what to decide.',pt:'Sinaliza o que mudou, por que importa e o que decidir.'}},
    {i:'shield',t:{es:'Gobierno del crecimiento',en:'Growth governance',pt:'Governança do crescimento'},x:{es:'Cada indicador tiene dueño, meta y ritual de revisión. La estrategia se ejecuta, no se archiva.',en:'Every indicator has an owner, target and review ritual.',pt:'Cada indicador tem dono, meta e ritual de revisão.'}}],
    features:[
    {i:'growth',t:{es:'KPIs en vivo',en:'Live KPIs',pt:'KPIs ao vivo'},x:{es:'Ventas, margen, cash y eficiencia conectados a la fuente.',en:'Sales, margin, cash and efficiency.',pt:'Vendas, margem, caixa e eficiência.'}},
    {i:'ia',t:{es:'Señales con IA',en:'AI signals',pt:'Sinais com IA'},x:{es:'Anomalías y tendencias detectadas antes de ser problemas.',en:'Anomalies detected before they are problems.',pt:'Anomalias detectadas antes de virarem problemas.'}},
    {i:'gear',t:{es:'Flujos automatizados',en:'Automated flows',pt:'Fluxos automatizados'},x:{es:'Reportes y rituales ejecutivos que se preparan solos.',en:'Reports and rituals that prepare themselves.',pt:'Relatórios e rituais que se preparam sozinhos.'}},
    {i:'people',t:{es:'Adopción y cultura',en:'Adoption & culture',pt:'Adoção e cultura'},x:{es:'Uso real por equipos: la transformación también se mide.',en:'Real usage by teams.',pt:'Uso real pelas equipes.'}}]}; },
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl },
  mounted(){ setMeta(t('tablero.meta_title')+' · ExperientIA', t('tablero.meta_desc')); },
};

export const Productos = {
  components: { Icon, PageHero, SectionCTA },
  template: `<div>
    <PageHero :eyebrow="t('productos.eyebrow')" :titulo="t('productos.titulo')" :sub="t('productos.sub')"/>
    <section class="section"><div class="container" style="display:grid;gap:1.5rem" :style="dosCol">
      <router-link v-for="(p,i) in items" :key="p.id" :to="dest(p)" class="glass card sol-card" :class="{'glass-lit':p.destacado}" v-reveal :style="{'--d':i*.08+'s',display:'grid',gap:'1rem',alignContent:'start',padding:'2rem'}">
        <div class="chip-row"><span class="icon-chip"><Icon :name="p.icon"/></span><span class="chip chip--cyan">{{ tr(p.rol) }}</span></div>
        <h2 class="h3">{{ tr(p.nombre) }}</h2><p>{{ tr(p.texto) }}</p>
        <span class="link-arrow">{{ tx('common.conocer_mas','Conocer más') }} <Icon name="arrow" :size="16"/></span></router-link></div></section>
    <SectionCTA :titulo="t('home.cta_titulo')" :sub="t('home.cta_sub')" :primary="pageUrl('contacto')" :primaryLabel="t('home.cta_cta1')" :secondary="pageUrl('casos')" :secondaryLabel="t('nav.casos')"/>
  </div>`,
  data(){ return { items:[], dosCol: window.innerWidth>=860?{gridTemplateColumns:'repeat(2,1fr)'}:{} }; },
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl },
  methods:{ tx, dest(p){ return p.destacado ? pageUrl('tablero') : pageUrl('productos',{slug:slugify(tr(p.nombre,'es'))}); } },
  async mounted(){ setMeta(t('productos.meta_title')+' · ExperientIA', t('productos.meta_desc')); this.items=await fc('productos'); },
};

export const Casos = {
  components: { PageHero, SectionCTA },
  template: `<div>
    <PageHero :eyebrow="t('casos.eyebrow')" :titulo="t('casos.titulo')" :sub="t('casos.sub')"/>
    <section class="section"><div class="container" style="display:grid;gap:1.75rem">
      <article v-for="(c,i) in items" :key="c.id" class="glass glass-lit card" v-reveal :style="{'--d':i*.06+'s',display:'grid',gap:'1.75rem',padding:'clamp(1.75rem,4vw,2.75rem)'}">
        <header><p class="sector-label" style="color:var(--cyan);margin-bottom:.6rem">{{ tr(c.sector) }}</p><h2 class="h2" style="font-size:clamp(1.4rem,2.6vw,1.9rem)">{{ tr(c.titulo) }}</h2></header>
        <div style="display:grid;gap:1.75rem" :style="tresCol">
          <div><h3 class="lbl">{{ t('common.contexto') }}</h3><p style="font-size:.95rem">{{ tr(c.contexto) }}</p></div>
          <div><h3 class="lbl">{{ t('common.intervencion') }}</h3><p style="font-size:.95rem">{{ tr(c.intervencion) }}</p></div>
          <div :style="resBorder"><h3 class="lbl" style="color:var(--cyan)">{{ t('common.resultados') }}</h3>
            <ul style="list-style:none;margin:0;padding:0;display:grid;gap:.9rem">
              <li v-for="(r,j) in c.resultados" :key="j" style="display:grid;gap:.15rem"><span class="grad-text" style="font-size:1.6rem;font-weight:700;line-height:1">{{ r.valor }}</span><span style="font-size:.85rem;color:var(--text-secondary)">{{ tr(r.label) }}</span></li></ul></div></div></article>
      <p class="small" style="text-align:center" v-reveal>{{ t('casos.nota') }}</p></div></section>
    <SectionCTA :titulo="t('home.cta_titulo')" :sub="t('home.cta_sub')" :primary="pageUrl('contacto')" :primaryLabel="t('home.cta_cta1')" :secondary="pageUrl('soluciones')" :secondaryLabel="t('common.ver_soluciones')"/>
  </div>`,
  data(){ const w=window.innerWidth>=900; return { items:[], tresCol:w?{gridTemplateColumns:'1fr 1fr 1fr'}:{}, resBorder:w?{borderLeft:'1px solid var(--line-soft)',paddingLeft:'1.75rem'}:{} }; },
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl },
  async mounted(){ setMeta(t('casos.meta_title')+' · ExperientIA', t('casos.meta_desc')); this.items=await fc('casos'); },
};

export const Nosotros = {
  components: { Icon, BrandSymbol, SectionCTA },
  template: `<div>
    <section class="page-hero"><div class="bg-atmos"><div class="halo halo-cyan" style="width:520px;height:520px;top:-260px;right:-140px"></div></div>
      <div class="container page-hero__in"><p class="eyebrow" v-reveal>{{ t('nosotros.eyebrow') }}</p><h1 class="display" v-reveal :style="{'--d':'.08s'}">{{ t('nosotros.titulo') }}</h1><p class="lead" v-reveal :style="{'--d':'.16s'}">{{ t('nosotros.sub') }}</p></div></section>
    <section class="section"><div class="container"><div class="section-head"><p class="eyebrow" v-reveal>{{ t('nosotros.esencia_eyebrow') }}</p><h2 class="h2" v-reveal :style="{'--d':'.08s'}">{{ t('nosotros.esencia_titulo') }}</h2></div>
      <div class="grid grid-4"><article v-for="(v,i) in valores" :key="i" class="glass card" v-reveal :style="{'--d':i*.08+'s',display:'grid',gap:'.8rem',alignContent:'start'}">
        <span class="icon-chip"><Icon :name="v.icon"/></span><h3 class="h3" style="font-size:1.05rem">{{ v.titulo }}</h3><p class="small">{{ v.texto }}</p></article></div></div></section>
    <section class="section"><div class="bg-atmos"><div class="halo halo-violet" style="width:460px;height:460px;top:-140px;left:-240px;opacity:.3"></div></div>
      <div class="container"><div class="section-head"><p class="eyebrow" v-reveal>{{ t('nosotros.personalidad_eyebrow') }}</p><h2 class="h2" v-reveal :style="{'--d':'.08s'}">{{ t('nosotros.personalidad_titulo') }}</h2></div>
      <div style="display:grid;gap:2.5rem;align-items:center" :style="persCol">
        <div class="glass" v-reveal style="display:grid;place-items:center;padding:2.5rem"><BrandSymbol :size="150" class="anim-float"/></div>
        <div style="display:grid;gap:1.5rem" :style="rasgosCol"><div v-for="(r,i) in rasgos" :key="i" v-reveal :style="{'--d':i*.08+'s',display:'grid',gap:'.4rem',paddingLeft:'1.1rem',borderLeft:'2px solid rgba(24,214,241,.4)'}">
          <h3 class="h3 grad-text">{{ r.titulo }}</h3><p>{{ r.texto }}</p></div></div></div></div></section>
    <section class="section"><div class="container"><div class="glass glass-lit card" v-reveal style="position:relative;overflow:hidden;padding:clamp(2.5rem,6vw,4rem)">
      <div class="bg-atmos"><div class="halo halo-cyan" style="width:420px;height:420px;top:-240px;right:-100px;opacity:.35"></div></div>
      <div style="position:relative;z-index:1;display:grid;gap:1.2rem;justify-items:start;max-width:44rem">
        <p class="eyebrow">{{ t('nosotros.eco_eyebrow') }}</p><h2 class="h2">{{ t('nosotros.eco_titulo') }}</h2><p class="lead">{{ t('nosotros.eco_texto') }}</p>
        <a href="https://tonnydager.com" target="_blank" rel="noopener" class="link-arrow">{{ t('nosotros.eco_cta') }} <Icon name="arrow" :size="16"/></a></div></div></div></section>
    <SectionCTA :titulo="t('nosotros.cta_titulo')" :sub="t('nosotros.cta_sub')" :primary="pageUrl('agenda')" :primaryLabel="t('nosotros.cta_cta')"/>
  </div>`,
  data(){ const w=window.innerWidth; return { persCol:w>=860?{gridTemplateColumns:'.75fr 1.25fr'}:{}, rasgosCol:w>=560?{gridTemplateColumns:'repeat(2,1fr)'}:{} }; },
  computed:{ t:()=>t, pageUrl:()=>pageUrl, valores(){ return t('nosotros.valores')||[]; }, rasgos(){ return t('nosotros.rasgos')||[]; } },
  mounted(){ setMeta(t('nosotros.meta_title')+' · ExperientIA', t('nosotros.meta_desc')); },
};

export const FAQ = {
  components: { SectionCTA },
  template: `<div>
    <PageHeroInline :eyebrow="t('faq.eyebrow')" :titulo="t('faq.titulo')" :sub="t('faq.sub')"/>
    <section class="section"><div class="container"><div class="faq-list">
      <details v-for="(f,i) in items" :key="f.id" class="glass faq-item" v-reveal :open="i===0"><summary>{{ tr(f.pregunta) }}</summary><p>{{ tr(f.respuesta) }}</p></details></div></div></section>
    <SectionCTA :titulo="t('faq.cta_titulo')" :sub="t('faq.cta_sub')" :primary="pageUrl('agenda')" :primaryLabel="t('common.agendar_sesion')" :secondary="pageUrl('contacto')" :secondaryLabel="t('nav.contacto')"/>
  </div>`,
  components: { SectionCTA, PageHeroInline: PageHero },
  data(){ return { items:[] }; },
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl },
  async mounted(){
    setMeta(t('faq.meta_title')+' · ExperientIA', t('faq.meta_desc'));
    this.items = await fc('faqs');
    // JSON-LD FAQPage dinámico
    const ld = { '@context':'https://schema.org','@type':'FAQPage','mainEntity': this.items.map(f=>({'@type':'Question','name':tr(f.pregunta),'acceptedAnswer':{'@type':'Answer','text':tr(f.respuesta)}})) };
    const s = document.createElement('script'); s.type='application/ld+json'; s.textContent=JSON.stringify(ld); s.id='faq-ld'; document.getElementById('faq-ld')?.remove(); document.head.appendChild(s);
  },
};
