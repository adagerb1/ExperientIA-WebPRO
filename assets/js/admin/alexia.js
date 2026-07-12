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

const AX_TXT = '#cdd7ea', AX_SUB = '#8792a8', AX_BG = '#0a1b3a';

// Gráfico como SVG autocontenido (colores en línea) → se puede descargar como PNG.
function svgChart(spec) {
  const series = (spec.series || []).filter((s) => s && s.label != null);
  if (!series.length) { return '<p class="aic-empty">Sin datos para graficar.</p>'; }
  const max = Math.max(...series.map((s) => +s.value || 0), 1);
  const title = spec.title || '';
  const W = 340; const tH = title ? 24 : 6;
  let inner = ''; let H;

  if (spec.type === 'pie') {
    const tot = series.reduce((a, s) => a + (+s.value || 0), 0) || 1;
    const cx = 72, cy = tH + 74, r = 62; let a0 = -Math.PI / 2;
    H = Math.max(tH + 156, tH + 16 + series.length * 20);
    series.forEach((s, i) => {
      const frac = (+s.value || 0) / tot; const a1 = a0 + frac * 2 * Math.PI;
      const x0 = cx + r * Math.cos(a0), y0 = cy + r * Math.sin(a0), x1 = cx + r * Math.cos(a1), y1 = cy + r * Math.sin(a1);
      const large = frac > 0.5 ? 1 : 0;
      inner += `<path d="M${cx},${cy} L${x0.toFixed(1)},${y0.toFixed(1)} A${r},${r} 0 ${large} 1 ${x1.toFixed(1)},${y1.toFixed(1)} Z" fill="${PAL[i % PAL.length]}"/>`;
      a0 = a1;
    });
    series.forEach((s, i) => { const y = tH + 20 + i * 20; const pct = Math.round((+s.value || 0) / tot * 100);
      inner += `<rect x="156" y="${y - 9}" width="11" height="11" rx="2" fill="${PAL[i % PAL.length]}"/><text x="173" y="${y}" font-size="11" fill="${AX_TXT}">${esc(String(s.label).slice(0, 20))} · ${pct}%</text>`; });
  } else if (spec.type === 'line') {
    const n = series.length; H = tH + 150; const padL = 10, padR = 10, padT = tH + 6, padB = 24;
    const px = (i) => n > 1 ? padL + i / (n - 1) * (W - padL - padR) : W / 2;
    const py = (v) => H - padB - (+v || 0) / max * (H - padT - padB);
    inner += `<polyline points="${series.map((s, i) => px(i).toFixed(1) + ',' + py(s.value).toFixed(1)).join(' ')}" fill="none" stroke="#18d6f1" stroke-width="2.5"/>`;
    series.forEach((s, i) => { inner += `<circle cx="${px(i).toFixed(1)}" cy="${py(s.value).toFixed(1)}" r="3" fill="#18d6f1"/>`; });
    const step = Math.ceil(n / 8);
    series.forEach((s, i) => { if (n <= 8 || i % step === 0) { inner += `<text x="${px(i).toFixed(1)}" y="${H - 8}" font-size="9" fill="${AX_SUB}" text-anchor="middle">${esc(String(s.label).slice(0, 7))}</text>`; } });
  } else {
    const rowH = 26, barX = 116, valW = 34; H = tH + series.length * rowH + 6;
    series.forEach((s, i) => { const y = tH + i * rowH; const bw = Math.max(3, (+s.value || 0) / max * (W - barX - valW));
      inner += `<text x="0" y="${y + 16}" font-size="11" fill="${AX_TXT}">${esc(String(s.label).slice(0, 17))}</text>`;
      inner += `<rect x="${barX}" y="${y + 6}" width="${bw.toFixed(1)}" height="12" rx="4" fill="${PAL[i % PAL.length]}"/>`;
      inner += `<text x="${W}" y="${y + 16}" font-size="11" fill="${AX_TXT}" text-anchor="end">${esc(String(s.value))}</text>`; });
  }
  const t = title ? `<text x="0" y="14" font-size="12" font-weight="700" fill="${AX_TXT}">${esc(title)}</text>` : '';
  return `<svg class="aic-svg" viewBox="0 0 ${W} ${H}" width="100%" xmlns="http://www.w3.org/2000/svg" font-family="system-ui,-apple-system,sans-serif">${t}${inner}</svg>`;
}

function downloadChart(svg) {
  const vb = (svg.getAttribute('viewBox') || '0 0 340 200').split(' ').map(Number);
  const W = vb[2] || 340, H = vb[3] || 200, scale = 2;
  const clone = svg.cloneNode(true); clone.setAttribute('width', W); clone.setAttribute('height', H);
  const xml = new XMLSerializer().serializeToString(clone);
  const img = new Image();
  img.onload = () => {
    const cv = document.createElement('canvas'); cv.width = W * scale; cv.height = H * scale;
    const ctx = cv.getContext('2d'); ctx.fillStyle = AX_BG; ctx.fillRect(0, 0, cv.width, cv.height); ctx.scale(scale, scale); ctx.drawImage(img, 0, 0);
    cv.toBlob((b) => { const a = document.createElement('a'); a.href = URL.createObjectURL(b); a.download = 'alexia-grafica.png'; a.click(); URL.revokeObjectURL(a.href); }, 'image/png');
  };
  img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(xml)));
}

function renderCharts(root) {
  if (!root) { return; }
  root.querySelectorAll('.ai-chart[data-chart]:not([data-done])').forEach((el) => {
    el.setAttribute('data-done', '1');
    let spec; try { spec = JSON.parse(el.getAttribute('data-chart')); } catch (e) { return; }
    el.innerHTML = `<div class="aic-box">${svgChart(spec)}<button class="aic-dl" title="Descargar PNG">⬇ PNG</button></div>`;
    const svg = el.querySelector('svg'); const btn = el.querySelector('.aic-dl');
    if (svg && btn) { btn.onclick = () => downloadChart(svg); }
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
        <div class="ai-msg a"><p>Hola {{ store.admin?store.admin.name.split(' ')[0]:'' }}, soy <b>AlexIA</b>, tu analista de datos. Consulto toda tu base de datos y respondo con tablas y gráficas (descargables). Pregúntame por leads, embudo, campañas, cohortes, correlaciones… o pídeme una regresión.</p></div>
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
      const r = await api.post('/admin/alexia/analista', { mensaje: tt, conversation_id: this.convId });
      this.loading = false;
      if (r.ok) { this.convId = r.data.conversation_id; this.msgs.push({ role: 'assistant', html: this.clean(r.data.reply) }); }
      else { this.msgs.push({ role: 'assistant', html: '<p>' + esc(r.error || 'AlexIA no está disponible. Configura y activa OpenAI o Claude en Conectores.') + '</p>' }); }
      this.scroll(); this.$nextTick(() => renderCharts(this.$refs.msgs));
    },
    clean(html) { return String(html || '').replace(/^\s*```(?:html)?\s*/i, '').replace(/```\s*$/i, '').trim(); },
    scroll() { this.$nextTick(() => { const m = this.$refs.msgs; if (m) { m.scrollTop = m.scrollHeight; } }); },
  },
};
