import Alpine from 'alpinejs';

/*
|--------------------------------------------------------------------------
| Catalog filtering, sorting, and paging
|--------------------------------------------------------------------------
|
| Alpine component for the catalog.
|
| The server stays the authority. This component does not filter or sort
| anything: it intercepts the form's submit, fetches the URL the browser was
| about to navigate to, and replaces the contents of the results region with
| the region from the response. The result set, the ordering, the counts, and
| the pagination links all come from the server, so none of them can disagree
| with each other.
|
| Doing the work in the browser was the tempting alternative and it would have
| been wrong rather than merely slow. The catalog paginates at 24, so "name
| A-Z" computed over the rows on screen is not "name A-Z" over the catalog, and
| a client-side filter would silently hide every match on page 2. A wrong
| answer presented as a complete one is worse than a page reload.
|
| A visitor without JavaScript gets the plain form and a full navigation. That
| is not a fallback in name only: a test asserts the grid is complete and the
| sort is applied with this file never running.
|
| The response is a whole document and the region is read out of it, rather
| than a partial endpoint being added. A partial would be a second render path
| to keep in step with the full page, and the two would drift.
|
*/

Alpine.data('catalogResults', () => ({
    // Drives `aria-busy`. A reactive property rather than a class toggle, so
    // assistive technology is told about the in-flight request and not just
    // the eye.
    loading: false,

    // Only the newest request may paint. A filter changed and re-submitted
    // before the first response lands would otherwise be overwritten by the
    // slower reply, showing results for a query the visitor has moved on from.
    inFlight: null,

    /**
     * Build the request URL from the form the browser would have submitted.
     *
     * Serialising the form means the request is assembled from the same
     * controls the browser would send, including any added later, with no
     * second list of field names to keep in step.
     *
     * Empty values are dropped. A native GET submit sends `category=&sort=`
     * for untouched controls, and those are equivalent to absent on the server,
     * so dropping them keeps the address bar canonical: one URL per state
     * rather than one per combination of empty fields.
     */
    endpoint(form) {
        const url = new URL(form.action || window.location.href, window.location.href);
        const params = new URLSearchParams(new FormData(form));

        for (const [key, value] of [...params]) {
            if (value === '') {
                params.delete(key);
            }
        }

        url.search = params.toString();

        return url;
    },

    async submit(event) {
        const form = event.target;

        // A modified click is a request to open the filtered view in a new tab
        // or window. That is the browser's job, not this component's.
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        event.preventDefault();

        await this.load(this.endpoint(form));
    },

    /**
     * Follow a pagination link without a full navigation.
     *
     * Delegated from the region, so it keeps working after a swap has replaced
     * the links: the region element itself is never replaced, only its
     * contents.
     *
     * The lookup is scoped to `[data-catalog-pagination]`. The region also
     * contains every product card, and those are real links to other pages:
     * intercepting them would turn "view this product" into a grid swap.
     */
    async page(event) {
        const link = event.target.closest('[data-catalog-pagination] a[href]');

        if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        event.preventDefault();

        await this.load(new URL(link.href));
    },

    /**
     * Fetch a URL and swap the results region with the one it returns.
     *
     * Every failure path falls back to a real navigation, so a broken response
     * surfaces as a page the visitor can see rather than as a grid that
     * silently did not change.
     */
    async load(url) {
        this.inFlight?.abort();
        const controller = new AbortController();
        this.inFlight = controller;

        this.loading = true;

        try {
            const response = await fetch(url, {
                signal: controller.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const next = response.ok ? this.extract(await response.text()) : null;

            if (!next) {
                window.location.assign(url);

                return;
            }

            // The region's contents are replaced, not the region itself. The
            // element carries this component's `x-ref` and its delegated
            // listener, and replacing it would detach both along with the
            // reactive `aria-busy` binding.
            this.$refs.results.innerHTML = next.innerHTML;

            // The address bar follows the result set, so a reload, a bookmark,
            // and a shared link all reproduce what is on screen.
            window.history.pushState({}, '', url);

            // Focus moves to the region so the replacement is announced and the
            // keyboard position is not left on a control that no longer
            // matches what is displayed. `preventScroll` keeps the viewport
            // where the visitor left it instead of jumping to the top.
            this.$refs.results.focus({ preventScroll: true });
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.assign(url);
            }
        } finally {
            if (this.inFlight === controller) {
                this.inFlight = null;
                this.loading = false;
            }
        }
    },

    /**
     * Read the results region out of a full response document.
     */
    extract(html) {
        const parsed = new DOMParser().parseFromString(html, 'text/html');

        return parsed.querySelector('[data-catalog-results]');
    },

    /**
     * Handle a back or forward move between the states this component pushed.
     *
     * The grid has to follow the address bar, or the URL and the products on
     * screen disagree. The server is asked for the state rather than a snapshot
     * being cached in memory, so a back move cannot restore a stale grid.
     */
    async restore() {
        this.loading = true;

        try {
            const response = await fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const next = response.ok ? this.extract(await response.text()) : null;

            if (next) {
                this.$refs.results.innerHTML = next.innerHTML;
            }
        } catch {
            window.location.reload();
        } finally {
            this.loading = false;
        }
    },

    init() {
        window.addEventListener('popstate', () => this.restore());
    },
}));

// Registered before `start()`, because Alpine reads its component registry when
// it walks the DOM. Starting first would leave the markup uninitialised until
// some later pass.
Alpine.start();
