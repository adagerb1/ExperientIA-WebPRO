// Propuesta comercial confidencial: el cliente llega con el enlace del mailing,
// se identifica con su correo autorizado + NIT (compuerta) y ve el documento
// premium (resumen → contexto → objetivos → alcance → fases → entregables →
// tiempos → inversión → condiciones → próximos pasos) con opción de aceptarla.
import { t, pageUrl, api, store, setMeta } from '../lib/core.js';
import { Icon, CountUp, ScrollProgress } from '../lib/ui.js';

const tx = (k, fb) => { const v = t(k); return (v && v !== k) ? v : fb; };

export const PropuestaView = {
  components: { Icon, CountUp, ScrollProgress },
  template: `<div>
    <!-- Compuerta de acceso -->
    <section v-if="fase!=='doc'" class="page-hero" style="min-height:88vh;display:grid;align-items:center">
      <div class="bg-atmos"><div class="halo halo-cyan anim-pulse" style="width:560px;height:560px;top:-240px;right:-160px"></div>
        <div class="halo halo-violet anim-pulse" style="width:460px;height:460px;bottom:-200px;left:-180px;animation-delay:2.2s"></div></div>
      <div class="container" style="max-width:30rem">
        <div v-if="fase==='cargando'" class="glass card" style="text-align:center;padding:2.5rem"><p>{{ tx('prop.cargando','Verificando el enlace…') }}</p></div>
        <div v-else-if="fase==='invalido'" class="glass card" style="text-align:center;padding:2.5rem;display:grid;gap:1rem;justify-items:center">
          <h2 class="h3">{{ tx('prop.invalido_t','Propuesta no disponible') }}</h2>
          <p>{{ tx('prop.invalido_s','El enlace no es válido o la propuesta fue retirada. Escríbenos y lo resolvemos.') }}</p>
          <a href="mailto:hello@experientia.pro" class="btn btn-primary">hello@experientia.pro</a></div>
        <form v-else class="glass glass-lit card" style="display:grid;gap:1.1rem;padding:2.2rem" @submit.prevent="entrar" v-reveal>
          <div style="display:grid;gap:.4rem;justify-items:center;text-align:center">
            <span class="chip chip--conf"><Icon name="shield" :size="12"/> {{ tx('prop.conf','Documento confidencial') }}</span>
            <h1 class="h3" style="margin-top:.4rem">{{ tx('prop.gate_t','Propuesta para') }} {{ cliente }}</h1>
            <p class="small">{{ tx('prop.gate_s','Para proteger la información, confirma tu identidad con los datos autorizados.') }}</p></div>
          <div class="field"><label>{{ tx('prop.gate_email','Tu correo corporativo') }}</label>
            <input v-model="gate.email" type="email" class="field-el" placeholder="nombre@tuempresa.com" autocomplete="email"></div>
          <div class="field"><label>{{ tx('prop.gate_nit','NIT de la empresa (solo números)') }}</label>
            <input v-model="gate.nit" inputmode="numeric" class="field-el" placeholder="9001234567">
            <p class="hint">{{ tx('prop.gate_nit_h','Sin puntos ni guiones. Si tu propuesta no exige NIT, puedes dejarlo vacío.') }}</p></div>
          <p v-if="error" class="small" style="color:#ff7d9d">{{ error }}</p>
          <button class="btn btn-grad" :disabled="busy">{{ busy?tx('prop.entrando','Abriendo…'):tx('prop.entrar','Abrir la propuesta') }}</button>
          <p class="small" style="text-align:center">{{ tx('prop.gate_nota','Cada apertura queda registrada. Uso exclusivo del cliente.') }}</p>
        </form></div></section>

    <!-- Documento -->
    <div v-else class="prop-doc">
      <ScrollProgress/>
      <section class="page-hero"><div class="bg-atmos"><div class="halo halo-cyan" style="width:560px;height:560px;top:-260px;right:-140px"></div><div class="halo halo-violet" style="width:440px;height:440px;top:60px;left:-200px;opacity:.32"></div></div>
        <div class="container page-hero__in" style="max-width:56rem">
          <p class="eyebrow" v-reveal>{{ c.eyebrow || tx('prop.conf','Documento confidencial') }}</p>
          <h1 class="h1" v-reveal :style="{'--d':'.08s'}">{{ c.title || d.title }}</h1>
          <p class="lead" v-reveal :style="{'--d':'.16s'}" v-if="c.subtitle">{{ c.subtitle }}</p>
          <div class="prop-meta" v-reveal :style="{'--d':'.22s'}">
            <span><b>{{ tx('prop.m_cliente','Cliente') }}:</b> {{ d.cliente }}</span>
            <span v-if="d.nit"><b>NIT:</b> {{ d.nit }}</span>
            <span v-if="d.contact"><b>{{ tx('prop.m_contacto','Contacto') }}:</b> {{ d.contact }}</span>
            <span v-if="d.doc_date"><b>{{ tx('prop.m_fecha','Fecha') }}:</b> {{ d.doc_date }}</span>
            <span v-if="d.version">{{ d.version }}</span>
            <span v-if="d.estado==='aceptada'" class="badge ok" style="font-size:.72rem">{{ tx('prop.aceptada','Aceptada') }} ✓</span></div>
          <p class="small" v-if="c.hero_note" v-reveal :style="{'--d':'.28s'}">{{ c.hero_note }}</p></div></section>

      <section class="section" style="padding-top:0"><div class="container" style="max-width:56rem;display:grid;gap:1.4rem">
        <div v-if="c.resumen && c.resumen.text" class="glass glass-lit card" v-reveal><h2 class="lbl" style="color:var(--cyan);margin-bottom:.8rem">{{ c.resumen.title }}</h2><p class="lead">{{ c.resumen.text }}</p></div>
        <div v-if="c.contexto && c.contexto.text" class="glass card" v-reveal><h2 class="lbl" style="margin-bottom:.8rem">{{ c.contexto.title }}</h2><p style="line-height:1.65">{{ c.contexto.text }}</p></div>

        <div class="prop-two" v-if="hayLista(c.objetivos)||hayLista(c.alcance)">
          <div v-if="hayLista(c.objetivos)" class="glass card" v-reveal><h2 class="lbl" style="margin-bottom:1rem">{{ c.objetivos.title }}</h2>
            <ul class="check-list"><li v-for="(o,i) in c.objetivos.items" :key="i"><span class="check-chip"><Icon name="check" :size="13"/></span>{{ o }}</li></ul></div>
          <div v-if="hayLista(c.alcance)" class="glass card" v-reveal :style="{'--d':'.08s'}"><h2 class="lbl" style="margin-bottom:1rem">{{ c.alcance.title }}</h2>
            <ul class="check-list"><li v-for="(o,i) in c.alcance.items" :key="i"><span class="check-chip"><Icon name="check" :size="13"/></span>{{ o }}</li></ul></div></div>

        <div v-if="c.fases && c.fases.length" v-reveal>
          <h2 class="h2" style="margin:1rem 0 1.4rem">{{ tx('prop.fases','Cómo lo haremos') }}</h2>
          <div class="land-steps">
            <div v-for="(f,i) in c.fases" :key="i" class="glass card step" v-reveal :style="{'--d':i*.08+'s'}">
              <span class="step-num">{{ i+1 }}</span><div><span class="prop-fase">{{ f.phase }}</span><h3 class="h3">{{ f.title }}</h3><p>{{ f.text }}</p></div></div></div></div>

        <div class="prop-two">
          <div v-if="hayLista(c.entregables)" class="glass card" v-reveal><h2 class="lbl" style="margin-bottom:1rem">{{ c.entregables.title }}</h2>
            <ul class="check-list"><li v-for="(o,i) in c.entregables.items" :key="i"><span class="check-chip"><Icon name="check" :size="13"/></span>{{ o }}</li></ul></div>
          <div v-if="c.tiempo && c.tiempo.text" class="glass card" v-reveal :style="{'--d':'.08s'}"><h2 class="lbl" style="margin-bottom:.8rem"><Icon name="clock" :size="14"/> {{ c.tiempo.title }}</h2><p style="line-height:1.6">{{ c.tiempo.text }}</p></div></div>

        <div v-if="c.inversion" class="glass glass-lit card prop-inv" v-reveal>
          <div><h2 class="lbl" style="color:var(--cyan)">{{ c.inversion.title }}</h2>
            <p class="prop-amount grad-text">{{ c.inversion.amount }}</p>
            <p class="small" v-if="c.inversion.note">{{ c.inversion.note }}</p></div>
          <div v-if="hayLista(c.payment_terms)"><h3 class="lbl" style="margin-bottom:.6rem">{{ c.payment_terms.title }}</h3>
            <ul class="prop-list"><li v-for="(o,i) in c.payment_terms.items" :key="i">{{ o }}</li></ul></div></div>

        <div v-if="hayLista(c.conditions)" class="glass card" v-reveal><h2 class="lbl" style="margin-bottom:1rem">{{ c.conditions.title }}</h2>
          <ul class="prop-list"><li v-for="(o,i) in c.conditions.items" :key="i">{{ o }}</li></ul></div>

        <div v-if="hayLista(c.next_steps)" class="glass card" v-reveal><h2 class="lbl" style="margin-bottom:1rem">{{ c.next_steps.title }}</h2>
          <ol class="prop-steps"><li v-for="(o,i) in c.next_steps.items" :key="i">{{ o }}</li></ol></div>

        <div class="glass glass-lit card land-final" v-reveal>
          <h2 class="h2">{{ (c.cta && c.cta.title) || tx('prop.cta_t','Demos el siguiente paso') }}</h2>
          <p class="lead" v-if="c.cta && c.cta.text">{{ c.cta.text }}</p>
          <div class="land-cta-row" v-if="d.estado!=='aceptada'">
            <button class="btn btn-grad" @click="aceptar" :disabled="busy">{{ busy?'…':tx('prop.aceptar','Aceptar la propuesta') }}</button>
            <router-link :to="pageUrl('agenda')" class="btn btn-ghost">{{ tx('prop.dudas','Agendar una conversación') }}</router-link></div>
          <p v-else class="lead" style="color:#4be3a0">✓ {{ tx('prop.aceptada_msg','Propuesta aceptada. Nos pondremos en contacto para el arranque.') }}</p>
          <p v-if="error" class="small" style="color:#ff7d9d">{{ error }}</p>
          <p class="small garantia" style="justify-content:center"><Icon name="shield" :size="13"/> {{ tx('prop.pie','Documento confidencial · uso exclusivo de') }} {{ d.cliente }}</p></div>
      </div></section></div></div>`,
  data() { return { fase: 'cargando', cliente: '', gate: { email: '', nit: '' }, d: {}, c: {}, error: '', busy: false }; },
  computed: { pageUrl: () => pageUrl },
  methods: { tx,
    hayLista(x) { return x && Array.isArray(x.items) && x.items.length; },
    async entrar() {
      this.error = '';
      if (!this.gate.email.trim()) { this.error = tx('prop.err_email', 'Escribe tu correo corporativo.'); return; }
      this.busy = true;
      const r = await api.post('/propuesta/' + this.$route.params.code + '/acceso', this.gate);
      this.busy = false;
      if (r.ok) { this.d = r.data; this.c = r.data.contenido || {}; this.fase = 'doc'; window.scrollTo({ top: 0 }); }
      else this.error = r.error || tx('prop.err_acceso', 'No fue posible validar el acceso.');
    },
    async aceptar() {
      if (!confirm(tx('prop.confirmar', '¿Confirmas la aceptación de esta propuesta? Notificaremos al equipo de ExperientIA.'))) return;
      this.busy = true; this.error = '';
      const r = await api.post('/propuesta/' + this.$route.params.code + '/aceptar', { email: this.gate.email });
      this.busy = false;
      if (r.ok) { this.d.estado = 'aceptada'; }
      else this.error = r.error || 'No se pudo registrar la aceptación.';
    },
  },
  async mounted() {
    setMeta(tx('prop.meta', 'Propuesta confidencial') + ' · ExperientIA', '');
    const r = await api.get('/propuesta/' + this.$route.params.code);
    if (!r.ok) { this.fase = 'invalido'; return; }
    this.cliente = r.data.cliente || '';
    this.fase = 'gate';
  },
};
