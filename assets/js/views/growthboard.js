// GrowthBoard · el instrumento del Tablero de Crecimiento (Tonny Dager x ExperientIA).
// La cancha es la interfaz: 11 zonas en 4 líneas, con semáforo por puntaje.
// La plataforma mide y ordena; la interpretación es del consultor (lectura estratégica).
import { t, tr, pageUrl, api, store, setMeta, loadMeta } from '../lib/core.js';
import { Icon, CountUp, ScrollProgress } from '../lib/ui.js';
import { PageHero } from '../lib/layout.js';
import { PhoneInput, Combo } from '../lib/forms.js';

const tx = (key, fb) => { const v = t(key); return (v && v !== key) ? v : fb; };
const gbUrl = (sub) => pageUrl('tablero') + '/' + sub;

// Semáforo del método: crítico/débil → ámbar; funcional → amarillo; sólido → cian.
const nivel = (s) => s == null ? 'off' : (s < 2.5 ? 'bajo' : (s < 3.5 ? 'medio' : 'alto'));

// ── La cancha (4 líneas, 11 posiciones) ──────────────────────────────────────
export const Cancha = {
  components: { Icon },
  props: { zonas: { type: Array, default: () => [] }, scores: { type: Object, default: null }, critica: { type: String, default: '' }, compact: Boolean },
  computed: {
    tr: () => tr,
    filas() {
      const by = {}; for (const z of this.zonas) by[z.zkey] = z;
      const orden = [['vision'], ['direccion'], ['finanzas', 'operacion', 'cultura'], ['datos', 'procesos', 'automatizacion'], ['marketing', 'ventas', 'experiencia']];
      return orden.map(f => f.map(k => by[k]).filter(Boolean)).filter(f => f.length);
    },
  },
  methods: {
    nivel(z) { return nivel(this.scores ? this.scores[z.zkey] : null); },
    score(z) { const s = this.scores ? this.scores[z.zkey] : null; return s == null ? '—' : Number(s).toFixed(1); },
  },
  template: `<div class="gb-cancha" :class="{compact}" role="img" aria-label="GrowthBoard">
    <div class="gb-cancha__lines" aria-hidden="true"><span class="gb-mid"></span><span class="gb-circle"></span><span class="gb-box top"></span><span class="gb-box bot"></span></div>
    <div v-for="(fila,i) in filas" :key="i" class="gb-fila" :class="'n'+fila.length">
      <div v-for="z in fila" :key="z.zkey" class="gb-nodo" :class="[nivel(z), {critica: z.zkey===critica}]">
        <span class="gb-nodo__icon"><Icon :name="z.icon||'target'" :size="compact?13:16"/></span>
        <span class="gb-nodo__name">{{ tr(z.nombre) }}</span>
        <span class="gb-nodo__score">{{ score(z) }}</span>
      </div>
    </div>
  </div>`,
};

// ── Barras de las 4 líneas (lectura por líneas) ──────────────────────────────
const LineasBars = {
  props: { lineas: Object, debil: String },
  computed: { tr: () => tr },
  methods: { tx },
  template: `<div class="gb-lineas">
    <div v-for="(ln,k) in lineas" :key="k" class="gb-linea" :class="{debil:k===debil}">
      <div class="gb-linea__head"><span>{{ tr(ln.nombre) }}</span><b>{{ ln.score }} / {{ ln.max }}</b></div>
      <div class="gb-linea__bar"><span :style="{width: Math.round(ln.score/ln.max*100)+'%'}"></span></div>
      <p v-if="k===debil" class="gb-linea__sintoma"><Icon name="alert" :size="12"/> {{ tr(ln.sintoma) }}</p>
    </div>
  </div>`,
  components: { Icon },
};

