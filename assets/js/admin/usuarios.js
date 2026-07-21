// Portal admin · Usuarios del equipo: rol, estado y notificaciones por Telegram.
import { api, toast, store } from '../lib/core.js';
import { Icon } from '../lib/ui.js';
import { SmartTable } from './table.js';

const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));
const ROLES = { owner:'Propietario', admin:'Administrador', staff:'Staff' };
const blank = () => ({ name:'', email:'', password:'', role:'staff', telegram_user_id:'', notify_telegram:0, active:1 });

export const Usuarios = {
  components: { Icon, SmartTable },
  data(){ return { items:[], q:'', form:null, guardando:false,
    cols:[
      { key:'name', label:'Usuario', render:(r)=>'<b style="color:var(--neutral-light)">'+esc(r.name)+'</b><br><span class="small">'+esc(r.email)+'</span>' },
      { key:'role', label:'Rol', render:(r)=>ROLES[r.role]||r.role },
      { key:'notify_telegram', label:'Telegram', raw:(r)=>Number(r.notify_telegram),
        render:(r)=> r.telegram_user_id ? (Number(r.notify_telegram)?'<span class="badge cliente">🔔 Notifica</span>':'<span class="badge">Vinculado</span>') : '<span class="small">—</span>' },
      { key:'active', label:'Estado', raw:(r)=>Number(r.active), render:(r)=> Number(r.active)?'<span class="badge cliente">Activo</span>':'<span class="badge descartado">Inactivo</span>' },
    ] }; },
  computed:{ roles(){ return ROLES; }, esOwner(){ return store.admin && store.admin.role==='owner'; } },
  methods:{
    async load(){ const r=await api.get('/admin/usuarios'); if(r.ok) this.items=r.data; else toast(r.error||'Error','err'); },
    nuevo(){ this.form=blank(); },
    editar(u){ this.form={ ...blank(), ...u, password:'', notify_telegram:Number(u.notify_telegram), active:Number(u.active) }; },
    async guardar(){ const f=this.form;
      if(!f.name.trim()||!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.email)){ toast('Nombre y correo válido son obligatorios.','err'); return; }
      if(!f.id && f.password.length<8){ toast('La contraseña debe tener al menos 8 caracteres.','err'); return; }
      this.guardando=true;
      const r=f.id ? await api.put('/admin/usuarios/'+f.id, f) : await api.post('/admin/usuarios', f);
      this.guardando=false;
      if(r.ok){ toast('Usuario guardado.'); this.form=null; this.load(); } else toast(r.error||'Error','err'); },
    async eliminar(){ if(!this.form.id) { this.form=null; return; }
      if(!confirm('¿Eliminar a '+this.form.name+'?')) return;
      const r=await api.del('/admin/usuarios/'+this.form.id);
      if(r.ok){ toast('Eliminado.'); this.form=null; this.load(); } else toast(r.error||'Error','err'); },
  },
  mounted(){ this.load(); },
  template:`<div>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
      <div><h1>Usuarios</h1><p class="adm__sub" style="margin:0">Equipo del portal · roles y notificaciones por Telegram</p></div>
      <button v-if="esOwner" class="btn btn-primary btn-sm" @click="nuevo"><Icon name="users" :size="14"/> Nuevo usuario</button></div>

    <p v-if="!esOwner" class="small" style="margin-top:1rem;color:#ffb84d">Solo el propietario puede crear o editar usuarios. Puedes ver el equipo abajo.</p>

    <div class="toolbar" style="margin-top:1.2rem"><input class="inp" v-model="q" placeholder="Buscar usuario…" style="max-width:260px"></div>
    <SmartTable :columns="cols" :rows="items" :search="q" :searchKeys="['name','email']" @rowClick="esOwner?editar:()=>{}"/>

    <div class="modal-bg" v-if="form" @click.self="form=null"><div class="glass modal">
      <div style="display:flex;justify-content:space-between;align-items:center"><h2>{{ form.id?'Editar usuario':'Nuevo usuario' }}</h2>
        <button class="btn btn-ghost btn-sm" @click="form=null">✕</button></div>
      <div class="form-grid two" style="margin-top:1rem">
        <div class="field"><label>Nombre</label><input class="inp" v-model="form.name"></div>
        <div class="field"><label>Correo</label><input class="inp" type="email" v-model="form.email"></div></div>
      <div class="form-grid two">
        <div class="field"><label>Rol</label><select class="inp" v-model="form.role"><option v-for="(l,k) in roles" :key="k" :value="k">{{ l }}</option></select></div>
        <div class="field"><label>{{ form.id?'Nueva contraseña (opcional)':'Contraseña' }}</label><input class="inp" type="password" v-model="form.password" autocomplete="new-password"></div></div>
      <div class="sec-divider"><span>Notificaciones por Telegram</span></div>
      <p class="small" style="margin-bottom:.8rem">El usuario escribe al bot interno de AlexIA y este le responde su ID de Telegram. Pégalo aquí para vincular su cuenta.</p>
      <div class="form-grid two">
        <div class="field"><label>ID de Telegram</label><input class="inp" v-model="form.telegram_user_id" placeholder="Ej: 123456789"></div>
        <div class="field"><label>¿Recibe notificaciones del negocio?</label>
          <label class="switch-row"><label class="switch"><input type="checkbox" v-model="form.notify_telegram" :true-value="1" :false-value="0"><span></span></label> Leads, diagnósticos y actualizaciones</label></div></div>
      <div class="field"><label class="switch-row"><label class="switch"><input type="checkbox" v-model="form.active" :true-value="1" :false-value="0"><span></span></label> Cuenta activa</label></div>
      <div style="display:flex;gap:.6rem;justify-content:flex-end;margin-top:1rem">
        <button v-if="form.id" class="btn btn-danger btn-sm" style="margin-right:auto" @click="eliminar">Eliminar</button>
        <button class="btn btn-ghost btn-sm" @click="form=null">Cancelar</button>
        <button class="btn btn-primary btn-sm" @click="guardar" :disabled="guardando">{{ guardando?'…':'Guardar' }}</button></div>
    </div></div>
  </div>`,
};
