// ExperientIA · Componentes UI compartidos (Vue 3, sin build).
import { tr } from './core.js';

const ICONS = {
  target:'<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none"/>',
  growth:'<path d="M3 21h18"/><path d="M4 16.5 9.5 11l3.5 3.5L19.5 8"/><path d="M15.5 8h4v4"/>',
  gear:'<circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v2.6M12 18.6v2.6M21.2 12h-2.6M5.4 12H2.8M18.5 5.5l-1.9 1.9M7.4 16.6l-1.9 1.9M18.5 18.5l-1.9-1.9M7.4 7.4 5.5 5.5"/>',
  ia:'<circle cx="6" cy="6" r="2.2"/><circle cx="17.5" cy="4.5" r="1.8"/><circle cx="12" cy="12" r="2.6"/><circle cx="5.5" cy="18" r="1.8"/><circle cx="18" cy="18.5" r="2.2"/><path d="M7.8 7.4l2.4 2.7M14.4 10.6l1.8-4.3M10.2 13.9l-3.2 2.7M14.3 13.7l2.3 3.1"/>',
  shield:'<path d="M12 3l7 2.8v5.3c0 4.4-2.9 7.4-7 9.1-4.1-1.7-7-4.7-7-9.1V5.8z"/><path d="m8.8 12 2.2 2.2 4.2-4.4"/>',
  bulb:'<path d="M12 3a6 6 0 0 0-3.4 10.9c.7.5 1.1 1.3 1.1 2.1h4.6c0-.8.4-1.6 1.1-2.1A6 6 0 0 0 12 3z"/><path d="M9.7 19.5h4.6M10.6 22h2.8"/>',
  cube:'<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12 4 7.5M12 12v9"/>',
  analitica:'<rect x="3" y="4" width="18" height="15" rx="2"/><path d="M3 8.5h18"/><path d="M7 15.5v-2.2M10.3 15.5v-3.8M13.6 15.5v-2.8M16.9 15.5v-4.6"/>',
  people:'<circle cx="9" cy="8.2" r="3"/><path d="M3.8 19.5c0-2.9 2.3-5.2 5.2-5.2s5.2 2.3 5.2 5.2"/><circle cx="16.8" cy="9" r="2.4"/><path d="M16 14.6c2.4.3 4.2 2.2 4.2 4.9"/>',
  check:'<path d="m4.5 12.5 5 5L19.5 6.5"/>', arrow:'<path d="M4 12h15"/><path d="m13.5 6 6 6-6 6"/>',
  mail:'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
  clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5.2l3.4 2"/>',
  eye:'<path d="M2.5 12S6 5.8 12 5.8 21.5 12 21.5 12 18 18.2 12 18.2 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.6"/>',
  alert:'<path d="M6.2 9.3a5.8 5.8 0 0 1 11.6 0c0 4.6 1.9 5.8 1.9 5.8H4.3s1.9-1.2 1.9-5.8z"/><path d="M10 19.2a2.1 2.1 0 0 0 4 0"/>',
  doc:'<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4"/><path d="M10 12h5M10 15.5h5"/>',
  play:'<circle cx="12" cy="12" r="9"/><path d="m10 8.5 5.5 3.5-5.5 3.5z"/>',
  calendar:'<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/>',
  send:'<path d="M4 12 20 4l-4 16-4-6-8-2z"/>', sparkle:'<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/>',
  instagram:'<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1.1" fill="currentColor" stroke="none"/>',
  linkedin:'<rect x="3.5" y="3.5" width="17" height="17" rx="2.5"/><path d="M7.2 10.2v6.6M7.2 7.4v.02"/><path d="M11 16.8v-3.6a2.2 2.2 0 0 1 4.4 0v3.6M11 16.8v-6.6"/>',
  facebook:'<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><path d="M14.8 8.2h-1.3c-.9 0-1.6.7-1.6 1.6v1.4m-1.6 0h4m-2.4 0v6.1"/>',
  logout:'<path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9"/>', users:'<circle cx="9" cy="8" r="3"/><path d="M3.5 20c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/><circle cx="17.5" cy="8.5" r="2.4"/>',
  plug:'<path d="M9 3v6M15 3v6M7 9h10v3a5 5 0 0 1-10 0z"/><path d="M12 17v4"/>',
};

