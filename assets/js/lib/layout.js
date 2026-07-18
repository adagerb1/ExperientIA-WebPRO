// ExperientIA · Layout del sitio público: header, footer, AlexIA, toasts.
import { store, t, tr, pageUrl, api, toast } from './core.js';
import { Icon, BrandLogo, BrandSymbol } from './ui.js';
import { PhoneInput } from './forms.js';

const SOCIAL = [
  { name: 'Instagram', icon: 'instagram', url: 'https://www.instagram.com/experientia.sas/' },
  { name: 'LinkedIn', icon: 'linkedin', url: 'https://www.linkedin.com/in/tonny-dager/' },
  { name: 'Facebook', icon: 'facebook', url: 'https://www.facebook.com/Experientia.SAS' },
];

export const SiteHeader = {
  components: { Icon, BrandLogo },
  template: `<header class="site-header" :class="{scrolled,open}">
    <div class="container site-header__in">
      <router-link :to="pageUrl('home')" @click="open=false"><BrandLogo :size="36" :descriptor="false"/></router-link>
      <nav class="nav">
        <ul>
          <li v-for="it in items" :key="it.key"><router-link class="navlink" :class="{active:isActive(it.key)}" :to="pageUrl(it.key)" @click="open=false">{{ t('nav.'+it.key) }}</router-link></li>
        </ul>
        <div class="langs">
          <button v-for="l in locales" :key="l" :class="{active:store.locale===l}" @click="switchLocale(l)">{{ l.toUpperCase() }}</button>
        </div>
        <router-link :to="pageUrl('contacto')" class="btn btn-primary" style="padding:.65rem 1.15rem;font-size:.72rem" @click="open=false">{{ t('nav.cta') }}</router-link>
      </nav>
      <button class="burger" @click="open=!open" :aria-expanded="open" aria-label="Menú"><span></span><span></span><span></span></button>
    </div></header>`,
  data() { return { store, scrolled: false, open: false, locales: ['es','en','pt'], items: [{key:'soluciones'},{key:'tablero'},{key:'productos'},{key:'casos'},{key:'recursos'},{key:'nosotros'},{key:'faq'}] }; },
  computed: { t: () => t, pageUrl: () => pageUrl },
  methods: {
    isActive(k) { return this.$route.path.includes('/' + ((store.dict.slugs && store.dict.slugs[k]) || k)); },
    async switchLocale(l) {
      this.open = false;
      if (l === store.locale) { return; }
      // Resuelve la página actual en el idioma destino (traduciendo el slug), en vez
      // de reusar el slug actual (que produciría un 404 en otro idioma).
      const parts = this.$route.path.split('/').filter(Boolean); // [loc, slug, sub?]
      const slug = parts[1] || '';
      const sub = parts.slice(2).join('/');
      const curSlugs = (store.dict && store.dict.slugs) || {};
      let key = null;
      for (const k in curSlugs) { if (curSlugs[k] === slug) { key = k; break; } }
      const m = await import('./core.js');
      await m.loadDict(l);
      store.locale = l; document.documentElement.lang = l;
      try { localStorage.setItem('exp_locale', l); } catch (e) {}
      const tgt = (store.dict && store.dict.slugs) || {};
      let path;
      if (!slug) { path = '/' + l + '/'; }
      else if (key && tgt[key]) { path = '/' + l + '/' + tgt[key] + (sub ? '/' + sub : ''); }
      else { path = '/' + l + '/'; } // página desconocida → inicio en el idioma destino
      this.$router.push(path);
    },
  },
  mounted() { this._s = () => { this.scrolled = scrollY > 24; }; this._s(); addEventListener('scroll', this._s, { passive: true }); },
  beforeUnmount() { removeEventListener('scroll', this._s); },
};

