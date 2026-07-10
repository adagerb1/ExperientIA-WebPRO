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

export const Dashboard = {
  components: { Icon },
  template: `<div><h1>Resumen</h1><p class="adm__sub">Centro de decisiones · datos en vivo (actualiza cada 20 s)</p>
    <div class="kpis">
      <button v-for="k in kpiList" :key="k.key" class="glass kpi" @click="k.action && k.action()">
        <b class="grad-text">{{ kpis[k.key] ?? '—' }}</b><span>{{ k.label }}</span><span class="ctx" v-if="k.ctx">{{ k.ctx }}</span></button></div>
    <div class="panels">
      <div class="glass panel"><h3>Leads por día (14 días)</h3>
        <div class="chart-bars"><span v-for="(d,i) in tendencia" :key="i" :style="{height:barH(d.c)+'%'}" :title="d.d+': '+d.c"></span></div></div>
      <div class="glass panel"><h3>Distribución por estado</h3>
        <div class="dist"><div v-for="(c,st) in porEstado" :key="st" class="dist-row">
          <span class="badge" :class="st">{{ estadoLabel(st) }}</span><div><div class="dist-bar" :style="{width:pct(c)+'%'}"></div></div><span>{{ c }}</span></div>
          <p v-if="!Object.keys(porEstado).length" class="adm__sub" style="margin:0">Aún sin leads.</p></div></div></div>
    <div class="glass panel" style="margin-top:1.2rem"><h3>Últimos leads</h3>
      <table><thead><tr><th>Nombre</th><th>Empresa</th><th>Estado</th><th>Origen</th><th>Fecha</th></tr></thead><tbody>
        <tr v-for="l in ultimos" :key="l.id" class="row" @click="$router.push('/admin/leads/'+l.id)">
          <td style="color:var(--neutral-light)">{{ l.name }}</td><td>{{ l.company||'—' }}</td><td><span class="badge" :class="l.status">{{ estadoLabel(l.status) }}</span></td><td>{{ l.source||'—' }}</td><td class="small">{{ (l.created_at||'').slice(0,16) }}</td></tr></tbody></table></div>
  </div>`,
  data(){ return { kpis:{}, tendencia:[], porEstado:{}, ultimos:[], timer:null,
    kpiList:[
      {key:'leads',label:'Leads totales',action:()=>this.$router.push('/admin/leads')},
      {key:'leads_nuevos',label:'Leads nuevos',ctx:'Clic para gestionar',action:()=>this.$router.push('/admin/leads?status=nuevo')},
      {key:'interacciones',label:'Interacciones'},
      {key:'reservas_proximas',label:'Sesiones próximas',action:()=>this.$router.push('/admin/reservas')},
      {key:'descargas',label:'Descargas'},
      {key:'conversaciones_ia',label:'Conversaciones IA'},
    ] }; },
  methods:{
    async load(){ const r=await api.get('/admin/resumen'); if(r.ok){ this.kpis=r.data.kpis; this.tendencia=r.data.tendencia; this.porEstado=r.data.por_estado; this.ultimos=r.data.ultimos; } },
    barH(c){ const max=Math.max(1,...this.tendencia.map(x=>x.c)); return Math.max(3,c/max*100); },
    pct(c){ const tot=Object.values(this.porEstado).reduce((a,b)=>a+b,0)||1; return c/tot*100; },
    estadoLabel(s){ return {nuevo:'Nuevo',contactado:'Contactado',calificado:'Calificado',propuesta:'Propuesta',cliente:'Cliente',descartado:'Descartado'}[s]||s; },
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
