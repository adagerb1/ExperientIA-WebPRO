// Vistas del sitio: Home, Soluciones, Tablero, Productos, Casos
import { t, tr, trLines, pageUrl, api, store, setMeta } from '../lib/core.js';
import { Icon, DashMock } from '../lib/ui.js';
import { PageHero, SectionCTA } from '../lib/layout.js';
import { slugify } from './pages5.js';
const tx = (k, fb) => { const v = t(k); return (v && v !== k) ? v : fb; };

const META = {
  Home: () => setMeta(t('meta.title_base'), t('meta.description')),
};
async function fetchContent(seccion) { const r = await api.get('/content/' + seccion); return r.ok ? r.data : []; }

export const Home = {
  components: { Icon, DashMock, SectionCTA },
  template: `<div>
    <section class="hero"><div class="bg-atmos">
      <div class="halo halo-cyan anim-pulse" style="width:640px;height:640px;top:-300px;right:-180px"></div>
      <div class="halo halo-violet anim-pulse" style="width:520px;height:520px;bottom:-200px;left:-220px;animation-delay:2.6s"></div></div>
      <div class="container hero__inner">
        <div class="hero__copy">
          <p class="eyebrow" v-reveal>{{ t('home.hero_eyebrow') }}</p>
          <h1 class="display" v-reveal :style="{'--d':'.08s'}">{{ t('home.hero_titulo1') }}<br><span class="grad-text">{{ t('home.hero_titulo2') }}</span></h1>
          <p class="lead" v-reveal :style="{'--d':'.16s'}">{{ t('home.hero_sub') }}</p>
          <div class="hero__actions" v-reveal :style="{'--d':'.24s'}">
            <router-link :to="pageUrl('contacto')" class="btn btn-primary">{{ t('home.hero_cta1') }}</router-link>
            <router-link :to="pageUrl('diagnostico')" class="btn btn-ghost">{{ t('common.hacer_diagnostico') }}</router-link></div>
          <p class="small" v-reveal :style="{'--d':'.32s'}">{{ t('home.hero_nota') }}</p></div>
        <div class="hero__visual" v-reveal :style="{'--d':'.2s'}"><DashMock cls="anim-float"/></div></div></section>

    <section class="section"><div class="container">
      <div class="section-head"><p class="eyebrow" v-reveal>{{ t('home.proof_eyebrow') }}</p><h2 class="h2" v-reveal :style="{'--d':'.08s'}">{{ t('home.proof_titulo') }}</h2></div>
      <div class="grid grid-4">
        <article v-for="(m,i) in metrics" :key="i" class="glass card" v-reveal :style="{'--d':i*.08+'s',display:'grid',gap:'.4rem',alignContent:'start'}">
          <p class="proof__v grad-text">{{ m.v }}</p><h3 class="h3" style="font-size:1.02rem">{{ tr(m.l) }}</h3><p class="small">{{ tr(m.d) }}</p></article></div>
      <p class="small" style="text-align:center;margin-top:2rem" v-reveal>{{ t('home.proof_sectores') }}</p></div></section>

    <section class="section"><div class="bg-atmos"><div class="halo halo-violet" style="width:480px;height:480px;top:10%;right:-240px;opacity:.3"></div></div>
      <div class="container"><div class="section-head"><p class="eyebrow" v-reveal>{{ t('home.problema_eyebrow') }}</p><h2 class="h2" v-reveal :style="{'--d':'.08s'}">{{ t('home.problema_titulo') }}</h2><p class="lead" v-reveal :style="{'--d':'.16s'}">{{ t('home.problema_sub') }}</p></div>
      <div class="grid grid-3">
        <article v-for="(b,i) in bloques" :key="i" class="glass card" v-reveal :style="{'--d':i*.1+'s',display:'grid',gap:'1rem',alignContent:'start'}">
          <span class="icon-chip"><Icon :name="b.i"/></span><h3 class="h3">{{ tr(b.t) }}</h3><p>{{ tr(b.x) }}</p></article></div></div></section>

    <section class="section"><div class="container" style="display:grid;gap:3rem;align-items:center" :style="lg">
      <div style="display:grid;gap:1.2rem;justify-items:start">
        <p class="eyebrow" v-reveal>{{ t('home.tablero_eyebrow') }}</p><h2 class="h2" v-reveal :style="{'--d':'.08s'}">{{ t('home.tablero_titulo') }}</h2>
        <p class="lead" v-reveal :style="{'--d':'.16s'}">{{ t('home.tablero_sub') }}</p>
        <router-link :to="pageUrl('tablero')" class="link-arrow" v-reveal :style="{'--d':'.3s'}">{{ t('home.tablero_cta') }} <Icon name="arrow" :size="16"/></router-link></div>
      <div v-reveal :style="{'--d':'.15s'}"><DashMock/></div></div></section>

    <section class="section" style="padding-top:0" v-if="otrosProductos.length"><div class="container">
      <p class="home-prod-lead" v-reveal>{{ tx('home.productos_lead','Y para acompañar tu crecimiento:') }}</p>
      <div class="home-prod-strip">
        <router-link v-for="(p,i) in otrosProductos" :key="p.id" :to="pageUrl('productos',{slug:slugify(tr(p.nombre,'es'))})" class="glass card home-prod-card" v-reveal :style="{'--d':i*.08+'s'}">
          <span class="icon-chip"><Icon :name="p.icon"/></span>
          <div class="home-prod-txt"><h3 class="h3" style="font-size:1.02rem">{{ tr(p.nombre) }}</h3><p class="small">{{ tr(p.rol) }}</p></div>
          <Icon name="arrow" :size="16" class="home-prod-arrow"/></router-link></div></div></section>

    <section class="section"><div class="container">
      <div class="section-head"><p class="eyebrow" v-reveal>{{ t('home.soluciones_eyebrow') }}</p><h2 class="h2" v-reveal :style="{'--d':'.08s'}">{{ t('home.soluciones_titulo') }}</h2><p class="lead" v-reveal :style="{'--d':'.16s'}">{{ t('home.soluciones_sub') }}</p></div>
      <div class="grid grid-2">
        <router-link v-for="(s,i) in soluciones" :key="s.id" :to="pageUrl('soluciones',{slug:s.skey})" class="glass glass-lit card sol-card" v-reveal :style="{'--d':i*.08+'s',display:'grid',gap:'.9rem',alignContent:'start'}">
          <div class="chip-row"><span class="icon-chip"><Icon :name="s.icon"/></span><span class="chip">{{ tr(s.pilar) }}</span></div>
          <h3 class="h3">{{ tr(s.titulo) }}</h3><p>{{ tr(s.cambia) }}</p>
          <span class="link-arrow">{{ tx('landing.ver_sol','Ver solución') }} <Icon name="arrow" :size="16"/></span></router-link></div>
      <div class="section-foot" v-reveal><router-link :to="pageUrl('soluciones')" class="btn btn-ghost">{{ t('home.soluciones_cta') }}</router-link></div></div></section>

    <section class="section"><div class="bg-atmos"><div class="halo halo-cyan" style="width:460px;height:460px;bottom:-200px;left:-220px;opacity:.28"></div></div>
      <div class="container"><div class="section-head"><p class="eyebrow" v-reveal>{{ t('home.casos_eyebrow') }}</p><h2 class="h2" v-reveal :style="{'--d':'.08s'}">{{ t('home.casos_titulo') }}</h2><p class="lead" v-reveal :style="{'--d':'.16s'}">{{ t('home.casos_sub') }}</p></div>
      <div class="grid grid-3">
        <router-link v-for="(c,i) in casos" :key="c.id" :to="pageUrl('casos',{slug:slugify(tr(c.titulo,'es'))})" class="glass card caso-list-card" v-reveal :style="{'--d':i*.1+'s',display:'grid',gap:'.6rem',alignContent:'start'}">
          <p class="sector-label">{{ tr(c.sector) }}</p><h3 class="h3">{{ tr(c.titulo) }}</h3>
          <p class="metric-big grad-text" v-if="c.resultados&&c.resultados[0]">{{ c.resultados[0].valor }}</p>
          <p class="small" v-if="c.resultados&&c.resultados[0]">{{ tr(c.resultados[0].label) }}</p>
          <span class="link-arrow">{{ tx('caso.ver_completo','Ver el caso completo') }} <Icon name="arrow" :size="15"/></span></router-link></div>
      <div class="section-foot" v-reveal><router-link :to="pageUrl('casos')" class="link-arrow">{{ t('home.casos_cta') }} <Icon name="arrow" :size="16"/></router-link></div></div></section>

    <SectionCTA :titulo="t('home.cta_titulo')" :sub="t('home.cta_sub')" :primary="pageUrl('contacto')" :primaryLabel="t('home.cta_cta1')" :secondary="pageUrl('diagnostico')" :secondaryLabel="t('home.cta_cta2')"/>
  </div>`,
  data() { return {
    soluciones: [], casos: [], productos: [],
    lg: window.innerWidth >= 1020 ? { gridTemplateColumns: '.95fr 1.05fr' } : {},
    metrics: [
      { v:'+42%', l:{es:'Crecimiento sostenible',en:'Sustainable growth',pt:'Crescimento sustentável'}, d:{es:'promedio en programas de 12 meses',en:'average across 12-month programs',pt:'média em programas de 12 meses'} },
      { v:'78%', l:{es:'Procesos optimizados',en:'Processes optimized',pt:'Processos otimizados'}, d:{es:'en operaciones con IA',en:'in operations with AI',pt:'em operações com IA'} },
      { v:'3.2x', l:{es:'Retorno sobre automatización',en:'Return on automation',pt:'Retorno sobre automação'}, d:{es:'mediana a los 18 meses',en:'median at 18 months',pt:'mediana em 18 meses'} },
      { v:'48 h', l:{es:'De datos a decisión',en:'From data to decision',pt:'De dados a decisão'}, d:{es:'ciclos de reporte ejecutivo',en:'executive reporting cycles',pt:'ciclos de reporte executivo'} },
    ],
    bloques: [
      { i:'analitica', t:{es:'Decisiones sin visibilidad',en:'Decisions without visibility',pt:'Decisões sem visibilidade'}, x:{es:'Los reportes llegan tarde y fragmentados. El comité decide con intuición donde debería decidir con datos.',en:'Reports arrive late and fragmented. The committee decides on intuition where it should decide on data.',pt:'Os relatórios chegam tarde e fragmentados. O comitê decide por intuição onde deveria decidir com dados.'} },
      { i:'gear', t:{es:'Operación que consume talento',en:'Operations that consume talent',pt:'Operação que consome talento'}, x:{es:'Procesos manuales absorben horas de los equipos más valiosos. La capacidad estratégica se gasta en tareas operativas.',en:'Manual processes absorb hours from your most valuable teams.',pt:'Processos manuais absorvem horas das equipes mais valiosas.'} },
      { i:'cube', t:{es:'Herramientas sin estrategia',en:'Tools without strategy',pt:'Ferramentas sem estratégia'}, x:{es:'Licencias y pilotos de IA que se acumulan sin dueño ni métrica. Inversión que no aparece en el estado de resultados.',en:'Licenses and AI pilots pile up with no owner and no metric.',pt:'Licenças e pilotos de IA que se acumulam sem dono nem métrica.'} },
    ],
  }; },
  computed: { t: () => t, tr: () => tr, tx: () => tx, pageUrl: () => pageUrl, slugify: () => slugify,
    otrosProductos() { return this.productos.filter(p => !Number(p.destacado)); } },
  async mounted() { setMeta(t('meta.title_base'), t('meta.description'));
    this.soluciones = await fetchContent('soluciones'); this.casos = await fetchContent('casos'); this.productos = await fetchContent('productos'); },
};

