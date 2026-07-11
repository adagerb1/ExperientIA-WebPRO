// Portal admin · AlexIA — asesora estratégica flotante (chat con HTML, tablas y gráficos)
import { api, store } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

const QUICK = [
  { label: 'Leads sin gestionar', desc: '¿Cuáles priorizar hoy?', prompt: '¿Cuáles leads están sin gestionar y en qué orden de prioridad los abordarías? Preséntalo en una tabla.' },
  { label: 'Resumen de crecimiento', desc: 'Estado con métricas clave', prompt: 'Dame un resumen ejecutivo del estado de crecimiento de ExperientIA con las métricas clave y una lectura estratégica.' },
  { label: 'Leads por fuente', desc: 'Gráfica de distribución', prompt: 'Grafica la distribución de leads por fuente de entrada y dime qué canal está funcionando mejor.' },
  { label: 'Embudo de conversión', desc: 'Dónde se fuga', prompt: 'Analiza el embudo de conversión (leads → contactados → reserva → clientes) con una gráfica y dime dónde hay fuga.' },
  { label: 'Decisiones de la semana', desc: 'Como consejera del CEO', prompt: 'Como consejera del consejo consultivo, ¿qué 3 a 5 decisiones tomarías esta semana para acelerar el crecimiento? Justifica con la data.' },
];

const PAL = ['#18d6f1', '#7a63ff', '#4be3a0', '#ffc247', '#ff7d9d', '#5aa9ff', '#c8bcff', '#39f9b0'];
const esc = (s) => String(s ?? '').replace(/[&<>]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;' }[c]));

function chartHTML(spec) {
  const series = (spec.series || []).filter((s) => s && s.label != null);
  const title = spec.title ? `<p class="aic-title">${esc(spec.title)}</p>` : '';
  if (!series.length) { return title + '<p class="aic-empty">Sin datos para graficar.</p>'; }
  const max = Math.max(...series.map((s) => +s.value || 0), 1);

  if (spec.type === 'pie') {
    const tot = series.reduce((a, s) => a + (+s.value || 0), 0) || 1;
    let acc = 0; const stops = [];
    const legend = series.map((s, i) => {
      const pct = (+s.value || 0) / tot * 100; const from = acc; acc += pct;
      stops.push(`${PAL[i % PAL.length]} ${from}% ${acc}%`);
      return `<span class="aic-leg"><i style="background:${PAL[i % PAL.length]}"></i>${esc(s.label)} · ${Math.round(pct)}%</span>`;
    }).join('');
    return `${title}<div class="aic-pie-wrap"><div class="aic-pie" style="background:conic-gradient(${stops.join(',')})"></div><div class="aic-legend">${legend}</div></div>`;
  }
  if (spec.type === 'line') {
    const n = series.length; const w = 280; const h = 120; const pad = 8;
    const pts = series.map((s, i) => {
      const x = n > 1 ? pad + i / (n - 1) * (w - 2 * pad) : w / 2;
      const y = h - pad - (+s.value || 0) / max * (h - 2 * pad);
      return x.toFixed(1) + ',' + y.toFixed(1);
    }).join(' ');
    const labels = series.map((s) => `<span>${esc(s.label)}</span>`).join('');
    return `${title}<svg class="aic-line" viewBox="0 0 ${w} ${h}" preserveAspectRatio="none"><polyline points="${pts}" fill="none" stroke="#18d6f1" stroke-width="2.5"/></svg><div class="aic-xlabels">${labels}</div>`;
  }
  // barras horizontales (default)
  const rows = series.map((s, i) => `<div class="aic-row"><span class="aic-lbl" title="${esc(s.label)}">${esc(s.label)}</span><div class="aic-track"><div class="aic-fill" style="width:${Math.max(3, (+s.value || 0) / max * 100)}%;background:${PAL[i % PAL.length]}"></div></div><span class="aic-val">${esc(String(s.value))}</span></div>`).join('');
  return `${title}<div class="aic-bars">${rows}</div>`;
}

function renderCharts(root) {
  if (!root) { return; }
  root.querySelectorAll('.ai-chart[data-chart]:not([data-done])').forEach((el) => {
    el.setAttribute('data-done', '1');
    let spec; try { spec = JSON.parse(el.getAttribute('data-chart')); } catch (e) { return; }
    el.innerHTML = chartHTML(spec);
  });
}

export const AlexiaWidget = {
  components: { Icon },
  template: `<div>
    <button class="ai-fab" :class="{on:open}" @click="open=!open" title="AlexIA · asesora estratégica"><Icon v-if="!open" name="sparkle" :size="24"/><span v-else style="font-size:1.3rem;line-height:1">✕</span></button>
    <div class="ai-widget glass" v-if="open">
      <div class="ai-head"><div><b>AlexIA</b><span>Consejera estratégica · consejo consultivo</span></div>
        <div class="ai-head-btns"><button @click="quickOpen=!quickOpen" title="Solicitudes rápidas"><Icon name="sparkle" :size="16"/></button><button @click="open=false" title="Cerrar">✕</button></div></div>
      <div class="ai-msgs" ref="msgs">
        <div class="ai-msg a"><p>Hola {{ store.admin?store.admin.name.split(' ')[0]:'' }}, soy <b>AlexIA</b>. Pregúntame por tus leads, el crecimiento, los recursos o pídeme una gráfica y la genero.</p></div>
        <div v-for="(m,i) in msgs" :key="i" class="ai-msg" :class="m.role==='user'?'u':'a'" v-html="m.html"></div>
        <div v-if="loading" class="ai-msg a ai-typing"><span></span><span></span><span></span></div></div>
      <div class="ai-quick" v-if="quickOpen">
        <p class="ai-quick-t">Solicitudes rápidas</p>
        <button v-for="q in quick" :key="q.label" @click="ask(q.prompt)"><b>{{ q.label }}</b><em>{{ q.desc }}</em></button></div>
      <div class="ai-input">
        <textarea v-model="text" rows="1" placeholder="Escribe tu consulta…" @keydown.enter.exact.prevent="send"></textarea>
        <button class="btn btn-primary btn-sm" @click="send" :disabled="loading"><Icon name="send" :size="16"/></button></div>
    </div></div>`,
  data() { return { open: false, quickOpen: false, msgs: [], text: '', loading: false, convId: null, quick: QUICK, store }; },
  methods: {
    ask(prompt) { this.text = prompt; this.quickOpen = false; this.send(); },
    async send() {
      const tt = this.text.trim();
      if (!tt || this.loading) { return; }
      this.msgs.push({ role: 'user', html: '<p>' + esc(tt) + '</p>' });
      this.text = ''; this.loading = true; this.scroll();
      const r = await api.post('/admin/alexia', { mensaje: tt, conversation_id: this.convId });
      this.loading = false;
      if (r.ok) { this.convId = r.data.conversation_id; this.msgs.push({ role: 'assistant', html: this.clean(r.data.reply) }); }
      else { this.msgs.push({ role: 'assistant', html: '<p>' + esc(r.error || 'AlexIA no está disponible. Configura y activa OpenAI o Claude en Conectores.') + '</p>' }); }
      this.scroll(); this.$nextTick(() => renderCharts(this.$refs.msgs));
    },
    clean(html) { return String(html || '').replace(/^\s*```(?:html)?\s*/i, '').replace(/```\s*$/i, '').trim(); },
    scroll() { this.$nextTick(() => { const m = this.$refs.msgs; if (m) { m.scrollTop = m.scrollHeight; } }); },
  },
};