export const SiteFooter = {
  components: { Icon, BrandLogo },
  template: `<footer class="site-footer"><div class="container"><hr class="divider">
    <div class="site-footer__grid">
      <div class="site-footer__brand"><router-link :to="pageUrl('home')"><BrandLogo :size="46"/></router-link>
        <p class="small" style="max-width:26ch;color:var(--text-secondary)">{{ t('footer.tagline') }}</p>
        <div class="socials"><a v-for="s in social" :key="s.name" :href="s.url" target="_blank" rel="noopener" :aria-label="s.name"><Icon :name="s.icon" :size="18"/></a></div></div>
      <nav><h4>{{ t('footer.col_soluciones') }}</h4><ul>
        <li v-for="k in ['soluciones','tablero','productos','diagnostico']" :key="k"><router-link :to="pageUrl(k)">{{ navLabel(k) }}</router-link></li></ul></nav>
      <nav><h4>{{ t('footer.col_compania') }}</h4><ul>
        <li v-for="k in ['casos','recursos','nosotros','faq','contacto']" :key="k"><router-link :to="pageUrl(k)">{{ t('nav.'+k) }}</router-link></li></ul></nav>
      <div><h4>{{ t('footer.col_contacto') }}</h4><ul>
        <li><a href="mailto:hello@experientia.pro">hello@experientia.pro</a></li>
        <li><router-link :to="pageUrl('agenda')">{{ t('common.agendar_sesion') }}</router-link></li></ul></div>
    </div>
    <div class="legal"><p>© {{ year }} ExperientIA. {{ t('footer.legal') }}</p><p>{{ t('footer.ecosistema') }} <a href="https://tonnydager.com" target="_blank" rel="noopener">Tonny Dager</a></p></div>
  </div></footer>`,
  data() { return { social: SOCIAL, year: new Date().getFullYear() }; },
  computed: { t: () => t, pageUrl: () => pageUrl },
  methods: { navLabel(k) { const v = t('nav.' + k); return v === 'nav.' + k ? t('diagnostico.eyebrow') : v; } },
};

export const Toasts = {
  template: `<div class="toasts"><div v-for="x in store.toasts" :key="x.id" class="toast" :class="x.type">{{ x.message }}</div></div>`,
  data() { return { store }; },
};

