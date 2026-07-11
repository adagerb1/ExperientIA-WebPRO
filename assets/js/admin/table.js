// Tabla inteligente reutilizable: orden por encabezado, filtro y paginación (cliente).
// columns: [{ key, label, sortable?:true, cls?, render?(row)->HTML, raw?(row)->valor de orden }]
export const SmartTable = {
  props: {
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    pageSize: { type: Number, default: 10 },
    search: { type: String, default: '' },
    searchKeys: { type: Array, default: () => [] },
    rowKey: { type: String, default: 'id' },
    clickable: { type: Boolean, default: true },
  },
  emits: ['rowClick'],
  data() { return { sortKey: '', sortDir: 1, page: 1 }; },
  computed: {
    filtered() {
      const q = (this.search || '').trim().toLowerCase();
      if (!q || !this.searchKeys.length) { return this.rows; }
      return this.rows.filter(r => this.searchKeys.some(k => String((typeof k === 'function' ? k(r) : r[k]) ?? '').toLowerCase().includes(q)));
    },
    sorted() {
      if (!this.sortKey) { return this.filtered; }
      const col = this.columns.find(c => c.key === this.sortKey);
      const val = (r) => (col && col.raw ? col.raw(r) : r[this.sortKey]);
      return [...this.filtered].sort((a, b) => {
        const x = val(a), y = val(b);
        if (typeof x === 'number' && typeof y === 'number') { return (x - y) * this.sortDir; }
        return String(x ?? '').localeCompare(String(y ?? ''), undefined, { numeric: true }) * this.sortDir;
      });
    },
    pages() { return Math.max(1, Math.ceil(this.sorted.length / this.pageSize)); },
    pageRows() { const s = (this.page - 1) * this.pageSize; return this.sorted.slice(s, s + this.pageSize); },
  },
  watch: {
    search() { this.page = 1; },
    rows() { if (this.page > this.pages) { this.page = this.pages; } },
  },
  methods: {
    sortBy(c) { if (c.sortable === false) { return; } if (this.sortKey === c.key) { this.sortDir *= -1; } else { this.sortKey = c.key; this.sortDir = 1; } this.page = 1; },
    arrow(c) { return this.sortKey === c.key ? (this.sortDir > 0 ? '▲' : '▼') : ''; },
  },
  template: `<div>
    <div class="glass panel" style="padding:0;overflow:hidden">
      <table><thead><tr>
        <th v-for="c in columns" :key="c.key" @click="sortBy(c)" :style="c.sortable===false?'cursor:default':''">
          {{ c.label }}<span class="sort-arrow" v-if="arrow(c)">{{ arrow(c) }}</span></th>
        <th v-if="$slots.actions" style="cursor:default;text-align:right"></th></tr></thead>
      <tbody>
        <tr v-for="r in pageRows" :key="r[rowKey]" :class="{row:clickable}" @click="clickable && $emit('rowClick',r)">
          <td v-for="c in columns" :key="c.key" :class="c.cls">
            <span v-if="c.render" v-html="c.render(r)"></span><span v-else>{{ r[c.key] }}</span></td>
          <td v-if="$slots.actions" style="text-align:right;white-space:nowrap" @click.stop><slot name="actions" :row="r"/></td></tr>
        <tr v-if="!pageRows.length"><td :colspan="columns.length + ($slots.actions?1:0)" class="empty" style="text-align:center;padding:1.8rem">Sin resultados.</td></tr>
      </tbody></table></div>
    <div class="pager" v-if="pages>1"><span>Página {{ page }} de {{ pages }} · {{ sorted.length }} registros</span>
      <button :disabled="page<=1" @click="page--">‹</button><button :disabled="page>=pages" @click="page++">›</button></div>
  </div>`,
};
