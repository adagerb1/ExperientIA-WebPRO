// Portal admin · Reservas, Contenido (CRUD trilingüe), Conectores, Plantillas, AlexIA interno
import { api, store, toast, CMS_LANGS, CMS_CODES } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { SmartTable } from './table.js';
const escT = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));

export const Reservas = {
  components:{ SmartTable },
  template: `<div><h1>Reservas</h1><p class="adm__sub">Sesiones 1:1 agendadas · ordena por cualquier columna</p>
    <div class="toolbar"><input class="inp" v-model="q" placeholder="Buscar lead, correo o tema…" style="max-width:300px"></div>
    <SmartTable :columns="cols" :rows="items" :search="q" :searchKeys="['lead_name','lead_email','tema']" :clickable="false">
      <template #actions="{row}"><select class="inp" style="width:auto" :value="row.status" @change="cambiar(row,$event.target.value)">
        <option>confirmada</option><option>realizada</option><option>cancelada</option></select></template></SmartTable></div>`,
  data(){ return { items:[], q:'',
    cols:[
      { key:'starts_at', label:'Fecha (UTC)' },
      { key:'lead_name', label:'Lead', render:(b)=>'<b style="color:var(--neutral-light)">'+escT(b.lead_name)+'</b><br><span class="small">'+escT(b.lead_email||'')+'</span>' },
      { key:'tema', label:'Tema', render:(b)=>'<span class="small">'+escT((b.tema||'').slice(0,120))+'</span>' },
      { key:'status', label:'Estado', render:(b)=>'<span class="badge '+escT(b.status)+'">'+escT(b.status)+'</span>' },
    ] }; },
  methods:{ async load(){ const r=await api.get('/admin/reservas'); if(r.ok) this.items=r.data; },
    async cambiar(b,v){ const r=await api.patch('/admin/reservas/'+b.id,{status:v}); toast(r.ok?'Actualizado.':(r.error||'Error'),r.ok?'ok':'err'); if(r.ok) b.status=v; } },
  mounted(){ this.load(); },
};

// Editor trilingüe genérico de contenido
const MODULOS = {
  solutions:{titulo:'Soluciones',list:['titulo','skey','active'],campos:[
    {n:'skey',l:'Clave interna',t:'text'},{n:'icon',l:'Ícono',t:'sel',op:['target','gear','analitica','growth','ia','bulb','cube','people','shield']},
    {n:'titulo',l:'Título',t:'i18n'},{n:'pilar',l:'Pilar',t:'i18n'},{n:'problema',l:'El problema',t:'i18ta'},{n:'como',l:'Cómo (un punto por línea)',t:'i18ta'},{n:'cambia',l:'Qué cambia',t:'i18ta'},
    {n:'sort',l:'Orden',t:'num'},{n:'active',l:'Activa',t:'bool'}]},
  products:{titulo:'Productos',list:['nombre','rol','active'],campos:[
    {n:'icon',l:'Ícono',t:'sel',op:['analitica','target','gear','people','cube','growth']},{n:'nombre',l:'Nombre',t:'i18n'},{n:'rol',l:'Rol',t:'i18n'},{n:'texto',l:'Descripción',t:'i18ta'},{n:'destacado',l:'Destacado',t:'bool'},{n:'sort',l:'Orden',t:'num'},{n:'active',l:'Activo',t:'bool'}]},
  case_studies:{titulo:'Casos',list:['titulo','sector','active'],campos:[
    {n:'sector',l:'Sector',t:'i18n'},{n:'titulo',l:'Título',t:'i18n'},{n:'contexto',l:'Contexto',t:'i18ta'},{n:'intervencion',l:'Intervención',t:'i18ta'},{n:'resultados',l:'Resultados (JSON)',t:'json'},{n:'sort',l:'Orden',t:'num'},{n:'active',l:'Activo',t:'bool'}]},
  faqs:{titulo:'FAQs',list:['pregunta','active'],campos:[{n:'pregunta',l:'Pregunta',t:'i18n'},{n:'respuesta',l:'Respuesta',t:'i18ta'},{n:'sort',l:'Orden',t:'num'},{n:'active',l:'Activa',t:'bool'}]},
  resources:{titulo:'Recursos',list:['titulo','type','downloads','active'],campos:[
    {n:'slug',l:'Slug (URL)',t:'text'},{n:'type',l:'Tipo',t:'sel',op:['article','download']},{n:'tipo_label',l:'Etiqueta',t:'i18n'},{n:'titulo',l:'Título',t:'i18n'},{n:'extracto',l:'Extracto',t:'i18ta'},{n:'cuerpo',l:'Contenido (HTML)',t:'i18ta'},{n:'file_path',l:'Archivo PDF',t:'file'},{n:'published_at',l:'Publicado (YYYY-MM-DD HH:MM:SS)',t:'text'},{n:'sort',l:'Orden',t:'num'},{n:'active',l:'Activo',t:'bool'}]},
  availability_rules:{titulo:'Disponibilidad',list:['weekday','start_time','end_time','active'],campos:[
    {n:'weekday',l:'Día (1=Lun … 7=Dom)',t:'num'},{n:'start_time',l:'Desde (HH:MM)',t:'text'},{n:'end_time',l:'Hasta (HH:MM)',t:'text'},{n:'active',l:'Activa',t:'bool'}]},
  industries:{titulo:'Industrias',list:['nombre','ikey','active'],campos:[
    {n:'ikey',l:'Clave interna',t:'text'},{n:'nombre',l:'Nombre',t:'i18n'},{n:'sort',l:'Orden',t:'num'},{n:'active',l:'Activa',t:'bool'}]},
  campaign_templates:{titulo:'Plantillas de campaña',list:['nombre','canal','active'],campos:[
    {n:'nombre',l:'Nombre',t:'text'},{n:'canal',l:'Canal',t:'sel',op:['email','whatsapp']},
    {n:'asunto',l:'Asunto (solo correo)',t:'i18n'},{n:'cuerpo',l:'Mensaje · usa {nombre} y {empresa}',t:'i18ta'},
    {n:'sort',l:'Orden',t:'num'},{n:'active',l:'Activa',t:'bool'}]},
};