// Widget AlexIA (comercial, web)
const axEsc = (s) => String(s ?? '').replace(/[&<>]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;' }[c]));

export const AlexIA = {
  components: { Icon, PhoneInput },
  template: `<div>
    <button class="ax-fab" :class="{on:open}" @click="toggle" aria-label="AlexIA"><span class="ax-fab-ring"></span>
      <Icon v-if="!open" name="sparkle" :size="24"/><span v-else class="ax-fab-x">✕</span></button>
    <transition name="ax-pop">
    <div class="ax-panel glass" v-if="open">
      <div class="ax-head"><div class="ax-ava"><Icon name="sparkle" :size="18"/></div>
        <div class="ax-head-t"><b>AlexIA</b><span>{{ tx('alexia.sub','Asesora comercial') }}</span></div>
        <button v-if="started" class="ax-quick-toggle" :class="{on:quickOpen, hint:quickHint}" @click="toggleQuick" :title="tx('alexia.quick_title','Preguntas rápidas')"><Icon name="bulb" :size="15"/></button>
        <button class="ax-close" @click="open=false" aria-label="Cerrar">✕</button></div>

      <transition name="ax-quick-pop">
      <div v-if="quickOpen && started" class="ax-quick-pop-menu">
        <p class="ax-quick-h"><Icon name="bulb" :size="13"/> {{ tx('alexia.quick_title','Preguntas rápidas') }}</p>
        <button v-for="q in quick" :key="q" @click="ask(q)">{{ q }}</button></div></transition>

      <div v-if="!started" class="ax-gate">
        <div class="ax-msg a ax-in"><p v-html="saludo"></p></div>
        <p class="ax-gate-lead ax-in">{{ tx('alexia.gate','Para atenderte mejor, cuéntame tu nombre, correo y WhatsApp.') }}</p>
        <form class="ax-gate-form ax-in" @submit.prevent="startChat">
          <div class="honeypot"><input type="text" v-model="hp" tabindex="-1" autocomplete="off"></div>
          <input v-model="lead.name" :placeholder="tx('alexia.gate_name','Tu nombre')" autocomplete="name" required>
          <input v-model="lead.email" type="email" :placeholder="tx('alexia.gate_email','Tu correo')" autocomplete="email" required>
          <PhoneInput ref="axPhone" v-model="lead.phone_wa" @dial="lead.phone_dial=$event"/>
          <div class="ax-captcha">
            <div class="ax-captcha-img">
              <img v-if="cap.image && cap.image.startsWith('data:')" :src="cap.image" alt="captcha" draggable="false">
              <b v-else-if="cap.image">{{ cap.image.replace('text:','') }}</b>
              <span v-else class="ax-captcha-cargando">…</span>
              <button type="button" class="ax-captcha-refresh" @click="cargarCaptcha" :title="tx('alexia.gate_captcha_otro','Otro código')">↻</button></div>
            <input v-model="cap.code" :placeholder="tx('alexia.gate_captcha','Escribe el código')" autocomplete="off" maxlength="8" required>
          </div>
          <button class="btn btn-grad" :disabled="gating">{{ gating?'…':tx('alexia.gate_cta','Empezar a chatear') }}</button>
          <p class="err" v-if="gateErr">{{ gateErr }}</p>
          <p class="ax-gate-nota">{{ tx('alexia.gate_nota','Sin spam. Solo para ayudarte y enviarte el resumen si lo quieres.') }}</p></form>
      </div>

      <template v-else>
      <div class="ax-msgs" ref="msgs">
        <div class="ax-msg a ax-in"><p v-html="saludo"></p></div>
        <template v-for="(m,i) in msgs" :key="i">
          <div class="ax-msg ax-in" :class="m.role==='user'?'u':'a'"><p v-html="m.html"></p></div>
          <div v-if="m.role==='assistant' && m.links && m.links.length" class="ax-links ax-in">
            <router-link v-for="(l,j) in m.links" :key="j" :to="l.to" class="ax-link-btn" @click="open=false">{{ l.label }} <Icon name="arrow" :size="14"/></router-link></div>
          <div v-if="m.role==='assistant' && i===msgs.length-1 && !loading" class="ax-cta ax-in">
            <span>{{ tx('alexia.cta_titulo','¿Damos el siguiente paso?') }}</span>
            <div class="ax-cta-btns"><router-link :to="pageUrl('diagnostico')" class="ax-cta-btn" @click="open=false">{{ tx('alexia.cta_diag','Diagnóstico gratis') }}</router-link>
              <router-link :to="pageUrl('agenda')" class="ax-cta-btn ghost" @click="open=false">{{ tx('alexia.cta_agenda','Agendar 1:1') }}</router-link></div></div>
        </template>
        <div v-if="loading" class="ax-msg a ax-thinking ax-in"><span class="ax-ava sm"><Icon name="sparkle" :size="12"/></span>
          <span class="ax-status">{{ statusText }}</span><span class="ax-wave"><i></i><i></i><i></i></span></div>
      </div>
      <div class="ax-inbox"><textarea v-model="text" :placeholder="tx('alexia.placeholder','Escribe tu consulta…')" rows="1" @keydown.enter.exact.prevent="send"></textarea>
        <button class="ax-send" @click="send" :disabled="loading"><Icon name="send" :size="18"/></button></div>
      </template>
    </div></transition></div>`,
  data() { return { open: false, text: '', msgs: [], loading: false, convId: null, statusText: '',
    started: false, gating: false, gateErr: '', quickOpen: false, quickHint: true, hp: '',
    lead: { name: '', email: '', phone_wa: '', phone_dial: '' }, leadId: null, resumenSent: false,
    cap: { image: '', token: '', code: '' } }; },
  computed: {
    t: () => t, pageUrl: () => pageUrl,
    saludo() { const n = this.lead.name ? (', ' + this.lead.name.split(' ')[0]) : ''; return '<p>' + axEsc(this.tx('alexia.saludo', 'Hola 👋 Soy AlexIA, de ExperientIA. Cuéntame tu reto de crecimiento y te muestro cómo la automatización y la IA pueden ayudarte.')).replace('👋', n + ' 👋') + '</p>'; },
    quick() { const q = t('alexia.quick'); return Array.isArray(q) ? q : ['¿Qué hace ExperientIA?', '¿Cómo es el diagnóstico?', '¿Qué resultados logran?', 'Quiero agendar una sesión']; },
  },
  mounted() { try { const s = JSON.parse(localStorage.getItem('exp_chatlead') || 'null'); if (s && s.email) { this.lead = { ...this.lead, name: s.name || '', email: s.email }; this.leadId = s.leadId || null; this.started = true; } } catch (e) {} },
  methods: {
    tx(key, fb) { const v = t(key); return (v && v !== key) ? v : fb; },
    toggle() {
      this.open = !this.open;
      if (this.open && !this.started && !this.cap.token) { this.cargarCaptcha(); }
      if (!this.open) { this.quickOpen = false; this.enviarResumen(); }
    },
    async cargarCaptcha() {
      this.cap.image = ''; this.cap.token = ''; this.cap.code = '';
      const r = await api.get('/captcha');
      if (r.ok) { this.cap.image = r.data.image; this.cap.token = r.data.token; }
    },
    toggleQuick() { this.quickOpen = !this.quickOpen; this.quickHint = false; },
    async startChat() {
      if (this.hp) { return; }
      const name = this.lead.name.trim(); const email = this.lead.email.trim();
      if (name.length < 2 || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) { this.gateErr = this.tx('alexia.gate_err', 'Escribe tu nombre y un correo válido.'); return; }
      const waDigits = (this.lead.phone_wa || '').replace(/\D/g, '');
      if (waDigits.length < 8 || waDigits.length > 15 || (this.$refs.axPhone && !this.$refs.axPhone.valid())) {
        this.gateErr = this.tx('alexia.gate_wa_err', 'Escribe un número de WhatsApp válido.'); return; }
      if (!this.cap.code.trim()) { this.gateErr = this.tx('alexia.gate_captcha_err', 'Escribe el código de verificación.'); return; }
      this.gating = true; this.gateErr = '';
      const r = await api.post('/alexia/lead', {
        name, email, phone_wa: this.lead.phone_wa, phone_dial: this.lead.phone_dial,
        captcha_code: this.cap.code.trim(), captcha_token: this.cap.token,
        website: this.hp, locale: store.locale,
      });
      this.gating = false;
      if (!r.ok) {
        // El gate solo se abre con verificación real: nuevo reto y mensaje claro.
        this.gateErr = r.captcha === 'invalid'
          ? this.tx('alexia.gate_captcha_err2', 'El código no coincide. Prueba con el nuevo.')
          : (r.error || this.tx('alexia.gate_err2', 'No pudimos validar tus datos. Revisa e intenta de nuevo.'));
        this.cargarCaptcha();
        return;
      }
      this.leadId = r.data.lead_id || null;
      this.started = true; this.quickHint = true;
      try { localStorage.setItem('exp_chatlead', JSON.stringify({ name, email, leadId: this.leadId })); } catch (e) {}
    },
    resolveLink(key) {
      const parts = String(key || '').split('/'); const base = parts[0]; const sub = parts[1];
      const known = ['soluciones', 'productos', 'casos', 'recursos', 'tablero', 'diagnostico', 'agenda', 'contacto', 'nosotros', 'faq'];
      if (!known.includes(base)) { return pageUrl('contacto'); }
      return sub ? pageUrl(base, { slug: sub }) : pageUrl(base);
    },
    status(q) {
      const s = q.toLowerCase();
      if (/precio|costo|plan|inversi|tarifa|cuánto|cuanto/.test(s)) { return this.tx('alexia.st_precio', 'Revisando cómo lo abordaríamos…'); }
      if (/agenda|reuni|sesi|cita|llamada/.test(s)) { return this.tx('alexia.st_agenda', 'Preparando la agenda…'); }
      if (/resultado|caso|éxito|exito|roi/.test(s)) { return this.tx('alexia.st_casos', 'Buscando casos relevantes…'); }
      if (/diagn/.test(s)) { return this.tx('alexia.st_diag', 'Alistando tu diagnóstico…'); }
      if (/servicio|soluci|producto|hac[eé]|ofrec/.test(s)) { return this.tx('alexia.st_serv', 'Revisando nuestros servicios…'); }
      return this.tx('alexia.st_default', 'Analizando tu consulta…');
    },
    ask(q) { this.quickOpen = false; this.text = q; this.send(); },
    async send() {
      const txt = this.text.trim(); if (!txt || this.loading) { return; }
      this.quickOpen = false;
      this.msgs.push({ role: 'user', html: '<p>' + axEsc(txt) + '</p>' });
      this.text = ''; this.loading = true; this.statusText = this.status(txt); this.scroll();
      const r = await api.post('/alexia', { mensaje: txt, conversation_id: this.convId, locale: store.locale, nombre: this.lead.name, lead_id: this.leadId });
      this.loading = false;
      if (r.ok) {
        this.convId = r.data.conversation_id;
        const links = [];
        const clean = String(r.data.reply || '').replace(/\[\[ir:([^|\]]+)\|([^\]]+)\]\]/g, (m, key, label) => { links.push({ to: this.resolveLink(key.trim()), label: label.trim() }); return ''; }).trim();
        this.msgs.push({ role: 'assistant', html: '<p>' + axEsc(clean).replace(/\n+/g, '</p><p>') + '</p>', links });
      } else { this.msgs.push({ role: 'assistant', html: '<p>' + axEsc(r.error || 'AlexIA no está disponible ahora. Escríbenos o agenda una sesión.') + '</p>', links: [] }); }
      this.scroll();
    },
    enviarResumen() {
      if (this.resumenSent || !this.convId || !this.lead.email) { return; }
      if (!this.msgs.some(m => m.role === 'assistant')) { return; }
      this.resumenSent = true;
      api.post('/alexia/resumen', { conversation_id: this.convId, email: this.lead.email, locale: store.locale });
    },
    scroll() { this.$nextTick(() => { const m = this.$refs.msgs; if (m) { m.scrollTop = m.scrollHeight; } }); },
  },
};

