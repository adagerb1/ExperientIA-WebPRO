@props(['eyebrow', 'titulo', 'sub' => null])
<section class="page-hero">
  <div class="bg-atmos" aria-hidden="true">
    <div class="bg-grid"></div>
    <div class="halo halo-cyan" style="width:520px;height:520px;top:-260px;right:-140px;"></div>
    <div class="halo halo-violet" style="width:420px;height:420px;top:40px;left:-200px;opacity:.35;"></div>
  </div>
  <div class="container page-hero__inner">
    <p class="eyebrow reveal">{{ $eyebrow }}</p>
    <h1 class="display reveal" style="--reveal-delay:.08s">{{ $titulo }}</h1>
    @if ($sub)
      <p class="lead reveal" style="--reveal-delay:.16s">{{ $sub }}</p>
    @endif
    {{ $slot }}
  </div>
</section>