export const Icon = {
  props: { name: String, size: { type: Number, default: 24 } },
  template: `<svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" v-html="body"></svg>`,
  computed: { body() { return ICONS[this.name] || ICONS.target; } },
};

export const BrandSymbol = {
  props: { size: { type: Number, default: 40 }, variant: { type: String, default: 'gradient' } },
  template: `<svg :width="size*1.28" :height="size" viewBox="0 0 128 100" fill="none" aria-hidden="true">
    <defs v-if="variant==='gradient'"><linearGradient :id="uid" x1="18" y1="0" x2="50" y2="100" gradientUnits="userSpaceOnUse">
      <stop offset="0" stop-color="#00e5ff"/><stop offset="0.52" stop-color="#7b61ff"/><stop offset="1" stop-color="#00e5ff"/></linearGradient></defs>
    <path :fill="solid" d="M 6 2 L 38 2 L 64 41.5 L 64 58.5 L 38 98 L 6 98 L 6 55.5 L 12.5 52 L 12.5 48 L 6 44.5 Z"/>
    <g :stroke="stroke" fill="none" stroke-width="10.5">
      <polyline points="88,26.5 72,50 88,73.5"/><polyline points="61.5,32 78.5,7.25 96,7.25"/>
      <line x1="102.5" y1="6" x2="124" y2="31.5" stroke-width="11"/>
      <polyline points="61.5,68 78.5,92.75 96,92.75"/><line x1="102.5" y1="94" x2="124" y2="68.5" stroke-width="11"/></g></svg>`,
  data() { return { uid: 'xg' + Math.random().toString(36).slice(2, 7) }; },
  computed: {
    solid() { return this.variant === 'white' ? '#fff' : this.variant === 'black' ? '#0a1224' : `url(#${this.uid})`; },
    stroke() { return this.variant === 'white' ? '#fff' : this.variant === 'black' ? '#0a1224' : '#00e5ff'; },
  },
};

export const BrandLogo = {
  components: { BrandSymbol },
  props: { size: { type: Number, default: 40 }, descriptor: { type: Boolean, default: true } },
  template: `<span class="brand-logo" :style="{'--h': size+'px'}" translate="no">
    <BrandSymbol :size="size"/><span class="txt"><span class="word">Experient<i>IA</i></span>
    <span class="desc" v-if="descriptor">Automatización · Growth · IA</span></span></span>`,
};