// Fondo global con profundidad y parallax al hacer scroll (más fuerte arriba).
export const SiteBackdrop = {
  template: `<div class="backdrop" aria-hidden="true" ref="bd">
    <div class="bd-layer bd-neb"></div>
    <div class="bd-layer bd-grid"></div>
    <div class="bd-layer bd-shards">
      <span class="bd-shard s1"></span><span class="bd-shard s2"></span><span class="bd-shard s3"></span><span class="bd-shard s4"></span><span class="bd-shard s5"></span></div>
  </div>`,
  mounted() {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
    this.raf = null;
    this.onScroll = () => {
      if (this.raf) { return; }
      this.raf = requestAnimationFrame(() => {
        this.raf = null;
        const y = window.scrollY || 0;
        const el = this.$refs.bd;
        if (!el) { return; }
        el.style.setProperty('--p1', (y * -0.06) + 'px');
        el.style.setProperty('--p2', (y * -0.14) + 'px');
        el.style.setProperty('--p3', (y * -0.28) + 'px');
      });
    };
    window.addEventListener('scroll', this.onScroll, { passive: true });
    this.onScroll();
  },
  beforeUnmount() { if (this.onScroll) { window.removeEventListener('scroll', this.onScroll); } },
};

export const PageHero = {
  template: `<section class="page-hero"><div class="bg-atmos"><div class="halo halo-cyan" style="width:520px;height:520px;top:-260px;right:-140px"></div><div class="halo halo-violet" style="width:420px;height:420px;top:40px;left:-200px;opacity:.35"></div></div>
    <div class="container page-hero__in"><p class="eyebrow" v-reveal>{{ eyebrow }}</p><h1 class="display" v-reveal :style="{'--d':'.08s'}">{{ titulo }}</h1><p class="lead" v-reveal :style="{'--d':'.16s'}" v-if="sub">{{ sub }}</p><slot/></div></section>`,
  props: { eyebrow: String, titulo: String, sub: String },
};

