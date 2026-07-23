// Portal admin · Pipeline comercial (Kanban). Mueve leads por etapa (drag o menú),
// registra el cambio como touchpoint y pide a AlexIA el siguiente paso.
import { api, toast } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

export const Pipeline = {
  components: { Icon },
  template: `<div><h1>Pipeline <span class="grad-text">comercial</span></h1>
    <p class="adm__sub">Arrastra un lead o usa el menú para cambiar su etapa · AlexIA sugiere el siguiente paso</p>
    <div class="pipe" v-if="cols.length">
      <div v-for="c in cols" :key="c.estado" class="pipe-col" :class="{over:overCol===c.estado}"
           @dragover.prevent="overCol=c.estado" @dragleave="overCol===c.estado&&(overCol='')" @drop="drop(c.estado)">
        <div class="pipe-head"><span class="pipe-dot" :class="'st-'+c.estado"></span>{{ c.label }}<b>{{ c.total }}</b></div>
        <div class="pipe-cards">
          <div v-for="l in c.items" :key="l.id" class="glass pipe-card" draggable="true" @dragstart="drag(l,c.estado)" @dragend="overCol=''">
            <div class="pipe-card-top"><b>{{ l.name }}</b><span v-if="l.company" class="pipe-co">{{ l.company }}</span></div>
            <div class="pipe-tags"><span class="chip chip--cyan" v-if="l.source">{{ l.source }}</span><span class="chip" v-if="l.utm_campaign">{{ l.utm_campaign }}</span><span class="chip" v-if="l.country">{{ l.country }}</span></div>
            <div v-if="sug[l.id]" class="pipe-sug"><Icon name="ia" :size="13"/> <span>{{ sug[l.id] }}</span></div>
            <div class="pipe-card-act">
              <select class="inp inp-sm" :value="c.estado" @change="mover(l,c.estado,$event.target.value)"><option v-for="o in cols" :key="o.estado" :value="o.estado">{{ o.label }}</option></select>
              <button class="btn btn-ghost btn-sm" :disabled="sugLoading[l.id]" @click="sugerir(l)"><Icon name="ia" :size="14"/> {{ sugLoading[l.id]?'…':'AlexIA' }}</button>
              <a class="btn btn-ghost btn-sm" @click="$router.push('/admin/leads/'+l.id)"><Icon name="eye" :size="14"/></a>
            </div>
          </div>
          <p v-if="!c.items.length" class="pipe-empty">Sin leads</p>
        </div>
      </div>
    </div>
  </div>`,
  data() { return { cols: [], overCol: '', dragId: null, dragFrom: '', sug: {}, sugLoading: {} }; },
  async mounted() { const r = await api.get('/admin/pipeline'); if (r.ok) this.cols = r.data.columnas; },
  methods: {
    drag(lead, from) { this.dragId = lead.id; this.dragFrom = from; },
    drop(to) { this.overCol = ''; if (this.dragId) { const l = this.find(this.dragId); if (l && this.dragFrom !== to) this.mover(l, this.dragFrom, to); } this.dragId = null; },
    find(id) { for (const c of this.cols) { const l = c.items.find(x => x.id === id); if (l) return l; } return null; },
    async mover(lead, from, to) {
      if (from === to) return;
      const src = this.cols.find(c => c.estado === from), dst = this.cols.find(c => c.estado === to);
      const i = src.items.findIndex(x => x.id === lead.id);
      if (i > -1) { src.items.splice(i, 1); src.total--; }
      dst.items.unshift(lead); dst.total++;
      const r = await api.patch('/admin/leads/' + lead.id, { status: to });
      if (r.ok) { toast('Movido a ' + dst.label + '.'); } else { toast('No se pudo mover.'); }
    },
    async sugerir(lead) {
      this.sugLoading = { ...this.sugLoading, [lead.id]: true };
      const r = await api.post('/admin/leads/' + lead.id + '/sugerencia', {});
      this.sugLoading = { ...this.sugLoading, [lead.id]: false };
      if (r.ok) this.sug = { ...this.sug, [lead.id]: r.data.sugerencia };
    },
  },
};
