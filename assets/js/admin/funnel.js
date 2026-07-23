// Portal admin · Funnel comercial y costo por lead: visitas → leads → diagnósticos
// → reservas → clientes, global y por campaña UTM, con gasto editable y CPL/CPA.
import { api, toast } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));
const num = (n) => Number(n || 0).toLocaleString('es-CO');

export const Funnel = {
  components: { Icon },
  data(){ return { dias:30, d:null, edit:{}, guardando:'' }; },
  computed:{
    etapas(){ if(!this.d) return [];
      const f=this.d.funnel; const base=Math.max(f.visitas, f.leads, 1);
      const conv=(a,b)=> b>0 ? Math.round(a/b*1000)/10 : null;
      return [
        { k:'visitas', l:'Visitas', v:f.visitas, pct:100, conv:null },
        { k:'leads', l:'Leads', v:f.leads, pct:Math.max(4,Math.round(f.leads/base*100)), conv:conv(f.leads,f.visitas) },
        { k:'diagnosticos', l:'Diagnósticos GB', v:f.diagnosticos, pct:Math.max(4,Math.round(f.diagnosticos/base*100)), conv:conv(f.diagnosticos,f.leads) },
        { k:'reservas', l:'Reservas 1:1', v:f.reservas, pct:Math.max(4,Math.round(f.reservas/base*100)), conv:conv(f.reservas,f.leads) },
        { k:'clientes', l:'Clientes', v:f.clientes, pct:Math.max(4,Math.round(f.clientes/base*100)), conv:conv(f.clientes,f.leads) },
      ]; },
    serie(){ if(!this.d || !this.d.por_dia.length) return { bars:[], max:0 };
      const max=Math.max(...this.d.por_dia.map(x=>Number(x.c)),1);
      return { bars:this.d.por_dia.map(x=>({ d:String(x.d).slice(5), c:Number(x.c), h:Math.max(6,Math.round(Number(x.c)/max*100)) })), max }; },
  },
  methods:{ num, esc,
    async load(){ const r=await api.get('/admin/funnel?dias='+this.dias);
      if(r.ok){ this.d=r.data; this.edit={}; for(const c of r.data.campanas) this.edit[c.campaign]=c.gasto||''; }
      else toast(r.error||'No se pudo cargar el funnel.','err'); },
    cambiar(d){ this.dias=d; this.load(); },
    async guardarGasto(c){ this.guardando=c.campaign;
      const r=await api.put('/admin/funnel/gasto',{ mes:this.d.mes_actual, campaign:c.campaign, source:c.source, monto:Number(this.edit[c.campaign]||0) });
      this.guardando='';
      if(r.ok){ toast('Gasto de "'+c.campaign+'" guardado ('+this.d.mes_actual+').'); this.load(); } else toast(r.error||'Error','err'); },
  },
  mounted(){ this.load(); },
  template:`<div>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
      <div><h1>Funnel y pauta</h1><p class="adm__sub" style="margin:0">Visitas → leads → diagnósticos → reservas → clientes · costo por lead por campaña</p></div>
      <div class="lang-tabs" style="margin:0;border:0;padding:0">
        <button v-for="p in [7,30,90]" :key="p" type="button" :class="{active:dias===p}" @click="cambiar(p)">{{ p }} días</button></div></div>

    <template v-if="d">
    <div class="panels" style="grid-template-columns:1.2fr 1fr;margin-top:1.4rem">
      <div class="glass panel"><h3>El embudo · últimos {{ d.dias }} días</h3>
        <div class="fun-etapas">
          <div v-for="e in etapas" :key="e.k" class="fun-etapa">
            <div class="fun-etapa__head"><span>{{ e.l }}</span>
              <span><b>{{ num(e.v) }}</b><em v-if="e.conv!==null" class="fun-conv">{{ e.conv }}%</em></span></div>
            <div class="fun-etapa__bar"><span :style="{width:e.pct+'%'}"></span></div>
          </div></div>
        <p class="small" style="margin-top:.8rem">Las visitas se miden con analítica propia (sin cookies) desde hoy; el porcentaje es conversión sobre la etapa anterior (leads sobre visitas; el resto sobre leads).</p></div>
      <div class="glass panel"><h3>Leads por día</h3>
        <div class="fun-chart" v-if="serie.bars.length">
          <div v-for="b in serie.bars" :key="b.d" class="fun-bar" :title="b.d+': '+b.c"><span :style="{height:b.h+'%'}"></span></div></div>
        <p class="small" v-else>Sin leads en el período.</p></div>
    </div>

    <div class="glass panel" style="margin-top:1.2rem"><h3>Por campaña · gasto del mes {{ d.mes_actual }} editable</h3>
      <div style="overflow-x:auto"><table>
        <thead><tr><th>Campaña</th><th>Fuente</th><th>Leads</th><th>Diagn.</th><th>Reservas</th><th>Clientes</th><th>Gasto (USD)</th><th>CPL</th><th>CPA</th><th></th></tr></thead>
        <tbody><tr v-for="c in d.campanas" :key="c.campaign+c.source">
          <td><b style="color:var(--neutral-light)">{{ c.campaign }}</b></td>
          <td>{{ c.source }}</td>
          <td>{{ num(c.leads) }}</td><td>{{ num(c.diagnosticos) }}</td><td>{{ num(c.reservas) }}</td><td>{{ num(c.clientes) }}</td>
          <td style="min-width:120px"><input class="inp" type="number" min="0" step="0.01" v-model="edit[c.campaign]" style="padding:.4rem .6rem;width:110px"></td>
          <td><b :style="c.cpl!==null?'color:var(--cyan)':''">{{ c.cpl!==null ? '$'+c.cpl : '—' }}</b></td>
          <td>{{ c.cpa!==null ? '$'+c.cpa : '—' }}</td>
          <td><button class="btn btn-primary btn-sm" @click="guardarGasto(c)" :disabled="guardando===c.campaign">{{ guardando===c.campaign?'…':'✓' }}</button></td>
        </tr>
        <tr v-if="!d.campanas.length"><td colspan="10" style="text-align:center;padding:1.4rem">Sin leads en el período.</td></tr></tbody>
      </table></div>
      <p class="small" style="margin-top:.8rem">CPL = gasto / leads · CPA = gasto / clientes. El gasto se registra por mes; el período suma los meses que toca. "(directo)" agrupa leads sin campaña UTM.</p></div>
    </template>
  </div>`,
};
