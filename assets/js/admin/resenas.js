// Portal admin · Reseñas de Google: prueba social + respuestas de AlexIA.
// Flujo por defecto: AlexIA SUGIERE, el admin aprueba (y al aprobar se publica).
// Auto-piloto: AlexIA genera y publica sola, documentado y visible.
import { api, toast } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

export const Resenas = {
  components: { Icon },
  template: `<div><h1>Reseñas de Google</h1>
    <p class="adm__sub">Prueba social del sitio + respuestas redactadas por AlexIA · sincronizadas desde tu ficha de Google Business</p>

    <div class="conn-kpis rev-kpis">
      <div class="glass stat"><b class="grad-text">{{ resumen.total||0 }}</b><span>Reseñas</span></div>
      <div class="glass stat"><b class="grad-text">{{ Number(resumen.promedio||0).toFixed(1) }}★</b><span>Promedio</span></div>
      <div class="glass stat"><b class="grad-text">{{ resumen.pendientes||0 }}</b><span>Sin responder</span></div>
      <div class="glass stat"><b class="grad-text">{{ resumen.destacadas||0 }}</b><span>Destacadas en el sitio</span></div></div>

    <div class="glass rev-autopilot" :class="{on:autoReply}">
      <div class="ra-txt"><b>{{ autoReply ? 'Auto-piloto de AlexIA · ACTIVO' : 'Aprobación manual (recomendado al inicio)' }}</b>
        <p v-if="!autoReply">AlexIA sugiere una respuesta para cada reseña. Tú la revisas y, al aprobar, se publica en Google. Nada se publica sin tu clic.</p>
        <p v-else>AlexIA genera <b>y publica</b> las respuestas automáticamente, alineadas con la misión y los servicios de ExperientIA. Quedan documentadas y visibles aquí, sin aprobación previa.</p></div>
      <label class="switch"><input type="checkbox" :checked="autoReply" @change="toggleAuto($event.target.checked)"><span></span></label>
    </div>
    <p v-if="autoReply" class="rev-warn">⚠️ Con el auto-piloto activo, las próximas sincronizaciones publicarán las respuestas de AlexIA sin pedirte confirmación. Actívalo solo cuando confíes en que sus respuestas reflejan la voz de ExperientIA.</p>

    <div class="rev-toolbar">
      <button class="btn btn-ghost btn-sm" @click="sincronizar" :disabled="busy.sync"><Icon name="gear" :size="15"/> {{ busy.sync?'Sincronizando…':'Sincronizar con Google' }}</button>
      <span v-if="syncMsg" class="small" :style="{color:syncOk?'#4be3a0':'#ff7d9d'}">{{ syncMsg }}</span></div>

    <div v-if="!items.length" class="glass panel" style="text-align:center;padding:2.5rem">
      <p>Aún no hay reseñas sincronizadas.</p>
      <p class="small">Configura y prueba el conector <b>Google Business</b> en Conectores, luego pulsa “Sincronizar con Google”.</p></div>

    <div class="rev-list">
      <div v-for="r in items" :key="r.id" class="glass rev-card" :class="'st-'+(r.reply_status||'ninguna')">
        <div class="rev-head">
          <div class="rev-who"><span class="rev-avatar">{{ inicial(r.author) }}</span>
            <div><b>{{ r.author }}</b><div class="rev-stars"><span v-for="s in 5" :key="s" :class="{on:s<=r.stars}">★</span> <em>{{ fecha(r.created_at) }}</em></div></div></div>
          <div class="rev-flags">
            <span class="badge" :class="estadoClase(r.reply_status)">{{ estadoLabel(r.reply_status) }}</span>
            <button class="btn btn-ghost btn-sm" :class="{'is-on':r.featured}" @click="destacar(r)" :title="r.featured?'Quitar de destacados':'Mostrar en el sitio'">★ {{ r.featured?'Destacada':'Destacar' }}</button></div>
        </div>
        <p class="rev-comment" v-if="r.comment">“{{ r.comment }}”</p>
        <p class="rev-comment rev-empty" v-else>(Solo calificación, sin comentario)</p>

        <div v-if="r.reply_status==='publicada'" class="rev-published">
          <b>Respuesta publicada{{ r.reply_by ? ' · '+(r.reply_by==='alexia'?'AlexIA (auto)':r.reply_by) : '' }}:</b>
          <p>{{ r.reply }}</p></div>

        <div v-else class="rev-reply-box">
          <label class="rev-lbl">Respuesta sugerida por AlexIA <span v-if="r.reply_status!=='sugerida'" class="small">(genera un borrador)</span></label>
          <textarea class="inp" rows="3" v-model="r.ai_reply" placeholder="AlexIA redactará aquí. También puedes escribir o editar la respuesta antes de publicar."></textarea>
          <div class="rev-actions">
            <button class="btn btn-ghost btn-sm" @click="sugerir(r)" :disabled="r._busy"><Icon name="sparkle" :size="14"/> {{ r._busy?'Redactando…':(r.ai_reply?'Regenerar':'Que AlexIA redacte') }}</button>
            <button class="btn btn-primary btn-sm" @click="aprobar(r)" :disabled="r._busy||!(r.ai_reply&&r.ai_reply.trim())">Aprobar y publicar</button>
          </div>
          <p v-if="r._msg" class="small" :style="{color:r._ok?'#4be3a0':'#ff7d9d'}">{{ r._msg }}</p>
        </div>
      </div></div></div>`,
  data(){ return { items:[], resumen:{}, autoReply:false, busy:{sync:false}, syncMsg:'', syncOk:false }; },
  methods:{
    async load(){ const r=await api.get('/admin/resenas'); if(!r.ok){ toast(r.error||'Error','err'); return; }
      this.resumen=r.data.resumen; this.autoReply=!!r.data.auto_reply;
      this.items=r.data.items.map(x=>({ ...x, _busy:false, _msg:'', _ok:false })); },
    inicial(a){ return (a||'?').trim().charAt(0).toUpperCase(); },
    fecha(f){ if(!f) return ''; return new Date(f.replace(' ','T')+'Z').toLocaleDateString('es',{day:'numeric',month:'short',year:'numeric'}); },
    estadoLabel(s){ return {ninguna:'Sin responder',sugerida:'Borrador listo',aprobada:'Aprobada',publicada:'Publicada'}[s]||'Sin responder'; },
    estadoClase(s){ return {publicada:'ok',sugerida:'cliente',aprobada:'cliente'}[s]||''; },
    async toggleAuto(val){ const r=await api.post('/admin/resenas/ajustes',{auto_reply:val});
      if(r.ok){ this.autoReply=!!r.data.auto_reply; toast(this.autoReply?'Auto-piloto activado.':'Aprobación manual restablecida.'); }
      else toast(r.error||'Error','err'); },
    async sincronizar(){ this.busy.sync=true; this.syncMsg='';
      const r=await api.post('/admin/connectors/google_business/accion/sync_reviews',{});
      this.busy.sync=false; this.syncOk=!!(r.data&&r.data.ok); this.syncMsg=(r.data&&(r.data.message||r.data.error))||r.error||'—';
      if(this.syncOk) this.load(); },
    async sugerir(r){ r._busy=true; r._msg=''; const res=await api.post('/admin/resenas/'+r.id+'/sugerir',{});
      r._busy=false; if(res.ok){ r.ai_reply=res.data.ai_reply; r.reply_status=r.reply_status==='publicada'?'publicada':'sugerida'; r._ok=true; r._msg='Borrador listo. Revísalo y aprueba.'; }
      else { r._ok=false; r._msg=res.error||'AlexIA no disponible.'; } },
    async aprobar(r){ if(!confirm('¿Publicar esta respuesta en Google Business? Será visible públicamente.')) return;
      r._busy=true; r._msg=''; const res=await api.post('/admin/resenas/'+r.id+'/aprobar',{reply:r.ai_reply});
      r._busy=false; if(res.ok){ toast('Respuesta publicada en Google.'); this.load(); }
      else { r._ok=false; r._msg=res.error||'No se pudo publicar.'; } },
    async destacar(r){ const nv=r.featured?0:1; const res=await api.post('/admin/resenas/'+r.id+'/destacar',{featured:!!nv});
      if(res.ok){ r.featured=res.data.featured; this.load(); } else toast(res.error||'Error','err'); },
  },
  mounted(){ this.load(); },
};
