// Portal admin · GrowthBoard Clientes: la herramienta del consultor durante el
// acompañamiento. Lista de clientes con diagnóstico, su cancha, jugadas (CRUD)
// y marcador semanal del cliente; enlace de acceso a Mi GrowthBoard.
import { api, toast, tr } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { SmartTable } from './table.js';
import { Cancha } from '../views/growthboard.js';

const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));
const ESTADOS = { pendiente:'Pendiente', ejecucion:'En ejecución', ejecutada:'Ejecutada', descartada:'Descartada' };
const blankPlay = () => ({ zona:'', titulo:'', porque:'', responsable:'', fecha_limite:'', indicador:'', estado:'pendiente', resultado:'', sort:0 });

export const GBClientes = {
  components: { Icon, SmartTable, Cancha },
  data(){ return { items:[], q:'', zonas:[], det:null, play:null, guardando:false,
    cols:[
      { key:'name', label:'Cliente', render:(r)=>'<b style="color:var(--neutral-light)">'+esc(r.name)+'</b><br><span class="small">'+esc(r.company||'')+'</span>' },
      { key:'total', label:'Tablero', raw:(r)=>Number(r.total), render:(r)=>'<b>'+r.total+'</b>/55 · <span class="chip chip-soft">'+esc(r.banda)+'</span>' },
      { key:'zona_critica', label:'Zona crítica' },
      { key:'jugadas', label:'Jugadas', raw:(r)=>Number(r.jugadas), render:(r)=>r.ejecutadas+' / '+r.jugadas },
      { key:'checkins', label:'Semanas', raw:(r)=>Number(r.checkins) },
      { key:'fecha', label:'Diagnóstico', render:(r)=>String(r.fecha||'').slice(0,10) },
    ] }; },
  computed:{ tr:()=>tr,
    ultimo(){ return this.det ? this.det.results[0] : null; },
    zonaOpc(){ return this.zonas.map(z=>({ v:z.zkey, l:(z.nombre&&z.nombre.es)||z.zkey })); },
  },
  methods:{
    estados(){ return ESTADOS; },
    fecha(s){ return String(s||'').slice(0,10); },
    async load(){ const r=await api.get('/admin/growthboard/clientes'); if(r.ok) this.items=r.data; },
    async abrir(row){ const r=await api.get('/admin/growthboard/clientes/'+row.id); if(r.ok){ this.det=r.data; this.play=null; } },
    async copiarAcceso(){ return this.copiarAccesoDe(this.det.lead.id); },
    async copiarAccesoDe(id){ const r=await api.get('/admin/growthboard/clientes/'+id+'/acceso');
      if(!r.ok){ toast(r.error||'Error','err'); return; }
      try { await navigator.clipboard.writeText(r.data.url); toast('Enlace de acceso copiado. Compártelo con el cliente.'); }
      catch(e){ prompt('Copia el enlace de acceso:', r.data.url); } },
    nuevaJugada(){ this.play={ ...blankPlay() }; },
    editarJugada(p){ this.play={ ...blankPlay(), ...p }; },
    async guardarJugada(){ if(!this.play.titulo.trim()){ toast('La jugada necesita un título.','err'); return; }
      this.guardando=true;
      const id=this.det.lead.id;
      const r=this.play.id ? await api.put('/admin/growthboard/clientes/'+id+'/jugadas/'+this.play.id, this.play)
                           : await api.post('/admin/growthboard/clientes/'+id+'/jugadas', this.play);
      this.guardando=false;
      if(r.ok){ toast('Jugada guardada.'); this.play=null; this.abrir({id}); this.load(); } else toast(r.error||'Error','err'); },
    async borrarJugada(p){ if(!confirm('¿Eliminar la jugada "'+p.titulo+'"?')) return;
      const id=this.det.lead.id;
      const r=await api.del('/admin/growthboard/clientes/'+id+'/jugadas/'+p.id);
      if(r.ok){ toast('Eliminada.'); this.abrir({id}); this.load(); } else toast(r.error||'Error','err'); },
  },
  async mounted(){ this.load(); const c=await api.get('/growthboard/config'); if(c.ok) this.zonas=c.data.zonas; },
  template:`<div>
    <div><h1>GrowthBoard · Clientes</h1><p class="adm__sub" style="margin:0">Seguimiento del acompañamiento · cancha, jugadas y marcador semanal de cada cliente</p></div>
    <div class="toolbar" style="margin-top:1.2rem"><input class="inp" v-model="q" placeholder="Buscar cliente o empresa…" style="max-width:280px"></div>
    <SmartTable :columns="cols" :rows="items" :search="q" :searchKeys="['name','company','email']" @rowClick="abrir">
      <template #actions="{row}">
        <button class="btn btn-ghost btn-sm" @click.stop="abrir(row)">Abrir</button>
        <button class="btn btn-primary btn-sm" @click.stop="copiarAccesoDe(row.id)"><Icon name="plug" :size="12"/> Enlace</button></template>
    </SmartTable>

    <div class="modal-bg" v-if="det" @click.self="det=null"><div class="glass modal modal-lg">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
        <div><h2>{{ det.lead.company || det.lead.name }}</h2>
          <p class="small">{{ det.lead.name }} · {{ det.lead.email }}<span v-if="det.lead.phone_wa"> · +{{ det.lead.phone_wa }}</span></p></div>
        <div style="display:flex;gap:.5rem">
          <button class="btn btn-primary btn-sm" @click="copiarAcceso"><Icon name="plug" :size="13"/> Copiar enlace del cliente</button>
          <button class="btn btn-ghost btn-sm" @click="det=null">✕</button></div></div>

      <div class="sec-divider"><span>Tablero · {{ ultimo.total }}/55 ({{ ultimo.banda }})<template v-if="det.results.length>1"> · evolución: {{ det.results[det.results.length-1].total }} → {{ ultimo.total }}</template></span></div>
      <Cancha :zonas="zonas" :scores="ultimo.scores" :critica="ultimo.zona_critica" compact/>

      <div class="sec-divider"><span>Jugadas <button type="button" class="btn btn-ghost btn-sm" @click="nuevaJugada"><Icon name="plug" :size="12"/> Nueva jugada</button></span></div>
      <div v-if="play" class="glass panel land-block">
        <div class="form-grid two">
          <div class="field"><label>Zona</label><select class="inp" v-model="play.zona"><option value="">—</option><option v-for="z in zonaOpc" :key="z.v" :value="z.v">{{ z.l }}</option></select></div>
          <div class="field"><label>Título de la jugada *</label><input class="inp" v-model="play.titulo" placeholder="Qué se va a hacer"></div></div>
        <div class="field"><label>Por qué (qué está afectando hoy)</label><textarea class="inp" rows="2" v-model="play.porque"></textarea></div>
        <div class="form-grid" style="grid-template-columns:repeat(3,1fr)">
          <div class="field"><label>Responsable</label><input class="inp" v-model="play.responsable"></div>
          <div class="field"><label>Fecha límite</label><input class="inp" v-model="play.fecha_limite" placeholder="AAAA-MM-DD"></div>
          <div class="field"><label>Estado</label><select class="inp" v-model="play.estado"><option v-for="(l,v) in estados()" :key="v" :value="v">{{ l }}</option></select></div></div>
        <div class="field"><label>Indicador de éxito</label><input class="inp" v-model="play.indicador" placeholder="Cómo sabremos que funcionó"></div>
        <div style="display:flex;justify-content:flex-end;gap:.6rem">
          <button class="btn btn-ghost btn-sm" @click="play=null">Cancelar</button>
          <button class="btn btn-primary btn-sm" @click="guardarJugada" :disabled="guardando">{{ guardando?'…':'Guardar jugada' }}</button></div>
      </div>
      <p class="small" v-if="!det.plays.length && !play">Sin jugadas todavía. Defínelas con el cliente en la lectura estratégica.</p>
      <div v-for="p in det.plays" :key="p.id" class="gb-play">
        <div class="gb-play__head"><span class="chip chip-soft" v-if="p.zona">{{ p.zona }}</span><b>{{ p.titulo }}</b>
          <span class="chip" :class="p.estado==='ejecutada'?'chip--cyan':''" style="margin-left:auto">{{ estados()[p.estado]||p.estado }}<template v-if="p.resultado"> · {{ p.resultado==='movio'?'movió ✓':'sin mover' }}</template></span></div>
        <p class="gb-play__meta"><span v-if="p.responsable">{{ p.responsable }}</span><span v-if="p.fecha_limite">{{ p.fecha_limite }}</span><span v-if="p.indicador">{{ p.indicador }}</span></p>
        <p class="small" v-if="p.notas">📝 {{ p.notas }}</p>
        <div style="display:flex;gap:.4rem"><button class="btn btn-ghost btn-sm" @click="editarJugada(p)">Editar</button>
          <button class="btn btn-danger btn-sm" @click="borrarJugada(p)">✕</button></div>
      </div>

      <div class="sec-divider"><span>Marcador semanal del cliente</span></div>
      <p class="small" v-if="!det.checkins.length">El cliente aún no registra semanas.</p>
      <details v-for="c in det.checkins" :key="c.id" class="gb-ck-item"><summary>{{ fecha(c.created_at) }} · {{ (c.avanzo||c.proxima||'—').slice(0,60) }}</summary>
        <ul><li v-if="c.avanzo"><b>Avanzó:</b> {{ c.avanzo }}</li><li v-if="c.trabo"><b>Se trabó:</b> {{ c.trabo }}</li>
          <li v-if="c.dato"><b>Dato:</b> {{ c.dato }}</li><li v-if="c.decision"><b>Decisión:</b> {{ c.decision }}</li>
          <li v-if="c.proxima"><b>Próxima jugada:</b> {{ c.proxima }}</li></ul></details>
    </div></div>
  </div>`,
};