export const SectionCTA = {
  template: `<section class="section"><div class="container"><div class="glass glass-lit card" v-reveal style="text-align:center;padding:clamp(2.75rem,7vw,5rem) clamp(1.5rem,6vw,4.5rem);position:relative;overflow:hidden">
    <div class="bg-atmos"><div class="halo halo-cyan anim-pulse" style="width:420px;height:420px;bottom:-260px;left:-120px"></div><div class="halo halo-violet anim-pulse" style="width:460px;height:460px;top:-280px;right:-140px;animation-delay:2.4s"></div></div>
    <div style="position:relative;z-index:1;display:grid;gap:1.4rem;justify-items:center;max-width:42rem;margin-inline:auto">
      <h2 class="h2">{{ titulo }}</h2><p class="lead" v-if="sub">{{ sub }}</p>
      <div style="display:flex;flex-wrap:wrap;gap:.9rem;justify-content:center">
        <router-link :to="primary" class="btn btn-primary">{{ primaryLabel }}</router-link>
        <router-link v-if="secondary" :to="secondary" class="btn btn-ghost">{{ secondaryLabel }}</router-link>
      </div></div></div></div></section>`,
  props: { titulo: String, sub: String, primary: String, primaryLabel: String, secondary: String, secondaryLabel: String },
};
