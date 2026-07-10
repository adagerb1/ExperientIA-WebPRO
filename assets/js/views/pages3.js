// Vistas con formularios: Recursos, RecursoDetalle, Contacto, Diagnostico, Agenda
import { t, tr, pageUrl, api, store, toast, setMeta } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { PageHero, SectionCTA } from '../lib/layout.js';
import { LeadFields } from '../lib/forms.js';
async function fc(s){ const r=await api.get('/content/'+s); return r.ok?r.data:[]; }
function blankLead(){ return { name:'',email:'',phone_wa:'',phone_dial:'',country:'',company:'',role:'',industry:'',company_size:'',website:'' }; }

export const Recursos = {
  components: { Icon, PageHero },
  template: `<div>
    <PageHero :eyebrow="t('recursos.eyebrow')" :titulo="t('recursos.titulo')" :sub="t('recursos.sub')"/>
    <section class="section"><div class="container"><div class="grid grid-3">
      <article v-for="(r,i) in items" :key="r.id" class="glass card" v-reveal :style="{'--d':(i%3)*.08+'s',display:'grid',gap:'.9rem',alignContent:'start'}">
        <div class="chip-row"><span class="icon-chip"><Icon :name="r.type==='download'?'doc':'eye'"/></span><span class="chip">{{ tr(r.tipo_label) }}</span></div>
        <h2 class="h3">{{ tr(r.titulo) }}</h2><p class="small">{{ tr(r.extracto) }}</p>
        <router-link :to="pageUrl('recursos',{slug:r.slug})" class="link-arrow">{{ r.type==='download'?t('common.descargar'):t('common.leer') }} <Icon name="arrow" :size="16"/></router-link></article></div></div></section>
    <section class="section"><div class="container"><div class="glass glass-lit card" v-reveal style="position:relative;overflow:hidden;text-align:center;padding:clamp(2.5rem,6vw,4rem)">
      <div class="bg-atmos"><div class="halo halo-cyan" style="width:400px;height:400px;top:-220px;right:-120px;opacity:.4"></div></div>
      <div style="position:relative;z-index:1;display:grid;gap:1.2rem;justify-items:center;max-width:38rem;margin-inline:auto">
        <h2 class="h2">{{ t('recursos.newsletter_titulo') }}</h2><p class="lead">{{ t('recursos.newsletter_sub') }}</p>
        <div v-if="subscrito" class="form-ok">{{ t('recursos.newsletter_gracias') }}</div>
        <form v-else style="display:flex;flex-wrap:wrap;gap:.75rem;justify-content:center;width:100%" @submit.prevent="suscribir">
          <input type="email" v-model="email" required :placeholder="t('recursos.newsletter_placeholder')" class="field-el" style="flex:1 1 240px;max-width:340px">
          <button class="btn btn-primary" :disabled="loading">{{ t('recursos.newsletter_cta') }}</button></form>
        <p class="small">{{ t('recursos.newsletter_nota') }}</p></div></div></div></section>
  </div>`,
  data(){ return { items:[], email:'', subscrito:false, loading:false }; },
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl },
  async mounted(){ setMeta(t('recursos.meta_title')+' · ExperientIA', t('recursos.meta_desc')); this.items=await fc('recursos'); },
  methods:{ async suscribir(){ this.loading=true; const r=await api.post('/newsletter',{email:this.email,locale:store.locale}); this.loading=false; if(r.ok){this.subscrito=true}else{toast(r.error||'Error','err')} } },
};

