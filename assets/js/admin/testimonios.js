// Portal admin · Testimonios de clientes: invitar con enlace de código corto
// (email directo o compartir por WhatsApp/Telegram), revisar lo recibido,
// editar, publicar y destacar. Nada aparece en el sitio sin publicar.
import { api, toast } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { PageHelp, HelpDot } from './help.js';

export const Testimonios = {
  components: { Icon, PageHelp, HelpDot },
  template: `<div><h1>Testimonios
      <PageHelp titulo="Testimonios de clientes"
        que="Convierte la satisfacción de tus clientes en prueba social del sitio. Invitas al cliente con un enlace seguro de código corto; él diligencia un formulario de 2 minutos (texto, foto y logo opcionales, y con qué soluciones/productos trabajó) y tú decides qué se publica. Lo publicado aparece en el Home y en las landings de las soluciones y productos que el cliente marcó."
        :pasos="['Pulsa «Invitar cliente» y escribe su nombre (y correo si lo enviarás por email).','Envía la invitación: por correo directo, o ábrela en WhatsApp/Telegram para mandarla tú mismo. También puedes copiar el enlace.','Cuando el cliente responda, el testimonio queda «Por revisar» (te avisamos por Telegram).','Revisa el texto, ajusta si hay errores de tipeo y pulsa «Publicar».','Marca «Destacar» los mejores: salen de primeros en el sitio.']"/></h1>
    <p class="adm__sub">Prueba social real: tus clientes cuentan su experiencia con su foto y el logo de su empresa</p>

    <div class="conn-kpis">
      <div class="glass stat"><b class="grad-text">{{ resumen.total||0 }}</b><span>Invitaciones</span></div>
      <div class="glass stat"><b class="grad-text">{{ resumen.por_revisar||0 }}</b><span>Por revisar</span></div>
      <div class="glass stat"><b class="grad-text">{{ resumen.publicados||0 }}</b><span>Publicados en el sitio</span></div></div>

    <div class="rev-toolbar">
      <button class="btn btn-primary btn-sm" @click="nuevo=true"><Icon name="plus" :size="14"/> Invitar cliente</button>
      <HelpDot titulo="Invitar cliente" texto="Crea el enlace personal del cliente (con código corto seguro). Después de crearlo verás los botones para enviarlo por email, WhatsApp o Telegram, o copiar el enlace y mandarlo por donde prefieras."/></div>

    <div v-if="!items.length" class="glass panel" style="text-align:center;padding:2.5rem">
      <p>Aún no hay testimonios.</p><p class="small">Pulsa <b>Invitar cliente</b> para enviar tu primera invitación.</p></div>

    <div class="rev-list">
      <div v-for="x in items" :key="x.id" class="glass rev-card" :class="'st-'+estadoCss(x.status)">
        <div class="rev-head">
          <div class="rev-who">
            <img v-if="x.photo" :src="x.photo" alt="" class="testi-photo" style="width:2.4rem;height:2.4rem;border-radius:50%;object-fit:cover">
            <span v-else class="rev-avatar">{{ inicial(x.author||x.client_name) }}</span>
            <div><b>{{ x.author||x.client_name }}</b>
              <div class="rev-stars"><em>{{ [x.cargo,x.empresa].filter(Boolean).join(' · ') || (x.client_email||'') }}</em></div></div></div>
          <div class="rev-flags">
            <span class="badge" :class="estadoClase(x.status)">{{ estadoLabel(x.status) }}</span>
            <img v-if="x.logo" :src="x.logo" alt="" style="height:24px;border-radius:4px;background:#fff;padding:2px 4px">
            <button v-if="x.status!=='invitado'" class="btn btn-ghost btn-sm" :class="{'is-on':x.featured}" @click="destacar(x)">★ {{ x.featured?'Destacado':'Destacar' }}</button></div></div>

        <p v-if="x.quote" class="rev-comment">“{{ x.quote }}”</p>
        <p v-else class="rev-comment rev-empty">El cliente aún no ha diligenciado el formulario.</p>
        <p v-if="x.items&&x.items.length" class="small" style="color:var(--text-tertiary)">Relacionado con: {{ x.items.join(', ') }}</p>

        <div class="conn-actions">
          <template v-if="x.status==='invitado'">
            <button class="btn btn-ghost btn-sm" @click="enviar(x,'email')" :disabled="x._busy">✉ Enviar por email</button>
            <button class="btn btn-ghost btn-sm" @click="enviar(x,'whatsapp')" :disabled="x._busy">Abrir en WhatsApp</button>
            <button class="btn btn-ghost btn-sm" @click="enviar(x,'telegram')" :disabled="x._busy">Abrir en Telegram</button>
            <button class="btn btn-ghost btn-sm" @click="copiar(x)">Copiar enlace</button>
            <HelpDot titulo="Formas de envío" texto="«Enviar por email» lo manda directo desde la plataforma (requiere correo del cliente y el conector SendGrid). «Abrir en WhatsApp/Telegram» abre la app con el mensaje y el enlace listos para que tú lo envíes desde tu cuenta. «Copiar enlace» te deja el enlace en el portapapeles para enviarlo por cualquier otro medio."/>
          </template>
          <template v-if="x.status==='recibido'">
            <button class="btn btn-primary btn-sm" @click="publicar(x)" :disabled="x._busy">Publicar en el sitio</button>
          </template>
          <template v-if="x.status==='publicado'">
            <button class="btn btn-ghost btn-sm" @click="despublicar(x)" :disabled="x._busy">Retirar del sitio</button>
          </template>
          <button class="btn btn-danger btn-sm" @click="eliminar(x)">Eliminar</button></div>
        <p v-if="x._msg" class="small" :style="{color:x._ok?'#4be3a0':'#ff7d9d'}">{{ x._msg }}</p></div></div>

    <div v-if="nuevo" class="modal-bg" @click.self="nuevo=false"><div class="glass modal">
      <h2>Invitar cliente <HelpDot titulo="Invitación" texto="Solo necesitas el nombre. El correo es opcional: si lo pones podrás enviar la invitación por email directo; si no, podrás compartir el enlace por WhatsApp, Telegram o copiado."/></h2>
      <div class="field"><label>Nombre del cliente * <HelpDot titulo="Nombre del cliente" texto="El nombre de la persona que dará el testimonio. Aparece en el saludo del formulario («Hola, Laura») para que se sienta personal."/></label>
        <input class="inp" v-model="inv.client_name" placeholder="Ej: Laura Méndez"></div>
      <div class="field"><label>Correo (opcional) <HelpDot titulo="Correo del cliente" texto="Si lo escribes, el botón «Enviar por email» mandará la invitación directa desde la plataforma con la plantilla de marca (editable en Plantillas email)."/></label>
        <input class="inp" v-model="inv.client_email" placeholder="cliente@empresa.com"></div>
      <div class="field"><label>Idioma del cliente <HelpDot titulo="Idioma" texto="Define el idioma del mensaje de invitación y del formulario que verá el cliente (español, inglés o portugués)."/></label>
        <select class="inp" v-model="inv.locale"><option value="es">Español</option><option value="en">English</option><option value="pt">Português</option></select></div>
      <div style="display:flex;justify-content:flex-end;gap:.7rem">
        <button class="btn btn-ghost btn-sm" @click="nuevo=false">Cancelar</button>
        <button class="btn btn-primary btn-sm" @click="crear" :disabled="busy">{{ busy?'Creando…':'Crear invitación' }}</button></div>
    </div></div></div>`,
  data() { return { items: [], resumen: {}, nuevo: false, busy: false, inv: { client_name: '', client_email: '', locale: 'es' } }; },
  methods: {
    async load() { const r = await api.get('/admin/testimonios'); if (!r.ok) { toast(r.error || 'Error', 'err'); return; }
      this.resumen = r.data.resumen; this.items = r.data.items.map(x => ({ ...x, _busy: false, _msg: '', _ok: false })); },
    inicial(a) { return (a || '?').trim().charAt(0).toUpperCase(); },
    estadoCss(s) { return { invitado: 'ninguna', recibido: 'sugerida', publicado: 'publicada' }[s] || 'ninguna'; },
    estadoLabel(s) { return { invitado: 'Invitación enviada', recibido: 'Por revisar', publicado: 'Publicado' }[s] || s; },
    estadoClase(s) { return { publicado: 'ok', recibido: 'cliente' }[s] || ''; },
    async crear() {
      if (!this.inv.client_name.trim()) { toast('Escribe el nombre del cliente.', 'err'); return; }
      this.busy = true;
      const r = await api.post('/admin/testimonios', this.inv);
      this.busy = false;
      if (r.ok) { toast('Invitación creada. Ahora envíala al cliente.'); this.nuevo = false; this.inv = { client_name: '', client_email: '', locale: 'es' }; this.load(); }
      else toast(r.error || 'Error', 'err');
    },
    async enviar(x, via) {
      x._busy = true; x._msg = '';
      const r = await api.post('/admin/testimonios/' + x.id + '/enviar', { via });
      x._busy = false;
      if (r.ok && r.data.share_url) { window.open(r.data.share_url, '_blank'); x._ok = true; x._msg = 'Se abrió ' + (via === 'whatsapp' ? 'WhatsApp' : 'Telegram') + ' con el mensaje listo.'; }
      else if (r.ok) { x._ok = true; x._msg = r.data.message || 'Invitación enviada.'; }
      else { x._ok = false; x._msg = r.error || 'No se pudo enviar.'; }
    },
    async copiar(x) { try { await navigator.clipboard.writeText(x.url); toast('Enlace copiado.'); } catch (e) { prompt('Copia el enlace:', x.url); } },
    async publicar(x) { x._busy = true; const r = await api.put('/admin/testimonios/' + x.id, { status: 'publicado' });
      x._busy = false; if (r.ok) { toast('Publicado en el sitio.'); this.load(); } else { x._ok = false; x._msg = r.error || 'Error'; } },
    async despublicar(x) { x._busy = true; const r = await api.put('/admin/testimonios/' + x.id, { status: 'recibido' });
      x._busy = false; if (r.ok) { toast('Retirado del sitio.'); this.load(); } else toast(r.error || 'Error', 'err'); },
    async destacar(x) { const r = await api.put('/admin/testimonios/' + x.id, { featured: !x.featured });
      if (r.ok) this.load(); else toast(r.error || 'Error', 'err'); },
    async eliminar(x) { if (!confirm('¿Eliminar esta invitación/testimonio? Esta acción no se puede deshacer.')) return;
      const r = await api.del('/admin/testimonios/' + x.id); if (r.ok) { toast('Eliminado.'); this.load(); } else toast(r.error || 'Error', 'err'); },
  },
  mounted() { this.load(); },
};
