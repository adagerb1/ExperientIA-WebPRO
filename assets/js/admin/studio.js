// Portal admin · GrowthBoard AI Content Studio (MVP Fase 1 del blueprint):
// documento estratégico → brief (ficha estratégica) → calendario editorial →
// piezas completas. Revisión humana obligatoria: la IA propone, Tonny aprueba.
import { api, toast, store } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { SmartTable } from './table.js';

const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));
const CANALES = ['linkedin', 'instagram', 'tiktok', 'youtube', 'email'];
const FORMATOS = ['post', 'carrusel', 'video', 'documento', 'newsletter'];
const PILARES = { diagnostico:'Diagnóstico', framework:'Framework', prueba:'Prueba', vision:'Visión', oferta:'Oferta' };
const MECANISMOS = { afirmacion_divisiva:'Afirmación divisiva', error_visible:'Error visible', pregunta_compleja:'Pregunta compleja', numero_especifico:'Número específico', reto_verificacion:'Reto de verificación', contraste:'Contraste' };
const ESTADOS = { idea:'Idea', redaccion:'En redacción', aprobada:'Aprobada', programada:'Programada', publicada:'Publicada' };
const BRIEF_CAMPOS = [
  ['campana','Campaña'],['periodo','Periodo'],['objetivo_general','Objetivo general'],['audiencia','Audiencia'],
  ['problema','Problema'],['promesa','Promesa'],['mecanismo_unico','Mecanismo único'],['enemigo','Enemigo narrativo'],
  ['oferta','Oferta'],['cta','CTA'],['frecuencia','Frecuencia'],['tono','Tono'],
];

