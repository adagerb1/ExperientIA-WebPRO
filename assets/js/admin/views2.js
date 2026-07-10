// Portal admin · Reservas, Contenido (CRUD trilingüe), Conectores, Plantillas, AlexIA interno
import { api, store, toast } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

export const Reservas = {
  template: `<div><h1>Reservas</h1><p class="adm__sub">Sesiones 1:1 agendadas</p>
    <div class="glass panel" style="padding:.6rem 1rem 1rem"><table><thead><tr><th>Fecha (UTC)</th><th>Lead</th><th>Tema</th><th>Estado</th></tr></thead><tbody>
      <tr v-for="b in items" :key="b.id"><td>{{ b.starts_at }}</td><td><b style="color:var(--neutral-light)">{{ b.lead_name }}</b><br><span class="small">{{ b.lead_email }}</span></td>
        <td class="small">{{ (b.tema||'').slice(0,120) }}</td><td><select class="inp" style="width:auto" :value="b.status" @change="cambiar(b,$event.target.value)"><option>confirmada</option><option>realizada</option><option>cancelada</option></select></td></tr>
      <tr v-if="!items.length"><td colspan="4" class="small" style="text-align:center;padding:2rem">Sin reservas.</td></tr></tbody></table></div></div>`,
  data(){ return { items:[] }; },
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
};

