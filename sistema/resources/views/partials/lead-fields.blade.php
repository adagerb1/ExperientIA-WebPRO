{{-- Campos compartidos de captura de lead. $full = true incluye empresa/cargo/industria/tamaño obligatorios. --}}
@php
$full = $full ?? false;
$company = $company ?? true;
use Symfony\Component\Intl\Countries;
$countries = collect(Countries::getNames(app()->getLocale()))->sortBy(fn ($v) => $v);
$industries = config('experientia.industries');
@endphp

<div class="honeypot" aria-hidden="true">
  <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
</div>

<div class="form-row">
  <div class="field">
    <label for="f-name">{{ __('site.form.nombre') }} *</label>
    <input id="f-name" name="name" type="text" autocomplete="name" required value="{{ old('name') }}" />
    @error('name')<p class="error">{{ $message }}</p>@enderror
  </div>
  <div class="field">
    <label for="f-email">{{ __('site.form.email') }} *</label>
    <input id="f-email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" />
    @error('email')<p class="error">{{ $message }}</p>@enderror
  </div>
</div>

<div class="form-row">
  <div class="field">
    <label for="f-phone">{{ __('site.form.telefono') }}</label>
    <input id="f-phone" type="tel" data-phone autocomplete="tel" />
    <input type="hidden" name="phone_wa" value="{{ old('phone_wa') }}" />
    <input type="hidden" name="phone_dial" value="{{ old('phone_dial') }}" />
    <p class="hint">{{ __('site.form.telefono_hint') }}</p>
    <p class="error" data-phone-error hidden>{{ __('site.form.error_telefono') }}</p>
    @error('phone_wa')<p class="error">{{ $message }}</p>@enderror
  </div>
  <div class="field">
    <label for="f-country">{{ __('site.form.pais') }} *</label>
    <select id="f-country" name="country" required data-combobox data-placeholder="{{ __('site.form.pais_placeholder') }}">
      <option value=""></option>
      @foreach ($countries as $code => $name)
        <option value="{{ $code }}" @selected(old('country') === $code)>{{ $name }}</option>
      @endforeach
    </select>
    @error('country')<p class="error">{{ $message }}</p>@enderror
  </div>
</div>

@if ($company)
<div class="form-row">
  <div class="field">
    <label for="f-company">{{ __('site.form.empresa') }} @if($full)*@endif</label>
    <input id="f-company" name="company" type="text" autocomplete="organization" @if($full) required @endif value="{{ old('company') }}" />
    @error('company')<p class="error">{{ $message }}</p>@enderror
  </div>
  @if ($full)
  <div class="field">
    <label for="f-role">{{ __('site.form.rol') }}</label>
    <input id="f-role" name="role" type="text" autocomplete="organization-title" value="{{ old('role') }}" />
  </div>
  @endif
</div>

<div class="form-row">
  <div class="field">
    <label for="f-industry">{{ __('site.form.industria') }} @if($full)*@endif</label>
    <select id="f-industry" name="industry" @if($full) required @endif data-combobox data-placeholder="{{ __('site.form.industria_placeholder') }}">
      <option value=""></option>
      @foreach ($industries as $key => $label)
        <option value="{{ $key }}" @selected(old('industry') === $key)>{{ $label }}</option>
      @endforeach
    </select>
    @error('industry')<p class="error">{{ $message }}</p>@enderror
  </div>
  <div class="field">
    <label for="f-size">{{ __('site.form.empleados') }} @if($full)*@endif</label>
    <select id="f-size" name="company_size" @if($full) required @endif data-combobox>
      <option value=""></option>
      @foreach (__('site.form.empleados_opciones') as $key => $label)
        <option value="{{ $key }}" @selected(old('company_size') === $key)>{{ $label }}</option>
      @endforeach
    </select>
    @error('company_size')<p class="error">{{ $message }}</p>@enderror
  </div>
</div>
@endif
