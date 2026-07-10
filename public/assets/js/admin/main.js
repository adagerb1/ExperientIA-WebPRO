// Portal admin · Bootstrap (Vue 3 SPA, importmap). Auth Bearer, RBAC básico.
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { store, api, logout, loadDict } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { reveal } from '../lib/ui.js';
import { Toasts } from '../lib/layout.js';
import { Login, Dashboard, Leads, LeadDetail } from './views1.js';
import { Reservas, Contenido, Conectores, Plantillas, AlexIAInterno } from './views2.js';

const Shell = {
  components: { Icon, Toasts },
  template: `<div class="adm"><aside class="adm__side">
    <div class="adm__brand"><svg width="30" height="24" viewBox="0 0 128 100" fill="none"><defs><linearGradient id="sb" x1="18" y1="0" x2="50" y2="100" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#00e5ff"/><stop offset=".52" stop-color="#7b61ff"/><stop offset="1" stop-color="#00e5ff"/></linearGradient></defs><path fill="url(#sb)" d="M 6 2 L 38 2 L 64 41.5 L 64 58.5 L 38 98 L 6 98 L 6 55.5 L 12.5 52 L 12.5 48 L 6 44.5 Z"/><g stroke="#00e5ff" fill="none" stroke-width="10.5"><polyline points="88,26.5 72,50 88,73.5"/><polyline points="61.5,32 78.5,7.25 96,7.25"/><line x1="102.5" y1="6" x2="124" y2="31.5" stroke-width="11"/><polyline points="61.5,68 78.5,92.75 96,92.75"/><line x1="102.5" y1="94" x2="124" y2="68.5" stroke-width="11"/></g></svg> Experient<i>IA</i></div>
    <nav class="adm__nav">
      <p class="adm__grp">CRM</p>
      <button v-for="it in crm" :key="it.to" class="adm__link" :class="{active:active(it.to)}" @click="go(it.to)"><Icon :name="it.icon" :size="17"/> {{ it.label }}</button>
      <p class="adm__grp">Contenido</p>
      <button v-for="it in cms" :key="it.to" class="adm__link" :class="{active:active(it.to)}" @click="go(it.to)"><Icon :name="it.icon" :size="17"/> {{ it.label }}</button>
      <p class="adm__grp">Plataforma</p>
      <button v-for="it in plat" :key="it.to" class="adm__link" :class="{active:active(it.to)}" @click="go(it.to)"><Icon :name="it.icon" :size="17"/> {{ it.label }}</button>
    </nav>
    <div class="adm__user"><span>{{ store.admin?store.admin.name:'' }}</span><button class="btn btn-ghost btn-sm" @click="salir"><Icon name="logout" :size="14"/> Salir</button></div>
  </aside><main class="adm__main"><router-view/></main><Toasts/></div>`,
  data(){ return { store,
    crm:[{to:'/admin',icon:'analitica',label:'Resumen'},{to:'/admin/leads',icon:'users',label:'Leads'},{to:'/admin/reservas',icon:'calendar',label:'Reservas'},{to:'/admin/disponibilidad',icon:'clock',label:'Disponibilidad'}],
    cms:[{to:'/admin/soluciones',icon:'bulb',label:'Soluciones'},{to:'/admin/productos',icon:'cube',label:'Productos'},{to:'/admin/casos',icon:'growth',label:'Casos'},{to:'/admin/faqs',icon:'alert',label:'FAQs'},{to:'/admin/recursos',icon:'doc',label:'Recursos'}],
    plat:[{to:'/admin/conectores',icon:'plug',label:'Conectores'},{to:'/admin/plantillas',icon:'mail',label:'Plantillas email'},{to:'/admin/alexia',icon:'sparkle',label:'AlexIA'}],
  }; },
  methods:{ go(to){ this.$router.push(to); }, active(to){ return this.$route.path===to || (to!=='/admin'&&this.$route.path.startsWith(to)); }, salir(){ logout(); } },
};

const routes = [
  { path: '/admin/login', component: Login },
  { path: '/admin', component: Shell, children: [
    { path: '', component: Dashboard },
    { path: 'leads', component: Leads },
    { path: 'leads/:id', component: LeadDetail },
    { path: 'reservas', component: Reservas },
    { path: 'disponibilidad', component: Contenido, props: { modulo: 'availability_rules' } },
    { path: 'soluciones', component: Contenido, props: { modulo: 'solutions' } },
    { path: 'productos', component: Contenido, props: { modulo: 'products' } },
    { path: 'casos', component: Contenido, props: { modulo: 'case_studies' } },
    { path: 'faqs', component: Contenido, props: { modulo: 'faqs' } },
    { path: 'recursos', component: Contenido, props: { modulo: 'resources' } },
    { path: 'conectores', component: Conectores },
    { path: 'plantillas', component: Plantillas },
    { path: 'alexia', component: AlexIAInterno },
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
