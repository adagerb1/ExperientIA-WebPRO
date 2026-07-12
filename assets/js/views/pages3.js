// Vistas con formularios: Recursos, RecursoDetalle, Contacto, Diagnostico, Agenda
import { t, tr, pageUrl, api, store, toast, setMeta } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { PageHero, SectionCTA } from '../lib/layout.js';
import { LeadFields } from '../lib/forms.js';
async function fc(s){ const r=await api.get('/content/'+s); return r.ok?r.data:[]; }
function blankLead(){ return { name:'',email:'',phone_wa:'',phone_dial:'',country:'',company:'',role:'',industry:'',company_size:'',website:'' }; }
const tx = (key,fb)=>{ const v=t(key); return (v && v!==key)?v:fb; };
const RCATS = { ia_negocios:'IA aplicada a negocios', automatizacion:'Automatización', growth:'Growth', estrategia:'Estrategia', marketing:'Marketing estratégico', crm:'CRM', ventas:'Ventas', experiencia_cliente:'Experiencia de cliente', agentes:'Agentes inteligentes', datos:'Datos y analítica', liderazgo:'Liderazgo', transformacion:'Transformación digital' };
const catLabel = (k)=> RCATS[k]||k;

// Reproductor de audio liquid glass · narración de AlexIA
const AlexiaAudio = {
  components: { Icon },
  props: { src: String },
  template: `<div class="ax-audio glass">
    <button type="button" class="ax-audio-play" @click="toggle" :aria-label="playing?'Pausar':'Reproducir'">
      <svg v-if="playing" width="16" height="16" viewBox="0 0 16 16"><rect x="3.5" y="2.5" width="3.2" height="11" rx="1" fill="currentColor"/><rect x="9.3" y="2.5" width="3.2" height="11" rx="1" fill="currentColor"/></svg>
      <Icon v-else name="play" :size="18"/></button>
    <div class="ax-audio-mid">
      <div class="ax-audio-track" ref="track" @click="seek"><div class="ax-audio-fill" :style="{width:pct+'%'}"><span class="ax-audio-knob"></span></div></div>
      <div class="ax-audio-meta"><span>{{ fmt(cur) }} / {{ fmt(dur) }}</span>
        <span class="ax-audio-by"><Icon name="sparkle" :size="11"/> Narrado por AlexIA</span></div></div>
    <audio ref="a" :src="src" preload="metadata" @timeupdate="onTime" @loadedmetadata="onMeta" @ended="playing=false"></audio></div>`,
  data(){ return { playing:false, cur:0, dur:0 }; },
  computed:{ pct(){ return this.dur ? this.cur/this.dur*100 : 0; } },
  methods:{
    toggle(){ const a=this.$refs.a; if(this.playing){ a.pause(); this.playing=false; } else { a.play().then(()=>{this.playing=true;}).catch(()=>{}); } },
    onTime(){ this.cur=this.$refs.a.currentTime; },
    onMeta(){ this.dur=this.$refs.a.duration||0; },
    seek(e){ const t=this.$refs.track.getBoundingClientRect(); this.$refs.a.currentTime=Math.max(0,Math.min(1,(e.clientX-t.left)/t.width))*this.dur; },
    fmt(s){ s=Math.floor(s||0); return Math.floor(s/60)+':'+String(s%60).padStart(2,'0'); },
  },
};

