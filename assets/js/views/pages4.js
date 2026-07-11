// Vistas: Diagnostico (wizard) y Agenda (reserva)
import { t, tr, pageUrl, api, store, setMeta } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { PageHero } from '../lib/layout.js';
import { LeadFields } from '../lib/forms.js';
function blankLead(){ return { name:'',email:'',phone_wa:'',phone_dial:'',country:'',company:'',industry:'',company_size:'',website:'' }; }

const PREGUNTAS = null; // se cargan del backend vía content? No: van embebidas en lang; usamos endpoint dedicado

export const Diagnostico = {
  components: { Icon, LeadFields, PageHero },
  template: `<div>
    <PageHero :eyebrow="t('diagnostico.eyebrow')" :titulo="t('diagnostico.titulo')" :sub="t('diagnostico.sub')"/>
    <section class="section"><div class="container diag">
      <div v-if="resultado" class="glass glass-lit card" v-reveal style="display:grid;gap:1.2rem;justify-items:start;padding:clamp(2rem,5vw,3rem)">
        <p class="eyebrow">{{ t('diagnostico.resultado_eyebrow') }}</p><h2 class="h2">{{ t('diagnostico.resultado_titulo') }}</h2>
        <div class="chip-row" style="justify-content:flex-start;gap:.9rem"><span class="icon-chip"><Icon :name="resultado.icon"/></span><span class="chip">{{ resultado.pilar }}</span></div>
        <h3 class="h3 grad-text" style="font-size:1.6rem">{{ resultado.titulo }}</h3><p class="lead">{{ resultado.cambia }}</p><p>{{ t('diagnostico.resultado_sub') }}</p>
        <div style="display:flex;flex-wrap:wrap;gap:.9rem"><router-link :to="pageUrl('agenda')" class="btn btn-primary">{{ t('diagnostico.resultado_cta') }}</router-link><router-link :to="pageUrl('soluciones')" class="btn btn-ghost">{{ t('diagnostico.resultado_cta2') }}</router-link></div></div>

      <form v-else @submit.prevent="enviar">
        <div class="diag__bar"><i :style="{'--p':((paso)/(total)*100)+'%'}"></i></div>
        <transition :name="dir>0?'diag-next':'diag-prev'" mode="out-in">
        <div :key="paso" class="diag__step">
          <template v-if="paso < total">
            <p class="diag__num">{{ t('diagnostico.pregunta') }} {{ paso+1 }} {{ t('diagnostico.de') }} {{ total+1 }}</p>
            <h2 class="h3" style="font-size:1.35rem;margin:.4rem 0 1.1rem">{{ tr(preguntas[paso].texto) }}</h2>
            <div class="diag__opts"><label v-for="(o,oi) in preguntas[paso].opciones" :key="oi" class="diag__opt" :class="{sel:respuestas[preguntas[paso].id]===oi}">
              <input type="radio" style="display:none" :value="oi" v-model="respuestas[preguntas[paso].id]" @change="autoNext(paso)"><span>{{ tr(o.texto) }}</span></label></div>
            <div class="diag__nav"><button v-if="paso>0" type="button" class="btn btn-ghost" @click="prev">{{ t('diagnostico.atras') }}</button><span v-else></span>
              <button type="button" class="btn btn-primary" @click="next(paso)">{{ t('diagnostico.siguiente') }}</button></div>
          </template>
          <template v-else>
            <p class="diag__num">{{ t('diagnostico.pregunta') }} {{ total+1 }} {{ t('diagnostico.de') }} {{ total+1 }}</p>
            <h2 class="h3" style="font-size:1.35rem;margin:.4rem 0">{{ t('diagnostico.datos_titulo') }}</h2><p class="small">{{ t('diagnostico.datos_sub') }}</p>
            <div style="margin-top:1rem"><LeadFields ref="lf" v-model="lead" :full="false"/></div>
            <div class="diag__nav"><button type="button" class="btn btn-ghost" @click="prev">{{ t('diagnostico.atras') }}</button>
              <button class="btn btn-grad" :disabled="loading">{{ loading?t('form.enviando'):t('diagnostico.ver_resultado') }}</button></div>
            <p class="err" v-if="error">{{ error }}</p>
          </template>
        </div></transition>
      </form></div></section>
  </div>`,
  data(){ return { preguntas:[], respuestas:{}, paso:0, dir:1, lead:blankLead(), resultado:null, loading:false, error:'' }; },
  computed:{ t:()=>t, tr:()=>tr, pageUrl:()=>pageUrl, total(){ return this.preguntas.length; } },
  async mounted(){ setMeta(t('diagnostico.meta_title')+' · ExperientIA', t('diagnostico.meta_desc'));
    const r=await api.get('/content/soluciones'); // asegura contenido cacheado
    const dr=await fetch('/assets/js/lib/diagnostico.json'); this.preguntas=(await dr.json()).preguntas||[];
  },
  methods:{
    // Sin scroll: solo transición suave entre pasos (no molesta al usuario C-Level).
    next(qi){ if(this.respuestas[this.preguntas[qi].id]===undefined) return; this.dir=1; this.paso++; },
    prev(){ this.dir=-1; if(this.paso>0) this.paso--; },
    autoNext(qi){ setTimeout(()=>{ if(this.paso===qi){ this.dir=1; this.paso++; } },260); },
    async enviar(){
      if(!this.$refs.lf.validate(['name','email','country'])) return;
      this.loading=true; this.error='';
      const r=await api.post('/diagnostico',{...this.lead,respuestas:this.respuestas,locale:store.locale});
      this.loading=false;
      if(r.ok){ this.resultado=r.data.solucion; } else { this.error=r.error||'Error'; this.$refs.lf.setErrors(r.campos); }
    },
  },
};

