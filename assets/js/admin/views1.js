// Portal admin · Login, Dashboard (KPIs accionables + short polling), Leads (CRM)
import { api, store, setAuth, toast, exportCSV } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

export const Login = {
  components: { Icon },
  template: `<div class="login-wrap"><form class="glass login" @submit.prevent="entrar">
    <div style="justify-self:center;display:grid;justify-items:center;gap:.6rem">
      <img src="/assets/img/brand/symbol.png" alt="" aria-hidden="true" style="height:56px;width:auto" draggable="false">
      <b style="font-size:1.1rem">Portal Experient<i class="grad-text">IA</i></b></div>
    <div class="field"><label>Correo</label><input class="inp" type="email" v-model="email" autocomplete="username" required></div>
    <div class="field"><label>Contraseña</label><input class="inp" type="password" v-model="password" autocomplete="current-password" required></div>
    <button class="btn btn-primary" :disabled="loading">{{ loading?'Entrando…':'Entrar' }}</button>
    <p v-if="error" style="color:#ff7d9d;font-size:.85rem;text-align:center">{{ error }}</p></form></div>`,
  data(){ return { email:'', password:'', loading:false, error:'' }; },
  methods:{ async entrar(){ this.loading=true; this.error='';
    const r=await api.post('/admin/login',{email:this.email,password:this.password});
    this.loading=false;
    if(r.ok){ setAuth(r.data.token,r.data.admin); this.$router.push('/admin'); } else { this.error=r.error||'Error'; } } },
};

// Barras horizontales reutilizables (segmentaciones del tablero).
const HBars = {
  props: { rows: { type: Array, default: () => [] }, labelFn: Function, onPick: Function },
  template: `<div class="hbars"><p v-if="!rows.length" class="empty">Sin datos aún.</p>
    <div v-for="r in rows" :key="r.clave" class="hbar" :class="{clic:!!onPick}" @click="onPick && onPick(r.clave)">
      <span :title="lab(r.clave)">{{ lab(r.clave) }}</span>
      <div class="hbar-track"><div class="hbar-fill" :style="{width:pct(r.total)+'%'}"></div></div>
      <span class="hbar-val">{{ r.total }}</span></div></div>`,
  methods: {
    lab(k){ return this.labelFn ? this.labelFn(k) : k; },
    pct(v){ const m = Math.max(1, ...this.rows.map(x => x.total)); return Math.max(3, v / m * 100); },
  },
};

// AlexIA · Estratega — analiza la data del negocio y recomienda decisiones.
const AlexiaPanel = {
  components: { Icon },
  template: `<div class="glass panel alexia-panel mt">
    <div class="ap-head"><div><h3>AlexIA · <span class="grad-text">Estratega</span></h3>
      <p class="empty">Analiza tu data y recomienda dónde enfocar el crecimiento.</p></div>
      <button class="btn btn-primary btn-sm" @click="analizar" :disabled="loading"><Icon name="sparkle" :size="14"/> {{ loading?'Analizando…':'Analizar mi crecimiento' }}</button></div>
    <p v-if="error" class="small" style="color:#ff7d9d">{{ error }}</p>
    <p v-if="resumen" class="ap-resumen">{{ resumen }}</p>
    <ol v-if="recs.length" class="ap-recs"><li v-for="(r,i) in recs" :key="i" class="ap-rec">
      <div class="ap-rec-h"><b>{{ r.titulo }}</b><span class="prio" :class="r.prioridad">{{ r.prioridad }}</span></div>
      <p>{{ r.detalle }}</p><span v-if="r.accion" class="ap-accion">→ {{ r.accion }}</span></li></ol>
    <div class="ap-ask">
      <input class="inp" v-model="q" placeholder="Pregúntale a AlexIA sobre tus datos…" @keydown.enter="preguntar" :disabled="asking">
      <button class="btn btn-ghost btn-sm" @click="preguntar" :disabled="asking">Preguntar</button></div>
    <p v-if="answer" class="ap-answer">{{ answer }}</p></div>`,
  data(){ return { loading:false, asking:false, resumen:'', recs:[], error:'', q:'', answer:'', convId:null }; },
  methods:{
    async analizar(){ this.loading=true; this.error=''; this.resumen=''; this.recs=[];
      const r=await api.post('/admin/alexia/estrategia',{}); this.loading=false;
      if(r.ok){ this.resumen=r.data.resumen||''; this.recs=r.data.recomendaciones||[];
        if(!this.recs.length&&!this.resumen) this.error='AlexIA no devolvió recomendaciones. Intenta de nuevo.'; }
      else this.error=r.error||'AlexIA no está disponible. Configura y activa OpenAI o Claude en Conectores.'; },
    async preguntar(){ const t=this.q.trim(); if(!t||this.asking)return; this.asking=true; this.answer='Pensando…';
      const r=await api.post('/admin/alexia',{mensaje:t,conversation_id:this.convId}); this.asking=false;
      if(r.ok){ this.convId=r.data.conversation_id; this.answer=r.data.reply; this.q=''; } else this.answer=r.error||'No disponible.'; },
  },
};

