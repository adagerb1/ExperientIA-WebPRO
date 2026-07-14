// Portal admin · Estudio de Recursos con IA (contenido trilingüe, portada, audio)
import { api, toast, loadMeta, CMS_LANGS, CMS_CODES } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { SmartTable } from './table.js';
const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));

// Categorías por defecto (fallback si /meta aún no trae la taxonomía trilingüe de BD).
const CAT_FALLBACK = [
  { key:'ia_negocios', nombre:{ es:'IA aplicada a negocios' } }, { key:'automatizacion', nombre:{ es:'Automatización' } },
  { key:'growth', nombre:{ es:'Growth' } }, { key:'estrategia', nombre:{ es:'Estrategia' } },
  { key:'marketing', nombre:{ es:'Marketing estratégico' } }, { key:'crm', nombre:{ es:'CRM' } },
  { key:'ventas', nombre:{ es:'Ventas' } }, { key:'experiencia_cliente', nombre:{ es:'Experiencia de cliente' } },
  { key:'agentes', nombre:{ es:'Agentes inteligentes' } }, { key:'datos', nombre:{ es:'Datos y analítica' } },
  { key:'liderazgo', nombre:{ es:'Liderazgo' } }, { key:'transformacion', nombre:{ es:'Transformación digital' } },
];
const slugify = (s)=> (s||'').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,80);
const empty = ()=> { const o={}; for(const c of CMS_CODES) o[c]=''; return o; };
// Normaliza un campo a objeto {es,en,pt}. Una cadena heredada (portada/audio
// únicos) se replica a todos los idiomas para no perder el valor existente.
const toI18n = (v, replicate=false)=> { const o=empty();
  if(v && typeof v==='object'){ for(const c of CMS_CODES) o[c]=v[c]||''; }
  else if(typeof v==='string' && v){ if(replicate){ for(const c of CMS_CODES) o[c]=v; } else { o.es=v; } }
  return o; };

// ── Editor visual (WYSIWYG) ──────────────────────────────────────────
const RichEditor = {
  props:{ modelValue:{type:String,default:''} },
  emits:['update:modelValue'],
  template:`<div class="rich">
    <div class="rich-tb">
      <button type="button" @mousedown.prevent="cmd('formatBlock','P')">P</button>
      <button type="button" @mousedown.prevent="cmd('formatBlock','H2')">H2</button>
      <button type="button" @mousedown.prevent="cmd('bold')" style="font-weight:800">B</button>
      <button type="button" @mousedown.prevent="cmd('italic')" style="font-style:italic">I</button>
      <button type="button" @mousedown.prevent="cmd('insertUnorderedList')">• Lista</button>
      <button type="button" @mousedown.prevent="link">Enlace</button>
      <button type="button" @mousedown.prevent="img">Imagen</button>
      <button type="button" @mousedown.prevent="cmd('removeFormat')">✕ formato</button>
    </div>
    <div class="rich-area" contenteditable="true" ref="ed" @input="sync" @blur="sync"></div></div>`,
  watch:{ modelValue(v){ if(this.$refs.ed && v!==this.$refs.ed.innerHTML) this.$refs.ed.innerHTML=v||''; } },
  mounted(){ this.$refs.ed.innerHTML=this.modelValue||''; },
  methods:{
    cmd(c,v){ this.$refs.ed.focus(); document.execCommand(c,false,v||null); this.sync(); },
    link(){ const u=prompt('URL del enlace:'); if(u) this.cmd('createLink',u); },
    img(){ const u=prompt('URL de la imagen:'); if(u) this.cmd('insertImage',u); },
    sync(){ this.$emit('update:modelValue', this.$refs.ed.innerHTML); },
  },
};

