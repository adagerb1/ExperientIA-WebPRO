// Estándar de ayuda del portal admin (aplica a toda vista nueva o intervenida):
//  · <PageHelp> — botón "?" junto al título: qué hace la vista + pasos de uso.
//  · <HelpDot>  — botón "?" junto a un componente o campo: qué es y qué poner.
// Reutilizan los estilos .help-q / .help-modal ya existentes (conectores).
import { Icon } from '../lib/ui.js';

export const PageHelp = {
  components: { Icon },
  props: {
    titulo: { type: String, required: true },   // nombre de la vista
    que: { type: String, required: true },      // qué hace / para qué sirve
    pasos: { type: Array, default: () => [] },  // pasos de uso, en orden
  },
  data() { return { open: false }; },
  template: `<span>
    <button class="help-q help-q--page" title="¿Cómo funciona esta vista?" @click="open=true">?</button>
    <teleport to="body"><div v-if="open" class="help-modal" @click.self="open=false">
      <div class="glass help-box">
        <button class="help-close" @click="open=false">✕</button>
        <h3>{{ titulo }}</h3>
        <p class="help-que">{{ que }}</p>
        <div v-if="pasos.length" class="help-guia"><h4>Cómo se usa</h4>
          <ol><li v-for="(s,i) in pasos" :key="i">{{ s }}</li></ol></div>
      </div></div></teleport></span>`,
};

export const HelpDot = {
  props: {
    titulo: { type: String, default: 'Ayuda' },
    texto: { type: String, required: true },    // qué es este campo / componente y qué poner
  },
  data() { return { open: false }; },
  template: `<span>
    <button class="help-q" :title="titulo" @click.stop.prevent="open=true">?</button>
    <teleport to="body"><div v-if="open" class="help-modal" @click.self="open=false">
      <div class="glass help-box">
        <button class="help-close" @click="open=false">✕</button>
        <h3>{{ titulo }}</h3>
        <p class="help-que">{{ texto }}</p>
      </div></div></teleport></span>`,
};