export const Soluciones = {
  components: { Icon, PageHero, SectionCTA },
  template: `<div>
    <PageHero :eyebrow="t('soluciones.eyebrow')" :titulo="t('soluciones.titulo')" :sub="t('soluciones.sub')"/>
    <section class="section"><div class="container" style="display:grid;gap:1.5rem" :style="dosCol">
      <router-link v-for="(s,i) in items" :key="s.id" :to="pageUrl('soluciones',{slug:s.skey})" class="glass glass-lit card sol-card" v-reveal :style="{'--d':(i%2)*.08+'s',display:'grid',gap:'1.4rem',alignContent:'start',padding:'2rem'}">
        <header style="display:flex;gap:1rem;align-items:center"><span class="icon-chip"><Icon :name="s.icon"/></span>
          <div><p style="font-size:.68rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--violet);margin-bottom:.25rem">{{ tr(s.pilar) }}</p><h2 class="h3">{{ tr(s.titulo) }}</h2></div></header>
        <div><h3 class="lbl">{{ t('common.problema_label') }}</h3><p>{{ tr(s.problema) }}</p></div>
        <div><h3 class="lbl">{{ t('common.como_label') }}</h3><ul class="check-list">
          <li v-for="(l,j) in trLines(s.como)" :key="j"><span class="check-chip"><Icon name="check" :size="13"/></span>{{ l }}</li></ul></div>
        <div style="border-top:1px solid var(--line-soft);padding-top:1.2rem"><h3 class="lbl" style="color:var(--cyan)">{{ t('common.cambia_label') }}</h3><p>{{ tr(s.cambia) }}</p></div>
        <span class="link-arrow">{{ tx('landing.ver_sol','Ver solución') }} <Icon name="arrow" :size="16"/></span></router-link></div></section>
    <section class="section"><div class="bg-atmos"><div class="halo halo-violet" style="width:480px;height:480px;top:-160px;right:-240px;opacity:.3"></div></div>
      <div class="container"><div class="section-head"><p class="eyebrow" v-reveal>{{ t('soluciones.metodo_eyebrow') }}</p><h2 class="h2" v-reveal :style="{'--d':'.08s'}">{{ t('soluciones.metodo_titulo') }}</h2></div>
      <ol style="list-style:none;margin:0;padding:0;display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
        <li v-for="(p,i) in metodo" :key="i" class="glass card" v-reveal :style="{'--d':i*.1+'s',display:'grid',gap:'.7rem',alignContent:'start'}">
          <span class="grad-text" style="font-size:2rem;font-weight:700;line-height:1">{{ p.num }}</span><h3 class="h3">{{ p.titulo }}</h3><p>{{ p.texto }}</p></li></ol></div></section>
    <SectionCTA :titulo="t('home.cta_titulo')" :sub="t('home.cta_sub')" :primary="pageUrl('contacto')" :primaryLabel="t('home.cta_cta1')" :secondary="pageUrl('diagnostico')" :secondaryLabel="t('common.hacer_diagnostico')"/>
  </div>`,
  data() { return { items: [], dosCol: window.innerWidth >= 900 ? { gridTemplateColumns: 'repeat(2,1fr)' } : {} }; },
  computed: { t: () => t, tr: () => tr, trLines: () => trLines, pageUrl: () => pageUrl, metodo() { return t('soluciones.metodo') || []; } },
  methods: { tx(k, fb) { const v = t(k); return (v && v !== k) ? v : fb; } },
  async mounted() { setMeta(t('soluciones.meta_title') + ' · ExperientIA', t('soluciones.meta_desc')); this.items = await fetchContent('soluciones'); },
};
