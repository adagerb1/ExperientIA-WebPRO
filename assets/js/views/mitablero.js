// Mi GrowthBoard · el tablero vivo del cliente durante el acompañamiento.
// Acceso sin contraseña por enlace firmado (?t=). El cliente ve su cancha y su
// evolución, reporta el avance de sus jugadas y llena el marcador semanal.
import { t, tr, pageUrl, api, store, setMeta } from '../lib/core.js';
import { Icon, ScrollProgress } from '../lib/ui.js';
import { PageHero } from '../lib/layout.js';
import { Cancha } from './growthboard.js';

const tx = (key, fb) => { const v = t(key); return (v && v !== key) ? v : fb; };
const TKEY = 'exp_gbtoken';

export const MiTablero = {
  components: { Icon, Cancha, PageHero, ScrollProgress },
  data() { return {
    token: '', d: null, zonas: [], cargando: true, err: '',
    email: '', pidiendo: false, pedido: false,
    checkin: { avanzo: '', trabo: '', dato: '', decision: '', proxima: '' }, enviandoCk: false,
    notas: {}, guardando: 0,
  }; },
  computed: {
    t: () => t, tr: () => tr, pageUrl: () => pageUrl,
    ultimo() { return this.d ? this.d.results[0] : null; },
    previo() { return this.d && this.d.results.length > 1 ? this.d.results[1] : null; },
    delta() { return this.previo ? Math.round((this.ultimo.total - this.previo.total) * 10) / 10 : null; },
    estados() { return [
      { v: 'pendiente', l: tx('gbm.e_pendiente', 'Pendiente') }, { v: 'ejecucion', l: tx('gbm.e_ejecucion', 'En ejecución') },
      { v: 'ejecutada', l: tx('gbm.e_ejecutada', 'Ejecutada') }, { v: 'descartada', l: tx('gbm.e_descartada', 'Descartada') }]; },
    zonaNombre() { const m = {}; for (const z of this.zonas) m[z.zkey] = z.nombre; return m; },
  },
  methods: { tx,
    fecha(s) { return String(s || '').slice(0, 10); },
    async cargar() {
      this.cargando = true; this.err = '';
      const [cfg, r] = await Promise.all([api.get('/growthboard/config'), api.get('/mi-tablero?t=' + encodeURIComponent(this.token))]);
      if (cfg.ok) this.zonas = cfg.data.zonas;
      if (r.ok) { this.d = r.data; for (const p of r.data.plays) this.notas[p.id] = p.notas || ''; }
      else { this.err = r.error || ''; this.token = ''; try { localStorage.removeItem(TKEY); } catch (e) {} }
      this.cargando = false;
    },
    async pedirAcceso() {
      if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(this.email.trim())) { this.err = tx('gbm.err_email', 'Escribe el correo con el que hiciste tu diagnóstico.'); return; }
      this.pidiendo = true; this.err = '';
      await api.post('/mi-tablero/acceso', { email: this.email.trim(), locale: store.locale });
      this.pidiendo = false; this.pedido = true;
    },
    async cambiarEstado(p, estado) {
      p.estado = estado; this.guardando = p.id;
      await api.post('/mi-tablero/jugadas/' + p.id + '/estado', { t: this.token, estado, resultado: p.resultado || '', notas: this.notas[p.id] || '' });
      this.guardando = 0;
    },
    async marcarResultado(p, r) {
      p.resultado = r;
      await api.post('/mi-tablero/jugadas/' + p.id + '/estado', { t: this.token, estado: p.estado, resultado: r, notas: this.notas[p.id] || '' });
    },
    async guardarNotas(p) {
      this.guardando = p.id;
      await api.post('/mi-tablero/jugadas/' + p.id + '/estado', { t: this.token, estado: p.estado, resultado: p.resultado || '', notas: this.notas[p.id] || '' });
      this.guardando = 0;
    },
    async enviarCheckin() {
      if (!Object.values(this.checkin).some(v => v.trim())) { this.err = tx('gbm.err_ck', 'Escribe al menos una respuesta del marcador.'); return; }
      this.enviandoCk = true; this.err = '';
      const r = await api.post('/mi-tablero/checkin', { t: this.token, ...this.checkin });
      this.enviandoCk = false;
      if (r.ok) { this.checkin = { avanzo: '', trabo: '', dato: '', decision: '', proxima: '' }; this.cargar(); }
    },
  },
  async mounted() {
    setMeta('Mi GrowthBoard · ExperientIA', tx('gbm.meta', 'Tu tablero de crecimiento en vivo.'));
    const q = new URLSearchParams(location.search);
    this.token = q.get('t') || '';
    if (this.token) { try { localStorage.setItem(TKEY, this.token); } catch (e) {}
      history.replaceState(null, '', location.pathname); }
    else { try { this.token = localStorage.getItem(TKEY) || ''; } catch (e) {} }
    if (this.token) { await this.cargar(); } else { this.cargando = false; }
  },
  template: `<div>
    <ScrollProgress/>

    <!-- Sin acceso: pedir enlace -->
    <template v-if="!d">
      <PageHero eyebrow="Mi GrowthBoard" :titulo="tx('gbm.acceso_t','Tu tablero, en vivo')" :sub="tx('gbm.acceso_s','El acceso es personal: te enviamos un enlace a tu correo. Es el mismo con el que hiciste tu diagnóstico.')"/>
      <section class="section" style="padding-top:0"><div class="container" style="max-width:520px">
        <div class="glass card gb-paso" v-if="cargando"><p class="small">…</p></div>
        <div class="glass card gb-paso" v-else-if="!pedido">
          <div class="field"><label>{{ tx('gbm.acceso_email','Tu correo') }}</label><input class="inp" type="email" v-model="email" autocomplete="email" @keydown.enter="pedirAcceso"></div>
          <p class="err" v-if="err">{{ err }}</p>
          <button class="btn btn-grad" style="width:100%;margin-top:.6rem" @click="pedirAcceso" :disabled="pidiendo">{{ pidiendo?'…':tx('gbm.acceso_cta','Enviarme el enlace de acceso') }}</button>
          <p class="small" style="margin-top:.8rem;text-align:center">{{ tx('gbm.acceso_nota','¿Aún no tienes diagnóstico?') }} <router-link :to="pageUrl('tablero')+'/diagnostico'" style="color:var(--cyan)">{{ tx('gb.cta_diag','Hacer mi diagnóstico') }}</router-link></p>
        </div>
        <div class="glass glass-lit card gb-paso" v-else style="text-align:center">
          <span class="icon-chip" style="margin-inline:auto"><Icon name="mail" :size="18"/></span>
          <h2 class="h3" style="margin-top:.8rem">{{ tx('gbm.pedido_t','Revisa tu correo') }}</h2>
          <p class="small">{{ tx('gbm.pedido_s','Si tu correo tiene un diagnóstico asociado, el enlace de acceso ya va en camino. Vence en 30 días.') }}</p>
        </div>
      </div></section>
    </template>

    <!-- Con acceso: el tablero vivo -->
    <template v-else>
      <PageHero eyebrow="Mi GrowthBoard" :titulo="(d.lead.company||d.lead.name)" :sub="tx('gbm.sub','Tu partido, en vivo: cancha, jugadas y marcador semanal. La lectura profunda la hacen contigo en el acompañamiento.')"/>
      <section class="section" style="padding-top:0"><div class="container">

        <div class="gb-demo-grid">
          <div class="glass card gb-panel" v-reveal>
            <div class="gb-total"><span class="gb-total__v grad-text">{{ ultimo.total }}</span><span class="gb-total__m">/ 55</span>
              <span v-if="delta!==null" class="chip" :class="delta>=0?'chip--cyan':''" style="margin-left:.6rem">{{ delta>=0?'+':'' }}{{ delta }} {{ tx('gbm.vs','vs. diagnóstico anterior') }}</span></div>
            <Cancha :zonas="zonas" :scores="ultimo.scores" :critica="ultimo.zona_critica"/>
            <p class="small" style="text-align:center;margin-top:.8rem">{{ tx('gbm.rediag','¿Pasó un trimestre? Vuelve a leer el partido:') }} <router-link :to="pageUrl('tablero')+'/diagnostico'" style="color:var(--cyan)">{{ tx('gbm.rediag_cta','re-hacer diagnóstico') }}</router-link></p>
          </div>

          <div class="gb-demo-side">
            <!-- Jugadas -->
            <div class="glass card" v-reveal>
              <h2 class="lbl" style="margin-bottom:.9rem"><Icon name="target" :size="13"/> {{ tx('gbm.jugadas_t','Tus jugadas') }}</h2>
              <p class="small" v-if="!d.plays.length">{{ tx('gbm.jugadas_vacio','Tus jugadas se definen contigo en la lectura estratégica y aparecerán aquí.') }}</p>
              <div v-for="p in d.plays" :key="p.id" class="gb-play">
                <div class="gb-play__head">
                  <span class="chip chip-soft" v-if="p.zona && zonaNombre[p.zona]">{{ tr(zonaNombre[p.zona]) }}</span>
                  <b>{{ p.titulo }}</b></div>
                <p class="small" v-if="p.porque">{{ p.porque }}</p>
                <p class="gb-play__meta"><span v-if="p.responsable"><Icon name="people" :size="11"/> {{ p.responsable }}</span>
                  <span v-if="p.fecha_limite"><Icon name="calendar" :size="11"/> {{ p.fecha_limite }}</span>
                  <span v-if="p.indicador"><Icon name="analitica" :size="11"/> {{ p.indicador }}</span></p>
                <div class="gb-play__estado">
                  <button v-for="e in estados" :key="e.v" type="button" class="chip-cat" :class="{on:p.estado===e.v}" @click="cambiarEstado(p,e.v)">{{ e.l }}</button>
                </div>
                <div v-if="p.estado==='ejecutada'" class="gb-play__resultado">
                  <span class="small">{{ tx('gbm.movio_q','¿Movió el indicador?') }}</span>
                  <button type="button" class="chip-cat" :class="{on:p.resultado==='movio'}" @click="marcarResultado(p,'movio')">{{ tx('gbm.movio_si','Sí, movió') }}</button>
                  <button type="button" class="chip-cat" :class="{on:p.resultado==='no_movio'}" @click="marcarResultado(p,'no_movio')">{{ tx('gbm.movio_no','Aún no') }}</button>
                </div>
                <div class="gb-play__notas"><input class="inp" v-model="notas[p.id]" :placeholder="tx('gbm.notas_ph','Nota rápida de avance…')" @keydown.enter="guardarNotas(p)">
                  <button class="btn btn-ghost btn-sm" @click="guardarNotas(p)" :disabled="guardando===p.id">{{ guardando===p.id?'…':'✓' }}</button></div>
              </div>
            </div>

            <!-- Marcador semanal -->
            <div class="glass glass-lit card" v-reveal>
              <h2 class="lbl" style="color:var(--cyan);margin-bottom:.7rem"><Icon name="clock" :size="13"/> {{ tx('gbm.ck_t','Marcador semanal') }}</h2>
              <p class="small" style="margin-bottom:.8rem">{{ tx('gbm.ck_s','Las 5 preguntas del ritual. 5 minutos que mantienen el partido bajo control.') }}</p>
              <div class="field"><label>{{ tx('gbm.ck_avanzo','¿Qué avanzó?') }}</label><input class="inp" v-model="checkin.avanzo"></div>
              <div class="field"><label>{{ tx('gbm.ck_trabo','¿Qué se trabó?') }}</label><input class="inp" v-model="checkin.trabo"></div>
              <div class="field"><label>{{ tx('gbm.ck_dato','¿Qué dato cambió?') }}</label><input class="inp" v-model="checkin.dato"></div>
              <div class="field"><label>{{ tx('gbm.ck_decision','¿Qué decisión tomamos?') }}</label><input class="inp" v-model="checkin.decision"></div>
              <div class="field"><label>{{ tx('gbm.ck_proxima','¿Cuál es la próxima jugada?') }}</label><input class="inp" v-model="checkin.proxima"></div>
              <p class="err" v-if="err">{{ err }}</p>
              <button class="btn btn-primary btn-sm" style="width:100%" @click="enviarCheckin" :disabled="enviandoCk">{{ enviandoCk?'…':tx('gbm.ck_cta','Registrar la semana') }}</button>
              <div v-if="d.checkins.length" class="gb-ck-hist">
                <p class="lbl" style="margin:.9rem 0 .5rem">{{ tx('gbm.ck_hist','Semanas anteriores') }}</p>
                <details v-for="c in d.checkins" :key="c.id" class="gb-ck-item"><summary>{{ fecha(c.created_at) }} · {{ (c.avanzo||c.proxima||'—').slice(0,48) }}</summary>
                  <ul><li v-if="c.avanzo"><b>{{ tx('gbm.ck_avanzo','¿Qué avanzó?') }}</b> {{ c.avanzo }}</li>
                    <li v-if="c.trabo"><b>{{ tx('gbm.ck_trabo','¿Qué se trabó?') }}</b> {{ c.trabo }}</li>
                    <li v-if="c.dato"><b>{{ tx('gbm.ck_dato','¿Qué dato cambió?') }}</b> {{ c.dato }}</li>
                    <li v-if="c.decision"><b>{{ tx('gbm.ck_decision','¿Qué decisión tomamos?') }}</b> {{ c.decision }}</li>
                    <li v-if="c.proxima"><b>{{ tx('gbm.ck_proxima','¿Cuál es la próxima jugada?') }}</b> {{ c.proxima }}</li></ul></details>
              </div>
            </div>
          </div>
        </div>

        <div class="glass glass-lit card land-final" v-reveal style="margin-top:1.6rem">
          <h2 class="h2">{{ tx('gbm.cta_t','¿Necesitas revisar el partido con tu DT?') }}</h2>
          <p class="lead">{{ tx('gbm.cta_s','Agenda una sesión con Tonny Dager para leer tu tablero, ajustar jugadas y definir el siguiente ciclo.') }}</p>
          <div class="land-cta-row"><router-link :to="pageUrl('agenda')" class="btn btn-grad">{{ tx('gb.cta_lectura','Agendar lectura estratégica') }}</router-link></div></div>
      </div></section>
    </template>
  </div>`,
};
