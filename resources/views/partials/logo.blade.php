@php
    /*
    | The official mark, or the company name when no logo is configured.
    |
    | The logo is a static shipped asset rather than an upload, so it is stored
    | as a public-root-relative path and resolved with `asset()`. An admin
    | upload would need a storage-path convention that also governs Principal
    | logos, and that decision belongs with the tasks that render them, not
    | here.
    |
    | The plate: the logo's charcoal stroke is #393939. On the light canvas
    | that is 10.98:1 and no plate is needed, but on the dark canvas it drops
    | to 1.71:1 and the G in the monogram would disappear. `bg-logo-plate` is
    | white in both schemes, so in light mode it is invisible against the
    | header and in dark mode it reads as a deliberate chip. The brand's own
    | blue is 2.80:1 on white, which is below the text minimum, but a logo is
    | exempt from those minimums and the supplied colours are left untouched.
    |
    | The intrinsic `width`/`height` reserve the box before the image loads, so
    | the header does not shift. The file is 903x864; `h-*`/`w-auto` scales it.
    */
    $logo = \App\Models\Setting::get('logo');
    $companyName = \App\Models\Setting::get('company_name', 'Medquest Mitra Global');
    $height = $height ?? 'h-9';
@endphp

@if (filled($logo))
    <img src="{{ asset($logo) }}"
         alt="{{ $companyName }}"
         width="903"
         height="864"
         class="{{ $height }} w-auto rounded-sm bg-logo-plate object-contain p-1">
@else
    <span class="text-lg font-semibold tracking-tight text-ink">{{ $companyName }}</span>
@endif
