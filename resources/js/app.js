/*
|--------------------------------------------------------------------------
| Principal carousel
|--------------------------------------------------------------------------
|
| Progressive enhancement for the principal logo wall.
|
| The server renders every principal into the responsive grid the stylesheet
| defines. This script only takes over when that grid needs more than one row:
| it clips the strip to a single row and slides it a page at a time. A visitor
| without JavaScript keeps the whole register as a plain logo wall, which is
| the same content rather than a degraded version of it.
|
| The page size is read from the layout rather than configured here. How many
| marks fit on a row is a CSS decision, and the resolved
| `grid-template-columns` is the only authority on how many that turned out to
| be at the current width. Reading it back means the two can never drift: there
| is no second copy of the breakpoint list to keep in step. If a `grid-cols-*`
| class changes in the Blade view, or a new breakpoint is added, the paging
| follows automatically.
|
| The move is a transform on the track, not a change of which items exist.
| Nothing is hidden while the carousel runs: a hidden item leaves the grid and
| the remaining columns shift to close the gap, which is exactly the alignment
| the slide depends on. The strip is wider than its viewport, the viewport
| clips it, and the translation is the only thing that changes per page.
|
| Duplicates are appended so the loop has somewhere to land. The move off the
| last page runs onto them and is then swapped for the first page with the
| transition off, so the step is seamless: the two are the same picture. The
| duplicates also fill the tail of the last page when the list does not divide
| evenly, so the strip never slides with a hole in it.
|
*/

const CAROUSEL_SELECTOR = '[data-carousel]';

// How long one page takes to travel, and how long the strip holds between
// moves. The hold is long enough to read a page of marks and short enough that
// the register does not read as abandoned; the travel is slower than a control
// transition because the whole row is moving at once rather than one control.
//
// Both were raised from 620ms and 4500ms. The register is the only thing on the
// page that moves, and it was moving often enough to read as an interruption:
// a visitor looking at the products below it caught the strip out of the corner
// of their eye every four and a half seconds. A slower travel and a longer hold
// make it read as a slide that happens to be there rather than as a loop that
// is asking to be watched.
//
// The hold is the one that matters for calm; the travel is what stops the move
// itself being the thing that catches the eye. Raising the hold alone would
// leave the same snatch of motion, just less often.
const SLIDE_DURATION = 900;
const AUTOPLAY_DELAY = 7000;
const SLIDE_EASING = 'cubic-bezier(0.22, 0.61, 0.36, 1)';

// Motion is optional. A visitor who has asked the system for less of it gets a
// register that changes page without travelling, and no timer at all.
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

/**
 * How many items the grid is currently placing on one row.
 *
 * Returns `1` rather than `0` for a hidden or not-yet-laid-out grid, so callers
 * cannot divide by zero or produce a zero-length page. `1` pages one item at a
 * time, which is wrong but harmless and self-corrects on the next layout.
 */
function columnsPerRow(track) {
    const columns = getComputedStyle(track).gridTemplateColumns;

    // `grid-template-columns` computes to the used track sizes, so a
    // six-column grid is six values. `none` means the element is not a grid
    // (or is `display:none`), which is the case we guard against.
    if (!columns || columns === 'none') {
        return 1;
    }

    return Math.max(1, columns.split(' ').filter(Boolean).length);
}