export const Agenda = {
  components: { Icon, LeadFields, PageHero },
  template: `<div>
    <PageHero :eyebrow="t('agenda.eyebrow')" :titulo="t('agenda.titulo')" :sub="t('agenda.sub')"/>
    <section class="section"><div class="container">
      <div v-if="enviado" class="glass glass-lit form-panel" v-reveal style="max-width:34rem;margin-inline:auto;text-align:center;justify-items:center">
        <span class="icon-chip"><Icon name="check"/></span><h2 class="h3">{{ t('agenda.gracias_titulo') }}</h2><p>{{ t('agenda.gracias_sub') }}</p><router-link :to="pageUrl('home')" class="btn btn-ghost">{{ t('not_found.cta') }}</router-link></div>
      <div v-else-if="!slots.length" class="glass glass-lit form-panel" v-reveal style="max-width:34rem;margin-inline:auto;text-align:center;justify-items:center">
        <p class="lead">{{ t('agenda.sin_horarios') }}</p><router-link :to="pageUrl('contacto')" class="btn btn-primary">{{ t('nav.contacto') }}</router-link></div>
      <form v-else class="agenda__grid" @submit.prevent="reservar">
        <div class="glass form-panel" v-reveal>
          <div><p class="agenda__step">{{ t('agenda.elegir_dia') }}</p><div class="agenda__dias">
            <button v-for="d in dias" :key="d.key" type="button" class="agenda__dia" :class="{sel:diaSel===d.key}" @click="pickDia(d.key)">
              <span>{{ d.dow }}</span><b>{{ d.num }}</b><span>{{ d.mon }}</span></button></div></div>
          <div><p class="agenda__step">{{ t('agenda.elegir_hora') }}</p><div class="agenda__horas">
            <button v-for="h in horas" :key="h.iso" type="button" class="agenda__hora" :class="{sel:slot===h.iso}" @click="slot=h.iso">{{ h.label }}</button></div>
            <p class="small" style="margin-top:1rem">{{ t('agenda.zona') }} ({{ tz }})</p></div>
          <p class="agenda__resumen" v-if="slot">{{ resumen }}</p></div>
        <div class="glass form-panel" v-reveal :style="{'--d':'.1s'}">
          <p class="agenda__step">{{ t('agenda.sus_datos') }}</p><LeadFields ref="lf" v-model="lead" :full="false"/>
          <div class="field"><label>{{ t('agenda.tema') }}</label><textarea v-model="lead.tema" rows="3" class="field-el"></textarea></div>
          <button class="btn btn-grad" style="justify-self:start" :disabled="loading">{{ loading?t('form.enviando'):t('agenda.confirmar') }}</button>
          <p class="err" v-if="error">{{ error }}</p><p class="small">{{ t('form.privacidad') }}</p></div></form></div></section>
  </div>`,
  data(){ return { slots:[], slot:'', diaSel:'', lead:{...blankLead(),tema:''}, enviado:false, loading:false, error:'', tz:Intl.DateTimeFormat().resolvedOptions().timeZone }; },
  computed:{
    t:()=>t, pageUrl:()=>pageUrl,
    porDia(){ const m={}; for(const iso of this.slots){ const d=new Date(iso); const k=d.toLocaleDateString('en-CA',{timeZone:this.tz}); (m[k]=m[k]||[]).push(d); } return m; },
    dias(){ return Object.keys(this.porDia).sort().map(k=>{ const d=this.porDia[k][0]; const f=(o)=>new Intl.DateTimeFormat(store.locale,{...o,timeZone:this.tz}).format(d);
      return { key:k, dow:f({weekday:'short'}), num:f({day:'numeric'}), mon:f({month:'short'}) }; }); },
    horas(){ return (this.porDia[this.diaSel]||[]).map(d=>({ iso:d.toISOString(), label:new Intl.DateTimeFormat(store.locale,{hour:'2-digit',minute:'2-digit',timeZone:this.tz}).format(d) })); },
    resumen(){ if(!this.slot) return ''; return new Intl.DateTimeFormat(store.locale,{weekday:'long',day:'numeric',month:'long',hour:'2-digit',minute:'2-digit',timeZone:this.tz}).format(new Date(this.slot)); },
  },
  async mounted(){ setMeta(t('agenda.meta_title')+' · ExperientIA', t('agenda.meta_desc'));
    const r=await api.get('/slots'); this.slots=r.ok?r.data.slots:[]; if(this.dias.length) this.diaSel=this.dias[0].key; },
  methods:{
    pickDia(k){ this.diaSel=k; this.slot=''; },
    async reservar(){
      if(!this.slot){ this.error=t('agenda.reservado'); return; }
      if(!this.$refs.lf.validate(['name','email','country'])) return;
      this.loading=true; this.error='';
      const r=await api.post('/reserva',{...this.lead,slot:this.slot,timezone:this.tz,locale:store.locale});
      this.loading=false;
      if(r.ok){ this.enviado=true; window.scrollTo({top:0,behavior:'smooth'}); } else { this.error=r.error||'Error'; this.$refs.lf.setErrors(r.campos); }
    },
  },
};