export const Dashboard = {
  components: { Icon, HBars, AlexiaPanel },
  template: `<div><h1>Tablero de <span class="grad-text">Crecimiento</span></h1>
    <p class="adm__sub">Centro de comando de ExperientIA · datos en vivo (actualiza cada 20 s)</p>
    <div class="kpis">
      <button v-for="k in kpiList" :key="k.key" class="glass kpi" @click="k.action && k.action()">
        <b class="grad-text">{{ kpis[k.key] ?? '—' }}</b><span>{{ k.label }}</span><span class="ctx" v-if="k.ctx">{{ k.ctx }}</span></button></div>

    <AlexiaPanel/>

    <div class="glass panel mt"><h3>Embudo de conversión</h3>
      <div class="funnel">
        <div v-for="e in embudo.etapas" :key="e.label" class="funnel-step" :class="{clic:!!e.clave}" @click="e.clave && irEstado(e.clave)">
          <span>{{ e.label }}</span>
          <div class="funnel-track"><div class="funnel-fill" :style="{width:funnelPct(e.valor)+'%'}">{{ e.valor }}</div></div>
          <span class="funnel-val">{{ funnelPct(e.valor) }}%</span></div></div>
      <div class="rates">
        <span class="rate-chip">Tasa de contacto <b>{{ tasas.contacto }}%</b></span>
        <span class="rate-chip">Calificación <b>{{ tasas.calificacion }}%</b></span>
        <span class="rate-chip">Reserva <b>{{ tasas.reserva }}%</b></span>
        <span class="rate-chip">Cierre <b>{{ tasas.cierre }}%</b></span></div></div>

    <div class="grid-2 mt">
      <div class="glass panel"><h3>Tendencia · 12 semanas</h3>
        <div class="leg"><span><i style="background:var(--cyan)"></i>Leads</span><span><i style="background:var(--violet)"></i>Interacciones</span></div>
        <svg class="trend" viewBox="0 0 320 150" preserveAspectRatio="none">
          <polyline :points="linea('interacciones','#7a63ff')" fill="none" stroke="#7a63ff" stroke-width="2"/>
          <polyline :points="linea('leads','#18d6f1')" fill="none" stroke="#18d6f1" stroke-width="2.5"/>
        </svg>
        <p class="empty" style="text-align:right">Últimas semanas · {{ tendencia.length ? tendencia[0].label+' → '+tendencia[tendencia.length-1].label : '—' }}</p></div>
      <div class="glass panel"><h3>Pipeline comercial</h3>
        <div class="hbars"><div v-for="p in pipeline" :key="p.clave" class="hbar clic" @click="irEstado(p.clave)">
          <span><span class="badge" :class="p.clave">{{ estadoLabel(p.clave) }}</span></span>
          <div class="hbar-track"><div class="hbar-fill" :style="{width:pctMax(p.total,pipeline)+'%'}"></div></div>
          <span class="hbar-val">{{ p.total }}</span></div></div></div></div>

    <div class="grid-2 mt">
      <div class="glass panel"><h3>Fuentes de leads</h3><HBars :rows="fuentes" :onPick="null"/></div>
      <div class="glass panel"><h3>Canales de entrada</h3><HBars :rows="canales"/></div></div>

    <div class="grid-3 mt">
      <div class="glass panel"><h3>Industrias</h3><HBars :rows="industrias"/></div>
      <div class="glass panel"><h3>Tamaño de empresa</h3><HBars :rows="tamanos" :labelFn="sizeLabel"/></div>
      <div class="glass panel"><h3>Países</h3><HBars :rows="paises"/></div></div>

    <div class="grid-2 mt">
      <div class="glass panel"><h3>Retos declarados <span class="empty">· contacto</span></h3><HBars :rows="desafios" :labelFn="desafioLabel"/></div>
      <div class="glass panel"><h3>Soluciones más demandadas <span class="empty">· diagnóstico</span></h3><HBars :rows="soluciones" :labelFn="solucionLabel"/></div></div>

    <div class="glass panel mt"><h3>Rendimiento de contenido <span class="empty">· descargas</span></h3>
      <div class="hbars"><p v-if="!contenido.length" class="empty">Sin recursos aún.</p>
        <div v-for="c in contenido" :key="c.titulo" class="hbar"><span :title="c.titulo">{{ c.titulo }}</span>
          <div class="hbar-track"><div class="hbar-fill" :style="{width:pctMax(c.downloads,contenido,'downloads')+'%'}"></div></div>
          <span class="hbar-val">{{ c.downloads }}</span></div></div></div>

    <div class="glass panel mt"><h3>Últimos leads</h3>
      <table><thead><tr><th>Nombre</th><th>Empresa</th><th>Estado</th><th>Origen</th><th>Fecha</th></tr></thead><tbody>
        <tr v-for="l in ultimos" :key="l.id" class="row" @click="$router.push('/admin/leads/'+l.id)">
          <td style="color:var(--neutral-light)">{{ l.name }}</td><td>{{ l.company||'—' }}</td><td><span class="badge" :class="l.status">{{ estadoLabel(l.status) }}</span></td><td>{{ l.source||'—' }}</td><td class="small">{{ (l.created_at||'').slice(0,16) }}</td></tr>
        <tr v-if="!ultimos.length"><td colspan="5" class="empty" style="text-align:center;padding:1.4rem">Aún sin leads.</td></tr></tbody></table></div>
  </div>`,
  data(){ return { kpis:{}, ultimos:[], timer:null,
    embudo:{etapas:[],tasas:{}}, tendencia:[], fuentes:[], canales:[], industrias:[], tamanos:[], paises:[], pipeline:[], desafios:[], soluciones:[], contenido:[],
    kpiList:[
      {key:'leads',label:'Leads totales',action:()=>this.$router.push('/admin/leads')},
      {key:'leads_nuevos',label:'Leads nuevos',ctx:'Clic para gestionar',action:()=>this.$router.push('/admin/leads?status=nuevo')},
      {key:'interacciones',label:'Interacciones'},
      {key:'reservas_proximas',label:'Sesiones próximas',action:()=>this.$router.push('/admin/reservas')},
      {key:'descargas',label:'Descargas'},
      {key:'conversaciones_ia',label:'Conversaciones IA'},
    ] }; },
  computed:{ tasas(){ return this.embudo.tasas || {}; } },
  methods:{
    async load(){
      const [r, a] = await Promise.all([api.get('/admin/resumen'), api.get('/admin/analitica')]);
      if(r.ok){ this.kpis=r.data.kpis; this.ultimos=r.data.ultimos; }
      if(a.ok){ const d=a.data; this.embudo=d.embudo; this.tendencia=d.tendencia; this.fuentes=d.fuentes; this.canales=d.canales; this.industrias=d.industrias; this.tamanos=d.tamanos; this.paises=d.paises; this.pipeline=d.pipeline; this.desafios=d.desafios; this.soluciones=d.soluciones; this.contenido=d.contenido; }
    },
    irEstado(st){ if(st) this.$router.push('/admin/leads?status='+st); },
    funnelPct(v){ const top=this.embudo.etapas[0]?this.embudo.etapas[0].valor:0; return top>0?Math.round(v/top*100):0; },
    pctMax(v,arr,key){ const k=key||'total'; const m=Math.max(1,...arr.map(x=>x[k])); return Math.max(3,v/m*100); },
    linea(key,color){ const n=this.tendencia.length; if(!n) return ''; const max=Math.max(1,...this.tendencia.flatMap(x=>[x.leads,x.interacciones]));
      return this.tendencia.map((d,i)=>{ const x=n>1?i/(n-1)*320:160; const y=150-d[key]/max*138-6; return x.toFixed(1)+','+y.toFixed(1); }).join(' '); },
    estadoLabel(s){ return {nuevo:'Nuevo',contactado:'Contactado',calificado:'Calificado',propuesta:'Propuesta',cliente:'Cliente',descartado:'Descartado'}[s]||s; },
    sizeLabel(s){ return {micro:'Micro',pequena:'Pequeña',mediana:'Mediana',grande:'Grande',corporativa:'Corporativa'}[s]||s; },
    desafioLabel(s){ return {crecimiento:'Crecimiento',automatizacion:'Automatización',datos:'Datos',estrategia:'Estrategia',otro:'Otro'}[s]||s; },
    solucionLabel(s){ return {estrategia:'Estrategia & Roadmap',automatizacion:'Automatización',datos:'Datos & Analítica',growth:'Growth'}[s]||s; },
  },
  mounted(){ this.load(); this.timer=setInterval(()=>this.load(),20000); },   // Short polling (Framework §3.B)
  unmounted(){ clearInterval(this.timer); },
};