// ── Modal: estudio de recurso ────────────────────────────────────────
const RecursoEditor = {
  components:{ Icon, RichEditor },
  props:{ item:{ type:Object, default:null } },
  emits:['close','saved'],
  template:`<div class="modal-bg" @click.self="$emit('close')">
   <div class="glass modal modal-lg">
    <div class="rec-editor-head">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <h2>{{ item?'Editar recurso':'Nuevo recurso' }}</h2>
        <button class="btn btn-ghost btn-sm" @click="$emit('close')">✕</button></div>
      <div class="rec-langbar">
        <div class="lang-tabs" style="margin:0;border:0;padding:0">
          <button type="button" v-for="l in langs" :key="l.code" :class="{active:lang===l.code}" @click="lang=l.code">{{ l.label }}<span v-if="l.code==='es'" class="lang-req">·oblig</span></button></div>
        <button type="button" class="btn btn-grad btn-sm" @click="generar" :disabled="busy.gen"><Icon name="sparkle" :size="13"/> {{ busy.gen?('Generando en '+langs.length+' idiomas…'):'Generar todo con AlexIA' }}</button></div>
      <p class="rec-lang-note">Editando <b>{{ lang.toUpperCase() }}</b> · todos los campos (título, etiqueta, contenido y SEO) se guardan por idioma. AlexIA genera en los {{ langs.length }} idiomas de una sola vez.</p>
    </div>

    <div class="form-grid two">
      <div class="field"><label>Título · {{ lang.toUpperCase() }}</label><input class="inp" v-model="f.titulo[lang]" @input="onTitulo"></div>
      <div class="field"><label>Slug (URL) · del título ES</label><input class="inp" v-model="f.slug"></div></div>

    <div class="form-grid two">
      <div class="field"><label>Tipo</label><select class="inp" v-model="f.type"><option value="article">Artículo</option><option value="download">Descargable (PDF)</option></select></div>
      <div class="field"><label>Etiqueta · {{ lang.toUpperCase() }}</label><input class="inp" v-model="f.tipo_label[lang]" placeholder="Artículo"></div></div>

    <div class="field"><label>Categorías (elige una o varias) · {{ lang.toUpperCase() }}</label>
      <div class="cats-chips"><button type="button" v-for="c in cats" :key="c.key" class="chip-cat" :class="{on:f.categories.includes(c.key)}" @click="toggleCat(c.key)">{{ c.nombre[lang]||c.nombre.es }}</button></div></div>

    <div class="form-grid two">
      <div class="field"><label>Autor</label><input class="inp" v-model="f.author" placeholder="Equipo ExperientIA"></div>
      <div class="field"><label>Minutos de lectura</label><input class="inp" type="number" min="1" v-model="f.read_minutes"></div></div>

    <div class="sec-divider"><span>Contenido · {{ lang.toUpperCase() }}</span></div>
    <div class="field"><label>Resumen (excerpt) · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="2" v-model="f.extracto[lang]"></textarea></div>
    <div class="field"><label>Contenido · {{ lang.toUpperCase() }}</label><RichEditor :key="lang" v-model="f.cuerpo[lang]"/></div>

    <div class="sec-divider"><span>Imagen de portada · {{ lang.toUpperCase() }}</span></div>
    <p class="rec-lang-note">La portada en <b>ES</b> se replica a los {{ langs.length }} idiomas. Si cambias de pestaña y generas o subes otra, se aplica <b>solo</b> a ese idioma.</p>
    <div class="field"><label>Instrucciones para la imagen (opcional)</label><textarea class="inp" rows="2" v-model="img.instrucciones" placeholder="Qué quieres ver: escena, colores, elementos…"></textarea></div>
    <div class="form-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="field"><label>Estilo</label><input class="inp" v-model="img.estilo"></div>
      <div class="field"><label>Iluminación</label><input class="inp" v-model="img.iluminacion"></div>
      <div class="field"><label>Ambiente</label><input class="inp" v-model="img.ambiente"></div></div>
    <div class="cover-row">
      <div class="cover-prev"><img v-if="f.cover_image[lang]" :src="f.cover_image[lang]" alt=""><span v-else>Sin portada ({{ lang.toUpperCase() }})</span></div>
      <div class="cover-actions">
        <button type="button" class="btn btn-primary btn-sm" @click="portada" :disabled="busy.img"><Icon name="sparkle" :size="12"/> {{ busy.img?'Generando…':'Generar portada' }}</button>
        <label class="btn btn-ghost btn-sm" style="cursor:pointer">Subir imagen<input type="file" accept="image/*" hidden @change="subirImg"></label>
        <p class="hint">JPG, PNG o WebP · máx 10 MB · se optimiza a 1200×630.</p></div></div>

    <div class="sec-divider"><span>Audio (narración) · {{ lang.toUpperCase() }}</span></div>
    <p class="rec-lang-note">El audio es la narración del contenido, por eso se genera por idioma desde el texto de esta pestaña.</p>
    <div class="cover-actions" style="flex-direction:row;align-items:center;gap:1rem;flex-wrap:wrap">
      <button type="button" class="btn btn-primary btn-sm" @click="audio" :disabled="busy.audio"><Icon name="sparkle" :size="12"/> {{ busy.audio?'Generando…':'Generar audio ('+lang.toUpperCase()+')' }}</button>
      <audio v-if="f.audio_path[lang]" :key="lang" :src="f.audio_path[lang]" controls style="height:36px"></audio>
      <span v-else class="hint">Sin audio en {{ lang.toUpperCase() }}.</span></div>

    <div class="sec-divider"><span>Video</span></div>
    <div class="field"><label>URL de video (opcional)</label><input class="inp" v-model="f.video_url" placeholder="https://…"></div>

    <div class="sec-divider"><span>SEO y publicación</span></div>
    <div class="form-grid two">
      <div class="field"><label>SEO título · {{ lang.toUpperCase() }}</label><input class="inp" v-model="f.seo_title[lang]" maxlength="70"></div>
      <div class="field"><label>SEO descripción · {{ lang.toUpperCase() }}</label><input class="inp" v-model="f.seo_desc[lang]" maxlength="160"></div></div>
    <div class="form-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="field"><label>¿Requiere captura de lead?</label><select class="inp" v-model="f.gated"><option :value="0">No (artículo abierto)</option><option :value="1">Sí (pide datos)</option></select></div>
      <div class="field"><label>Destacado</label><select class="inp" v-model="f.featured"><option :value="0">No</option><option :value="1">Sí</option></select></div>
      <div class="field"><label>Estado</label><select class="inp" v-model="f.status"><option value="draft">Borrador</option><option value="published">Publicado</option></select></div></div>

    <div style="display:flex;justify-content:flex-end;gap:.7rem;margin-top:.5rem">
      <button class="btn btn-ghost btn-sm" @click="$emit('close')">Cancelar</button>
      <button class="btn btn-primary btn-sm" @click="guardar" :disabled="busy.save">{{ busy.save?'Guardando…':'Guardar recurso' }}</button></div>
   </div></div>`,
  data(){
    const base = { slug:'', type:'article', categories:[], author:'', read_minutes:5,
      titulo:empty(), extracto:empty(), cuerpo:empty(), tipo_label:{...empty(),es:'Artículo',en:'Article',pt:'Artigo'},
      cover_image:empty(), audio_path:empty(), video_url:'', file_path:'', seo_title:empty(), seo_desc:empty(),
      gated:0, featured:0, status:'draft', sort:0 };
    let f = base;
    if(this.item){ const it=this.item; f = { ...base,
      slug:it.slug||'', type:it.type||'article', categories:Array.isArray(it.categories)?it.categories:[],
      author:it.author||'', read_minutes:it.read_minutes||5,
      titulo:{...empty(),...(it.titulo||{})}, extracto:{...empty(),...(it.extracto||{})}, cuerpo:{...empty(),...(it.cuerpo||{})},
      tipo_label:{...base.tipo_label,...(it.tipo_label||{})},
      cover_image:toI18n(it.cover_image, true), audio_path:toI18n(it.audio_path, true), video_url:it.video_url||'', file_path:it.file_path||'',
      seo_title:{...empty(),...(it.seo_title||{})}, seo_desc:{...empty(),...(it.seo_desc||{})},
      gated:Number(it.gated||0), featured:Number(it.featured||0), status:it.status||'draft', sort:it.sort||0 }; }
    return { f, cats:CAT_FALLBACK, langs:CMS_LANGS, lang:'es', img:{ instrucciones:'', estilo:'Cinematográfico premium', iluminacion:'Natural cálida', ambiente:'Inspirador' }, busy:{ gen:false, img:false, audio:false, save:false } };
  },
  async mounted(){
    try { const m=await loadMeta(); if(m && Array.isArray(m.resource_categories) && m.resource_categories.length){
      this.cats = m.resource_categories.map(c=>({ key:c.key, nombre:(c.nombre&&typeof c.nombre==='object')?c.nombre:{ es:String(c.nombre||c.key) } })); } } catch(e){}
  },
  methods:{
    onTitulo(){ if(!this.item && this.lang==='es') this.f.slug = slugify(this.f.titulo.es); },
    toggleCat(k){ const i=this.f.categories.indexOf(k); if(i>=0) this.f.categories.splice(i,1); else this.f.categories.push(k); },
    catLabelES(k){ const c=this.cats.find(x=>x.key===k); return c?(c.nombre.es||c.nombre[CMS_CODES[0]]||k):k; },
    // La portada/audio en ES se replica a todos los idiomas; en otro idioma, solo a ese.
    aplicarPorIdioma(campo, valor){ if(this.lang==='es'){ for(const c of CMS_CODES) this.f[campo][c]=valor; } else { this.f[campo][this.lang]=valor; } },
    async generar(){ if(!this.f.titulo.es && !this.f.extracto.es){ toast('Escribe un título o una idea (en ES) primero.','err'); return; }
      this.busy.gen=true;
      const r=await api.post('/admin/recursos/generar',{ titulo:this.f.titulo.es, categorias:this.f.categories.map(k=>this.catLabelES(k)), resumen:this.f.extracto.es, idiomas:CMS_CODES });
      this.busy.gen=false;
      if(r.ok){ for(const k of ['titulo','tipo_label','extracto','cuerpo','seo_title','seo_desc']) if(r.data[k]) this.f[k]={...this.f[k],...r.data[k]};
        if(!this.item && !this.f.slug) this.f.slug=slugify(this.f.titulo.es);
        toast('AlexIA generó todo el contenido en los '+CMS_CODES.length+' idiomas.'); }
      else toast(r.error||'No se pudo generar. Revisa OpenAI en Conectores.','err'); },
    async portada(){ this.busy.img=true;
      const r=await api.post('/admin/recursos/portada',{ instrucciones:this.img.instrucciones, titulo:this.f.titulo[this.lang]||this.f.titulo.es, estilo:this.img.estilo, iluminacion:this.img.iluminacion, ambiente:this.img.ambiente });
      this.busy.img=false;
      if(r.ok){ this.aplicarPorIdioma('cover_image', r.data.cover_image); toast(this.lang==='es'?'Portada generada (aplicada a todos los idiomas).':'Portada generada solo para '+this.lang.toUpperCase()+'.'); } else toast(r.error||'No se pudo generar la portada.','err'); },
    async subirImg(e){ const file=e.target.files[0]; if(!file) return; const fd=new FormData(); fd.append('archivo',file);
      const r=await api.upload('/admin/recursos/subir-imagen',fd); if(r.ok){ this.aplicarPorIdioma('cover_image', r.data.cover_image); toast(this.lang==='es'?'Imagen aplicada a todos los idiomas.':'Imagen aplicada solo a '+this.lang.toUpperCase()+'.'); } else toast(r.error||'Error al subir.','err');
      e.target.value=''; },
    async audio(){ const txt=(this.f.cuerpo[this.lang]||'').replace(/<[^>]+>/g,' '); if(!txt.trim()){ toast('Escribe o genera el contenido en '+this.lang.toUpperCase()+' primero.','err'); return; }
      this.busy.audio=true; const r=await api.post('/admin/recursos/audio',{ texto:txt }); this.busy.audio=false;
      if(r.ok){ this.f.audio_path[this.lang]=r.data.audio_path; toast('Audio generado ('+this.lang.toUpperCase()+').'); } else toast(r.error||'No se pudo generar el audio.','err'); },
    async guardar(){ if(!this.f.slug){ toast('El slug es obligatorio.','err'); return; }
      const p={...this.f};
      if(p.status==='published'){ p.active=1; p.published_at=this.item&&this.item.published_at?this.item.published_at:new Date().toISOString().slice(0,19).replace('T',' '); }
      else { p.active=0; }
      this.busy.save=true;
      const r=this.item ? await api.put('/admin/resources/'+this.item.id,p) : await api.post('/admin/resources',p);
      this.busy.save=false;
      if(r.ok){ toast('Recurso guardado.'); this.$emit('saved'); } else toast(r.error||'Error al guardar.','err'); },
  },
};

