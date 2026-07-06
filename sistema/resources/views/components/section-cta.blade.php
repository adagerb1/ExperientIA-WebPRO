@props(['titulo', 'sub' => null, 'primaryHref', 'primaryLabel', 'secondaryHref' => null, 'secondaryLabel' => null])
<section class="section cta-final">
  <div class="container">
    <div class="cta-final__panel card-lum reveal">
      <div class="bg-atmos" aria-hidden="true">
        <div class="halo halo-cyan anim-pulse" style="width:420px;height:420px;bottom:-260px;left:-120px;"></div>
        <div class="halo halo-violet anim-pulse" style="width:460px;height:460px;top:-280px;right:-140px;animation-delay:2.4s;"></div>
        <div class="bg-skyline"></div>
      </div>
      <div class="cta-final__content">
        <h2 class="h2">{{ $titulo }}</h2>
        @if ($sub)
          <p class="lead">{{ $sub }}</p>
        @endif
        <div class="cta-final__actions">
          <a href="{{ $primaryHref }}" class="btn btn-primary">{{ $primaryLabel }}</a>
          @if ($secondaryHref && $secondaryLabel)
            <a href="{{ $secondaryHref }}" class="btn btn-ghost">{{ $secondaryLabel }}</a>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>