// ── Demo interactiva (datos de ejemplo por industria, declarados) ────────────
export const GBDemo = {
  components: { Icon, Cancha, LineasBars, CountUp, ScrollProgress, PageHero },
  data() { return { d: null, zonas: [], industria: 'default', industrias: [], nombres: {}, cargando: false }; },
  computed: { t: () => t, tr: () => tr, pageUrl: () => pageUrl,
    marcador() { if (!this.d) return [];
      return [
        { k: 'pipeline', v: this.d.marcador.pipeline, suf: '', l: tx('gb.m_pipeline', 'Pipeline · oportunidades') },
        { k: 'conversion', v: this.d.marcador.conversion, suf: '%', l: tx('gb.m_conversion', 'Conversión') },
        { k: 'rentabilidad', v: this.d.marcador.rentabilidad, suf: '%', l: tx('gb.m_rentabilidad', 'Rentabilidad') },
        { k: 'crecimiento', v: this.d.marcador.crecimiento, suf: '%', l: tx('gb.m_crecimiento', 'Crecimiento') },
      ]; },
    zonaCritica() { return this.zonas.find(z => z.zkey === (this.d && this.d.zona_critica)); },
  },
  methods: { tx, gbUrl,
    label(k) { if (k === 'default') return tx('gb.demo_general', 'Vista general'); const n = this.nombres[k]; return n ? tr(n) : k; },
    async cargar(k) { this.industria = k; this.cargando = true;
      const r = await api.get('/growthboard/demo/' + k);
      if (r.ok) { this.d = r.data; this.industrias = r.data.industrias; }
      this.cargando = false; },
  },
  async mounted() {
    setMeta('GrowthBoard Demo · ExperientIA', tx('gb.demo_meta', 'Explora el GrowthBoard con datos de ejemplo de tu industria.'));
    const [cfg, meta] = await Promise.all([api.get('/growthboard/config'), loadMeta()]);
    if (cfg.ok) this.zonas = cfg.data.zonas;
    for (const i of (meta.industries || [])) this.nombres[i.key] = i.nombre;
    await this.cargar('default');
  },
  template: `<div>
    <ScrollProgress/>
    <PageHero eyebrow="GrowthBoard · Demo" :titulo="tx('gb.demo_t','Así se ve tu empresa en el tablero')" :sub="tx('gb.demo_s','Explora el instrumento con datos de ejemplo. Tu tablero real se construye con tu diagnóstico.')"/>
    <section class="section" style="padding-top:0"><div class="container">
      <div class="gb-demo-bar" v-reveal>
        <span class="chip chip--cyan"><Icon name="eye" :size="12"/> {{ tx('gb.demo_datos','Datos de ejemplo') }}</span>
        <div class="gb-demo-inds"><button v-for="k in industrias" :key="k" class="chip-cat" :class="{on:industria===k}" @click="cargar(k)">{{ label(k) }}</button></div>
      </div>
      <div class="gb-demo-grid" v-if="d">
        <div class="glass card gb-panel" v-reveal>
          <Cancha :zonas="zonas" :scores="d.scores" :critica="d.zona_critica"/>
        </div>
        <div class="gb-demo-side">
          <div class="glass glass-lit card" v-reveal><h2 class="lbl" style="margin-bottom:.9rem">{{ tx('gb.marcador','Marcador de resultados') }}</h2>
            <div class="gb-marcador"><div v-for="m in marcador" :key="m.k" class="gb-marcador__tile"><span class="gb-marcador__v grad-text"><CountUp :value="m.v" :suffix="m.suf"/></span><span class="gb-marcador__l">{{ m.l }}</span></div></div></div>
          <div class="glass card" v-reveal>
            <h2 class="lbl" style="margin-bottom:.9rem">{{ tx('gb.lineas_t','Lectura por líneas') }}</h2>
            <LineasBars :lineas="d.lineas" :debil="d.linea_debil"/></div>
          <div class="glass card gb-senales" v-reveal v-if="zonaCritica">
            <h2 class="lbl" style="margin-bottom:.7rem"><Icon name="alert" :size="13"/> {{ tx('gb.senales_t','Señales en la zona crítica') }} · {{ tr(zonaCritica.nombre) }}</h2>
            <ul><li v-for="(s,i) in d.senales" :key="i">{{ tr(s) }}</li></ul></div>
        </div>
      </div>
      <div class="glass glass-lit card land-final" v-reveal style="margin-top:1.6rem">
        <h2 class="h2">{{ tx('gb.demo_cta_t','Este tablero, con TUS datos') }}</h2>
        <p class="lead">{{ tx('gb.demo_cta_s','Haz el diagnóstico de 11 zonas en menos de 20 minutos y descubre dónde está trabado tu crecimiento.') }}</p>
        <div class="land-cta-row"><router-link :to="gbUrl('diagnostico')" class="btn btn-grad">{{ tx('gb.cta_diag','Hacer mi diagnóstico') }}</router-link>
          <router-link :to="pageUrl('agenda')" class="btn btn-ghost">{{ tx('gb.cta_lectura','Agendar lectura estratégica') }}</router-link></div></div>
    </div></section>
  </div>`,
};