export const Recursos = {
  components: { Icon, PageHero },
  template: `<div>
    <PageHero :eyebrow="t('recursos.eyebrow')" :titulo="t('recursos.titulo')" :sub="t('recursos.sub')"/>
    <section class="section"><div class="container"><div class="grid grid-3">
      <article v-for="(r,i) in items" :key="r.id" class="glass card rec-card" v-reveal :style="{'--d':(i%3)*.08+'s'}">
        <router-link :to="pageUrl('recursos',{slug:r.slug})" v-if="r.cover_image" class="rec-cover"><img :src="r.cover_image" :alt="tr(r.titulo)" loading="lazy"></router-link>
        <div class="chip-row"><span class="icon-chip"><Icon :name="r.type==='download'?'doc':'eye'"/></span><span class="chip">{{ tr(r.tipo_label) }}</span>
          <span v-for="c in (r.categories||[]).slice(0,2)" :key="c" class="chip chip-soft">{{ catLabel(c) }}</span></div>
        <h2 class="h3">{{ tr(r.titulo) }}</h2>
        <p class="rec-meta">{{ r.author||'ExperientIA' }} · {{ r.read_minutes||5 }} min<span v-if="r.audio_path"> · 🔊 audio</span></p>
        <p class="small">{{ tr(r.extracto) }}</p>
        <router-link :to="pageUrl('recursos',{slug:r.slug})" class="link-arrow">{{ r.type==='download'?tx('recursos.list_descarga','Descargar gratis'):t('common.leer') }} <Icon name="arrow" :size="16"/></router-link></article></div></div></section>
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
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl, catLabel:()=>catLabel, tx:()=>tx },
  async mounted(){ setMeta(t('recursos.meta_title')+' · ExperientIA', t('recursos.meta_desc')); this.items=await fc('recursos'); },
  methods:{ async suscribir(){ this.loading=true; const r=await api.post('/newsletter',{email:this.email,locale:store.locale}); this.loading=false; if(r.ok){this.subscrito=true}else{toast(r.error||'Error','err')} } },
};

export const RecursoDetalle = {
  components: { Icon, LeadFields, SectionCTA },
  template: `<div v-if="rec">
    <PageHero :eyebrow="tr(rec.tipo_label)" :titulo="tr(rec.titulo)" :sub="tr(rec.extracto)"/>
    <section class="section" v-if="rec.type!=='download'"><div class="container articulo">
      <img v-if="rec.cover_image" :src="rec.cover_image" :alt="tr(rec.titulo)" class="articulo__cover" v-reveal>
      <div class="articulo__meta" v-reveal>
        <span v-for="c in (rec.categories||[])" :key="c" class="chip chip-soft">{{ catLabel(c) }}</span>
        <span class="rec-meta">{{ rec.author||'ExperientIA' }} · {{ rec.read_minutes||5 }} min de lectura</span></div>
      <AlexiaAudio v-if="rec.audio_path" :src="rec.audio_path" v-reveal/>
      <div class="articulo__body" v-reveal v-html="tr(rec.cuerpo)"></div></div></section>
    <section class="section" v-else><div class="container magnet-grid">
      <div class="magnet-sell" v-reveal>
        <span class="chip chip--cyan">{{ tx('recursos.magnet_badge','Descarga gratuita') }}</span>
        <img v-if="rec.cover_image" :src="rec.cover_image" :alt="tr(rec.titulo)" class="magnet-cover" loading="lazy">
        <h2 class="lbl" style="margin-top:.3rem">{{ tx('recursos.magnet_qt','Qué te llevas') }}</h2>
        <ul class="check-list check-list--lg">
          <li><span class="check-chip"><Icon name="check" :size="13"/></span>{{ tr(rec.extracto) }}</li>
          <li><span class="check-chip"><Icon name="check" :size="13"/></span>{{ tx('recursos.magnet_b1','Aplicable a tu empresa desde hoy, sin relleno teórico.') }}</li>
          <li><span class="check-chip"><Icon name="check" :size="13"/></span>{{ tx('recursos.magnet_b2','Lo recibes al instante, sin costo.') }}</li></ul></div>
      <div>
        <div v-if="listo" class="glass glass-lit form-panel magnet-ok" v-reveal>
          <span class="icon-chip"><Icon name="check"/></span>
          <h2 class="h3">{{ tx('recursos.post_t','Tu descarga está lista') }}</h2>
          <a :href="url" class="btn btn-primary" download>{{ t('common.descargar') }}</a>
          <p>{{ tx('recursos.post_s','¿Quieres que lo llevemos a tu caso? Empieza con un diagnóstico ejecutivo gratuito.') }}</p>
          <router-link :to="pageUrl('diagnostico')" class="btn btn-grad">{{ tx('recursos.post_cta','Quiero mi diagnóstico gratis') }}</router-link>
          <p class="small">{{ tx('recursos.post_nota','Te sumamos a nuestros insights ejecutivos. Puedes salir cuando quieras.') }}</p></div>
        <form v-else class="glass glass-lit form-panel" v-reveal @submit.prevent="descargar">
          <h2 class="h3">{{ tx('recursos.magnet_form_t','Descárgalo gratis') }}</h2><p class="small">{{ tx('recursos.magnet_form_s','Déjanos tu nombre y correo y te lo enviamos al instante.') }}</p>
          <div style="margin-top:1rem"><LeadFields ref="lf" v-model="lead" :minimal="true"/></div>
          <button class="btn btn-grad" style="justify-self:start;margin-top:.4rem" :disabled="loading">{{ loading?t('form.enviando'):tx('recursos.magnet_cta','Descargar gratis') }}</button>
          <p class="err" v-if="error">{{ error }}</p><p class="small garantia"><Icon name="shield" :size="12"/> {{ t('form.privacidad') }}</p></form></div></div></section>
    <SectionCTA :titulo="t('home.cta_titulo')" :sub="t('home.cta_sub')" :primary="pageUrl('diagnostico')" :primaryLabel="tx('landing.oferta_cta','Quiero mi diagnóstico gratis')" :secondary="pageUrl('recursos')" :secondaryLabel="t('nav.recursos')"/>
  </div>`,
  components: { Icon, LeadFields, SectionCTA, PageHero, AlexiaAudio },
  data(){ return { rec:null, lead:blankLead(), listo:false, url:'', loading:false, error:'' }; },
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl, catLabel:()=>catLabel, tx:()=>tx },
  async mounted(){ const all=await fc('recursos'); this.rec=all.find(r=>r.slug===this.$route.params.slug); if(this.rec) setMeta(tr(this.rec.titulo)+' · ExperientIA', tr(this.rec.extracto)); },
  methods:{ async descargar(){
    if(!this.$refs.lf.validate(['name','email'])) return;
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