const DOW = { 1:'Lunes',2:'Martes',3:'Miércoles',4:'Jueves',5:'Viernes',6:'Sábado',7:'Domingo' };
const txtOf = (v) => { if (v && typeof v === 'object') { return v.es || Object.values(v)[0] || ''; } return String(v == null ? '' : v); };
const blankI18n = () => { const o = {}; for (const c of CMS_CODES) o[c] = ''; return o; };

export const Contenido = {
  components: { Icon, SmartTable },
  props: ['modulo'],
  template: `<div><h1>{{ M.titulo }}</h1><p class="adm__sub">Contenido del sitio · edición trilingüe (ES obligatorio; EN/PT vacíos muestran ES)</p>
    <div class="toolbar"><button class="btn btn-primary btn-sm" @click="nuevo"><Icon name="plug" :size="14"/> Crear</button>
      <input class="inp" v-model="q" placeholder="Buscar…" style="max-width:240px"></div>
    <SmartTable :columns="cols" :rows="items" :search="q" :searchKeys="searchKeys" @rowClick="editar"/>
    <div class="modal-bg" v-if="editing" @click.self="editing=null"><div class="glass modal modal-lg">
      <h2>{{ form.id?'Editar':'Crear' }} · {{ M.titulo }}</h2>

      <div class="cms-ai glass" v-if="tieneRedactables">
        <div class="cms-ai-head"><span class="cms-ai-ava"><Icon name="sparkle" :size="15"/></span><b>Redactar con AlexIA</b>
          <span class="small">Describe qué quieres y AlexIA completa los campos en los {{ langs.length }} idiomas.</span></div>
        <div class="cms-ai-row"><textarea class="inp" v-model="aiPrompt" rows="2" :placeholder="aiPh" @keydown.enter.exact.prevent="redactarIA"></textarea>
          <button class="btn btn-grad btn-sm" @click="redactarIA" :disabled="aiLoading"><Icon name="sparkle" :size="14"/> {{ aiLoading?'Redactando…':'Redactar' }}</button></div>
      </div>

      <div class="lang-tabs" v-if="tieneI18n"><button v-for="l in langs" :key="l.code" type="button" :class="{active:lang===l.code}" @click="lang=l.code">{{ l.label }}<span v-if="l.code==='es'" class="lang-req">·oblig</span></button></div>

      <div class="form-grid">
        <template v-for="c in M.campos" :key="c.n">
          <div v-if="c.t==='i18n'||c.t==='i18ta'" class="field" :style="c.t==='i18ta'?'grid-column:1/-1':''">
            <label>{{ c.l }} <span class="lang-chip">{{ lang.toUpperCase() }}</span><span v-if="lang==='es'" style="color:var(--cyan)"> · obligatorio</span></label>
            <input v-if="c.t==='i18n'" class="inp" v-model="form[c.n][lang]"><textarea v-else class="inp" rows="3" v-model="form[c.n][lang]"></textarea></div>
          <div v-else-if="c.t==='sel'" class="field"><label>{{ c.l }}</label><select class="inp" v-model="form[c.n]"><option v-for="o in c.op" :key="o" :value="o">{{ o }}</option></select></div>
          <div v-else-if="c.t==='bool'" class="field"><label>{{ c.l }}</label><label class="switch"><input type="checkbox" v-model="form[c.n]"><span></span></label></div>
          <div v-else-if="c.t==='num'" class="field"><label>{{ c.l }}</label><input class="inp" type="number" v-model="form[c.n]"></div>
          <div v-else-if="c.t==='json'" class="field" style="grid-column:1/-1"><label>{{ c.l }}</label><textarea class="inp" rows="5" v-model="form[c.n]"></textarea></div>
          <div v-else-if="c.t==='file'" class="field"><label>{{ c.l }}</label><input type="file" accept="application/pdf" @change="subir($event)"><p class="small" v-if="form[c.n]">Actual: {{ form[c.n] }}</p></div>
          <div v-else class="field"><label>{{ c.l }}</label><input class="inp" v-model="form[c.n]"></div>
        </template></div>
      <div style="display:flex;gap:.7rem;align-items:center"><button class="btn btn-primary btn-sm" @click="guardar">Guardar</button>
        <button class="btn btn-ghost btn-sm" @click="editing=null">Cancelar</button>
        <button v-if="form.id" class="btn btn-danger btn-sm" style="margin-left:auto" @click="eliminar">Eliminar</button></div></div></div>
  </div>`,
  data(){ return { items:[], editing:false, form:{}, q:'', lang:'es', langs:CMS_LANGS, aiPrompt:'', aiLoading:false }; },
  computed:{
    M(){ return MODULOS[this.modulo]; },
    tieneI18n(){ return this.M.campos.some(c=>c.t==='i18n'||c.t==='i18ta'); },
    tieneRedactables(){ return this.M.campos.some(c=>c.t==='i18n'||c.t==='i18ta'||c.t==='sel'||c.t==='text'); },
    aiPh(){ const ej={ solutions:'Una solución sobre analítica predictiva para retail…', products:'Un producto de tablero para el comité ejecutivo…', case_studies:'Un caso de una fintech que automatizó su onboarding…', faqs:'Una FAQ sobre cuánto tarda ver resultados…', industries:'El nombre del sector salud…', campaign_templates:'Un correo de bienvenida cálido que invite a agendar…' }; return ej[this.modulo]||'Describe el contenido que quieres…'; },
    cols(){ return this.M.list.map(c=>({ key:c, label:c,
      raw:(it)=> c==='active'?Number(it[c]) : c==='weekday'?Number(it[c]) : txtOf(it[c]).toLowerCase(),
      render:(it)=> c==='active'?(Number(it[c])?'✓':'—') : c==='weekday'?escT(DOW[it[c]]||it[c]) : escT(txtOf(it[c]).slice(0,80)) })); },
    searchKeys(){ return this.M.list.map(c=> (it)=> txtOf(it[c])); },
  },
  watch:{ modulo(){ this.load(); } },
  methods:{
    async load(){ const r=await api.get('/admin/'+this.modulo+'/list'); if(r.ok) this.items=r.data; },
    txt(v){ if(v&&typeof v==='object') return (v.es||Object.values(v)[0]||'').slice(0,80); return String(v==null?'':v).slice(0,80); },
    dow(n){ return {1:'Lunes',2:'Martes',3:'Miércoles',4:'Jueves',5:'Viernes',6:'Sábado',7:'Domingo'}[n]||n; },
    blank(){ const f={}; for(const c of this.M.campos){ f[c.n]= (c.t==='i18n'||c.t==='i18ta')?blankI18n(): c.t==='bool'?true: c.t==='num'?0: c.t==='json'?'[]':''; } return f; },
    nuevo(){ this.form=this.blank(); this.lang='es'; this.aiPrompt=''; this.editing=true; },
    editar(it){ const f=this.blank(); for(const c of this.M.campos){ let v=it[c.n];
      if(c.t==='i18n'||c.t==='i18ta'){ const o=blankI18n(); if(v&&typeof v==='object'){ for(const k of CMS_CODES) o[k]=v[k]||''; } f[c.n]=o; }
      else if(c.t==='bool'){ f[c.n]=!!Number(v); } else if(c.t==='json'){ f[c.n]=JSON.stringify(v||[],null,1); } else { f[c.n]=v==null?'':v; } }
      f.id=it.id; this.form=f; this.lang='es'; this.aiPrompt=''; this.editing=true; },
    async redactarIA(){
      if(!this.aiPrompt.trim()){ toast('Escribe qué quieres que redacte AlexIA.','err'); return; }
      this.aiLoading=true;
      const r=await api.post('/admin/cms/redactar',{ tabla:this.modulo, instruccion:this.aiPrompt, campos:this.M.campos });
      this.aiLoading=false;
      if(r.ok){ const c=r.data.campos||{};
        for(const k in c){ const def=this.M.campos.find(x=>x.n===k); if(!def) continue;
          if(def.t==='i18n'||def.t==='i18ta'){ const o=blankI18n(); for(const l of CMS_CODES) o[l]=(c[k]&&c[k][l])||(this.form[k]&&this.form[k][l])||''; this.form[k]=o; }
          else { this.form[k]=c[k]; } }
        toast('AlexIA completó los campos. Revísalos y guarda.'); }
      else toast(r.error||'AlexIA no disponible. Configura OpenAI/Anthropic.','err'); },
    async subir(e){ const file=e.target.files[0]; if(!file)return; const fd=new FormData(); fd.append('archivo',file);
      const r=await api.upload('/admin/archivo',fd); if(r.ok){ this.form.file_path=r.data.file_path; toast('PDF subido.'); } else toast(r.error||'Error','err'); },
    async guardar(){ const payload={}; for(const c of this.M.campos){ let v=this.form[c.n];
        if(c.t==='bool') v=v?1:0; else if(c.t==='num') v=Number(v)||0; else if(c.t==='json'){ try{ v=JSON.parse(v||'[]'); }catch(e){ toast('JSON inválido en '+c.l,'err'); return; } }
        payload[c.n]=v; }
      const r=this.form.id? await api.put('/admin/'+this.modulo+'/'+this.form.id,payload) : await api.post('/admin/'+this.modulo,payload);
      if(r.ok){ toast('Guardado.'); this.editing=null; this.load(); } else toast(r.error||'Error','err'); },
    async eliminar(){ if(!confirm('¿Eliminar este registro?'))return; const r=await api.del('/admin/'+this.modulo+'/'+this.form.id); if(r.ok){ toast('Eliminado.'); this.editing=null; this.load(); } },
  },
  mounted(){ this.load(); },
};

