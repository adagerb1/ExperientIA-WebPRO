@props(['variant' => 'gradient', 'size' => 40])
@php
$uid = 'xg-' . substr(md5(uniqid()), 0, 6);
$solid = match ($variant) { 'white' => '#ffffff', 'black' => '#0a1224', 'current' => 'currentColor', default => "url(#{$uid})" };
$stroke = match ($variant) { 'white' => '#ffffff', 'black' => '#0a1224', 'current' => 'currentColor', default => '#00e5ff' };
@endphp
<svg {{ $attributes }} width="{{ $size * 1.28 }}" height="{{ $size }}" viewBox="0 0 128 100" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
  @if ($variant === 'gradient')
    <defs>
      <linearGradient id="{{ $uid }}" x1="18" y1="0" x2="50" y2="100" gradientUnits="userSpaceOnUse">
        <stop offset="0" stop-color="#00e5ff" />
        <stop offset="0.52" stop-color="#7b61ff" />
        <stop offset="1" stop-color="#00e5ff" />
      </linearGradient>
    </defs>
  @endif
  <path fill="{{ $solid }}" d="M 6 2 L 38 2 L 64 41.5 L 64 58.5 L 38 98 L 6 98 L 6 55.5 L 12.5 52 L 12.5 48 L 6 44.5 Z" />
  <g stroke="{{ $stroke }}" fill="none" stroke-width="10.5">
    <polyline points="88,26.5 72,50 88,73.5" />
    <polyline points="61.5,32 78.5,7.25 96,7.25" />
    <line x1="102.5" y1="6" x2="124" y2="31.5" stroke-width="11" />
    <polyline points="61.5,68 78.5,92.75 96,92.75" />
    <line x1="102.5" y1="94" x2="124" y2="68.5" stroke-width="11" />
  </g>
</svg>