// Tablero de Crecimiento (centro de comando · liquid glass)
export const DashMock = {
  props: { cls: String },
  template: `<div class="dash" :class="cls" role="img" aria-label="Tablero de Crecimiento">
    <div class="dash__chrome"><span class="dash__dot"></span><span class="dash__dot"></span><span class="dash__dot"></span>
      <span class="dash__title">ExperientIA · {{ L.titulo }}</span><span class="dash__live"><i class="anim-pulse"></i>{{ L.vivo }}</span></div>
    <div class="dash__grid">
      <div class="dash__c chart"><p class="dash__lbl">{{ L.crec }}</p><p class="dash__big grad-text">+42%</p><p class="dash__hint">{{ L.vs }}</p>
        <svg viewBox="0 0 220 74" preserveAspectRatio="none"><defs>
          <linearGradient :id="g+'l'" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#18d6f1"/><stop offset="1" stop-color="#7a63ff"/></linearGradient>
          <linearGradient :id="g+'a'" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="rgba(24,214,241,.25)"/><stop offset="1" stop-color="rgba(24,214,241,0)"/></linearGradient></defs>
          <path d="M0 62 L28 54 L56 58 L84 42 L112 46 L140 28 L168 32 L204 10 L220 12 L220 74 L0 74 Z" :fill="'url(#'+g+'a)'"/>
          <path class="dash__line" d="M0 62 L28 54 L56 58 L84 42 L112 46 L140 28 L168 32 L204 10 L220 12" fill="none" :stroke="'url(#'+g+'l)'" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
          <circle cx="204" cy="10" r="3.4" fill="#18d6f1" class="anim-pulse"/></svg></div>
      <div class="dash__c ring"><p class="dash__lbl">{{ L.auto }}</p><div class="dash__ring">
        <svg viewBox="0 0 96 96"><circle cx="48" cy="48" r="40" fill="none" stroke="rgba(217,226,240,.12)" stroke-width="7"/>
          <circle cx="48" cy="48" r="40" fill="none" :stroke="'url(#'+g+'r)'" stroke-width="7" stroke-linecap="round" stroke-dasharray="196 251" transform="rotate(-90 48 48)"/>
          <defs><linearGradient :id="g+'r'" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#18d6f1"/><stop offset="1" stop-color="#7a63ff"/></linearGradient></defs></svg>
        <span class="dash__ringv">78%</span></div><p class="dash__hint">{{ L.proc }}</p></div>
      <div class="dash__c bars"><p class="dash__lbl">{{ L.ing }}</p><div class="dash__bars">
        <span v-for="(h,i) in bars" :key="i" :class="{hot:i>=6}" :style="{height:h+'%'}"></span></div></div>
      <div class="dash__c roi"><p class="dash__lbl">{{ L.roi }}</p><p class="dash__big">3.2x</p>
        <div class="dash__spark"><span style="width:42%;background:rgba(122,99,255,.5)"></span><span style="width:26%;background:rgba(24,214,241,.55)"></span><span style="width:14%;background:rgba(217,226,240,.25)"></span></div></div>
      <div class="dash__c flow"><p class="dash__lbl">{{ L.flujo }}</p><div class="dash__flow">
        <span class="dash__node"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="m10 8.5 5.5 3.5-5.5 3.5z"/></svg></span>
        <i class="dash__wire"></i><span class="dash__node"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="3.2"/></svg></span>
        <i class="dash__wire"></i><span class="dash__node ok"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m4.5 12.5 5 5L19.5 6.5"/></svg></span></div></div>
    </div></div>`,
  data() {
    const S = {
      es:{titulo:'Tablero de Crecimiento',vivo:'En vivo',crec:'Crecimiento sostenible',vs:'vs. trimestre anterior',proc:'Procesos optimizados',auto:'Automatización',roi:'Retorno del programa',flujo:'Flujo de automatización',ing:'Ingresos por canal'},
      en:{titulo:'Growth Board',vivo:'Live',crec:'Sustainable growth',vs:'vs. previous quarter',proc:'Processes optimized',auto:'Automation',roi:'Program return',flujo:'Automation flow',ing:'Revenue by channel'},
      pt:{titulo:'Painel de Crescimento',vivo:'Ao vivo',crec:'Crescimento sustentável',vs:'vs. trimestre anterior',proc:'Processos otimizados',auto:'Automação',roi:'Retorno do programa',flujo:'Fluxo de automação',ing:'Receita por canal'},
    };
    return { g: 'dg' + Math.random().toString(36).slice(2, 6), bars: [34,52,40,66,58,80,72,92], S };
  },
  computed: { L() { return this.S[(document.documentElement.lang) || 'es'] || this.S.es; } },
};

// Directiva de reveal al hacer scroll
export const reveal = {
  mounted(el) {
    if (matchMedia('(prefers-reduced-motion: reduce)').matches) { el.classList.add('in'); return; }
    el.classList.add('reveal');
    const io = new IntersectionObserver((e) => { e.forEach(x => { if (x.isIntersecting) { x.target.classList.add('in'); io.unobserve(x.target); } }); }, { threshold: .12, rootMargin: '0px 0px -40px 0px' });
    io.observe(el);
  },
};
export { ICONS };