export const Leads = {
  components: { Icon },
  template: `<div><h1>Leads</h1><p class="adm__sub">CRM · {{ total }} registros</p>
    <div class="toolbar">
      <input class="inp" v-model="q" @input="debounced" placeholder="Buscar nombre, correo o empresa…">
      <select class="inp" v-model="status" @change="reload"><option value="">Todos los estados</option><option v-for="(v,k) in estados" :key="k" :value="k">{{ v }}</option></select>
      <select class="inp" v-model="channel" @change="reload"><option value="">Todos los canales</option><option value="web">Web</option><option value="telegram">Telegram</option><option value="whatsapp">WhatsApp</option></select>
      <button class="btn btn-ghost btn-sm" style="margin-left:auto" @click="exportar"><Icon name="doc" :size="15"/> Exportar CSV</button></div>
    <div class="glass panel" style="padding:.6rem 1rem 1rem">
      <table><thead><tr><th>Lead</th><th>Contacto</th><th>Industria</th><th>Estado</th><th>Interacc.</th><th>Actualizado</th></tr></thead>
        <tbody><tr v-for="l in items" :key="l.id" class="row" @click="$router.push('/admin/leads/'+l.id)">
          <td><b style="color:var(--neutral-light)">{{ l.name }}</b><br><span class="small">{{ l.company||'' }}</span></td>
          <td>{{ l.email||'' }}<br><a v-if="l.phone_wa" :href="'https://wa.me/'+l.phone_wa" target="_blank" @click.stop>+{{ l.phone_wa }}</a></td>
          <td>{{ industria(l.industry) }}</td><td><span class="badge" :class="l.status">{{ estados[l.status]||l.status }}</span></td><td>{{ l.touchpoints }}</td><td class="small">{{ (l.updated_at||'').slice(0,16) }}</td></tr>
        <tr v-if="!items.length"><td colspan="6" class="small" style="text-align:center;padding:2rem">No hay leads con estos filtros.</td></tr></tbody></table>
      <div class="pager"><span>Página {{ page }} de {{ pages }}</span>
        <button :disabled="page<=1" @click="go(page-1)">‹</button><button :disabled="page>=pages" @click="go(page+1)">›</button></div></div>
  </div>`,
  data(){ return { items:[], total:0, page:1, pages:1, q:'', status:this.$route.query.status||'', channel:'',
    estados:{nuevo:'Nuevo',contactado:'Contactado',calificado:'Calificado',propuesta:'Propuesta',cliente:'Cliente',descartado:'Descartado'}, _t:null }; },
  methods:{
    async load(){ const p=new URLSearchParams({page:this.page,per_page:20,q:this.q,status:this.status,channel:this.channel});
      const r=await api.get('/admin/leads?'+p); if(r.ok){ this.items=r.data; this.total=r.pagination.total; this.pages=r.pagination.pages; } },
    reload(){ this.page=1; this.load(); }, go(p){ this.page=p; this.load(); },
    debounced(){ clearTimeout(this._t); this._t=setTimeout(()=>this.reload(),350); },
    industria(k){ return {tecnologia:'Tecnología',retail:'Retail',financiero:'Financiero',salud:'Salud',manufactura:'Manufactura',educacion:'Educación',logistica:'Logística',agroindustria:'Agroindustria',turismo:'Turismo',profesionales:'Servicios',construccion:'Construcción',energia:'Energía',gobierno:'Gobierno',medios:'Medios',otro:'Otra'}[k]||(k||'—'); },
    async exportar(){ const r=await api.get('/admin/leads-export?status='+this.status+'&channel='+this.channel+'&q='+encodeURIComponent(this.q));
      if(r.ok){ exportCSV('leads-experientia.csv', r.data.rows, ['name','email','phone_wa','country','company','role','industry','company_size','status','source','channel','created_at']); toast('CSV exportado.'); } },
  },
  mounted(){ this.load(); },
};