// ── Diagnóstico de 11 zonas (el instrumento de lectura) ──────────────────────
export const GBDiagnostico = {
  components: { Icon, Cancha, LineasBars, PageHero, PhoneInput, Combo, ScrollProgress },
  data() { return {
    cfg: null, meta: { industries: [], countries: [], company_sizes: [] },
    paso: 0, resultado: null, enviando: false, error: '',
    lead: { name: '', email: '', phone_wa: '', phone_dial: '', company: '', role: '', country: '', city: '', web_url: '', company_size: '', industry: '', revenue: '' },
    contexto: {}, scores: {}, hp: '',
    cap: { image: '', token: '', code: '' },
  }; },
  computed: {
    t: () => t, tr: () => tr, pageUrl: () => pageUrl,
    zonas() { return this.cfg ? this.cfg.zonas : []; },
    // pasos: 0 datos · 1 perfil · 2 contexto · 3..(3+n-1) zonas · último verificación
    pasoZona() { return this.paso >= 3 && this.paso < 3 + this.zonas.length ? this.zonas[this.paso - 3] : null; },
    pasoFinal() { return this.paso === 3 + this.zonas.length; },
    totalPasos() { return 4 + this.zonas.length; },
    progreso() { return Math.round((this.paso) / (this.totalPasos - 1) * 100); },
    opcPais() { return (this.meta.countries || []).map(c => ({ value: c.iso, label: tr(c.nombre) })).sort((a, b) => a.label.localeCompare(b.label)); },
    opcInd() { return (this.meta.industries || []).map(i => ({ value: i.key, label: tr(i.nombre) })); },
    opcTam() { return (this.meta.company_sizes || []).map(s => ({ value: s.key, label: tr(s.nombre) })); },
    opcFact() { return this.cfg ? Object.entries(this.cfg.facturacion).map(([k, v]) => ({ value: k, label: tr(v) })) : []; },
    escala() { return [
      { v: 1, l: tx('gb.e1', 'Crítico') }, { v: 2, l: tx('gb.e2', 'Débil') }, { v: 3, l: tx('gb.e3', 'Funcional') },
      { v: 4, l: tx('gb.e4', 'Sólido') }, { v: 5, l: tx('gb.e5', 'Escalable') }]; },
    zonaCompleta() { const z = this.pasoZona; if (!z) return true; const s = this.scores[z.zkey] || []; return z.afirmaciones.every((_, i) => s[i] >= 1); },
    zonaCritica() { return this.resultado ? this.zonas.find(z => z.zkey === this.resultado.zona_critica) : null; },
  },
  methods: { tx, gbUrl,
    async cargarCaptcha() { this.cap = { image: '', token: '', code: '' }; const r = await api.get('/captcha'); if (r.ok) { this.cap.image = r.data.image; this.cap.token = r.data.token; } },
    // Contexto: las preguntas con multi permiten varias respuestas (toggle).
    ctxOn(q, oi) { const v = this.contexto[q.k]; return q.multi ? Array.isArray(v) && v.includes(oi) : v === oi; },
    ctxToggle(q, oi) {
      if (!q.multi) { this.contexto[q.k] = this.contexto[q.k] === oi ? undefined : oi; return; }
      const v = Array.isArray(this.contexto[q.k]) ? this.contexto[q.k] : (this.contexto[q.k] = []);
      const i = v.indexOf(oi); if (i >= 0) v.splice(i, 1); else v.push(oi);
    },
    marcar(zkey, i, v) { if (!this.scores[zkey]) this.scores[zkey] = []; this.scores[zkey][i] = v; },
    atras() { if (this.paso > 0) { this.paso--; this.error = ''; window.scrollTo({ top: 0 }); } },
    siguiente() {
      this.error = '';
      if (this.paso === 0) {
        const wa = (this.lead.phone_wa || '').replace(/\D/g, '');
        if (this.lead.name.trim().length < 2 || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(this.lead.email) || !this.lead.company.trim()) { this.error = tx('gb.err_datos', 'Completa tu nombre, correo y empresa.'); return; }
        if (wa.length < 8 || wa.length > 15) { this.error = tx('gb.err_wa', 'Escribe un número de WhatsApp válido.'); return; }
      }
      if (this.pasoZona && !this.zonaCompleta) { this.error = tx('gb.err_zona', 'Califica las 5 afirmaciones para continuar.'); return; }
      this.paso++; window.scrollTo({ top: 0 });
      if (this.pasoFinal && !this.cap.token) this.cargarCaptcha();
    },
    async enviar() {
      if (!this.cap.code.trim()) { this.error = tx('gb.err_captcha', 'Escribe el código de verificación.'); return; }
      this.enviando = true; this.error = '';
      const r = await api.post('/growthboard/diagnostico', {
        ...this.lead, locale: store.locale, contexto: this.contexto, scores: this.scores,
        captcha_code: this.cap.code.trim(), captcha_token: this.cap.token, website: this.hp,
      });
      this.enviando = false;
      if (!r.ok) {
        this.error = r.captcha === 'invalid' ? tx('gb.err_captcha2', 'El código no coincide. Prueba con el nuevo.') : (r.error || tx('gb.err_envio', 'No pudimos procesar el diagnóstico. Intenta de nuevo.'));
        this.cargarCaptcha(); return;
      }
      this.resultado = r.data; window.scrollTo({ top: 0 });
    },
  },
  async mounted() {
    setMeta(tx('gb.diag_meta_t', 'Diagnóstico GrowthBoard') + ' · ExperientIA', tx('gb.diag_meta_d', 'Descubre en qué zona tu empresa está perdiendo crecimiento.'));
    const [cfg, meta] = await Promise.all([api.get('/growthboard/config'), loadMeta()]);
    if (cfg.ok) this.cfg = cfg.data;
    this.meta = meta;
  },
  template: `<div>
    <ScrollProgress/>

    <template v-if="!resultado">
    <PageHero eyebrow="GrowthBoard" :titulo="tx('gb.diag_t','¿Tu empresa está creciendo o solo está corriendo?')" :sub="tx('gb.diag_s','Califica las 11 zonas del tablero y descubre dónde se está trabando tu negocio. En menos de 20 minutos, gratis.')"/>
    <section class="section" style="padding-top:0"><div class="container" style="max-width:760px">
      <div class="gb-progreso" v-if="cfg"><div class="gb-progreso__bar"><span :style="{width:progreso+'%'}"></span></div>
        <span class="gb-progreso__lbl">{{ pasoZona ? (tx('gb.zona','Zona')+' '+(paso-2)+' / '+zonas.length+' · '+tr(pasoZona.nombre)) : (paso+1)+' / '+totalPasos }}</span></div>

      <div class="glass card gb-paso" v-if="!cfg"><p class="small">…</p></div>

      <!-- Paso 0 · Datos -->
      <div class="glass card gb-paso" v-else-if="paso===0">
        <h2 class="h3">{{ tx('gb.p_datos','Tus datos') }}</h2>
        <p class="small" style="margin-bottom:1rem">{{ tx('gb.p_datos_s','El resultado es tuyo: te lo mostramos al final y te lo enviamos al correo.') }}</p>
        <div class="honeypot"><input type="text" v-model="hp" tabindex="-1" autocomplete="off"></div>
        <div class="form-grid two">
          <div class="field"><label>{{ tx('gb.f_nombre','Nombre completo') }} *</label><input class="inp" v-model="lead.name" autocomplete="name"></div>
          <div class="field"><label>{{ tx('gb.f_email','Correo') }} *</label><input class="inp" type="email" v-model="lead.email" autocomplete="email"></div></div>
        <div class="form-grid two">
          <div class="field"><label>WhatsApp *</label><PhoneInput v-model="lead.phone_wa" @dial="lead.phone_dial=$event"/></div>
          <div class="field"><label>{{ tx('gb.f_empresa','Empresa') }} *</label><input class="inp" v-model="lead.company" autocomplete="organization"></div></div>
        <div class="form-grid two">
          <div class="field"><label>{{ tx('gb.f_cargo','Cargo') }}</label><input class="inp" v-model="lead.role"></div>
          <div class="field"><label>{{ tx('gb.f_web','Sitio web o red principal') }}</label><input class="inp" v-model="lead.web_url" placeholder="https://…"></div></div>
      </div>

      <!-- Paso 1 · Perfil -->
      <div class="glass card gb-paso" v-else-if="paso===1">
        <h2 class="h3">{{ tx('gb.p_perfil','Tu empresa') }}</h2>
        <div class="form-grid two" style="margin-top:1rem">
          <div class="field"><label>{{ tx('gb.f_pais','País') }}</label><Combo v-model="lead.country" :options="opcPais" :placeholder="tx('gb.f_pais_ph','Selecciona tu país')"/></div>
          <div class="field"><label>{{ tx('gb.f_ciudad','Ciudad') }}</label><input class="inp" v-model="lead.city"></div></div>
        <div class="form-grid two">
          <div class="field"><label>{{ tx('gb.f_sector','Sector') }}</label><Combo v-model="lead.industry" :options="opcInd" :placeholder="tx('gb.f_sector_ph','Selecciona tu sector')"/></div>
          <div class="field"><label>{{ tx('gb.f_tam','Tamaño de empresa') }}</label><Combo v-model="lead.company_size" :options="opcTam" :placeholder="tx('gb.f_tam_ph','Número de empleados')"/></div></div>
        <div class="field"><label>{{ tx('gb.f_fact','Facturación mensual aproximada') }}</label><Combo v-model="lead.revenue" :options="opcFact" :placeholder="tx('gb.f_fact_ph','Selecciona un rango')"/></div>
      </div>

      <!-- Paso 2 · Contexto -->
      <div class="glass card gb-paso" v-else-if="paso===2">
        <h2 class="h3">{{ tx('gb.p_ctx','Tu contexto') }}</h2>
        <div v-for="(q,qi) in cfg.contexto" :key="q.k" class="gb-ctx">
          <p class="gb-ctx__q">{{ tr(q.q) }}</p>
          <div class="gb-ctx__ops"><button v-for="(op,oi) in q.op" :key="oi" type="button" class="chip-cat" :class="{on:ctxOn(q,oi)}" @click="ctxToggle(q,oi)">{{ tr(op) }}</button></div>
        </div>
      </div>

      <!-- Pasos de zona -->
      <div class="glass card gb-paso" v-else-if="pasoZona" :key="pasoZona.zkey">
        <div class="gb-zona-head"><span class="icon-chip"><Icon :name="pasoZona.icon||'target'"/></span>
          <div><h2 class="h3">{{ tr(pasoZona.nombre) }}</h2><p class="gb-zona-preg">{{ tr(pasoZona.pregunta) }}</p></div></div>
        <p class="small" style="margin:.4rem 0 1rem">{{ tx('gb.escala_hint','Califica cada afirmación de 1 (crítico) a 5 (escalable).') }}</p>
        <div v-for="(af,i) in pasoZona.afirmaciones" :key="i" class="gb-afirmacion">
          <p>{{ tr(af) }}</p>
          <div class="gb-rating"><button v-for="e in escala" :key="e.v" type="button" :class="{on:(scores[pasoZona.zkey]||[])[i]===e.v}" @click="marcar(pasoZona.zkey,i,e.v)" :title="e.l">{{ e.v }}</button></div>
        </div>
      </div>

      <!-- Paso final · Verificación -->
      <div class="glass card gb-paso" v-else-if="pasoFinal">
        <h2 class="h3">{{ tx('gb.p_verif','Último paso: verificación') }}</h2>
        <p class="small" style="margin-bottom:1rem">{{ tx('gb.p_verif_s','Confirma que eres humano y te mostramos tu tablero.') }}</p>
        <div class="ax-captcha" style="max-width:360px">
          <div class="ax-captcha-img">
            <img v-if="cap.image && cap.image.startsWith('data:')" :src="cap.image" alt="captcha" draggable="false">
            <b v-else-if="cap.image">{{ cap.image.replace('text:','') }}</b>
            <span v-else class="ax-captcha-cargando">…</span>
            <button type="button" class="ax-captcha-refresh" @click="cargarCaptcha" title="↻">↻</button></div>
          <input class="inp" v-model="cap.code" :placeholder="tx('gb.captcha_ph','Escribe el código')" maxlength="8" autocomplete="off">
        </div>
      </div>

      <p class="err" v-if="error" style="margin-top:.8rem">{{ error }}</p>
      <div class="gb-nav" v-if="cfg">
        <button v-if="paso>0" class="btn btn-ghost" @click="atras">← {{ tx('gb.atras','Atrás') }}</button>
        <button v-if="!pasoFinal" class="btn btn-primary" style="margin-left:auto" @click="siguiente">{{ tx('gb.siguiente','Siguiente') }} →</button>
        <button v-else class="btn btn-grad" style="margin-left:auto" @click="enviar" :disabled="enviando">{{ enviando?tx('gb.calculando','Leyendo tu tablero…'):tx('gb.ver','Ver mi tablero') }}</button>
      </div>
    </div></section>
    </template>

    <!-- Resultado -->
    <template v-else>
    <PageHero eyebrow="GrowthBoard" :titulo="tr(resultado.banda.titulo)" :sub="tr(resultado.banda.lectura)"/>
    <section class="section" style="padding-top:0"><div class="container">
      <div class="gb-demo-grid">
        <div class="glass card gb-panel" v-reveal>
          <div class="gb-total"><span class="gb-total__v grad-text">{{ resultado.total }}</span><span class="gb-total__m">/ 55</span></div>
          <Cancha :zonas="zonas" :scores="resultado.scores" :critica="resultado.zona_critica"/>
        </div>
        <div class="gb-demo-side">
          <div class="glass card" v-reveal><h2 class="lbl" style="margin-bottom:.9rem">{{ tx('gb.lineas_t','Lectura por líneas') }}</h2>
            <LineasBars :lineas="resultado.lineas" :debil="resultado.linea_debil"/></div>
          <div class="glass card gb-senales" v-reveal v-if="zonaCritica">
            <h2 class="lbl" style="margin-bottom:.7rem"><Icon name="alert" :size="13"/> {{ tx('gb.zona_critica','Tu zona crítica') }} · {{ tr(zonaCritica.nombre) }}</h2>
            <ul><li v-for="(s,i) in resultado.senales" :key="i">{{ tr(s) }}</li></ul></div>
          <div class="glass glass-lit card" v-reveal>
            <h2 class="lbl" style="color:var(--cyan);margin-bottom:.7rem"><Icon name="target" :size="13"/> {{ tx('gb.jugada_t','Primera jugada sugerida') }}</h2>
            <p class="lead" style="font-size:1.05rem">{{ tr(resultado.jugada) }}</p>
            <p class="small" style="margin-top:.6rem">{{ tx('gb.prioridad','Prioridad') }}: {{ tr(resultado.banda.prioridad) }}</p></div>
        </div>
      </div>
      <div class="glass glass-lit card land-final" v-reveal style="margin-top:1.6rem">
        <h2 class="h2">{{ tx('gb.res_cta_t','Tu tablero ya está en juego') }}</h2>
        <p class="lead">{{ tx('gb.res_cta_s','El siguiente paso es la lectura estratégica: 30 minutos con Tonny Dager para interpretar tu tablero y definir tu primera jugada de crecimiento.') }}</p>
        <div class="land-cta-row"><router-link :to="pageUrl('agenda')" class="btn btn-grad">{{ tx('gb.cta_lectura','Agendar lectura estratégica') }}</router-link>
          <router-link :to="gbUrl('demo')" class="btn btn-ghost">{{ tx('gb.ver_demo','Ver la demo del tablero') }}</router-link></div>
        <p class="small garantia" style="justify-content:center"><Icon name="check" :size="13"/> {{ tx('gb.res_ruta','Ruta recomendada') }}: {{ tr(resultado.banda.oferta) }} · {{ tx('gb.res_mail','Te enviamos el resumen a tu correo.') }}</p></div>
    </div></section>
    </template>
  </div>`,
};
