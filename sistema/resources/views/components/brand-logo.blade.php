@props(['variant' => 'gradient', 'descriptor' => true, 'size' => 40])
<span {{ $attributes->merge(['class' => 'brand-logo']) }} style="--logo-h:{{ $size }}px" translate="no">
  <x-brand-symbol :variant="$variant === 'gradient' ? 'gradient' : $variant" :size="$size" />
  <span class="brand-logo__text">
    <span class="brand-logo__word">Experient<i class="brand-logo__ia">IA</i></span>
    @if ($descriptor)
      <span class="brand-logo__descriptor">Automatización · Growth · IA</span>
    @endif
  </span>
</span>