export const LeadDetail = {
  components: { Icon },
  template: `<div v-if="lead"><button class="btn btn-ghost btn-sm" @click="$router.push('/admin/leads')" style="margin-bottom:1rem">← Volver</button>
    <h1>{{ lead.name }} <span v-if="lead.company" style="color:var(--text-secondary);font-size:1rem">· {{ lead.company }}</span></h1>
    <div class="panels" style="margin-top:1.2rem"><div class="glass panel">
      <div class="form-grid two">
        <div class="field"><label>Correo</label><input class="inp" :value="lead.email||'—'" readonly></div>
        <div class="field"><label>WhatsApp</label><input class="inp" :value="lead.phone_wa?'+'+lead.phone_wa:'—'" readonly></div>
        <div class="field"><label>País · Industria</label><input class="inp" :value="(lead.country||'—')+' · '+(lead.industry||'—')" readonly></div>
        <div class="field"><label>Tamaño · Idioma</label><input class="inp" :value="(lead.company_size||'—')+' · '+(lead.locale||'es').toUpperCase()" readonly></div>
        <div class="field"><label>Estado</label><select class="inp" v-model="lead.status"><option v-for="(v,k) in estados" :key="k" :value="k">{{ v }}</option></select></div>
        <div class="field"><label>Origen · Canal</label><input class="inp" :value="(lead.source||'—')+' · '+(lead.channel||'web')" readonly></div></div>
      <div class="field" style="margin-top:1rem"><label>Notas internas</label><textarea class="inp" rows="4" v-model="lead.notes"></textarea></div>
      <div style="display:flex;gap:.7rem;margin-top:1rem;align-items:center">
        <button class="btn btn-primary btn-sm" @click="guardar">Guardar</button>
        <a v-if="lead.phone_wa" class="btn btn-ghost btn-sm" :href="'https://wa.me/'+lead.phone_wa" target="_blank">WhatsApp</a>
        <button class="btn btn-danger btn-sm" style="margin-left:auto" @click="eliminar">Eliminar</button></div></div>
      <div class="glass panel"><h3>Historial ({{ touchpoints.length }})</h3><div class="timeline">
        <div v-for="tp in touchpoints" :key="tp.id" class="glass tl"><div><span class="badge nuevo">{{ tipoTp(tp.type) }}</span> <b style="color:var(--neutral-light)">{{ tp.title }}</b></div>
          <p class="small" v-if="detalle(tp)">{{ detalle(tp) }}</p><time>{{ tp.created_at }}</time></div>
        <p v-if="!touchpoints.length" class="small">Sin interacciones.</p></div>
        <template v-if="reservas.length"><h3 style="margin-top:1.4rem">Sesiones</h3><div class="timeline">
          <div v-for="b in reservas" :key="b.id" class="glass tl"><span class="badge" :class="b.status">{{ b.status }}</span> <b style="color:var(--neutral-light)">{{ b.starts_at }} UTC</b><p class="small" v-if="b.tema">{{ b.tema }}</p></div></div></template></div></div>
  </div>`,
  data(){ return { lead:null, touchpoints:[], reservas:[], estados:{nuevo:'Nuevo',contactado:'Contactado',calificado:'Calificado',propuesta:'Propuesta',cliente:'Cliente',descartado:'Descartado'} }; },
  methods:{
    async load(){ const r=await api.get('/admin/leads/'+this.$route.params.id); if(r.ok){ this.lead=r.data.lead; this.touchpoints=r.data.touchpoints; this.reservas=r.data.reservas; } },
    async guardar(){ const r=await api.patch('/admin/leads/'+this.lead.id,{status:this.lead.status,notes:this.lead.notes}); toast(r.ok?'Guardado.':(r.error||'Error'),r.ok?'ok':'err'); },
    async eliminar(){ if(!confirm('¿Eliminar este lead y todo su historial?'))return; const r=await api.del('/admin/leads/'+this.lead.id); if(r.ok){ toast('Eliminado.'); this.$router.push('/admin/leads'); } },
    tipoTp(t){ return {contacto:'Contacto',descarga:'Descarga',diagnostico:'Diagnóstico',reserva:'Reserva',newsletter:'Newsletter',telegram:'Telegram',whatsapp:'WhatsApp'}[t]||t; },
    detalle(tp){ let p={}; try{ p=JSON.parse(tp.payload)||{}; }catch(e){} return Object.entries(p).filter(([k,v])=>v&&typeof v!=='object').map(([k,v])=>k+': '+String(v).slice(0,200)).join(' · '); },
  },
  mounted(){ this.load(); },
};