export const RecursoDetalle = {
  components: { Icon, LeadFields, SectionCTA },
  template: `<div v-if="rec">
    <PageHero :eyebrow="tr(rec.tipo_label)" :titulo="tr(rec.titulo)" :sub="tr(rec.extracto)"/>
    <section class="section" v-if="rec.type!=='download'"><div class="container articulo"><div class="articulo__body" v-reveal v-html="tr(rec.cuerpo)"></div></div></section>
    <section class="section" v-else><div class="container contacto__grid">
      <aside><ul class="beneficios"><li class="glass card" v-reveal><span class="icon-chip"><Icon name="doc"/></span><p>{{ tr(rec.extracto) }}</p></li>
        <li class="glass card" v-reveal :style="{'--d':'.08s'}"><span class="icon-chip"><Icon name="shield"/></span><p>{{ t('form.privacidad') }}</p></li></ul></aside>
      <div v-if="listo" class="glass glass-lit form-panel" v-reveal><h2 class="h3">{{ t('recursos.descarga_lista') }}</h2><a :href="url" class="btn btn-primary">{{ t('common.descargar') }}</a><p class="small">{{ t('recursos.gated_sub') }}</p></div>
      <form v-else class="glass glass-lit form-panel" v-reveal @submit.prevent="descargar">
        <h2 class="h3">{{ t('recursos.gated_titulo') }}</h2><p class="small">{{ t('recursos.gated_sub') }}</p>
        <LeadFields ref="lf" v-model="lead" :full="false"/>
        <button class="btn btn-grad" style="justify-self:start" :disabled="loading">{{ loading?t('form.enviando'):t('common.descargar') }}</button>
        <p class="err" v-if="error">{{ error }}</p><p class="small">{{ t('form.privacidad') }}</p></form></div></section>
    <SectionCTA :titulo="t('home.cta_titulo')" :sub="t('home.cta_sub')" :primary="pageUrl('contacto')" :primaryLabel="t('home.cta_cta1')" :secondary="pageUrl('recursos')" :secondaryLabel="t('nav.recursos')"/>
  </div>`,
  components: { Icon, LeadFields, SectionCTA, PageHero },
  data(){ return { rec:null, lead:blankLead(), listo:false, url:'', loading:false, error:'' }; },
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl },
  async mounted(){ const all=await fc('recursos'); this.rec=all.find(r=>r.slug===this.$route.params.slug); if(this.rec) setMeta(tr(this.rec.titulo)+' · ExperientIA', tr(this.rec.extracto)); },
  methods:{ async descargar(){
    if(!this.$refs.lf.validate(['name','email','country'])) return;
    this.loading=true; this.error='';
    const r=await api.post('/descarga',{...this.lead,slug:this.rec.slug,locale:store.locale});
    this.loading=false;
    if(r.ok){ this.url=r.data.url; this.listo=true; } else { this.error=r.error||'Error'; this.$refs.lf.setErrors(r.campos); }
  }},
};

export const Contacto = {
  components: { Icon, LeadFields, PageHero },
  template: `<div>
    <PageHero :eyebrow="t('contacto.eyebrow')" :titulo="t('contacto.titulo')" :sub="t('contacto.sub')"/>
    <section class="section"><div class="container contacto__grid">
      <aside><ul class="beneficios"><li v-for="(b,i) in beneficios" :key="i" class="glass card" v-reveal :style="{'--d':i*.08+'s'}"><span class="icon-chip"><Icon :name="b.icon"/></span><p style="font-size:.95rem">{{ b.texto }}</p></li>
        <li class="glass card" v-reveal :style="{'--d':'.3s'}"><span class="icon-chip"><Icon name="calendar"/></span><p><router-link :to="pageUrl('agenda')">{{ t('common.agendar_sesion') }}</router-link></p></li>
        <li class="glass card" v-reveal :style="{'--d':'.36s'}"><span class="icon-chip"><Icon name="mail"/></span><p><a href="mailto:hello@experientia.pro">hello@experientia.pro</a></p></li></ul></aside>
      <div v-if="enviado" class="glass glass-lit form-panel" v-reveal><h2 class="h3">{{ t('contacto.gracias_titulo') }}</h2><p>{{ t('contacto.gracias_sub') }}</p><router-link :to="pageUrl('agenda')" class="btn btn-primary">{{ t('common.agendar_sesion') }}</router-link></div>
      <form v-else class="glass glass-lit form-panel" v-reveal @submit.prevent="enviar">
        <LeadFields ref="lf" v-model="lead" :full="true"/>
        <div class="field"><label>{{ t('form.desafio') }} *</label><select v-model="lead.desafio" class="field-el" required>
          <option v-for="(v,k) in desafios" :key="k" :value="k">{{ v }}</option></select></div>
        <div class="field"><label>{{ t('form.mensaje') }}</label><textarea v-model="lead.mensaje" rows="5" class="field-el"></textarea></div>
        <button class="btn btn-grad" style="justify-self:start" :disabled="loading">{{ loading?t('form.enviando'):t('form.enviar') }}</button>
        <p class="err" v-if="error">{{ error }}</p><p class="small">{{ t('form.privacidad') }}</p></form></div></section>
  </div>`,
  data(){ return { lead:{...blankLead(),desafio:'crecimiento',mensaje:'',role:''}, enviado:false, loading:false, error:'' }; },
  computed:{ t:()=>t, pageUrl:()=>pageUrl, beneficios(){ return t('contacto.beneficios')||[]; }, desafios(){ return t('form.desafios')||{}; } },
  mounted(){ setMeta(t('contacto.meta_title')+' · ExperientIA', t('contacto.meta_desc')); },
  methods:{ async enviar(){
    if(!this.$refs.lf.validate(['name','email','country','company','industry','company_size'])) return;
    this.loading=true; this.error='';
    const r=await api.post('/contacto',{...this.lead,locale:store.locale});
    this.loading=false;
    if(r.ok){ this.enviado=true; window.scrollTo({top:0,behavior:'smooth'}); } else { this.error=r.error||'Error'; this.$refs.lf.setErrors(r.campos); }
  }},
};
