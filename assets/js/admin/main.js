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
import { Diagnosticos } from './diagnosticos.js';
import { Recursos } from './recursos.js';
import { AlexiaWidget } from './alexia.js';

const Shell = {
  components: { Icon, Toasts, AlexiaWidget },
  template: `<div class="adm"><aside class="adm__side">
    <div class="adm__brand"><img src="/assets/img/brand/logo.png" alt="ExperientIA" style="height:28px;width:auto" draggable="false"></div>
    <nav class="adm__nav">
      <p class="adm__grp">CRM</p>
      <button v-for="it in crm" :key="it.to" class="adm__link" :class="{active:active(it.to)}" @click="go(it.to)"><Icon :name="it.icon" :size="17"/> {{ it.label }}</button>
      <p class="adm__grp">Contenido</p>
      <button v-for="it in cms" :key="it.to" class="adm__link" :class="{active:active(it.to)}" @click="go(it.to)"><Icon :name="it.icon" :size="17"/> {{ it.label }}</button>
      <p class="adm__grp">Plataforma</p>
      <button v-for="it in plat" :key="it.to" class="adm__link" :class="{active:active(it.to)}" @click="go(it.to)"><Icon :name="it.icon" :size="17"/> {{ it.label }}</button>
    </nav>
    <div class="adm__user"><span>{{ store.admin?store.admin.name:'' }}</span><button class="btn btn-ghost btn-sm" @click="salir"><Icon name="logout" :size="14"/> Salir</button></div>
  </aside><main class="adm__main"><router-view/></main><Toasts/><AlexiaWidget/></div>`,
  data(){ return { store,
    crm:[{to:'/admin',icon:'analitica',label:'Tablero'},{to:'/admin/leads',icon:'users',label:'Leads'},{to:'/admin/campanas',icon:'growth',label:'Campañas'},{to:'/admin/segmentos',icon:'target',label:'Segmentos'},{to:'/admin/reservas',icon:'calendar',label:'Reservas'},{to:'/admin/disponibilidad',icon:'clock',label:'Disponibilidad'}],
    cms:[{to:'/admin/soluciones',icon:'bulb',label:'Soluciones'},{to:'/admin/productos',icon:'cube',label:'Productos'},{to:'/admin/casos',icon:'growth',label:'Casos'},{to:'/admin/faqs',icon:'alert',label:'FAQs'},{to:'/admin/diagnosticos',icon:'target',label:'Diagnósticos'},{to:'/admin/recursos',icon:'doc',label:'Recursos'}],
    plat:[{to:'/admin/conectores',icon:'plug',label:'Conectores'},{to:'/admin/plantillas',icon:'mail',label:'Plantillas email'}],
  }; },
  methods:{ go(to){ this.$router.push(to); }, active(to){ return this.$route.path===to || (to!=='/admin'&&this.$route.path.startsWith(to)); }, salir(){ logout(); } },
};

const routes = [
  { path: '/admin/login', component: Login },
  { path: '/admin', component: Shell, children: [
    { path: '', component: Dashboard },
    { path: 'leads', component: Leads },
    { path: 'leads/:id', component: LeadDetail },
    { path: 'campanas', component: Campanas },
    { path: 'segmentos', component: Segmentos },
    { path: 'reservas', component: Reservas },
    { path: 'disponibilidad', component: Contenido, props: { modulo: 'availability_rules' } },
    { path: 'soluciones', component: Contenido, props: { modulo: 'solutions' } },
    { path: 'productos', component: Contenido, props: { modulo: 'products' } },
    { path: 'casos', component: Contenido, props: { modulo: 'case_studies' } },
    { path: 'faqs', component: Contenido, props: { modulo: 'faqs' } },
    { path: 'diagnosticos', component: Diagnosticos },
    { path: 'recursos', component: Recursos },
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