export const Conectores = {
  components: { Icon },
  template: `<div><h1>Conectores</h1><p class="adm__sub">Integra IA, pagos, correo, bots y agenda · las credenciales se guardan cifradas en el servidor</p>
    <div class="conn-kpis">
      <div class="glass stat"><b class="grad-text">{{ summary.disponibles||0 }}</b><span>Disponibles</span></div>
      <div class="glass stat"><b class="grad-text">{{ summary.configurados||0 }}</b><span>Con credenciales</span></div>
      <div class="glass stat"><b class="grad-text">{{ summary.activos||0 }}</b><span>En uso</span></div></div>

    <div class="conn-tabs">
      <button :class="{active:tab===''}" @click="tab=''">Todos <em>{{ items.length }}</em></button>
      <button v-for="g in groups" :key="g.key" :class="{active:tab===g.key}" @click="tab=g.key">{{ g.label }} <em>{{ countGroup(g.key) }}</em></button></div>

    <div class="conn-grid">
      <div v-for="c in filtered" :key="c.provider" class="glass conn-card">
        <div class="conn-head"><div><b>{{ c.nombre }}</b><p class="conn-desc">{{ c.desc }}</p></div>
          <div class="conn-badges"><span v-if="c.status!=='sin_configurar'" class="badge cliente">Configurado</span><span v-if="c.enabled" class="badge ok">Activo</span></div></div>
        <div class="field" v-for="f in c.campos" :key="f.n">
          <label>{{ f.l }} <span v-if="f.t==='secret' && c.saved[f.n]" class="saved">guardado ✓</span></label>
          <select v-if="f.t==='select'" class="inp" v-model="c.config[f.n]"><option v-for="o in f.op" :key="o" :value="o">{{ o }}</option></select>
          <input v-else-if="f.t==='secret'" class="inp" type="password" v-model="c.draft[f.n]" :placeholder="c.saved[f.n]?'Guardado — escribe para cambiar':(f.ph||'')" autocomplete="new-password">
          <input v-else class="inp" type="text" v-model="c.config[f.n]" :placeholder="f.ph||''">
          <p v-if="f.help" class="hint">{{ f.help }}</p></div>
        <label class="switch-row"><label class="switch"><input type="checkbox" v-model="c.enabled" :true-value="1" :false-value="0"><span></span></label> Activar</label>
        <div class="conn-actions">
          <button v-for="a in c.acciones" :key="a.k" class="btn btn-ghost btn-sm" @click="accion(c,a.k)">{{ a.l }}</button>
          <button class="btn btn-primary btn-sm" @click="guardar(c)">Guardar</button></div>
        <p v-if="c._msg" class="small" :style="{color:c._ok?'#4be3a0':'#ff7d9d'}">{{ c._msg }}</p></div></div></div>`,
  data(){ return { items:[], groups:[], summary:{}, tab:'' }; },
  computed:{ filtered(){ return this.tab ? this.items.filter(c=>c.grupo===this.tab) : this.items; } },
  methods:{
    async load(){ const r=await api.get('/admin/connectors'); if(!r.ok) return;
      this.groups=r.data.groups; this.summary=r.data.summary;
      this.items=r.data.items.map(c=>{ const cfg={...c.config};
        c.campos.forEach(f=>{ if(f.t==='select' && !cfg[f.n]) cfg[f.n]=f.op[0]; });
        return {...c, config:cfg, draft:{}, _msg:'', _ok:false }; }); },
    countGroup(k){ return this.items.filter(c=>c.grupo===k).length; },
    async guardar(c){ const p={enabled:c.enabled};
      for(const f of c.campos){ if(f.transient) continue;
        if(f.t==='secret'){ if(c.draft[f.n]) p[f.n]=c.draft[f.n]; } else { p[f.n]=c.config[f.n]??''; } }
      const r=await api.put('/admin/connectors/'+c.provider,p);
      if(r.ok){ toast(c.nombre+' guardado.'); this.load(); } else toast(r.error||'Error','err'); },
    async accion(c,k){ c._msg='Ejecutando…'; c._ok=false;
      const body={}; for(const f of c.campos){ if(f.transient) body[f.n]=c.config[f.n]||c.draft[f.n]||''; }
      const r=await api.post('/admin/connectors/'+c.provider+'/accion/'+k,body);
      c._ok=!!(r.data&&r.data.ok); c._msg=(r.data&&(r.data.message||r.data.error))||r.error||'—';
      if(c._ok&&k==='test') c.status='ok'; },
  },
  mounted(){ this.load(); },
};

