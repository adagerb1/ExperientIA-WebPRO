// Formulario público de testimonio: el cliente llega con un enlace de código
// corto, cuenta su experiencia, marca las soluciones/productos que usó y puede
// subir su foto y el logo de su empresa (opcionales). Nada se publica sin
// aprobación del equipo. Trilingüe y con guía en cada campo.
import { t, tr, pageUrl, api, store, setMeta } from '../lib/core.js';
import { Icon } from '../lib/ui.js';

const tx = (k, fb) => { const v = t(k); return (v && v !== k) ? v : fb; };

export const TestimonioForm = {
  components: { Icon },
  template: `<div>
    <section class="page-hero"><div class="bg-atmos"><div class="halo halo-cyan" style="width:520px;height:520px;top:-260px;right:-140px"></div></div>
      <div class="container page-hero__in" style="max-width:52rem">
        <p class="eyebrow" v-reveal>{{ tx('testi.eyebrow','Tu experiencia vale oro') }}</p>
        <h1 class="h1" v-reveal :style="{'--d':'.08s'}">{{ saludo }}</h1>
        <p class="lead" v-reveal :style="{'--d':'.16s'}">{{ tx('testi.sub','Cuéntanos en tus palabras qué cambió en tu negocio. Son 2 minutos y ayuda a otras empresas a dar el paso. Nada se publica sin tu texto tal como lo escribas.') }}</p>
      </div></section>

    <section class="section" style="padding-top:0"><div class="container" style="max-width:52rem">
      <div v-if="estado==='cargando'" class="glass card" style="text-align:center;padding:2.5rem"><p>{{ tx('testi.cargando','Verificando tu enlace…') }}</p></div>

      <div v-else-if="estado==='invalido'" class="glass card" style="text-align:center;padding:2.5rem;display:grid;gap:1rem;justify-items:center">
        <h2 class="h3">{{ tx('testi.invalido_t','Este enlace no es válido o ya venció') }}</h2>
        <p>{{ tx('testi.invalido_s','Pide un enlace nuevo a tu contacto en ExperientIA y con gusto guardamos tu testimonio.') }}</p>
        <router-link :to="pageUrl('contacto')" class="btn btn-primary">{{ t('nav.contacto') }}</router-link></div>

      <div v-else-if="estado==='gracias'" class="glass glass-lit card" style="text-align:center;padding:3rem;display:grid;gap:1rem;justify-items:center" v-reveal>
        <span class="icon-chip" style="width:3.2rem;height:3.2rem"><Icon name="check" :size="26"/></span>
        <h2 class="h2">{{ tx('testi.gracias_t','¡Gracias de corazón!') }}</h2>
        <p class="lead" style="max-width:34rem">{{ tx('testi.gracias_s','Recibimos tu testimonio. Nuestro equipo lo revisará y pronto estará ayudando a otras empresas a decidirse.') }}</p></div>

      <form v-else class="glass card" style="display:grid;gap:1.2rem;padding:clamp(1.5rem,4vw,2.4rem)" @submit.prevent="enviar" v-reveal>
        <div class="field"><label>{{ tx('testi.f_quote','Tu testimonio') }} *</label>
          <textarea v-model="f.quote" rows="5" class="field-el" :placeholder="tx('testi.f_quote_ph','¿Qué problema tenías, qué hicimos juntos y qué cambió? Escríbelo como se lo contarías a un colega.')"></textarea>
          <p class="hint">{{ tx('testi.f_quote_h','Entre 2 y 6 frases funciona perfecto. Se publicará tal cual lo escribas.') }}</p></div>

        <div class="form-grid two" style="display:grid;gap:1rem;grid-template-columns:1fr 1fr">
          <div class="field"><label>{{ tx('testi.f_nombre','Tu nombre') }} *</label><input v-model="f.author" class="field-el" :placeholder="tx('testi.f_nombre_ph','Nombre y apellido')"></div>
          <div class="field"><label>{{ tx('testi.f_cargo','Tu cargo') }}</label><input v-model="f.cargo" class="field-el" :placeholder="tx('testi.f_cargo_ph','Ej: Gerente General')"></div></div>
        <div class="field"><label>{{ tx('testi.f_empresa','Tu empresa') }}</label><input v-model="f.empresa" class="field-el" :placeholder="tx('testi.f_empresa_ph','Nombre de la empresa')"></div>

        <div class="field"><label>{{ tx('testi.f_items','¿Con qué trabajamos juntos?') }}</label>
          <p class="hint" style="margin-top:0">{{ tx('testi.f_items_h','Marca todo lo que aplique; así tu testimonio aparece junto al servicio correcto.') }}</p>
          <div style="display:flex;flex-wrap:wrap;gap:.5rem">
            <button v-for="c in catalogo" :key="c.token" type="button" class="chip-cat" :class="{on:f.items.includes(c.token)}" @click="toggleItem(c.token)">{{ trNombre(c.nombre) }}</button></div></div>

        <div class="form-grid two" style="display:grid;gap:1rem;grid-template-columns:1fr 1fr">
          <div class="field"><label>{{ tx('testi.f_foto','Tu foto (opcional)') }}</label>
            <div style="display:flex;gap:.7rem;align-items:center">
              <label class="btn btn-ghost btn-sm" style="cursor:pointer">{{ fotoNombre||tx('testi.f_subir','Subir imagen') }}<input type="file" accept="image/jpeg,image/png,image/webp" hidden @change="e=>archivo(e,'photo')"></label>
              <img v-if="prev.photo" :src="prev.photo" alt="" style="width:44px;height:44px;border-radius:50%;object-fit:cover"></div>
            <p class="hint">{{ tx('testi.f_foto_h','JPG, PNG o WebP · máximo 5 MB. Da rostro humano a tu testimonio.') }}</p></div>
          <div class="field"><label>{{ tx('testi.f_logo','Logo de tu empresa (opcional)') }}</label>
            <div style="display:flex;gap:.7rem;align-items:center">
              <label class="btn btn-ghost btn-sm" style="cursor:pointer">{{ logoNombre||tx('testi.f_subir','Subir imagen') }}<input type="file" accept="image/jpeg,image/png,image/webp" hidden @change="e=>archivo(e,'logo')"></label>
              <img v-if="prev.logo" :src="prev.logo" alt="" style="height:36px;border-radius:6px;object-fit:contain;background:#fff;padding:2px"></div>
            <p class="hint">{{ tx('testi.f_logo_h','Se muestra junto a tu testimonio como respaldo de marca.') }}</p></div></div>

        <p v-if="error" class="small" style="color:#ff7d9d">{{ error }}</p>
        <button class="btn btn-grad" type="submit" :disabled="busy" style="justify-self:start">{{ busy?tx('testi.enviando','Enviando…'):tx('testi.enviar','Enviar mi testimonio') }}</button>
        <p class="small">{{ tx('testi.privacidad','Solo publicaremos lo que escribas aquí, junto a tu nombre, cargo, empresa e imágenes que decidas compartir.') }}</p>
      </form></div></section></div>`,
  data() { return { estado: 'cargando', saludoNombre: '', catalogo: [], error: '', busy: false,
    f: { quote: '', author: '', cargo: '', empresa: '', items: [] },
    files: { photo: null, logo: null }, prev: { photo: '', logo: '' }, fotoNombre: '', logoNombre: '' }; },
  computed: { t: () => t, pageUrl: () => pageUrl,
    saludo() { const n = this.saludoNombre ? this.saludoNombre.split(' ')[0] : ''; return n ? (tx('testi.hola', 'Hola') + ', ' + n) : tx('testi.titulo', 'Déjanos tu testimonio'); } },
  methods: { tx,
    trNombre(n) { return (n && typeof n === 'object') ? (n[store.locale] || n.es || '') : String(n || ''); },
    toggleItem(tk) { const i = this.f.items.indexOf(tk); if (i >= 0) this.f.items.splice(i, 1); else this.f.items.push(tk); },
    archivo(e, k) { const file = e.target.files[0]; if (!file) return;
      if (file.size > 5 * 1024 * 1024) { this.error = tx('testi.err_peso', 'La imagen supera 5 MB.'); return; }
      this.files[k] = file; this.prev[k] = URL.createObjectURL(file);
      if (k === 'photo') this.fotoNombre = file.name; else this.logoNombre = file.name; },
    async enviar() {
      this.error = '';
      if ((this.f.quote || '').trim().length < 20) { this.error = tx('testi.err_quote', 'Cuéntanos tu experiencia en al menos una frase.'); return; }
      if (!(this.f.author || '').trim()) { this.error = tx('testi.err_nombre', 'Tu nombre es necesario.'); return; }
      this.busy = true;
      const fd = new FormData();
      fd.append('quote', this.f.quote); fd.append('author', this.f.author);
      fd.append('cargo', this.f.cargo); fd.append('empresa', this.f.empresa);
      fd.append('locale', store.locale); fd.append('items', JSON.stringify(this.f.items));
      if (this.files.photo) fd.append('photo', this.files.photo);
      if (this.files.logo) fd.append('logo', this.files.logo);
      const r = await fetch('/api/testimonio/' + this.$route.params.code, { method: 'POST', body: fd }).then(x => x.json()).catch(() => null);
      this.busy = false;
      if (r && r.ok) { this.estado = 'gracias'; window.scrollTo({ top: 0 }); }
      else this.error = (r && r.error) || tx('testi.err_envio', 'No pudimos guardar tu testimonio. Intenta de nuevo.');
    },
  },
  async mounted() {
    setMeta(tx('testi.titulo', 'Déjanos tu testimonio') + ' · ExperientIA', tx('testi.sub', ''));
    const r = await api.get('/testimonio/' + this.$route.params.code);
    if (!r.ok) { this.estado = 'invalido'; return; }
    this.saludoNombre = r.data.client_name || '';
    this.catalogo = r.data.catalogo || [];
    this.estado = r.data.ya_enviado ? 'gracias' : 'form';
  },
};
