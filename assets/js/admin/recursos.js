// Portal admin · Estudio de Recursos con IA (contenido trilingüe, portada, audio)
import { api, toast } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

// Categorías (deben coincidir con api/config/business.php · resource_categories)
const CATS = {
  ia_negocios:'IA aplicada a negocios', automatizacion:'Automatización', growth:'Growth',
  estrategia:'Estrategia', marketing:'Marketing estratégico', crm:'CRM', ventas:'Ventas',
  experiencia_cliente:'Experiencia de cliente', agentes:'Agentes inteligentes',
  datos:'Datos y analítica', liderazgo:'Liderazgo', transformacion:'Transformación digital',
};
const LANGS = ['es','en','pt'];
const slugify = (s)=> (s||'').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,80);
const empty = ()=> ({ es:'', en:'', pt:'' });

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
    <div style="display:flex;justify-content:space-between;align-items:center">
      <h2>{{ item?'Editar recurso':'Nuevo recurso' }}</h2>
      <button class="btn btn-ghost btn-sm" @click="$emit('close')">✕</button></div>

    <div class="form-grid two">
      <div class="field"><label>Título (ES)</label><input class="inp" v-model="f.titulo.es" @input="autoSlug"></div>
      <div class="field"><label>Slug (URL) · del título</label><input class="inp" v-model="f.slug"></div></div>

    <div class="form-grid two">
      <div class="field"><label>Tipo</label><select class="inp" v-model="f.type"><option value="article">Artículo</option><option value="download">Descargable (PDF)</option></select></div>
      <div class="field"><label>Etiqueta (ES)</label><input class="inp" v-model="f.tipo_label.es" placeholder="Artículo"></div></div>

    <div class="field"><label>Categorías (elige una o varias)</label>
      <div class="cats-chips"><button type="button" v-for="(l,k) in cats" :key="k" class="chip-cat" :class="{on:f.categories.includes(k)}" @click="toggleCat(k)">{{ l }}</button></div></div>

    <div class="form-grid two">
      <div class="field"><label>Autor</label><input class="inp" v-model="f.author" placeholder="Equipo ExperientIA"></div>
      <div class="field"><label>Minutos de lectura</label><input class="inp" type="number" min="1" v-model="f.read_minutes"></div></div>

    <div class="sec-divider"><span>Contenido</span>
      <div class="tabs">
        <button type="button" v-for="l in langs" :key="l" :class="{active:lang===l}" @click="lang=l">{{ l.toUpperCase() }}</button>
        <button type="button" class="gen-btn" @click="generar" :disabled="busy.gen"><Icon name="sparkle" :size="12"/> {{ busy.gen?'Generando…':'Generar con AlexIA' }}</button></div></div>

    <div class="field"><label>Resumen (excerpt) · {{ lang.toUpperCase() }}</label><textarea class="inp" rows="2" v-model="f.extracto[lang]"></textarea></div>
    <div class="field"><label>Contenido · {{ lang.toUpperCase() }}</label><RichEditor v-model="f.cuerpo[lang]"/></div>

    <div class="sec-divider"><span>Imagen de portada</span></div>
    <div class="field"><label>Instrucciones para la imagen (opcional)</label><textarea class="inp" rows="2" v-model="img.instrucciones" placeholder="Qué quieres ver: escena, colores, elementos…"></textarea></div>
    <div class="form-grid" style="grid-template-columns:repeat(3,1fr)">
      <div class="field"><label>Estilo</label><input class="inp" v-model="img.estilo"></div>
      <div class="field"><label>Iluminación</label><input class="inp" v-model="img.iluminacion"></div>
      <div class="field"><label>Ambiente</label><input class="inp" v-model="img.ambiente"></div></div>
    <div class="cover-row">
      <div class="cover-prev"><img v-if="f.cover_image" :src="f.cover_image" alt=""><span v-else>Sin portada</span></div>
      <div class="cover-actions">
        <button type="button" class="btn btn-primary btn-sm" @click="portada" :disabled="busy.img"><Icon name="sparkle" :size="12"/> {{ busy.img?'Generando…':'Generar portada' }}</button>
        <label class="btn btn-ghost btn-sm" style="cursor:pointer">Subir imagen<input type="file" accept="image/*" hidden @change="subirImg"></label>
        <p class="hint">JPG, PNG o WebP · máx 10 MB · se optimiza a 1200×630.</p></div></div>

    <div class="sec-divider"><span>Audio (narración)</span></div>
    <div class="cover-actions" style="flex-direction:row;align-items:center;gap:1rem;flex-wrap:wrap">
      <button type="button" class="btn btn-primary btn-sm" @click="audio" :disabled="busy.audio"><Icon name="sparkle" :size="12"/> {{ busy.audio?'Generando…':'Generar audio del artículo' }}</button>
      <audio v-if="f.audio_path" :src="f.audio_path" controls style="height:36px"></audio></div>

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
      titulo:empty(), extracto:empty(), cuerpo:empty(), tipo_label:{es:'Artículo',en:'Article',pt:'Artigo'},
      cover_image:'', audio_path:'', video_url:'', file_path:'', seo_title:empty(), seo_desc:empty(),
      gated:0, featured:0, status:'draft', sort:0 };
    let f = base;
    if(this.item){ const it=this.item; f = { ...base,
      slug:it.slug||'', type:it.type||'article', categories:Array.isArray(it.categories)?it.categories:[],
      author:it.author||'', read_minutes:it.read_minutes||5,
      titulo:{...empty(),...(it.titulo||{})}, extracto:{...empty(),...(it.extracto||{})}, cuerpo:{...empty(),...(it.cuerpo||{})},
      tipo_label:{...base.tipo_label,...(it.tipo_label||{})},
      cover_image:it.cover_image||'', audio_path:it.audio_path||'', video_url:it.video_url||'', file_path:it.file_path||'',
      seo_title:{...empty(),...(it.seo_title||{})}, seo_desc:{...empty(),...(it.seo_desc||{})},
      gated:Number(it.gated||0), featured:Number(it.featured||0), status:it.status||'draft', sort:it.sort||0 }; }
    return { f, cats:CATS, langs:LANGS, lang:'es', img:{ instrucciones:'', estilo:'Cinematográfico premium', iluminacion:'Natural cálida', ambiente:'Inspirador' }, busy:{ gen:false, img:false, audio:false, save:false } };
  },
  methods:{
    autoSlug(){ if(!this.item) this.f.slug = slugify(this.f.titulo.es); },
    toggleCat(k){ const i=this.f.categories.indexOf(k); if(i>=0) this.f.categories.splice(i,1); else this.f.categories.push(k); },
    async generar(){ if(!this.f.titulo.es && !this.f.extracto.es){ toast('Escribe un título o una idea primero.','err'); return; }
      this.busy.gen=true;
      const r=await api.post('/admin/recursos/generar',{ titulo:this.f.titulo.es, categorias:this.f.categories.map(k=>CATS[k]), resumen:this.f.extracto.es });
      this.busy.gen=false;
      if(r.ok){ for(const k of ['extracto','cuerpo','seo_title','seo_desc']) if(r.data[k]) this.f[k]={...this.f[k],...r.data[k]}; toast('Contenido generado con AlexIA.'); }
      else toast(r.error||'No se pudo generar. Revisa OpenAI en Conectores.','err'); },
    async portada(){ this.busy.img=true;
      const r=await api.post('/admin/recursos/portada',{ instrucciones:this.img.instrucciones, titulo:this.f.titulo.es, estilo:this.img.estilo, iluminacion:this.img.iluminacion, ambiente:this.img.ambiente });
      this.busy.img=false;
      if(r.ok){ this.f.cover_image=r.data.cover_image; toast('Portada generada.'); } else toast(r.error||'No se pudo generar la portada.','err'); },
    async subirImg(e){ const file=e.target.files[0]; if(!file) return; const fd=new FormData(); fd.append('archivo',file);
      const r=await api.upload('/admin/recursos/subir-imagen',fd); if(r.ok){ this.f.cover_image=r.data.cover_image; toast('Imagen subida.'); } else toast(r.error||'Error al subir.','err'); },
    async audio(){ const txt=(this.f.cuerpo.es||'').replace(/<[^>]+>/g,' '); if(!txt.trim()){ toast('Genera o escribe el contenido primero.','err'); return; }
      this.busy.audio=true; const r=await api.post('/admin/recursos/audio',{ texto:txt }); this.busy.audio=false;
      if(r.ok){ this.f.audio_path=r.data.audio_path; toast('Audio generado.'); } else toast(r.error||'No se pudo generar el audio.','err'); },
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
  components:{ Icon, RecursoEditor },
  template:`<div><div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
      <div><h1>Recursos & Blog</h1><p class="adm__sub" style="margin:0">Artículos y descargables · creación con IA (contenido, portada y audio)</p></div>
      <button class="btn btn-primary btn-sm" @click="nuevo"><Icon name="doc" :size="14"/> Nuevo recurso</button></div>

    <div class="toolbar" style="margin-top:1.2rem"><input class="inp" v-model="q" placeholder="Buscar por título…" style="max-width:280px">
      <select class="inp" v-model="filtroEstado" style="width:auto"><option value="">Todos los estados</option><option value="published">Publicado</option><option value="draft">Borrador</option></select></div>

    <div class="glass panel" style="padding:0;overflow:hidden;margin-top:.4rem">
      <table><thead><tr>
        <th>Título</th><th>Tipo</th><th>Categorías</th><th>Estado</th><th>Destacado</th><th></th></tr></thead>
      <tbody>
        <tr v-for="it in visibles" :key="it.id" class="row" @click="editar(it)">
          <td style="color:var(--neutral-light)">{{ (it.titulo&&it.titulo.es)||'(sin título)' }}</td>
          <td>{{ it.type==='download'?'Descargable':'Artículo' }}</td>
          <td class="small">{{ (it.categories||[]).length }} cat.</td>
          <td><span class="badge" :class="it.status==='published'?'cliente':'descartado'">{{ it.status==='published'?'Publicado':'Borrador' }}</span></td>
          <td>{{ Number(it.featured)?'★':'—' }}</td>
          <td style="text-align:right"><button class="btn btn-ghost btn-sm" @click.stop="editar(it)">Editar</button>
            <button class="btn btn-danger btn-sm" @click.stop="eliminar(it)">✕</button></td></tr>
        <tr v-if="!visibles.length"><td colspan="6" class="empty" style="text-align:center;padding:1.8rem">No hay recursos con estos filtros.</td></tr>
      </tbody></table></div>

    <RecursoEditor v-if="editor.open" :item="editor.item" @close="editor.open=false" @saved="onSaved"/></div>`,
  data(){ return { items:[], q:'', filtroEstado:'', editor:{ open:false, item:null } }; },
  computed:{ visibles(){ const q=this.q.trim().toLowerCase();
    return this.items.filter(it=>{ const t=((it.titulo&&it.titulo.es)||'').toLowerCase();
      return (!q||t.includes(q)) && (!this.filtroEstado||it.status===this.filtroEstado); }); } },
  methods:{
    async load(){ const r=await api.get('/admin/resources/list'); if(r.ok) this.items=r.data; },
    nuevo(){ this.editor={ open:true, item:null }; },
    editar(it){ this.editor={ open:true, item:it }; },
    onSaved(){ this.editor.open=false; this.load(); },
    async eliminar(it){ if(!confirm('¿Eliminar este recurso?'))return; const r=await api.del('/admin/resources/'+it.id); if(r.ok){ toast('Eliminado.'); this.load(); } else toast(r.error||'Error','err'); },
  },
  mounted(){ this.load(); },
};
