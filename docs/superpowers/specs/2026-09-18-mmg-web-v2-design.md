# MMG Web v2 — Design Spec

**Project:** Medquest Mitra Global — healthcare distribution company website
**Date:** 2026-09-18
**Status:** Approved (design phase complete, pending implementation plan)

---

## 1. Purpose & Scope

Public marketing website for PT Medquest Mitra Global, a healthcare/medical device
distribution company based in Jakarta, Indonesia.

**Phase 1 (this spec):** bilingual product catalog + company profile + RFQ (request for
quote) inquiry flow + admin CMS.

**Phase 2 (additive, not in this spec):** blog. Schema is pre-designed so no migration
pain is incurred.

**Out of scope:** e-commerce/cart/checkout, gated account pricing, stock levels,
payment processing, ERP integration.

**Terminology — "principal".** The company's own term for the manufacturers and
distributors whose products it distributes is **principal**. The codebase uses
that word throughout — model, table, column, routes, admin resource — rather
than "brand", so that the domain language, the UI, and the database agree. It is
a loanword used in both Indonesian and English business contexts, so it is the
same string in both locales and is not translated.

---

## 2. Requirements (from discovery)

| # | Requirement | Decision |
|---|---|---|
| 1 | Maintainer profile | Mixed — dev builds, non-technical staff edit content daily |
| 2 | Catalog visibility | Public products/specs; **no prices shown**; RFQ instead |
| 3 | Purchase flow | None. Browse + contact/RFQ only |
| 4 | Hosting | Domainesia shared hosting (cPanel). Domain already owned. |
| 5 | Catalog shape | Flat for demo (hundreds of products); must migrate cleanly to structured |
| 6 | Language | Bilingual ID + EN from day one, both required |
| 7 | Stack direction | Custom codebase, not WordPress |
| 8 | SEO | Required |
| 9 | AEO/GEO | Required — discoverability by AI systems and buying agents |
| 10 | Search/filter | Category + principal filter; MySQL FULLTEXT search |

### Confirmed hosting capability (Domaineisa Nimbus)

Verified from Domainesia's hosting page, not assumed:

