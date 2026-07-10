// ExperientIA · Layout del sitio público: header, footer, AlexIA, toasts.
import { store, t, tr, pageUrl, api, toast } from './core.js';
import { Icon, BrandLogo, BrandSymbol } from './ui.js';

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
    switchLocale(l) { const rest = this.$route.path.replace(/^\/(es|en|pt)/, ''); store.locale = l; document.documentElement.lang = l; import('./core.js').then(m => m.loadDict(l).then(() => this.$router.push('/' + l + rest))); this.open = false; },
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
export const AlexIA = {
  components: { Icon },
  template: `<div>
    <button class="alexia-fab" @click="toggle" aria-label="AlexIA"><Icon :name="open?'arrow':'sparkle'" :size="24"/></button>
    <div class="alexia-panel" v-if="open">
      <div class="alexia-head"><span class="dot"></span><b style="color:var(--neutral-light)">AlexIA</b><span class="small" style="margin-left:auto">{{ t('alexia.sub') }}</span></div>
      <div class="alexia-msgs" ref="msgs">
        <div class="alexia-msg a">{{ t('alexia.saludo') }}</div>
        <div v-for="(m,i) in msgs" :key="i" class="alexia-msg" :class="m.role==='user'?'u':'a'">{{ m.text }}</div>
        <div v-if="loading" class="alexia-msg a" style="opacity:.6">…</div>
      </div>
      <div class="alexia-in"><textarea v-model="text" :placeholder="t('alexia.placeholder')" @keydown.enter.exact.prevent="send"></textarea>
        <button class="alexia-send" @click="send" :disabled="loading"><Icon name="send" :size="18"/></button></div>
    </div></div>`,
  data() { return { open: false, text: '', msgs: [], loading: false, convId: null }; },
  computed: { t: () => t },
  methods: {
    toggle() { this.open = !this.open; },
    async send() {
      const txt = this.text.trim(); if (!txt || this.loading) return;
      this.msgs.push({ role: 'user', text: txt }); this.text = ''; this.loading = true;
      this.scroll();
      const r = await api.post('/alexia', { mensaje: txt, conversation_id: this.convId, locale: store.locale });
      this.loading = false;
      if (r.ok) { this.convId = r.data.conversation_id; this.msgs.push({ role: 'assistant', text: r.data.reply }); }
      else { this.msgs.push({ role: 'assistant', text: r.error || 'AlexIA no está disponible ahora.' }); }
      this.scroll();
    },
    scroll() { this.$nextTick(() => { const m = this.$refs.msgs; if (m) m.scrollTop = m.scrollHeight; }); },
  },
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
