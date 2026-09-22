<?php

namespace App\Http\Controllers;

use App\Models\Principal;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * The generated column each FULLTEXT index covers, keyed by locale.
     */
    private const NAME_COLUMN = [
        'id' => 'name_id_text',
        'en' => 'name_en_text',
    ];

    /**
     * `innodb_ft_min_token_size` on the target server. A word shorter than
     * this is not in the FULLTEXT index at all, so MATCH can never return it
     * and the query has to fall back to LIKE.
     */
    private const MIN_TOKEN = 3;

    /**
     * The sort keys the catalogue accepts, in the order they appear in the
     * dropdown.
     *
     * A whitelist rather than a passthrough: the value arrives from the query
     * string, so anything not named here would otherwise be a column name
     * chosen by the visitor. An unrecognised value falls back to the default
     * order rather than erroring, so a stale bookmark or a hand-edited URL
     * still renders a page.
     */
    private const SORTS = ['default', 'newest', 'oldest', 'name_asc', 'name_desc'];

    public function __invoke(Request $request): View
    {
        $locale = app()->getLocale();
        $column = self::NAME_COLUMN[$locale] ?? self::NAME_COLUMN['id'];

        $sort = $request->string('sort')->toString();

        if (! in_array($sort, self::SORTS, true)) {
            $sort = 'default';
        }

        $products = Product::query()
            ->published()
            ->with(['category', 'principal', 'images'])
            ->when($request->string('category')->toString(), fn ($query, $slug) => $query
                ->whereHas('category', fn ($category) => $category->where('slug', $slug)))
            ->when($request->string('principal')->toString(), fn ($query, $slug) => $query
                ->whereHas('principal', fn ($principal) => $principal->where('slug', $slug)))
            ->when($request->filled('q'), fn ($query) => $this->applySearch(
                $query,
                $column,
                $request->string('q')->toString(),
            ))
            ->tap(fn (Builder $query) => $this->applySort($query, $sort, $column))
            ->paginate(24)
            ->withQueryString();

        return view('pages.catalog', [
            'products' => $products,
            'categories' => ProductCategory::published()->orderBy('sort_order')->orderBy('id')->get(),
            'principals' => Principal::published()->orderBy('sort_order')->orderBy('id')->get(),
            'term' => $request->string('q')->toString(),
            'activeCategory' => $request->string('category')->toString(),
            'activePrincipal' => $request->string('principal')->toString(),
            'activeSort' => $sort,
        ]);
    }

    /**
     * Apply the whitelisted sort key.
     *
     * Every branch ends with an id tiebreak. The default order needs one for
     * the reason documented below; the others need one because `created_at` is
     * second-precision and product names are not unique, so two rows can share
     * a sort value and MySQL is free to order a tie group differently on each
     * request, which makes paging across the boundary lose and duplicate rows.
     *
     * The name sorts read the locale's generated text column rather than the
     * JSON, so the database sorts on a real string with the column's
     * `utf8mb4_unicode_ci` collation instead of comparing JSON blobs.
     */
    private function applySort(Builder $query, string $sort, string $column): void
    {
        match ($sort) {
            'newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'name_asc' => $query->orderBy($column)->orderBy('id'),
            'name_desc' => $query->orderByDesc($column)->orderByDesc('id'),
            // sort_order defaults to 0 on every row and created_at is
            // second-precision, so a catalogue imported in one pass is one
            // large tie group, and the order within it is whatever the
            // optimizer happens to produce. MySQL does not guarantee that
            // order, so paging across a tie group is not guaranteed stable.
            //
            // Measured on this server, though, the untiebroken order for a
            // 30-row tie group WAS stable and pages 1 and 2 did not overlap,
            // so this key is defensive rather than a fix for an observed bug.
            // It costs one index-covered sort key and removes a class of
            // paging bug that would be very hard to reproduce once it
            // appeared. A test asserting the tie group pages cleanly is
            // deliberately NOT written: the mutation probe showed it passed
            // with this line removed, so it would have been coverage in name
            // only.
            default => $query->orderBy('sort_order')->orderByDesc('id'),
        };
    }

    /**
     * FULLTEXT first, LIKE when FULLTEXT cannot serve the term.
     *
     * The term is split on anything that is not a letter or a digit before it
     * goes near the query builder, and that split is the whole sanitising
     * story. It is what makes `AGAINST` safe: `+`, `-`, `***`, `"`, `~`, `()`
     * yield either no tokens at all (so the query is left untouched) or clean
     * alphanumeric ones, and `AGAINST('+' IN BOOLEAN MODE)` is a SQL syntax
     * error (1064) rather than an empty result set. The LIKE branch escapes
     * its own wildcards. An earlier, separate clean-the-string step was
     * removed after the mutation probe showed it could not change any outcome:
     * every case it handled was already handled here.
     *
     * `innodb_ft_min_token_size` is 3 on this server, so `AB` — the SKU prefix
     * of a real product, `AB-100 Analyzer` — is not in the index at all. A
     * term is searched through FULLTEXT only when every one of its tokens is
     * at least that long; otherwise the whole term falls back to a LIKE scan
     * against the same locale column. Measured on the real server: FULLTEXT is
     * case-insensitive and supports the `*` suffix wildcard, and LIKE inherits
     * the column's `utf8mb4_unicode_ci` collation so it is case-insensitive
     * too.
     */
    private function applySearch(Builder $query, string $column, string $term): Builder
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return $query;
        }

        $indexable = collect($tokens)->every(
            fn (string $token) => mb_strlen($token) >= self::MIN_TOKEN,
        );

        if (! $indexable) {
            return $query->where($column, 'like', '%'.addcslashes($term, '%_\\').'%');
        }

        return $query->whereRaw(
            "MATCH({$column}) AGAINST (? IN BOOLEAN MODE)",
            [$this->booleanTerm($tokens)],
        );
    }

    /**
     * A BOOLEAN MODE expression with every token prefix-matched.
     *
     * Just the token followed by `*`. Quoting a token and appending the
     * wildcard (`"Enema"*`) is a syntax error on MariaDB, not a phrase-prefix
     * search, so the tokens go in bare. Tokens are the term split on anything
     * that is not a letter or a digit, which mirrors how FULLTEXT itself
     * tokenises: `AB-100` indexes as `ab` and `100`, so searching `AB-100` has
     * to become two prefix terms or it matches nothing.
     *
     * Bare terms are OR-ed by MySQL, which is the behaviour a single search
     * box should have.
     *
     * @param  list<string>  $tokens
     */
    private function booleanTerm(array $tokens): string
    {
        return implode(' ', array_map(
            fn (string $token) => $token.'*',
            $tokens,
        ));
    }
}