export const Contenido = {
  components: { Icon },
  props: ['modulo'],
  template: `<div><h1>{{ M.titulo }}</h1><p class="adm__sub">Contenido del sitio · edición trilingüe (ES obligatorio; EN/PT vacíos muestran ES)</p>
    <div class="toolbar"><button class="btn btn-primary btn-sm" @click="nuevo"><Icon name="plug" :size="14"/> Crear</button><span class="small">{{ items.length }} registros</span></div>
    <div class="glass panel" style="padding:.6rem 1rem 1rem"><table><thead><tr><th v-for="c in M.list" :key="c">{{ c }}</th></tr></thead><tbody>
      <tr v-for="it in items" :key="it.id" class="row" @click="editar(it)"><td v-for="c in M.list" :key="c">
        <span v-if="c==='active'">{{ Number(it[c])?'✓':'—' }}</span><span v-else-if="c==='weekday'">{{ dow(it[c]) }}</span><span v-else>{{ txt(it[c]) }}</span></td></tr></tbody></table></div>
    <div class="modal-bg" v-if="editing" @click.self="editing=null"><div class="glass modal modal-lg">
      <h2>{{ form.id?'Editar':'Crear' }} · {{ M.titulo }}</h2>
      <div class="form-grid">
        <template v-for="c in M.campos" :key="c.n">
          <div v-if="c.t==='i18n'||c.t==='i18ta'" class="i18n-box"><b>{{ c.l }}</b>
            <div class="field" v-for="l in ['es','en','pt']" :key="l"><label>{{ l.toUpperCase() }}<span v-if="l==='es'" style="color:var(--cyan)"> · obligatorio</span></label>
              <input v-if="c.t==='i18n'" class="inp" v-model="form[c.n][l]"><textarea v-else class="inp" rows="3" v-model="form[c.n][l]"></textarea></div></div>
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
  data(){ return { items:[], editing:false, form:{} }; },
  computed:{ M(){ return MODULOS[this.modulo]; } },
  watch:{ modulo(){ this.load(); } },
  methods:{
    async load(){ const r=await api.get('/admin/'+this.modulo+'/list'); if(r.ok) this.items=r.data; },
    txt(v){ if(v&&typeof v==='object') return (v.es||Object.values(v)[0]||'').slice(0,80); return String(v==null?'':v).slice(0,80); },
    dow(n){ return {1:'Lunes',2:'Martes',3:'Miércoles',4:'Jueves',5:'Viernes',6:'Sábado',7:'Domingo'}[n]||n; },
    blank(){ const f={}; for(const c of this.M.campos){ f[c.n]= (c.t==='i18n'||c.t==='i18ta')?{es:'',en:'',pt:''}: c.t==='bool'?true: c.t==='num'?0: c.t==='json'?'[]':''; } return f; },
    nuevo(){ this.form=this.blank(); this.editing=true; },
    editar(it){ const f=this.blank(); for(const c of this.M.campos){ let v=it[c.n];
      if(c.t==='i18n'||c.t==='i18ta'){ f[c.n]= (v&&typeof v==='object')?{es:v.es||'',en:v.en||'',pt:v.pt||''}:{es:'',en:'',pt:''}; }
      else if(c.t==='bool'){ f[c.n]=!!Number(v); } else if(c.t==='json'){ f[c.n]=JSON.stringify(v||[],null,1); } else { f[c.n]=v==null?'':v; } }
      f.id=it.id; this.form=f; this.editing=true; },
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
  template: `<div><h1>Conectores</h1><p class="adm__sub">Integraciones · las credenciales se guardan cifradas en el servidor</p>
    <div class="conn-grid"><div v-for="c in items" :key="c.provider" class="glass conn">
      <div class="conn__top"><b style="color:var(--neutral-light)">{{ c.nombre }}</b><span class="badge" :class="c.status==='ok'?'ok':c.status==='error'?'error':''">{{ estado(c.status) }}</span></div>
      <span class="chip">{{ c.grupo }}</span>
      <div class="field" v-for="campo in c.campos" :key="campo"><label>{{ campo }}</label>
        <input class="inp" :type="secreto(campo)?'password':'text'" v-model="c.config[campo]" :placeholder="c.config[campo]||''"></div>
      <label class="field" style="flex-direction:row;align-items:center;gap:.6rem"><label class="switch"><input type="checkbox" v-model="c.enabled" :true-value="1" :false-value="0"><span></span></label> Activo</label>
      <div style="display:flex;gap:.6rem"><button class="btn btn-primary btn-sm" @click="guardar(c)">Guardar</button><button class="btn btn-ghost btn-sm" @click="probar(c)">Probar</button></div>
      <p v-if="c._msg" class="small" :style="{color:c._ok?'#4be3a0':'#ff7d9d'}">{{ c._msg }}</p></div></div></div>`,
  data(){ return { items:[] }; },
  methods:{
    async load(){ const r=await api.get('/admin/connectors'); if(r.ok) this.items=r.data.map(c=>({...c,_msg:'',_ok:false})); },
    secreto(c){ return !['model','from_email','from_name','calendar_id','phone_id','verify_token'].includes(c); },
    estado(s){ return {ok:'Conectado',error:'Error',configurado:'Configurado',sin_configurar:'Sin configurar'}[s]||s; },
    async guardar(c){ const payload={enabled:c.enabled}; for(const k in c.config){ if(c.config[k]&&!String(c.config[k]).startsWith('••')) payload[k]=c.config[k]; }
      const r=await api.put('/admin/connectors/'+c.provider,payload); if(r.ok){ toast(c.nombre+' guardado.'); } else toast(r.error||'Error','err'); },
    async probar(c){ c._msg='Probando…'; const r=await api.post('/admin/connectors/'+c.provider+'/test',{}); c._ok=r.data&&r.data.ok; c._msg=(r.data&&(r.data.message||r.data.error))||r.error||'—'; if(c._ok)c.status='ok'; },
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

export const AlexIAInterno = {
  components: { Icon },
  template: `<div><h1>AlexIA <span class="grad-text">interno</span></h1><p class="adm__sub">Asistente del equipo · consulta leads, métricas y contenido</p>
    <div class="glass panel" style="max-width:760px;display:flex;flex-direction:column;height:60vh">
      <div style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:.7rem;padding:.4rem" ref="msgs">
        <div class="alexia-msg a">Hola {{ store.admin?store.admin.name:'' }}. Pregúntame por tus leads, métricas o contenido.</div>
        <div v-for="(m,i) in msgs" :key="i" class="alexia-msg" :class="m.role==='user'?'u':'a'" style="max-width:75%">{{ m.text }}</div>
        <div v-if="loading" class="alexia-msg a" style="opacity:.6">…</div></div>
      <div style="display:flex;gap:.5rem;padding-top:.8rem;border-top:1px solid var(--line-soft)">
        <textarea class="inp" style="resize:none;height:44px" v-model="text" placeholder="Escribe…" @keydown.enter.exact.prevent="send"></textarea>
        <button class="btn btn-primary" @click="send" :disabled="loading"><Icon name="send" :size="16"/></button></div></div></div>`,
  data(){ return { store, text:'', msgs:[], loading:false, convId:null }; },
  methods:{ async send(){ const t=this.text.trim(); if(!t||this.loading)return; this.msgs.push({role:'user',text:t}); this.text=''; this.loading=true; this.scroll();
    const r=await api.post('/admin/alexia',{mensaje:t,conversation_id:this.convId}); this.loading=false;
    if(r.ok){ this.convId=r.data.conversation_id; this.msgs.push({role:'assistant',text:r.data.reply}); } else this.msgs.push({role:'assistant',text:r.error||'AlexIA no está disponible. Configure OpenAI en Conectores.'});
    this.scroll(); },
    scroll(){ this.$nextTick(()=>{ const m=this.$refs.msgs; if(m)m.scrollTop=m.scrollHeight; }); } },
};