export const Plantillas = {
  template: `<div><h1>Plantillas de email</h1><p class="adm__sub">Correos automáticos (SendGrid o servidor) · variables entre <code v-pre>{{ ... }}</code></p>
    <div style="display:grid;gap:1rem">
      <div v-for="tpl in items" :key="tpl.tkey" class="glass panel"><h3>{{ nombre(tpl.tkey) }}</h3>
        <div class="tabs" style="margin-bottom:.8rem"><button v-for="l in ['es','en','pt']" :key="l" :class="{active:tab[tpl.tkey]===l}" @click="tab[tpl.tkey]=l">{{ l.toUpperCase() }}</button></div>
        <div class="field" style="margin-bottom:.7rem"><label>Asunto</label><input class="inp" v-model="tpl.subject[cur(tpl.tkey)]"></div>
        <div class="field"><label>Cuerpo (HTML)</label><textarea class="inp" rows="5" v-model="tpl.body[cur(tpl.tkey)]"></textarea></div>
        <button class="btn btn-primary btn-sm" style="margin-top:.8rem" @click="guardar(tpl)">Guardar</button></div></div></div>`,
  data(){ return { items:[], tab:{} }; },
  methods:{
    async load(){ const r=await api.get('/admin/email-templates'); if(r.ok){ this.items=r.data.map(t=>({...t,subject:t.subject||{es:'',en:'',pt:''},body:t.body||{es:'',en:'',pt:''}})); this.items.forEach(t=>this.tab[t.tkey]='es'); } },
    cur(k){ return this.tab[k]||'es'; },
    nombre(k){ return {lead_notify:'Notificación de lead (interno)',booking_confirm:'Confirmación de reserva',resource_delivery:'Entrega de recurso',newsletter_welcome:'Bienvenida al newsletter'}[k]||k; },
    async guardar(tpl){ const r=await api.put('/admin/email-templates/'+tpl.tkey,{subject:tpl.subject,body:tpl.body}); toast(r.ok?'Guardado.':(r.error||'Error'),r.ok?'ok':'err'); },
  },
  mounted(){ this.load(); },
};
