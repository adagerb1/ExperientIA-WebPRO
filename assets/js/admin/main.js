// Portal admin · Bootstrap (Vue 3 SPA, importmap). Auth Bearer, RBAC básico.
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { store, api, logout, loadDict } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { reveal } from '../lib/ui.js';
import { Toasts } from '../lib/layout.js';
import { Login, Dashboard, Leads, LeadDetail } from './views1.js';
import { Reservas, Contenido, Conectores, Plantillas } from './views2.js';
import { Campanas, Segmentos } from './views3.js';
import { Pipeline } from './pipeline.js';
import { Secuencias } from './sequences.js';
import { Diagnosticos } from './diagnosticos.js';
import { Recursos } from './recursos.js';
import { Taxonomias } from './taxonomias.js';
import { GBClientes } from './gbclientes.js';
import { Studio } from './studio.js';
import { Funnel } from './funnel.js';
import { Usuarios } from './usuarios.js';
import { AlexiaWidget } from './alexia.js';

const Shell = {
  components: { Icon, Toasts, AlexiaWidget },
  template: `<div class="adm" :class="{'adm--collapsed':collapsed}"><aside class="adm__side">
    <div class="adm__brand">
      <img src="/assets/img/brand/logo.png" alt="ExperientIA" class="adm__logo" draggable="false">
      <img src="/assets/img/brand/symbol.png" alt="" aria-hidden="true" class="adm__logo-mini" draggable="false">
      <button class="adm__toggle" @click="toggleCollapse" :aria-label="collapsed?'Ampliar menú':'Colapsar menú'" :title="collapsed?'Ampliar':'Colapsar'"><Icon name="arrow" :size="16"/></button>
    </div>
    <nav class="adm__nav">
      <div v-for="g in groups" :key="g.key" class="adm__group" :class="{open:openGroup===g.key, 'has-active':activeGroup===g.key}">
        <button class="adm__grp-btn" @click="toggleGroup(g.key)" :title="g.label">
          <Icon :name="g.icon" :size="17"/><span class="adm__grp-label">{{ g.label }}</span><Icon name="arrow" :size="14" class="adm__chev"/></button>
        <div class="adm__group-items">
          <button v-for="it in g.items" :key="it.to" class="adm__link" :class="{active:active(it.to)}" @click="go(it.to)" :title="it.label"><Icon :name="it.icon" :size="17"/><span class="adm__link-label">{{ it.label }}</span></button>
        </div>
      </div>
    </nav>
    <div class="adm__user"><span class="adm__user-name">{{ store.admin?store.admin.name:'' }}</span><button class="btn btn-ghost btn-sm adm__logout" @click="salir" title="Salir"><Icon name="logout" :size="14"/><span class="adm__link-label"> Salir</span></button></div>
  </aside><main class="adm__main"><router-view/></main><Toasts/><AlexiaWidget/></div>`,
  data(){ return { store, openGroup: 'crm', collapsed: false, groups: [
    { key:'crm', label:'CRM', icon:'analitica', items:[{to:'/admin',icon:'analitica',label:'Tablero'},{to:'/admin/leads',icon:'users',label:'Leads'},{to:'/admin/funnel',icon:'analitica',label:'Funnel y pauta'},{to:'/admin/pipeline',icon:'growth',label:'Pipeline'},{to:'/admin/growthboard',icon:'target',label:'GrowthBoard Clientes'},{to:'/admin/campanas',icon:'growth',label:'Campañas'},{to:'/admin/segmentos',icon:'target',label:'Segmentos'},{to:'/admin/reservas',icon:'calendar',label:'Reservas'},{to:'/admin/disponibilidad',icon:'clock',label:'Disponibilidad'}] },
    { key:'cms', label:'Contenido', icon:'doc', items:[{to:'/admin/soluciones',icon:'bulb',label:'Soluciones'},{to:'/admin/productos',icon:'cube',label:'Productos'},{to:'/admin/casos',icon:'growth',label:'Casos'},{to:'/admin/faqs',icon:'alert',label:'FAQs'},{to:'/admin/diagnosticos',icon:'target',label:'Diagnósticos'},{to:'/admin/recursos',icon:'doc',label:'Recursos'},{to:'/admin/growthboard-zonas',icon:'growth',label:'GrowthBoard'},{to:'/admin/studio',icon:'sparkle',label:'Content Studio'},{to:'/admin/industrias',icon:'cube',label:'Industrias'},{to:'/admin/segmentos-config',icon:'target',label:'Segmentos y categorías'}] },
    { key:'plat', label:'Plataforma', icon:'plug', items:[{to:'/admin/secuencias',icon:'gear',label:'Automatizaciones'},{to:'/admin/plantillas-campana',icon:'send',label:'Plantillas campaña'},{to:'/admin/conectores',icon:'plug',label:'Conectores'},{to:'/admin/plantillas',icon:'mail',label:'Plantillas email'},{to:'/admin/usuarios',icon:'users',label:'Usuarios'}] },
  ] }; },
  computed: {
    activeGroup(){ for(const g of this.groups){ if(g.items.some(it=>this.active(it.to))) return g.key; } return null; },
  },
  watch: { activeGroup(k){ if(k) this.openGroup=k; } },
  mounted(){ if(this.activeGroup) this.openGroup=this.activeGroup; try{ this.collapsed = localStorage.getItem('adm_collapsed')==='1'; }catch(e){} },
  methods:{
    go(to){ this.$router.push(to); },
    active(to){ return this.$route.path===to || (to!=='/admin'&&this.$route.path.startsWith(to)); },
    toggleGroup(k){ if(this.collapsed){ this.collapsed=false; this.persist(); } this.openGroup = this.openGroup===k ? null : k; },
    toggleCollapse(){ this.collapsed=!this.collapsed; this.persist(); },
    persist(){ try{ localStorage.setItem('adm_collapsed', this.collapsed?'1':'0'); }catch(e){} },
    salir(){ logout(); },
  },
};

