// Landings de venta por solución y producto (formulario modal, mobile-first)
import { t, tr, trLines, pageUrl, api, store, setMeta } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { PageHero } from '../lib/layout.js';
import { LeadModal } from '../lib/forms.js';

async function fc(s){ const r=await api.get('/content/'+s); return r.ok?r.data:[]; }
export const slugify = (s)=> (s||'').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,80);
const tx = (key,fb)=>{ const v=t(key); return (v && v!==key)?v:fb; };

const NoEncontrado = {
  template:`<section class="page-hero" style="min-height:50vh;display:grid;align-items:center"><div class="container" style="text-align:center;display:grid;gap:1rem;justify-items:center">
    <h1 class="h2">{{ tx('landing.no_t','Contenido no encontrado') }}</h1><router-link :to="pageUrl(volver)" class="btn btn-primary">{{ tx('landing.volver','Ver todo') }}</router-link></div></section>`,
  props:{ volver:String }, computed:{ pageUrl:()=>pageUrl }, methods:{ tx },
};

// Bloque de cierre + sticky mobile (compartido)
const landingTail = `
    <section class="section"><div class="container"><div class="glass glass-lit card land-final" v-reveal>
      <h2 class="h2">{{ tx('landing.final_t','¿Listo para aplicarlo en tu empresa?') }}</h2>
      <p class="lead">{{ tx('landing.final_s','Agenda una sesión o déjanos tus datos y te contactamos para mostrarte cómo funciona en tu caso.') }}</p>
      <div class="land-cta-row"><button class="btn btn-grad" @click="modal=true">{{ tx('landing.cta','Quiero más información') }}</button>
        <router-link :to="pageUrl('agenda')" class="btn btn-ghost">{{ tx('landing.agendar','Agendar 1:1') }}</router-link></div></div></div></section>
    <div class="land-sticky"><span>{{ heroTitulo }}</span><button class="btn btn-grad btn-sm" @click="modal=true">{{ tx('landing.cta_corto','Me interesa') }}</button></div>`;

export const SolucionLanding = {
  components: { Icon, PageHero, LeadModal, NoEncontrado },
  template: `<div v-if="s">
    <PageHero :eyebrow="tr(s.pilar)" :titulo="tr(s.titulo)" :sub="tr(s.cambia)">
      <div class="land-cta-row"><button class="btn btn-grad" @click="modal=true">{{ tx('landing.cta_hero','Quiero esta solución') }}</button>
        <router-link :to="pageUrl('diagnostico')" class="btn btn-ghost">{{ t('common.hacer_diagnostico') }}</router-link></div></PageHero>
    <section class="section"><div class="container land-grid">
      <div class="land-body">
        <div class="glass card" v-reveal><span class="icon-chip"><Icon :name="s.icon"/></span>
          <h2 class="lbl" style="margin-top:1rem">{{ t('common.problema_label') }}</h2><p class="lead">{{ tr(s.problema) }}</p></div>
        <div class="glass card" v-reveal><h2 class="lbl">{{ t('common.como_label') }}</h2>
          <ul class="check-list"><li v-for="(l,j) in trLines(s.como)" :key="j"><span class="check-chip"><Icon name="check" :size="13"/></span>{{ l }}</li></ul></div>
        <div class="glass glass-lit card" v-reveal><h2 class="lbl" style="color:var(--cyan)">{{ t('common.cambia_label') }}</h2><p class="lead">{{ tr(s.cambia) }}</p></div></div>
      <aside class="land-aside"><div class="glass glass-lit card land-cta" v-reveal>
        <h3 class="h3">{{ tx('landing.aside_t','Da el primer paso') }}</h3><p>{{ tx('landing.aside_s','Cuéntanos tu caso y te mostramos cómo aplicarlo a tu negocio.') }}</p>
        <button class="btn btn-grad" style="width:100%" @click="modal=true">{{ tx('landing.cta','Quiero más información') }}</button>
        <router-link :to="pageUrl('agenda')" class="btn btn-ghost" style="width:100%">{{ tx('landing.agendar','Agendar 1:1') }}</router-link>
        <p class="small" style="text-align:center">{{ t('form.privacidad') }}</p></div></aside></div></section>
    ${landingTail}
    <LeadModal v-if="modal" :titulo="tr(s.titulo)" :origen="'solucion:'+s.skey" @close="modal=false"/>
  </div><NoEncontrado v-else-if="cargado" volver="soluciones"/>`,
  data(){ return { s:null, modal:false, cargado:false }; },
  computed:{ t:()=>t, tr:()=>tr, trLines:()=>trLines, pageUrl:()=>pageUrl, heroTitulo(){ return this.s?tr(this.s.titulo):''; } },
  methods:{ tx },
  async mounted(){ const items=await fc('soluciones'); this.s=items.find(x=>x.skey===this.$route.params.slug)||null; this.cargado=true;
    if(this.s) setMeta(tr(this.s.titulo)+' · ExperientIA', tr(this.s.cambia)); },
};

export const ProductoLanding = {
  components: { Icon, PageHero, LeadModal, NoEncontrado },
  template: `<div v-if="p">
    <PageHero :eyebrow="tr(p.rol)" :titulo="tr(p.nombre)" :sub="tr(p.texto)">
      <div class="land-cta-row"><button class="btn btn-grad" @click="modal=true">{{ tx('landing.cta_hero_prod','Quiero este producto') }}</button>
        <router-link :to="pageUrl('agenda')" class="btn btn-ghost">{{ tx('landing.agendar','Agendar 1:1') }}</router-link></div></PageHero>
    <section class="section"><div class="container land-grid">
      <div class="land-body">
        <div class="glass card" v-reveal><span class="icon-chip"><Icon :name="p.icon"/></span>
          <h2 class="lbl" style="margin-top:1rem">{{ tx('landing.prod_que','Qué es') }}</h2><p class="lead">{{ tr(p.texto) }}</p></div>
        <div class="glass glass-lit card" v-reveal><h2 class="lbl" style="color:var(--cyan)">{{ tx('landing.prod_para','Para qué sirve') }}</h2><p class="lead">{{ tr(p.rol) }}</p></div></div>
      <aside class="land-aside"><div class="glass glass-lit card land-cta" v-reveal>
        <h3 class="h3">{{ tx('landing.aside_t','Da el primer paso') }}</h3><p>{{ tx('landing.aside_s_prod','Déjanos tus datos y te mostramos cómo integrarlo en tu operación.') }}</p>
        <button class="btn btn-grad" style="width:100%" @click="modal=true">{{ tx('landing.cta','Quiero más información') }}</button>
        <router-link :to="pageUrl('agenda')" class="btn btn-ghost" style="width:100%">{{ tx('landing.agendar','Agendar 1:1') }}</router-link>
        <p class="small" style="text-align:center">{{ t('form.privacidad') }}</p></div></aside></div></section>
    ${landingTail}
    <LeadModal v-if="modal" :titulo="tr(p.nombre)" :origen="'producto:'+slug" @close="modal=false"/>
  </div><NoEncontrado v-else-if="cargado" volver="productos"/>`,
  data(){ return { p:null, modal:false, cargado:false, slug:'' }; },
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl, heroTitulo(){ return this.p?tr(this.p.nombre):''; } },
  methods:{ tx },
  async mounted(){ this.slug=this.$route.params.slug; const items=await fc('productos');
    this.p=items.find(x=>slugify(tr(x.nombre,'es'))===this.slug)||null; this.cargado=true;
    if(this.p) setMeta(tr(this.p.nombre)+' · ExperientIA', tr(this.p.texto)); },
};