export const Studio = {
  components: { Icon, SmartTable },
  data(){ return { items:[], q:'', nuevo:null, det:null, pieza:null,
    cal:{ semanas:4, por_semana:5, canales:['linkedin'] },
    busy:{ brief:false, cal:false, pieza:false, save:false, pub:false },
    cols:[
      { key:'nombre', label:'Estrategia', render:(r)=>'<b style="color:var(--neutral-light)">'+esc(r.nombre)+'</b>' },
      { key:'periodo', label:'Periodo' },
      { key:'status', label:'Estado', render:(r)=>({borrador:'Borrador',brief:'Brief listo',aprobada:'Aprobada'})[r.status]||r.status },
      { key:'piezas', label:'Piezas', raw:(r)=>Number(r.piezas), render:(r)=>r.aprobadas+' / '+r.piezas },
      { key:'created_at', label:'Creada', render:(r)=>String(r.created_at||'').slice(0,10) },
    ] }; },
  computed:{
    briefCampos(){ return BRIEF_CAMPOS; },
    canales(){ return CANALES; }, formatos(){ return FORMATOS; },
    pilares(){ return PILARES; }, mecanismos(){ return MECANISMOS; }, estados(){ return ESTADOS; },
    semanas(){ if(!this.det) return {}; const g={};
      for(const it of this.det.items){ (g[it.semana] ??= []).push(it); } return g; },
  },
  methods:{
    async load(){ const r=await api.get('/admin/studio/estrategias'); if(r.ok) this.items=r.data; },
    abrirNueva(){ this.nuevo={ nombre:'', periodo:'mensual', source_doc:'' }; },
    async crear(){ if(!this.nuevo.nombre.trim()){ toast('Ponle nombre a la estrategia.','err'); return; }
      const r=await api.post('/admin/studio/estrategias', this.nuevo);
      if(r.ok){ this.nuevo=null; await this.load(); this.abrir({id:r.data.id}); } else toast(r.error||'Error','err'); },
    async abrir(row){ const r=await api.get('/admin/studio/estrategias/'+row.id);
      if(r.ok){ const d=r.data; d.estrategia.brief = d.estrategia.brief || {}; this.det=d; this.pieza=null; } },
    cerrar(){ this.det=null; this.load(); },
    async extraerBrief(){ this.busy.brief=true;
      const r=await api.post('/admin/studio/estrategias/'+this.det.estrategia.id+'/brief');
      this.busy.brief=false;
      if(r.ok){ this.det.estrategia.brief=r.data.brief; this.det.estrategia.status='brief'; toast('Brief extraído. Revísalo, ajústalo y apruébalo.'); }
      else toast(r.error||'AlexIA no disponible. Configura OpenAI/Anthropic en Conectores.','err'); },
    async guardarEstrategia(aprobar){ this.busy.save=true;
      const e=this.det.estrategia;
      const r=await api.put('/admin/studio/estrategias/'+e.id, { nombre:e.nombre, source_doc:e.source_doc, brief:e.brief, status: aprobar?'aprobada':e.status });
      this.busy.save=false;
      if(r.ok){ if(aprobar) e.status='aprobada'; toast(aprobar?'Brief aprobado: ya puedes generar el calendario.':'Guardado.'); } else toast(r.error||'Error','err'); },
    async eliminarEstrategia(){ if(!confirm('¿Eliminar la estrategia y todas sus piezas?')) return;
      const r=await api.del('/admin/studio/estrategias/'+this.det.estrategia.id);
      if(r.ok){ toast('Eliminada.'); this.cerrar(); } },
    toggleCanal(c){ const i=this.cal.canales.indexOf(c); if(i>=0){ if(this.cal.canales.length>1) this.cal.canales.splice(i,1); } else this.cal.canales.push(c); },
    async generarCalendario(){ this.busy.cal=true;
      const r=await api.post('/admin/studio/estrategias/'+this.det.estrategia.id+'/calendario', this.cal);
      this.busy.cal=false;
      if(r.ok){ toast('Calendario generado: '+r.data.creadas+' piezas. Ábrelas para redactarlas.'); this.abrir({id:this.det.estrategia.id}); }
      else toast(r.error||'AlexIA no disponible.','err'); },
    nuevaPieza(){ this.pieza={ semana:1, fecha:'', canal:this.cal.canales[0]||'linkedin', formato:'post', pilar:'diagnostico', tema:'', objetivo:'', mecanismo:'pregunta_compleja', estado:'idea', copy:{hook:'',texto:'',corta:'',cta:'',hashtags:'',comentario:''}, guion:'', notas:'' }; },
    abrirPieza(it){ this.pieza={ ...it, copy:{ hook:'',texto:'',corta:'',cta:'',hashtags:'',comentario:'', ...(it.copy||{}) }, guion:it.guion||'', notas:it.notas||'' }; },
    async generarPieza(){ if(!this.pieza.id){ toast('Guarda la pieza primero.','err'); return; }
      this.busy.pieza=true;
      const r=await api.post('/admin/studio/items/'+this.pieza.id+'/generar');
      this.busy.pieza=false;
      if(r.ok){ this.pieza.copy={...this.pieza.copy,...r.data.copy}; if(r.data.guion) this.pieza.guion=r.data.guion; this.pieza.score=r.data.score; this.pieza.estado='redaccion'; toast('Pieza redactada (score '+r.data.score+'/100). Revísala y apruébala.'); }
      else toast(r.error||'AlexIA no disponible.','err'); },
    async guardarPieza(){ if(!this.pieza.tema.trim()){ toast('La pieza necesita un tema.','err'); return; }
      this.busy.save=true;
      const id=this.det.estrategia.id;
      const r=this.pieza.id ? await api.put('/admin/studio/items/'+this.pieza.id, this.pieza)
                            : await api.post('/admin/studio/estrategias/'+id+'/items', this.pieza);
      this.busy.save=false;
      if(r.ok){ toast('Pieza guardada.'); this.pieza=null; this.abrir({id}); } else toast(r.error||'Error','err'); },
    async publicarPieza(){ if(!this.pieza.id){ toast('Guarda la pieza primero.','err'); return; }
      if(this.pieza.canal!=='linkedin'){ toast('La publicación automática está disponible para LinkedIn. Para '+this.pieza.canal+', usa Exportar.','err'); return; }
      if(!confirm('¿Publicar esta pieza en LinkedIn ahora?')) return;
      this.busy.pub=true;
      const r=await api.post('/admin/studio/items/'+this.pieza.id+'/publicar');
      this.busy.pub=false;
      if(r.ok){ this.pieza.estado='publicada'; toast('Publicada en LinkedIn.'); this.abrir({id:this.det.estrategia.id}); } else toast(r.error||'No se pudo publicar.','err'); },
    async eliminarPieza(){ if(!this.pieza.id){ this.pieza=null; return; }
      if(!confirm('¿Eliminar esta pieza?')) return;
      const id=this.det.estrategia.id;
      const r=await api.del('/admin/studio/items/'+this.pieza.id);
      if(r.ok){ toast('Eliminada.'); this.pieza=null; this.abrir({id}); } },
    async exportar(){ const e=this.det.estrategia;
      const resp=await fetch('/api/admin/studio/estrategias/'+e.id+'/export', { headers:{ Authorization:'Bearer '+store.token } });
      const blob=await resp.blob(); const a=document.createElement('a');
      a.href=URL.createObjectURL(blob); a.download='studio-'+e.nombre.replace(/\s+/g,'-')+'.csv'; a.click(); URL.revokeObjectURL(a.href); },
    badge(e){ return {idea:'descartado',redaccion:'contactado',aprobada:'cliente',programada:'calificado',publicada:'cliente'}[e]||'nuevo'; },
  },
  mounted(){ this.load(); },
  template:`<div>
    <!-- Lista de estrategias -->
    <template v-if="!det">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
        <div><h1>Content Studio</h1><p class="adm__sub" style="margin:0">Estrategia → brief → calendario → piezas · la IA propone, tú apruebas</p></div>
        <button class="btn btn-primary btn-sm" @click="abrirNueva"><Icon name="sparkle" :size="14"/> Nueva estrategia</button></div>
      <div class="toolbar" style="margin-top:1.2rem"><input class="inp" v-model="q" placeholder="Buscar estrategia…" style="max-width:260px"></div>
      <SmartTable :columns="cols" :rows="items" :search="q" :searchKeys="['nombre']" @rowClick="abrir"/>

      <div class="modal-bg" v-if="nuevo" @click.self="nuevo=null"><div class="glass modal">
        <h2>Nueva estrategia</h2>
        <div class="form-grid two" style="margin-top:1rem">
          <div class="field"><label>Nombre</label><input class="inp" v-model="nuevo.nombre" placeholder="Plan LinkedIn Q3"></div>
          <div class="field"><label>Periodo</label><select class="inp" v-model="nuevo.periodo"><option value="mensual">Mensual</option><option value="trimestral">Trimestral</option><option value="campana">Campaña</option></select></div></div>
        <div class="field"><label>Documento estratégico (pega aquí objetivos, OKR, audiencias, oferta, manual de marca…)</label>
          <textarea class="inp" rows="10" v-model="nuevo.source_doc" placeholder="Pega el documento completo. AlexIA extraerá el brief estructurado."></textarea></div>
        <div style="display:flex;justify-content:flex-end;gap:.6rem"><button class="btn btn-ghost btn-sm" @click="nuevo=null">Cancelar</button>
          <button class="btn btn-primary btn-sm" @click="crear">Crear estrategia</button></div></div></div>
    </template>

    <!-- Espacio de trabajo de una estrategia -->
    <template v-else>
      <button class="btn btn-ghost btn-sm" @click="cerrar" style="margin-bottom:1rem">← Estrategias</button>
      <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
        <div><h1 style="margin:0">{{ det.estrategia.nombre }}</h1>
          <p class="adm__sub" style="margin:0">{{ det.estrategia.periodo }} · {{ {borrador:'Borrador',brief:'Brief listo para revisar',aprobada:'Brief aprobado'}[det.estrategia.status] }}</p></div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
          <button class="btn btn-ghost btn-sm" @click="exportar"><Icon name="doc" :size="13"/> Exportar CSV</button>
          <button class="btn btn-danger btn-sm" @click="eliminarEstrategia">✕</button></div></div>

      <div class="sec-divider"><span>1 · Documento y brief</span></div>
      <div class="glass panel">
        <details :open="det.estrategia.status==='borrador'"><summary class="small" style="cursor:pointer">Documento estratégico (clic para ver/editar)</summary>
          <textarea class="inp" rows="8" v-model="det.estrategia.source_doc" style="margin-top:.6rem"></textarea></details>
        <div style="display:flex;gap:.6rem;margin-top:.8rem;flex-wrap:wrap">
          <button class="btn btn-grad btn-sm" @click="extraerBrief" :disabled="busy.brief"><Icon name="sparkle" :size="13"/> {{ busy.brief?'Extrayendo…':'Extraer brief con AlexIA' }}</button>
          <button class="btn btn-ghost btn-sm" @click="guardarEstrategia(false)" :disabled="busy.save">Guardar</button>
          <button v-if="det.estrategia.status!=='aprobada'" class="btn btn-primary btn-sm" @click="guardarEstrategia(true)" :disabled="busy.save">Aprobar brief</button></div>
        <div class="form-grid two" style="margin-top:1rem">
          <div v-for="[k,l] in briefCampos" :key="k" class="field"><label>{{ l }}</label><input class="inp" v-model="det.estrategia.brief[k]"></div></div>
        <div class="form-grid two">
          <div class="field"><label>Pilares (coma)</label><input class="inp" :value="(det.estrategia.brief.pilares||[]).join(', ')" @input="det.estrategia.brief.pilares=$event.target.value.split(',').map(s=>s.trim()).filter(Boolean)"></div>
          <div class="field"><label>Métricas (coma)</label><input class="inp" :value="(det.estrategia.brief.metricas||[]).join(', ')" @input="det.estrategia.brief.metricas=$event.target.value.split(',').map(s=>s.trim()).filter(Boolean)"></div></div>
        <p class="small" v-if="(det.estrategia.brief.faltantes||[]).length" style="color:#ffb84d"><Icon name="alert" :size="12"/> Faltantes: {{ det.estrategia.brief.faltantes.join(' · ') }}</p>
        <p class="small" v-if="(det.estrategia.brief.contradicciones||[]).length" style="color:#ff8c42"><Icon name="alert" :size="12"/> Revisar: {{ det.estrategia.brief.contradicciones.join(' · ') }}</p>
      </div>

      <div class="sec-divider"><span>2 · Calendario editorial</span></div>
      <div class="glass panel">
        <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
          <div class="field" style="width:110px"><label>Semanas</label><input class="inp" type="number" min="1" max="13" v-model.number="cal.semanas"></div>
          <div class="field" style="width:130px"><label>Piezas / semana</label><input class="inp" type="number" min="1" max="7" v-model.number="cal.por_semana"></div>
          <div class="field"><label>Canales</label><div class="cats-chips"><button v-for="c in canales" :key="c" type="button" class="chip-cat" :class="{on:cal.canales.includes(c)}" @click="toggleCanal(c)">{{ c }}</button></div></div>
          <button class="btn btn-grad btn-sm" @click="generarCalendario" :disabled="busy.cal || det.estrategia.status!=='aprobada'" :title="det.estrategia.status!=='aprobada'?'Aprueba el brief primero':''"><Icon name="sparkle" :size="13"/> {{ busy.cal?'Generando…':'Generar calendario' }}</button>
          <button class="btn btn-ghost btn-sm" @click="nuevaPieza">+ Pieza manual</button></div>
        <p class="small" v-if="!det.items.length" style="margin-top:.8rem">Sin piezas todavía. Genera el calendario con AlexIA o crea piezas manuales.</p>
        <div v-for="(lista,sem) in semanas" :key="sem" style="margin-top:1rem">
          <p class="lbl" style="margin-bottom:.5rem">Semana {{ sem }}</p>
          <div class="studio-item" v-for="it in lista" :key="it.id" @click="abrirPieza(it)">
            <span class="chip chip-soft">{{ it.canal }}</span><span class="chip chip-soft" v-if="it.formato">{{ it.formato }}</span>
            <span class="chip chip--cyan" v-if="it.pilar">{{ pilares[it.pilar]||it.pilar }}</span>
            <b>{{ it.tema }}</b>
            <span style="margin-left:auto;display:flex;gap:.4rem;align-items:center">
              <span class="small" v-if="it.score">{{ it.score }}/100</span>
              <span class="badge" :class="badge(it.estado)">{{ estados[it.estado]||it.estado }}</span></span></div></div>
      </div>

      <!-- Modal de pieza -->
      <div class="modal-bg" v-if="pieza" @click.self="pieza=null"><div class="glass modal modal-lg">
        <div style="display:flex;justify-content:space-between;align-items:center"><h2>{{ pieza.id?'Pieza':'Nueva pieza' }}</h2>
          <button class="btn btn-ghost btn-sm" @click="pieza=null">✕</button></div>
        <div class="form-grid" style="grid-template-columns:repeat(4,1fr);margin-top:1rem">
          <div class="field"><label>Semana</label><input class="inp" type="number" min="1" max="13" v-model.number="pieza.semana"></div>
          <div class="field"><label>Fecha</label><input class="inp" v-model="pieza.fecha" placeholder="AAAA-MM-DD"></div>
          <div class="field"><label>Canal</label><select class="inp" v-model="pieza.canal"><option v-for="c in canales" :key="c" :value="c">{{ c }}</option></select></div>
          <div class="field"><label>Formato</label><select class="inp" v-model="pieza.formato"><option v-for="f in formatos" :key="f" :value="f">{{ f }}</option></select></div></div>
        <div class="form-grid" style="grid-template-columns:repeat(3,1fr)">
          <div class="field"><label>Pilar</label><select class="inp" v-model="pieza.pilar"><option v-for="(l,k) in pilares" :key="k" :value="k">{{ l }}</option></select></div>
          <div class="field"><label>Mecanismo de interacción</label><select class="inp" v-model="pieza.mecanismo"><option v-for="(l,k) in mecanismos" :key="k" :value="k">{{ l }}</option></select></div>
          <div class="field"><label>Estado</label><select class="inp" v-model="pieza.estado"><option v-for="(l,k) in estados" :key="k" :value="k">{{ l }}</option></select></div></div>
        <div class="field"><label>Tema *</label><input class="inp" v-model="pieza.tema"></div>
        <div class="field"><label>Objetivo</label><input class="inp" v-model="pieza.objetivo"></div>

        <div class="sec-divider"><span>Copy <button type="button" class="btn btn-grad btn-sm" @click="generarPieza" :disabled="busy.pieza || !pieza.id"><Icon name="sparkle" :size="12"/> {{ busy.pieza?'Redactando…':'Redactar con AlexIA' }}</button><span class="small" v-if="pieza.score" style="text-transform:none;letter-spacing:0;margin-left:.5rem">score {{ pieza.score }}/100</span></span></div>
        <div class="field"><label>Hook (primera línea)</label><input class="inp" v-model="pieza.copy.hook"></div>
        <div class="field"><label>Texto completo</label><textarea class="inp" rows="7" v-model="pieza.copy.texto"></textarea></div>
        <div class="form-grid two">
          <div class="field"><label>Versión corta</label><textarea class="inp" rows="3" v-model="pieza.copy.corta"></textarea></div>
          <div class="field"><label>Comentario inicial sugerido</label><textarea class="inp" rows="3" v-model="pieza.copy.comentario"></textarea></div></div>
        <div class="form-grid two">
          <div class="field"><label>CTA</label><input class="inp" v-model="pieza.copy.cta"></div>
          <div class="field"><label>Hashtags</label><input class="inp" v-model="pieza.copy.hashtags"></div></div>
        <div class="field" v-if="pieza.formato==='video' || pieza.canal==='tiktok' || pieza.canal==='youtube'"><label>Guion de video</label><textarea class="inp" rows="6" v-model="pieza.guion"></textarea></div>
        <div class="field"><label>Notas</label><input class="inp" v-model="pieza.notas"></div>
        <div style="display:flex;gap:.6rem;justify-content:flex-end;flex-wrap:wrap">
          <button v-if="pieza.id" class="btn btn-danger btn-sm" style="margin-right:auto" @click="eliminarPieza">Eliminar</button>
          <button v-if="pieza.id && pieza.canal==='linkedin'" class="btn btn-grad btn-sm" @click="publicarPieza" :disabled="busy.pub"><Icon name="send" :size="12"/> {{ busy.pub?'Publicando…':'Publicar en LinkedIn' }}</button>
          <button class="btn btn-ghost btn-sm" @click="pieza=null">Cancelar</button>
          <button class="btn btn-primary btn-sm" @click="guardarPieza" :disabled="busy.save">Guardar pieza</button></div>
      </div></div>
    </template>
  </div>`,
};