const routes = [
  { path: '/admin/login', component: Login },
  { path: '/admin', component: Shell, children: [
    { path: '', component: Dashboard },
    { path: 'leads', component: Leads },
    { path: 'leads/:id', component: LeadDetail },
    { path: 'campanas', component: Campanas },
    { path: 'segmentos', component: Segmentos },
    { path: 'pipeline', component: Pipeline },
    { path: 'secuencias', component: Secuencias },
    { path: 'plantillas-campana', component: Contenido, props: { modulo: 'campaign_templates' } },
    { path: 'reservas', component: Reservas },
    { path: 'disponibilidad', component: Contenido, props: { modulo: 'availability_rules' } },
    { path: 'soluciones', component: Contenido, props: { modulo: 'solutions' } },
    { path: 'productos', component: Contenido, props: { modulo: 'products' } },
    { path: 'casos', component: Contenido, props: { modulo: 'case_studies' } },
    { path: 'faqs', component: Contenido, props: { modulo: 'faqs' } },
    { path: 'diagnosticos', component: Diagnosticos },
    { path: 'industrias', component: Contenido, props: { modulo: 'industries' } },
    { path: 'recursos', component: Recursos },
    { path: 'segmentos-config', component: Taxonomias },
    { path: 'growthboard-zonas', component: Contenido, props: { modulo: 'gb_zones' } },
    { path: 'growthboard', component: GBClientes },
    { path: 'studio', component: Studio },
    { path: 'funnel', component: Funnel },
    { path: 'usuarios', component: Usuarios },
    { path: 'conectores', component: Conectores },
    { path: 'plantillas', component: Plantillas },
  ]},
];

const router = createRouter({ history: createWebHistory(), routes });
// Guard: exige sesión Bearer salvo /admin/login
router.beforeEach((to) => {
  if (to.path !== '/admin/login' && !store.token) return '/admin/login';
  if (to.path === '/admin/login' && store.token) return '/admin';
  return true;
});

(async () => {
  await loadDict('es');
  const app = createApp({ template: '<router-view/>' });
  app.directive('reveal', reveal);
  app.use(router);
  app.mount('#app');
})();
