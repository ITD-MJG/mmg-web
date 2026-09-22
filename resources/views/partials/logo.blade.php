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
    | No background. The PNG is already transparent, and the plate that used to
    | sit behind it (`bg-logo-plate` plus `p-1`) is gone. It was invisible
    | against the header, which is near-white, but the footer sits on
    | `bg-surface-muted` (#f4f3f0), where a white plate read as a plain
    | rectangle floating behind the mark. The mark now sits directly on
    | whatever surface it is placed on.
    |
    | The plate survives in dark mode only, as `dark:bg-logo-plate`. It is still
    | needed there: the logo's charcoal stroke is #393939, which is 10.98:1 on
    | the light canvas but 1.71:1 on the dark one, where the G in the monogram
    | would all but vanish. Light is the default and nothing sets `.dark`, so
    | this has no effect on the site as it ships; it keeps the dark palette
    | usable for whenever a toggle is added.
    |
    | The 16:9 frame. The frame is a wrapper element, not the image itself.
    | Putting `aspect-video` on the `<img>` does not work: `w-auto` on a replaced
    | element resolves its width from the file's own intrinsic ratio (903x864),
    | not from the `aspect-ratio` property, so the box measured 387x48, an 8:1
    | ratio, and the frame was only 16:9 in the class list. A non-replaced
    | wrapper has no intrinsic ratio to compete with, so `aspect-video` plus a
    | height gives a real 16:9 box, and `object-contain` centres the mark inside
    | it.
    |
    | The mark is near-square, so on a 16:9 box it is height-constrained: the
    | frame's height decides the mark's size and the surplus width is empty
    | space. That is the point of the frame, and it is what lets a future wide
    | wordmark drop in without a layout change.
    |
    | Sizing is therefore one value. The caller passes the frame height and the
    | width follows from the ratio, which is why no `w-*` is set anywhere.
    |
    | The intrinsic `width`/`height` on the image are the file's own, so the box
    | is reserved before the image arrives and the header does not shift.
    */
    $logo = \App\Models\Setting::get('logo');
    $companyName = \App\Models\Setting::get('company_name', 'Medquest Mitra Global');

    // Callers pass the frame's height; the width follows from 16:9. Defaults
    // match the footer so the partial is usable on its own.
    $height = $height ?? 'h-12';
@endphp

@if (filled($logo))
    <span class="flex {{ $height }} aspect-video items-center justify-center">
        <img src="{{ asset($logo) }}"
             alt="{{ $companyName }}"
             width="903"
             height="864"
             class="site-logo h-full w-full object-contain dark:rounded-sm dark:bg-logo-plate dark:p-1">
    </span>
@else
    <span class="text-xl font-semibold tracking-tight text-ink">{{ $companyName }}</span>
@endif