function createCarousel(root) {
    const viewport = root.querySelector('[data-carousel-viewport]');
    const track = root.querySelector('[data-carousel-track]');
    const prev = root.querySelector('[data-carousel-prev]');
    const next = root.querySelector('[data-carousel-next]');

    if (!viewport || !track || !prev || !next) {
        return null;
    }

    const items = Array.from(track.children);

    if (items.length === 0) {
        return null;
    }

    let perPage = 1;
    let pages = 1;
    let page = 0;
    let step = 0;
    let moving = false;
    let clones = [];
    let timer = 0;

    // Every reason the strip is currently holding still: the pointer is over
    // it, focus is inside it, the tab is in the background, or it is off
    // screen. A set rather than a boolean because these overlap, and the strip
    // must not restart while any of them is still true.
    const holds = new Set();

    function clearClones() {
        clones.forEach((clone) => clone.remove());
        clones = [];
    }

    /**
     * Hand the strip back to the stylesheet.
     *
     * The single-row layout, the duplicates and the timer all exist only for as
     * long as there is something to page. Leaving any of them behind would show
     * a clipped strip with no way to move it.
     */
    function teardown() {
        root.removeAttribute('data-carousel-active');
        clearClones();
        clearTimeout(timer);
        track.removeAttribute('style');
    }

    /**
     * Ask the stylesheet how many marks it is putting on one row.
     *
     * While the carousel is running, the single-row layout is written inline on
     * the track and `grid-template-columns` is `none`, so the question would be
     * answered with `none` and a count of one. The inline layout is therefore
     * lifted for the length of the question and put straight back. It is never
     * observable, because the browser does not paint between the two writes.
     */
    function readColumns() {
        if (!root.hasAttribute('data-carousel-active')) {
            return columnsPerRow(track);
        }

        const saved = {
            flow: track.style.gridAutoFlow,
            rows: track.style.gridTemplateRows,
            columns: track.style.gridTemplateColumns,
            auto: track.style.gridAutoColumns,
        };

        track.style.gridAutoFlow = '';
        track.style.gridTemplateRows = '';
        track.style.gridTemplateColumns = '';
        track.style.gridAutoColumns = '';

        const columns = columnsPerRow(track);

        track.style.gridAutoFlow = saved.flow;
        track.style.gridTemplateRows = saved.rows;
        track.style.gridTemplateColumns = saved.columns;
        track.style.gridAutoColumns = saved.auto;

        return columns;
    }

    /**
     * Lay the strip out as one row that does not wrap.
     *
     * The column width is resolved here rather than left to the stylesheet,
     * because the track is sized to its own content: a percentage track would
     * be asking the element's width to define the very thing it is made of.
     * The width is the one the stylesheet would have given a column inside the
     * viewport, so a page comes out exactly one viewport wide and `place` can
     * step by that width.
     *
     * `grid-auto-flow: column` with a single explicit row is what keeps the
     * marks in one line: items fill the row and then extend sideways instead of
     * wrapping to a second row, which is what the stylesheet's own grid does.
     * The count still comes from the stylesheet, which is why this runs after
     * `readColumns`.
     */
    function applyLayout() {
        const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        const columnWidth = (viewport.clientWidth - (perPage - 1) * gap) / perPage;

        // How far one page travels. It is the stride between a column and the
        // same column on the next page, not the width of the viewport: the last
        // column of a page and the first of the next are still a gap apart, so
        // stepping by the viewport alone would leave every page after the first
        // short by one gap, drifting further out of alignment the longer the
        // strip ran. A page is `perPage` columns and the gaps between them.
        step = perPage * (columnWidth + gap);

        root.setAttribute('data-carousel-active', '');

        track.style.gridAutoFlow = 'column';
        track.style.gridTemplateRows = 'auto';
        track.style.gridTemplateColumns = 'none';
        track.style.gridAutoColumns = `${columnWidth}px`;
    }

    function buildClones() {
        clearClones();

        // Two runs of duplicates, in this order. The first `pad` fill the tail
        // of the last page when the list does not divide evenly, so the strip
        // never slides with a hole at the end of it. The next full page is what
        // the move off the end lands on, and it is the same picture as the
        // first page, which is what makes the swap back invisible. The order
        // matters: the second run only starts where the first one ends, which
        // is exactly where the last page stops.
        const pad = pages * perPage - items.length;

        clones = [...items.slice(0, pad), ...items.slice(0, perPage)].map((item) => {
            const clone = item.cloneNode(true);

            // Hidden from assistive technology: the marks they copy are
            // already in the register, and a second reading of them would be
            // noise. The item marker is dropped as well, so the attribute means
            // one thing only: a real principal rather than a copy of one.
            clone.setAttribute('data-carousel-clone', '');
            clone.setAttribute('aria-hidden', 'true');
            clone.removeAttribute('data-carousel-item');
            track.append(clone);

            return clone;
        });
    }

    function place(animate) {
        track.style.transition = animate
            ? `transform ${SLIDE_DURATION}ms ${SLIDE_EASING}`
            : 'none';
        track.style.transform = `translate3d(${-page * step}px, 0, 0)`;
    }

    /**
     * Reposition the strip without animating, then let the next move start from
     * there.
     *
     * Reading a layout property makes the browser commit the new position as
     * the starting point of the next transition. Without it the two writes
     * collapse into one and the swap animates, which is the jump the swap
     * exists to hide.
     */
    function jump(target) {
        page = target;
        place(false);
        void track.offsetWidth;
    }

    function reflect() {
        // Only shown when there is more than one page, so a register that fits
        // on one row is a plain wall with no controls that cannot do anything.
        const paged = pages > 1;

        // The loop parks on the duplicate page for the length of one move. It
        // is the same picture as the first page, so it has to read as the first
        // page: without this the arrows would dim at the one moment the strip
        // is showing what they open with, and re-light a frame later.
        const current = page === pages ? 0 : page;

        prev.hidden = !paged;
        next.hidden = !paged;

        prev.disabled = !paged || current === 0;
        next.disabled = !paged || current >= pages - 1;
    }

    /**
     * The strip has come to rest: publish the position and start the hold again.
     */
    function rest() {
        moving = false;

        // The loop parks on the duplicate page for the length of one move, and
        // this is where it swaps to the real first page.
        if (page === pages) {
            jump(0);
            reflect();
        }

        schedule();
    }

    function move(target) {
        page = target;

        const animated = !prefersReducedMotion.matches;

        moving = animated;
        place(animated);
        reflect();

        // With no transition there is no `transitionend` to come, and the swap
        // off the duplicate page still has to happen.
        if (!animated) {
            rest();
        }
    }

    function go(direction) {
        if (pages < 2 || moving) {
            return;
        }

        if (direction > 0) {
            move(page + 1);

            return;
        }

        if (page === 0) {
            // Stepping back off the start: stand on the duplicate of the first
            // page first. It is the same picture, so the eye sees only the move
            // that follows and the strip appears to run backwards into the end
            // rather than rewinding across every page.
            jump(pages);
            move(pages - 1);

            return;
        }

        move(page - 1);
    }

    function schedule() {
        clearTimeout(timer);

        if (pages < 2 || holds.size > 0 || prefersReducedMotion.matches) {
            return;
        }

        timer = setTimeout(() => go(1), AUTOPLAY_DELAY);
    }

    function hold(reason) {
        holds.add(reason);
        clearTimeout(timer);
    }

    function release(reason) {
        holds.delete(reason);

        if (holds.size === 0) {
            schedule();
        }
    }

    function sync() {
        moving = false;
        clearTimeout(timer);

        perPage = readColumns();
        pages = Math.max(1, Math.ceil(items.length / perPage));
        page = Math.min(page, pages - 1);

        // The arrows sit inside the row the columns are measured against, so
        // they have to be in their final state before `applyLayout` computes a
        // column width. Measured while they are still hidden, the viewport is
        // wider than it will be, the columns come out too wide, and every page
        // after the first drifts by the difference. The count itself is safe
        // either way: `grid-cols-*` answers to the window, not to this box.
        reflect();

        if (pages < 2) {
            teardown();

            return;
        }

        applyLayout();
        buildClones();
        jump(page);
        schedule();
    }

    prev.addEventListener('click', () => go(-1));
    next.addEventListener('click', () => go(1));

    track.addEventListener('transitionend', (event) => {
        if (event.target === track && event.propertyName === 'transform') {
            rest();
        }
    });

    // Pausing is hover, not click: a strip that moves under a pointer which is
    // already there should hold, and it should start again when the pointer
    // leaves without the visitor having to ask twice. Focus is treated the same
    // way, so a keyboard visitor who reaches the controls is not chasing them.
    root.addEventListener('pointerenter', () => hold('pointer'));
    root.addEventListener('pointerleave', () => release('pointer'));
    root.addEventListener('focusin', () => hold('focus'));
    root.addEventListener('focusout', () => release('focus'));

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            hold('hidden');
        } else {
            release('hidden');
        }
    });

    // Off screen there is nothing to watch, and the first move a visitor sees
    // should be the one that follows their arrival rather than a page already
    // in flight.
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    release('offscreen');
                } else {
                    hold('offscreen');
                }
            });
        }, { threshold: 0.25 });

        observer.observe(root);
    }

    // The preference can change while the page is open, and the strip should
    // follow it without a reload.
    prefersReducedMotion.addEventListener('change', sync);

    // Recomputed on resize because the width of a page is a function of it.
    // Guarded behind a frame because this fires while a window is dragged.
    let resizeFrame = 0;
    window.addEventListener('resize', () => {
        cancelAnimationFrame(resizeFrame);
        resizeFrame = requestAnimationFrame(sync);
    });

    sync();

    // And once more after the browser has finished the current layout pass.
    //
    // The first `sync` runs while the document is still being parsed, and
    // `clientWidth` at that moment is not the width the element ends up with:
    // on a 390px phone the viewport measured 192px instead of 300px, so the
    // column width came out 56px instead of 88px and the first page showed
    // four specks until something forced a resize. A `requestAnimationFrame`
    // callback runs after layout, so the second pass reads the real box. It is
    // idempotent — `sync` tears the strip down and rebuilds it — and on a page
    // whose first measurement was already correct it is a no-op.
    //
    // The fonts are the other half of it: a webfont that arrives after this
    // frame changes the width of every mark's name, and with it the grid the
    // column count is read from. `ready` is the point at which that can no
    // longer change.
    requestAnimationFrame(sync);

    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(sync).catch(() => {});
    }

    return { go, refresh: sync };
}

const carousels = Array.from(document.querySelectorAll(CAROUSEL_SELECTOR)).map(createCarousel);

// Exposed so a manual pass can drive the carousel without reaching into the DOM
// for state. Nothing in the application reads this.
window.principalCarousels = carousels;
