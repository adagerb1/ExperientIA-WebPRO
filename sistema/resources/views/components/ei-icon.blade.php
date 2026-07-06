@props(['name' => 'target', 'size' => 24])
@php
$icons = [
  'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none"/>',
  'growth' => '<path d="M3 21h18"/><path d="M4 16.5 9.5 11l3.5 3.5L19.5 8"/><path d="M15.5 8h4v4"/>',
  'gear' => '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.8v2.6M12 18.6v2.6M21.2 12h-2.6M5.4 12H2.8M18.5 5.5l-1.9 1.9M7.4 16.6l-1.9 1.9M18.5 18.5l-1.9-1.9M7.4 7.4 5.5 5.5"/>',
  'ia' => '<circle cx="6" cy="6" r="2.2"/><circle cx="17.5" cy="4.5" r="1.8"/><circle cx="12" cy="12" r="2.6"/><circle cx="5.5" cy="18" r="1.8"/><circle cx="18" cy="18.5" r="2.2"/><path d="M7.8 7.4l2.4 2.7M14.4 10.6l1.8-4.3M10.2 13.9l-3.2 2.7M14.3 13.7l2.3 3.1"/>',
  'shield' => '<path d="M12 3l7 2.8v5.3c0 4.4-2.9 7.4-7 9.1-4.1-1.7-7-4.7-7-9.1V5.8z"/><path d="m8.8 12 2.2 2.2 4.2-4.4"/>',
  'bulb' => '<path d="M12 3a6 6 0 0 0-3.4 10.9c.7.5 1.1 1.3 1.1 2.1h4.6c0-.8.4-1.6 1.1-2.1A6 6 0 0 0 12 3z"/><path d="M9.7 19.5h4.6M10.6 22h2.8"/>',
  'cube' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12 4 7.5M12 12v9"/>',
  'analitica' => '<rect x="3" y="4" width="18" height="15" rx="2"/><path d="M3 8.5h18"/><path d="M7 15.5v-2.2M10.3 15.5v-3.8M13.6 15.5v-2.8M16.9 15.5v-4.6"/>',
  'people' => '<circle cx="9" cy="8.2" r="3"/><path d="M3.8 19.5c0-2.9 2.3-5.2 5.2-5.2s5.2 2.3 5.2 5.2"/><circle cx="16.8" cy="9" r="2.4"/><path d="M16 14.6c2.4.3 4.2 2.2 4.2 4.9"/>',
  'impulso' => '<path d="M12 2.5c3 2 4.9 5.8 4.9 9.6l-2.3 2.4H9.4L7.1 12c0-3.8 1.9-7.5 4.9-9.5z"/><circle cx="12" cy="8.8" r="1.7"/><path d="M7.2 12.4 4.4 15l2.8.6M16.8 12.4l2.8 2.6-2.8.6M10.4 17l1.6 4 1.6-4"/>',
  'check' => '<path d="m4.5 12.5 5 5L19.5 6.5"/>',
  'arrow' => '<path d="M4 12h15"/><path d="m13.5 6 6 6-6 6"/>',
  'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
  'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.2l3.4 2"/>',
  'alert' => '<path d="M6.2 9.3a5.8 5.8 0 0 1 11.6 0c0 4.6 1.9 5.8 1.9 5.8H4.3s1.9-1.2 1.9-5.8z"/><path d="M10 19.2a2.1 2.1 0 0 0 4 0"/>',
  'eye' => '<path d="M2.5 12S6 5.8 12 5.8 21.5 12 21.5 12 18 18.2 12 18.2 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.6"/>',
  'doc' => '<path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4"/><path d="M10 12h5M10 15.5h5"/>',
  'play' => '<circle cx="12" cy="12" r="9"/><path d="m10 8.5 5.5 3.5-5.5 3.5z"/>',
  'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/><circle cx="12" cy="15" r="1.4" fill="currentColor" stroke="none"/>',
  'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1.1" fill="currentColor" stroke="none"/>',
  'linkedin' => '<rect x="3.5" y="3.5" width="17" height="17" rx="2.5"/><path d="M7.2 10.2v6.6M7.2 7.4v.02"/><path d="M11 16.8v-3.6a2.2 2.2 0 0 1 4.4 0v3.6M11 16.8v-6.6"/>',
  'facebook' => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><path d="M14.8 8.2h-1.3c-.9 0-1.6.7-1.6 1.6v1.4m-1.6 0h4m-2.4 0v6.1"/>',
];
@endphp
<svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icons[$name] ?? $icons['target'] !!}</svg>
