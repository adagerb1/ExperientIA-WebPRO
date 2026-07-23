// Portal admin · Segmentos configurables y trilingües (categorías, tamaños, orígenes, canales)
import { api, toast, CMS_LANGS, CMS_CODES } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

const blankI18n = () => { const o = {}; for (const c of CMS_CODES) o[c] = ''; return o; };

// Tipos de segmento gestionables (deben coincidir con SegmentTaxonomyController::KINDS).
const KINDS = [
  { kind: 'category', label: 'Categorías de recursos', hint: 'Etiquetas del blog/recursos. Se muestran en el idioma del visitante.' },
  { kind: 'company_size', label: 'Tamaños de empresa', hint: 'Rangos de empleados usados en formularios y segmentación.' },
  { kind: 'source', label: 'Orígenes de lead', hint: 'De dónde llega cada lead (formulario, campaña, chat…).' },
  { kind: 'channel', label: 'Canales', hint: 'Canal de captación (web, Telegram, WhatsApp…).' },
];

export const Taxonomias = {
  components: { Icon },
  template: `<div>
    <div><h1>Segmentos y categorías</h1><p class="adm__sub" style="margin:0">Taxonomías configurables y trilingües · categorías, tamaños, orígenes y canales</p></div>

    <div class="lang-tabs" style="margin-top:1.2rem">
      <button type="button" v-for="k in kinds" :key="k.kind" :class="{active:kind===k.kind}" @click="cambiar(k.kind)">{{ k.label }}</button>
    </div>
    <p class="rec-lang-note">{{ actual.hint }}</p>

    <div class="glass panel" style="margin-top:1rem">
      <table class="tax-table" style="width:100%;border-collapse:collapse">
        <thead><tr>
          <th style="text-align:left;padding:.5rem">Clave</th>
          <th v-for="l in langs" :key="l.code" style="text-align:left;padding:.5rem">{{ l.label }}</th>
          <th style="width:70px;padding:.5rem">Orden</th>
          <th style="width:60px;padding:.5rem">Activo</th>
          <th style="width:60px"></th>
        </tr></thead>
        <tbody>
          <tr v-for="it in items" :key="it.id" style="border-top:1px solid var(--line)">
            <td style="padding:.4rem"><code>{{ it.skey }}</code></td>
            <td v-for="l in langs" :key="l.code" style="padding:.4rem"><input class="inp" v-model="it.nombre[l.code]"></td>
            <td style="padding:.4rem"><input class="inp" type="number" v-model.number="it.sort" style="width:60px"></td>
            <td style="padding:.4rem;text-align:center"><input type="checkbox" v-model="it.active" :true-value="1" :false-value="0"></td>
            <td style="padding:.4rem;white-space:nowrap">
              <button class="btn btn-primary btn-sm" @click="guardar(it)" :disabled="it._busy">✓</button>
              <button class="btn btn-danger btn-sm" @click="eliminar(it)">✕</button></td>
          </tr>
          <tr v-if="!items.length"><td :colspan="langs.length+4" style="padding:1rem;text-align:center;color:var(--muted)">Sin elementos todavía.</td></tr>
        </tbody>
      </table>
    </div>

    <div class="glass panel" style="margin-top:1rem">
      <h3 style="margin:0 0 .7rem">Agregar {{ actual.label.toLowerCase() }}</h3>
      <div class="form-grid two">
        <div class="field"><label>Clave interna (a-z, guion bajo)</label><input class="inp" v-model="nuevo.skey" placeholder="p.ej. nuevo_segmento"></div>
        <div class="field"><label>Orden</label><input class="inp" type="number" v-model.number="nuevo.sort"></div>
      </div>
      <div class="form-grid" :style="'grid-template-columns:repeat('+langs.length+',1fr)'">
        <div class="field" v-for="l in langs" :key="l.code"><label>Nombre · {{ l.label }}</label><input class="inp" v-model="nuevo.nombre[l.code]"></div>
      </div>
      <div style="display:flex;justify-content:flex-end;margin-top:.5rem">
        <button class="btn btn-primary btn-sm" @click="crear" :disabled="creando"><Icon name="plus" :size="13"/> Agregar</button></div>
    </div>
  </div>`,
  data() { return { kinds: KINDS, langs: CMS_LANGS, kind: 'category', items: [], nuevo: this.blankNuevo(), creando: false }; },
  computed: { actual() { return this.kinds.find(k => k.kind === this.kind) || this.kinds[0]; } },
  methods: {
    blankNuevo() { return { skey: '', nombre: blankI18n(), sort: 0, active: 1 }; },
    async load() {
      const r = await api.get('/admin/taxonomia/' + this.kind);
      if (r.ok) this.items = r.data.map(it => ({ ...it, active: Number(it.active), nombre: (it.nombre && typeof it.nombre === 'object') ? { ...blankI18n(), ...it.nombre } : { ...blankI18n(), es: String(it.nombre || '') } }));
    },
    cambiar(k) { this.kind = k; this.nuevo = this.blankNuevo(); this.load(); },
    async guardar(it) {
      it._busy = true;
      const r = await api.put('/admin/taxonomia/' + this.kind + '/' + it.id, { skey: it.skey, nombre: it.nombre, sort: it.sort, active: it.active });
      it._busy = false;
      toast(r.ok ? 'Guardado.' : (r.error || 'Error'), r.ok ? 'ok' : 'err');
    },
    async crear() {
      if (!this.nuevo.skey) { toast('La clave interna es obligatoria.', 'err'); return; }
      if (!this.nuevo.nombre.es) { toast('El nombre en ES es obligatorio.', 'err'); return; }
      this.creando = true;
      const r = await api.post('/admin/taxonomia/' + this.kind, this.nuevo);
      this.creando = false;
      if (r.ok) { toast('Agregado.'); this.nuevo = this.blankNuevo(); this.load(); } else toast(r.error || 'Error', 'err');
    },
    async eliminar(it) {
      if (!confirm('¿Eliminar "' + (it.nombre.es || it.skey) + '"?')) return;
      const r = await api.del('/admin/taxonomia/' + this.kind + '/' + it.id);
      if (r.ok) { toast('Eliminado.'); this.load(); } else toast(r.error || 'Error', 'err');
    },
  },
  mounted() { this.load(); },
};
