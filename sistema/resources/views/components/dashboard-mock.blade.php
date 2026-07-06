@php
$L = [
  'es' => ['titulo' => 'Tablero de Crecimiento', 'vivo' => 'En vivo', 'crecimiento' => 'Crecimiento sostenible', 'vsTrim' => 'vs. trimestre anterior', 'procesos' => 'Procesos optimizados', 'automatizacion' => 'Automatización', 'roi' => 'Retorno del programa', 'flujo' => 'Flujo de automatización', 'ingresos' => 'Ingresos por canal'],
  'en' => ['titulo' => 'Growth Board', 'vivo' => 'Live', 'crecimiento' => 'Sustainable growth', 'vsTrim' => 'vs. previous quarter', 'procesos' => 'Processes optimized', 'automatizacion' => 'Automation', 'roi' => 'Program return', 'flujo' => 'Automation flow', 'ingresos' => 'Revenue by channel'],
  'pt' => ['titulo' => 'Painel de Crescimento', 'vivo' => 'Ao vivo', 'crecimiento' => 'Crescimento sustentável', 'vsTrim' => 'vs. trimestre anterior', 'procesos' => 'Processos otimizados', 'automatizacion' => 'Automação', 'roi' => 'Retorno do programa', 'flujo' => 'Fluxo de automação', 'ingresos' => 'Receita por canal'],
][app()->getLocale()] ?? [];
$bars = [34, 52, 40, 66, 58, 80, 72, 92];
$gid = 'dg-' . substr(md5(uniqid()), 0, 5);
@endphp
<div {{ $attributes->merge(['class' => 'dash']) }} role="img" aria-label="{{ $L['titulo'] }}">
  <div class="dash__chrome">
    <span class="dash__dot"></span><span class="dash__dot"></span><span class="dash__dot"></span>
    <span class="dash__title">ExperientIA · {{ $L['titulo'] }}</span>
    <span class="dash__live"><i class="anim-pulse"></i>{{ $L['vivo'] }}</span>
  </div>
  <div class="dash__grid">
    <div class="dash__card dash__card--chart">
      <p class="dash__label">{{ $L['crecimiento'] }}</p>
      <p class="dash__big grad-text">+42%</p>
      <p class="dash__hint">{{ $L['vsTrim'] }}</p>
      <svg viewBox="0 0 220 74" preserveAspectRatio="none" aria-hidden="true">
        <defs>
          <linearGradient id="{{ $gid }}-l" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#18d6f1"/><stop offset="1" stop-color="#7a63ff"/></linearGradient>
          <linearGradient id="{{ $gid }}-a" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="rgba(24,214,241,0.25)"/><stop offset="1" stop-color="rgba(24,214,241,0)"/></linearGradient>
        </defs>
        <path d="M0 62 L28 54 L56 58 L84 42 L112 46 L140 28 L168 32 L204 10 L220 12 L220 74 L0 74 Z" fill="url(#{{ $gid }}-a)"/>
        <path class="dash__line" d="M0 62 L28 54 L56 58 L84 42 L112 46 L140 28 L168 32 L204 10 L220 12" fill="none" stroke="url(#{{ $gid }}-l)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="204" cy="10" r="3.4" fill="#18d6f1" class="anim-pulse"/>
      </svg>
    </div>
    <div class="dash__card dash__card--ring">
      <p class="dash__label">{{ $L['automatizacion'] }}</p>
      <div class="dash__ring">
        <svg viewBox="0 0 96 96" aria-hidden="true">
          <circle cx="48" cy="48" r="40" fill="none" stroke="rgba(217,226,240,0.12)" stroke-width="7"/>
          <circle cx="48" cy="48" r="40" fill="none" stroke="url(#{{ $gid }}-r)" stroke-width="7" stroke-linecap="round" stroke-dasharray="196 251" transform="rotate(-90 48 48)"/>
          <defs><linearGradient id="{{ $gid }}-r" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#18d6f1"/><stop offset="1" stop-color="#7a63ff"/></linearGradient></defs>
        </svg>
        <span class="dash__ring-val">78%</span>
      </div>
      <p class="dash__hint">{{ $L['procesos'] }}</p>
    </div>
    <div class="dash__card dash__card--bars">
      <p class="dash__label">{{ $L['ingresos'] }}</p>
      <div class="dash__bars" aria-hidden="true">
        @foreach ($bars as $i => $h)
          <span style="--h:{{ $h }}%; --d:{{ $i * 0.08 }}s" @class(['is-hot' => $i >= 6])></span>
        @endforeach
      </div>
    </div>
    <div class="dash__card dash__card--roi">
      <p class="dash__label">{{ $L['roi'] }}</p>
      <p class="dash__big">3.2x</p>
      <div class="dash__spark" aria-hidden="true"><span></span><span></span><span></span></div>
    </div>
    <div class="dash__card dash__card--flow">
      <p class="dash__label">{{ $L['flujo'] }}</p>
      <div class="dash__flow" aria-hidden="true">
        <span class="dash__node"><x-ei-icon name="play" :size="16" /></span>
        <i class="dash__wire"></i>
        <span class="dash__node"><x-ei-icon name="gear" :size="16" /></span>
        <i class="dash__wire"></i>
        <span class="dash__node dash__node--ok"><x-ei-icon name="check" :size="16" /></span>
      </div>
    </div>
  </div>
</div>