- PHP 5.x / 7.x / 8.x — **customer confirms PHP 8.4 available**
- MySQL + PostgreSQL, phpMyAdmin, Remote MySQL
- SSH access, cron jobs, Composer/package manager
- MySQL, Redis, Memcached available (Redis on Advanced tier and above)
- Git Deploy Manager (deploy from repository)
- LiteSpeed web server, HTTP/3 (QUIC), Brotli, CageFS, Imunify360
- Disk: 15 GB NVMe (Nimbus Go) — **inode limits apply**
- RAM 1–2 GB, Entry Process 8–50 depending on tier
- Softaculous one-click installer, JetBackup, free SSL (Let's Encrypt)

**Implication:** shared-hosting Entry Process and RAM limits make a persistent Node SSR
process a poor fit. PHP with pre-built assets is the correct shape.

---

## 3. Stack

| Layer | Choice | Rationale |
|---|---|---|
| Runtime | PHP 8.4 | Laravel 13 + Filament 5 require `^8.2`; confirmed available |
| Framework | Laravel 13 | Current stable; first-party ecosystem |
| Admin panel | Filament 5 (at `/admin`) | CRUD, auth, roles, media upload, relations for free |
| Frontend | Blade + Tailwind 4 + Alpine.js | No Node runtime needed on the server |
| Build | Vite (local/CI only), `public/build` committed or deployed | Shared hosting has no reliable Node build step |
| Database | MySQL 8 / MariaDB | Native to hosting, portable, no extra service |
| Session/Cache/Queue | `database` driver; Redis when available | Shared-hosting safe default; Redis is an available upgrade |
| Media | Local disk → Cloudflare R2 if inode/disk pressure appears | 15 GB + inode cap is the real constraint |
| Search | MySQL `FULLTEXT` | Meilisearch/Typesense cannot run on shared hosting |
| Permissions | `spatie/laravel-permission` | Mature, Filament-compatible |
| Translations (content) | `spatie/laravel-translatable` (JSON columns) | One table per entity |
| Tests | Pest (feature/unit) + Laravel Dusk (RFQ flow only) | |
| Deploy | GitHub → Domainesia Git Deploy Manager (or rsync over SSH) | |

**Rejected alternatives:**
- **WordPress + custom theme** — viable, but chosen against: plugin drift, PHP/WP
  maintenance burden, weaker control over the AEO layer, and less maintainable for a
  custom data model.
- **Laravel + Inertia + React** — overkill. Catalog + RFQ has almost no client state.
- **Next.js + headless CMS** — contradicts the hosting decision; two deploy targets,
  CORS, cache invalidation. Explicitly rejected.

---

## 4. Architecture

### 4.1 Locale strategy

Two locales, ID (default, no URL prefix) and EN (`/en` prefix).

```
/produk/{slug}          (id)
/en/products/{slug}     (en)
```

**One slug per record, shared across locales** (Latin, ID-derived).

Rationale — corrected after research; the earlier draft of this spec justified the
decision with a claim that was false, and the reasoning is recorded here so it is not
re-litigated:

- **Keywords in URLs are a negligible ranking factor.** Google's John Mueller has stated
  repeatedly that URL keywords are "a very very lightweight factor", "minimal once the
  content is indexed", and "overrated", adding that it is not worth restructuring a site
  to place keywords in URLs. The SEO delta between shared and translated slugs is
  effectively zero.
- **Translated slugs do not create duplicate content.** Google treats localized versions
  as duplicates only when the main content remains untranslated. `hreflang` exists
  specifically to declare intentional language variants. There is no duplicate-content
  penalty for either slug strategy.
- **`hreflang` is a routing directive, not a ranking signal.** It selects which locale
  version is served to which user; it does not raise rankings.
- **The decisive factor is the slug-parity trap.** If `hreflang` generation assumes slug
  parity across locales and a slug drifts — typo, orphaned record, or a deliberately
  translated slug — the page emits a `hreflang` pointing at a 404. Google then discards
  **the entire annotation pair**, both sides, silently. There is no build failure and
  Search Console hreflang reporting lags 2–4 weeks behind the break. A single shared slug
  makes parity structural and therefore makes this failure mode impossible.
- **The remaining cost is CTR/UX only**, and for this catalog it is near zero: product
  names are principal + model identifiers (`OneMed Enema Set`, `Mindray uMEC12`) which do not
  translate. A translated slug would be identical to the ID slug in most cases, adding a
  field to maintain for no benefit.

**Upgrade path if translated EN slugs are later wanted:** add a nullable `slug_en` column,
resolve either slug at the route level, make the canonical locale-specific, and switch
`hreflang` from assumed parity to explicit cross-locale references. Additive migration,
no rewrite of surrounding schema — the same pattern used for `specs`.

**hreflang implementation requirements (apply regardless of slug strategy):** every page
must be self-referencing, annotations must be bidirectional, and URLs must be
fully-qualified absolute URLs. Partial or non-reciprocal sets cause Google to ignore the
annotations entirely.

- Implemented with a thin custom middleware (~40 lines): resolve locale, set app locale,
  share with views, emit `hreflang`. **No localization package** — two locales do not
  justify the dependency.

**Content translation storage:** JSON columns on the same table.

```json
{"id": "Alat Kesehatan A", "en": "Medical Device A"}
```

Chosen over a `*_translations` side table (2× tables, more joins, no benefit at this
scale) and over `name_id`/`name_en` column pairs (rigid, breaks on locale #3).

### 4.2 Routing

`routes/localized.php` registers each route twice inside a loop over
`['id' => '', 'en' => 'en']`, using `->name("$locale.products.show")`. Explicit and
greppable rather than a package's dynamic route magic.

### 4.3 Rendering

Plain Blade. **No Livewire on the public site** — Livewire is confined to Filament.
Public filter/search state lives in the query string, driven by Alpine. This keeps every
public page guest-cacheable, which is what allows the site to survive Entry Process limits.

### 4.4 Caching

- LiteSpeed Cache for guest HTML; purge on model save via Eloquent observers.
- Fallback if LiteSpeed cache control is not exposed: `spatie/laravel-responsecache`.
- Application-level: `Cache::remember` for categories, principals, settings, navigation,
  and the markdown/agent endpoints.

---

## 5. Data Model

### 5.1 Phase 1 tables

```
categories      id, parent_id?, slug, name(json), description(json),
                image, sort_order, is_published, timestamps

principals          id, slug, name, logo, description(json),
                certifications(json)?, sort_order, is_published, timestamps

products        id, category_id, principal_id?, slug, sku?,
                name(json), short_description(json), description(json),
                specs(json),
                certifications(json)?,
                is_published, is_featured, sort_order, timestamps

product_images  id, product_id, is_cover(bool, default false), path,
                alt(json), sort_order, timestamps
                + cover_key (generated), unique(cover_key)

inquiries       id, name, company?, email, phone, product_id?,
                message, locale, status(enum: new/read/replied),
                source_path?, ip_hash?, timestamps
                -- ip_hash = sha256(ip + APP_KEY), used only for rate limiting and
                -- duplicate-submission detection. Raw IPs are never stored (UU PDP).

pages           id, slug, title(json), body(json), is_published, timestamps

settings        key, value(json)

users           + spatie/laravel-permission roles
```

### 5.2 Phase 2 tables (pre-designed, not built now)

```
posts            id, post_category_id, slug, title(json), excerpt(json),
                 body(json), cover, author_id?, published_at,
                 is_published, timestamps

post_categories  id, slug, name(json), sort_order
```

### 5.3 Key modeling decisions

**`specs` as JSON.** Holds arbitrary key/value pairs, e.g.
`{"Ukuran": "10x15cm", "Kemasan": "50 pcs/box"}`. Demonstrates the flat catalog shape
requested while keeping a clean migration path to a `product_specs` table later — no data
loss, no rewrite of surrounding schema.

Keys are translatable-capable but stored as plain strings for phase 1 (spec keys such as
"Ukuran"/"Size" are the only ones that would need translation; if that becomes necessary,
the JSON value for a key becomes `{"id": "...", "en": "..."}` and the renderer handles
both shapes).

**`certifications` nullable on `products` and `principals`.** Distribution licence, AKL, and
izin edar numbers are not yet available — the company must confirm what may be published.
The field is designed now so enabling it later is a data-entry task, not a migration.

Shape is an array of objects so multiple certificates per record are supported:

```json
[
  {
    "type": "izin_edar",
    "number": "AKL 12345678901",
    "issuer": "Kemenkes RI",
    "valid_until": "2029-01-31",
    "url": null
  }
]
```

`type` is an enum-backed string (`izin_edar`, `akl`, `iso_13485`, `iso_9001`,
`distributor_licence`, `other`). `valid_until` and `url` are optional. The array is empty
until the company supplies data, and rendering is conditional everywhere — no empty
schema nodes are emitted.

**One cover image per product, enforced at the database level.** MySQL/MariaDB cannot
express a partial unique index, so a stored generated column is used:

```sql
cover_key BIGINT AS (IF(is_cover, product_id, NULL)) STORED,
UNIQUE KEY uniq_cover_per_product (cover_key)
```

Multiple `NULL`s are permitted, so any number of gallery images coexist with exactly one
cover per product. The application layer (Filament) additionally unsets other covers in
the same transaction when a new cover is set.

**Missing image fallback.** Products with zero images must render a placeholder rather
than depend on a cover row existing.

**`inquiries` rather than `orders`.** Matches the browse-and-ask model. Filament provides
an inbox with a `new → read → replied` workflow.

### 5.4 Media handling

Filament `FileUpload` with `->image()->imageEditor()`, writing to `storage/app/public`.
Thumbnails generated on upload via a queued job. `storage:link` requires SSH (available);
if symlinks are blocked by CageFS, fall back to a `public/uploads` disk — verify at deploy.

`spatie/laravel-medialibrary` is deliberately not used: its polymorphic media table and
conversion machinery are unnecessary at this scale. Revisit only if responsive `srcset`
generation becomes a requirement.

---

## 6. Page Inventory

ID paths have no prefix; EN paths are prefixed with `/en`.

| ID | EN | Notes |
|---|---|---|
| `/` | `/en` | hero, facilities marquee, principal marquee, products grid 3×2, contact, footer |
| `/produk` | `/en/products` | filter by category + principal, search, paginated |
| `/produk/{slug}` | `/en/products/{slug}` | gallery, specs table, description, RFQ CTA, related products |
| `/principal` | `/en/principals` | principal index |
| `/principal/{slug}` | `/en/principals/{slug}` | principal profile + its products |
| `/tentang-kami` | `/en/about` | company profile |
| `/kontak` | `/en/contact` | contact details + RFQ form |
| `/halaman/{slug}` | `/en/pages/{slug}` | privacy, terms, and other static pages |
| — phase 2 — | | |
| `/blog` | `/en/blog` | post index |
| `/blog/{slug}` | `/en/blog/{slug}` | post detail |
| `/blog/kategori/{slug}` | `/en/blog/categories/{slug}` | post category |

---

## 7. Admin (`/admin`, Filament 5)

- **Products** — ID/EN tabs for translatable fields; `specs` key/value repeater; images
  relation manager with `is_cover` toggle and drag-sort; publish toggle; SEO tab;
  optional certifications section.
- **Categories** — same translatable pattern; tree structure.
- **Principals** — translatable, logo, optional certifications.
- **Pages** — translatable title/body.
- **Inquiries** — inbox with status actions (`new → read → replied`), product context
  when submitted from a product page, email notification on submit.
- **Settings** — single page: company info, WhatsApp number, address, email, socials,
  default meta.
- **Users** — roles via `spatie/laravel-permission`: `admin` (full), `editor`
  (content only — no users, no settings).
- **Dashboard widgets** — new inquiries, product counts, unpublished drafts.

---

## 8. SEO Layer

| Item | Implementation |
|---|---|
| Sitemap | `sitemap.xml` generated by artisan command + cron |
| robots.txt | explicit, allows AI crawlers (see §9) |
| Canonical | per-page canonical, locale-aware |
| hreflang | `id`, `en`, `x-default` |
| Meta | per-page editable title/description in admin; sensible fallbacks |
| Open Graph | OG/Twitter cards; fallback image per category |
| JSON-LD | see §9 Layer 4 |
| Slugs | shared across locales (§4.1) — parity is structural, not assumed |
| Performance | guest HTML cache, pre-built assets, HTTP/3, Brotli |

---

## 9. AEO / GEO Layer

Positioning: AEO/GEO is roughly 80% shared infrastructure with SEO. Google's official
guidance states that generative AI features require **no special files or markup** — they
run on core Search ranking. The additions below target non-Google AI engines (ChatGPT,
Perplexity, Claude, Copilot) and autonomous buying agents, and are harmless to Google.

**Domain note:** medical/health content is YMYL. Google applies its strictest E-E-A-T
standards. Named authorship, sourced claims, and regulatory accuracy are ranking
mechanisms here, not decoration.

### Layer 1 — Crawlability

- Blade server-side rendering means full HTML is present without JavaScript. This is the
  single largest structural advantage over JS-rendered alternatives for both AI crawlers
  and accessibility-tree consumers.
- `robots.txt` explicitly allows: `GPTBot`, `ChatGPT-User`, `OAI-SearchBot`,
  `PerplexityBot`, `ClaudeBot`, `anthropic-ai`, `Google-Extended`, `Bingbot`.
- Training-only crawlers (e.g. `CCBot`) are a separate policy decision and may be
  blocked without affecting citation eligibility.
- `sitemap.xml` submitted to Google Search Console and Bing Webmaster Tools.

### Layer 2 — Extractability (enforced editorially)

- Every product page opens with a **40–60 word definition block** that stands alone
  without surrounding context. This is stored in the existing `short_description(json)`
  field — no additional column. The admin form labels it "Ringkasan (40–60 kata)" and
  shows a live word count; a validation warning (not a hard error) fires outside the
  40–60 range so editors are guided rather than blocked.
- `specs` render as a real HTML `<table>`, never as prose. Tables extract reliably.
- H2/H3 headings phrased as queries (`Apa itu X?`, `Apa perbedaan X dan Y?`,
  `Bagaimana cara order X?`). Product `description` is rich-text, so heading structure is
  authored by editors — the Filament rich editor is configured with only H2/H3 available
  (no H1, which is reserved for the page title, and no H4+ which fragments content).
- `updated_at` surfaced as "Terakhir diperbarui: [date]". Undated content loses to dated.
- No primary content behind JavaScript.

**Comparison content is NOT a phase-1 feature.** No comparison page type, no
`ItemList` comparison schema, and no comparison route is built in phase 1. Comparison
articles are the highest-citation format, but they require real editorial work. When the
blog ships in phase 2, comparison content is published as `posts` with a table in the
body and `ItemList` JSON-LD added at that time. This keeps phase 1 free of a content type
that would ship empty.

### Layer 3 — Machine-readable files

| File | Content | Purpose |
|---|---|---|
| `/llms.txt` | company overview, distribution scope, licences, links to `/katalog.md`, `/tentang.md`, `/kontak.md` | directory for AI systems (llmstxt.org) |
| `/llms-full.txt` | full catalog + company profile in one markdown document | single-fetch agent context |
| `/katalog.md` | every product: name, principal, category, specs, certifications (when available), and a quote-request path with link to the RFQ form | primary agent-facing artifact |
| Content negotiation | `Accept: text/markdown` on catalog/product routes returns a markdown representation | matches Cloudflare/Stripe/Anthropic/Mintlify practice |

Content negotiation requires an explicit `Vary: Accept` response header on negotiated
routes, otherwise LiteSpeed (and any intermediary cache) will serve a markdown body to a
browser or an HTML body to an agent. Negotiated routes are therefore excluded from the
LiteSpeed guest cache and served from the application cache keyed by
`(locale, path, format)` instead. `Vary: Accept` is emitted on every negotiated response
including `304`s.
| `Link:` headers | advertise `llms.txt` and sitemap | discovery |

All of these are **generated from the same Eloquent models** via cached Blade views —
never authored twice. `/katalog.md` regenerates on product save via observer + queue.

Because no prices are published, the catalog markdown must present specs plus an explicit
quote-request path. A dead end ("contact us") is worse than structured data with a clear
next action.

### Layer 4 — Structured data (JSON-LD, Blade partials)

| Page | Schema |
|---|---|
| Global | `Organization` with stable `@id`, name, logo, `address` (Jakarta, real geo), `telephone`, `sameAs`, `hasCertification` |
| Product | `Product` + `additionalType: MedicalDevice`, `additionalProperty: PropertyValue[]` (from `specs`), `hasCertification` (when available), `manufacturer` as nested `Organization` with stable `@id`, `category`, `isRelatedTo` |
| Category | `ItemList` |
| About / Contact | `WholesaleStore` (distributor), `LocalBusiness` properties |
| Blog (phase 2) | `Article`, named `author`, `datePublished`, `dateModified` |
| All | `BreadcrumbList` |

`@id` values are consistent across every product page so that principal → manufacturer
relationships feed Knowledge Graph entity matching instead of being re-declared ad hoc.

### Layer 5 — Third-party presence (operational, greenfield)

Principals are substantially more likely to be cited via third-party sources than via their
own domain. Self-hosted content is necessary but not sufficient.

Starting-point checklist (no existing presence; nothing to migrate):

1. **e-Katalog LKPP** registration — government/hospital procurement; both GEO visibility
   and direct commercial value.
2. **Google Business Profile** — verified Jakarta address, categories, photos.
3. **Kemenkes regalkes** listing alignment.
4. **Wikidata** `Organization` entity — machine-checkable entity for Knowledge Graph.
5. **Industry directories** appropriate to Indonesian healthcare distribution.
6. **LinkedIn company page** kept current.
7. **Industry press / association mentions** — one credible third-party mention outweighs
   many self-published pages.

This layer is a business-run activity, not a code deliverable. It is documented here so
the implementation plan does not silently omit it.

### Layer 6 — Monitoring

- Monthly manual prompt tracking across ChatGPT, Perplexity, Google AI Overviews, and
  Copilot for ~20 priority queries. Record: cited?, competitors cited?, which page?
- Track **recommendation rate** (on the shortlist) separately from **citation rate**
  (mentioned as a source) — they are governed by different signals.
- Google Search Console provides no AI-specific reporting. Third-party tools (Peec AI,
  Otterly, ZipTie, LLMrefs) are the only cross-platform view. Manual tracking is
  sufficient and free at this scale.

### Explicitly rejected (per Google's AI-features guidance)

- Separate content variants written "for AI" — risks the scaled content abuse spam policy.
- Chunking pages into AI-bait fragments.
- Mass-generated thin content.
- Fabricated or bulk-spammed third-party mentions.
- Hiding primary content behind JavaScript.
- Keyword stuffing (measurably reduces AI visibility).

---

## 10. Testing

- **Pest feature tests** per route, per locale variant: renders, locale switching,
  `hreflang` correctness, 404 on unpublished records.
- **hreflang integrity test**: for every published record, assert the emitted `hreflang`
  URLs return 200, are self-referencing, bidirectional, and fully-qualified. This is the
  automated guard against the slug-parity trap — the failure is otherwise silent and only
  surfaces in Search Console weeks later.
- **RFQ form**: validation, honeypot, rate limiting, email dispatch, `inquiries` row
  created with correct `locale` and `product_id`.
- **Catalog**: filtering by category/principal, FULLTEXT search, pagination, cover-image
  fallback when a product has no images.
- **DB constraint test**: attempting to set two covers on one product fails.
- **Agent endpoints**: `/llms.txt`, `/llms-full.txt`, `/katalog.md` return valid content
  and include all published products; unpublished products never leak.
- **Content negotiation**: `Accept: text/markdown` returns markdown, `Accept: text/html`
  returns HTML, and every negotiated response carries `Vary: Accept`.
- **JSON-LD**: valid schema, required fields present, `@id` consistency between product
  and manufacturer.
- **Dusk**: end-to-end RFQ submission flow (one or two scenarios only).
- **Filament**: smoke tests via Livewire testing for each resource.

---

## 11. Deployment

1. Local/CI: `composer install --no-dev`, `npm ci && npm run build`.
2. Deploy via Domainesia Git Deploy Manager or rsync over SSH.
3. Post-deploy: `php artisan migrate --force`, `config:cache`, `route:cache`,
   `view:cache`, `storage:link`, then the custom `php artisan mmg:sitemap` and
   `php artisan mmg:agent-docs` commands (both registered on the scheduler).
4. Cron: Laravel scheduler every minute; queue worker via cron if no persistent worker is
   permitted (shared hosting caveat — verify on the actual plan).
5. Verify: PHP version, symlink behavior, queue processing, LiteSpeed cache purge.

**Known risks to validate on the real hosting account before or during build:**

| Risk | Check | Fallback |
|---|---|---|
| PHP capped below 8.2 | cPanel → MultiPHP Manager | Laravel 12 + Filament 4 |
| `storage:link` blocked by CageFS | deploy a test symlink | `public/uploads` disk |
| No persistent queue worker | check process policy | `queue:work --stop-when-empty` via cron |
| Inode limit hit by image derivatives | count files after pilot upload | reduce thumbnail sizes, move to R2 |
| LiteSpeed cache not purgeable programmatically | test purge after save | `spatie/laravel-responsecache` |

---

## 12. Open Items

| # | Item | Owner | Blocks |
|---|---|---|---|
| 1 | Confirm whether distribution licence / AKL / izin edar numbers may be published, and obtain them per product/principal | company | not blocking — `certifications` is nullable |
| 2 | Confirm PHP 8.4 selectable in cPanel | dev | build start (low risk; customer confirms) |
| 3 | Confirm queue worker policy on the plan | dev | deploy step only |
| 4 | Final product data set / spreadsheet for import | company | content seeding |
| 5 | Phase 2 (blog) approval | company | not blocking phase 1 |

---

## 13. Success Criteria (Phase 1)

- Bilingual catalog browsable and filterable by category and principal, with search.
- Staff can add/edit/publish products, categories, principals, images, and pages via
  `/admin` without developer involvement.
- RFQ submissions captured in admin with status workflow and email notification.
- Public pages render fully server-side and are cacheable.
- Valid `hreflang`, canonical, sitemap, and JSON-LD on all public pages.
- `/llms.txt`, `/llms-full.txt`, and `/katalog.md` served and kept in sync with the DB.
- Product model migrates to a structured `product_specs` table without data loss if the
  flat `specs` JSON is outgrown.
- Site runs within Domainesia shared-hosting resource limits.