// ── Vista: lista de recursos ─────────────────────────────────────────
export const Recursos = {
  components:{ Icon, RecursoEditor, SmartTable },
  template:`<div><div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
      <div><h1>Recursos & Blog</h1><p class="adm__sub" style="margin:0">Artículos y descargables · creación con IA (contenido, portada y audio)</p></div>
      <button class="btn btn-primary btn-sm" @click="nuevo"><Icon name="doc" :size="14"/> Nuevo recurso</button></div>

    <div class="toolbar" style="margin-top:1.2rem"><input class="inp" v-model="q" placeholder="Buscar por título…" style="max-width:280px">
      <select class="inp" v-model="filtroEstado" style="width:auto"><option value="">Todos los estados</option><option value="published">Publicado</option><option value="draft">Borrador</option></select></div>

    <SmartTable :columns="cols" :rows="filtradas" :search="q" :searchKeys="searchKeys" @rowClick="editar">
      <template #actions="{row}"><button class="btn btn-ghost btn-sm" @click="editar(row)">Editar</button>
        <button class="btn btn-danger btn-sm" @click="eliminar(row)">✕</button></template></SmartTable>

    <RecursoEditor v-if="editor.open" :item="editor.item" @close="editor.open=false" @saved="onSaved"/></div>`,
  data(){ return { items:[], q:'', filtroEstado:'', editor:{ open:false, item:null },
    searchKeys:[ (r)=>(r.titulo&&r.titulo.es)||'' ],
    cols:[
      { key:'titulo', label:'Título', raw:(r)=>(r.titulo&&r.titulo.es)||'', render:(r)=>'<b style="color:var(--neutral-light)">'+esc((r.titulo&&r.titulo.es)||'(sin título)')+'</b>' },
      { key:'type', label:'Tipo', render:(r)=> r.type==='download'?'Descargable':'Artículo' },
      { key:'categories', label:'Categorías', raw:(r)=>(r.categories||[]).length, render:(r)=>((r.categories||[]).length)+' cat.' },
      { key:'status', label:'Estado', render:(r)=> r.status==='published'?'<span class="badge cliente">Publicado</span>':'<span class="badge descartado">Borrador</span>' },
      { key:'featured', label:'Destacado', raw:(r)=>Number(r.featured), render:(r)=> Number(r.featured)?'★':'—' },
    ] }; },
  computed:{ filtradas(){ return this.filtroEstado ? this.items.filter(it=>it.status===this.filtroEstado) : this.items; } },
  methods:{
    async load(){ const r=await api.get('/admin/resources/list'); if(r.ok) this.items=r.data; },
    nuevo(){ this.editor={ open:true, item:null }; },
    editar(it){ this.editor={ open:true, item:it }; },
    onSaved(){ this.editor.open=false; this.load(); },
    async eliminar(it){ if(!confirm('¿Eliminar este recurso?'))return; const r=await api.del('/admin/resources/'+it.id); if(r.ok){ toast('Eliminado.'); this.load(); } else toast(r.error||'Error','err'); },
  },
  mounted(){ this.load(); },
};
