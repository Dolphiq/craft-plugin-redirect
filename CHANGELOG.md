# Redirect Manager — Changelog

All notable changes to `dolphiq/redirect`. Format based on
[Keep a Changelog](https://keepachangelog.com/); this project follows Craft's major versions.

| Plugin line | Craft | PHP |
|-------------|-------|-----|
| `3.x`       | Craft 5 | 8.2+ |
| `2.x`       | Craft 4 | 8.0.2+ |
| `1.x`       | Craft 3 | 7.x – 8.0 |

See [README → Upgrading](README.md#upgrading) for migration steps between major versions.

---

## 3.0.0 — unreleased (Craft 5)

**Compatibility:** requires Craft CMS `^5.0` and PHP `^8.2`. Existing redirects keep working and are
migrated automatically (a `matchType` is inferred for each). See the upgrade notes for the one
behaviour change (redirects no longer shadow real pages).

### Added
- **Craft 5 support.**
- **Event-based redirect resolution.** Redirects are resolved on demand only when a URL would
  otherwise 404, instead of registering a URL rule per redirect on every request. Resolved matches
  are cached and invalidated automatically when a redirect changes. This is faster and means a
  redirect no longer shadows a real page that exists at the same path.
- **Match types** — `exact`, `prefix`, `wildcard`, `pattern` (`<name>` / `<name:regex>`) and `regex`,
  chosen from a picker on the edit form (and inferred for existing redirects). Adds a new `prefix`
  type, a `*` **wildcard** that is copied into the destination (e.g. `docs/*` → `help/*`), and a raw
  **`regex`** type with `$1`/`$2` backreferences (e.g. `^blog/(\d+)$` → `news/$1`).
- **Enable / disable per redirect** — disabled redirects are kept but don't resolve. The element
  index gains a status dot, a status filter, and a bulk **Set status** action (enable / disable /
  delete a selection).
- **Scheduled redirects** — optional **Start date** / **End date**; a redirect only resolves within
  its window.
- **Priority** — when more than one redirect could match a URL, the lower priority number wins.
- **Automatic redirects on URI change** — when an element's URI changes, a 301 from the old URI to
  the new one is created automatically (reverse redirects are removed to prevent loops). Toggle with
  the `autoCreateRedirectOnUriChange` setting.
- **CSV import / export** on a dedicated **Import / Export** page (`sourceUrl, destinationUrl,
  statusCode`; header and blank/incomplete rows skipped; status code validated; 5 MB upload guard).
- **Privacy-first 404 analytics** (opt-in, off by default): aggregate daily counts, top referrers and
  browser families per missed URL — **no IP addresses or raw user agents are stored**. Configurable
  retention (default 90 days), pruned during garbage collection.
- **"Latest 404s" dashboard widget** showing recently missed URLs and their hit counts.
- **GraphQL** — a `redirects(siteId)` query exposing `sourceUrl`, `destinationUrl`, `statusCode` and
  `hitCount`.
- **Edit-form helpers** — a **"Test this redirect"** box (checks a URL live, without saving), a
  collapsible **pattern helper** with click-to-insert tokens, match-type-aware hints, and a **regex
  helper** (token insertion, live validation with a capture-group count, and `$1`/`$2` chips).
- **16 interface languages** alongside English: Dutch, German, French, Spanish, Italian, Danish,
  Norwegian Bokmål, Swedish, Portuguese (Portugal & Brazil), Polish, Czech, Finnish, Japanese,
  Simplified Chinese and Russian. A `TranslationsTest` guards every locale against the canonical
  string set. (Machine-assisted translations — native review via PR is welcome.)
- **Tooling** — a Codeception unit suite running against a real Craft test app, PHPStan (level 4)
  and ECS.
- **Documentation** — rewrote `RULES.md` (every match type) and `README`, and added a
  `DEVELOPERS.md` reference (settings, service API, events, caching, GraphQL, localization).

### Changed
- **Control-panel redesign to Craft 5 conventions** — two-pane edit form (URLs in the main pane,
  read-only meta in the details sidebar), **Delete** moved into the header action menu, rarely-used
  options collapsed into an **Advanced** section, settings grouped into sections, and a polished
  "Latest 404s" widget, Import/Export and 404-statistics screens.
- Matching supports `<name:regex>` constraints and `*` wildcards and still fills `<name>` destination
  placeholders from the query string — preserved through the move to event-based resolution.
- Renamed the element-index method `tableAttributeHtml()` to `attributeHtml()` (Craft 5).
- Redirect Manager is actively maintained again — removed the "discontinued" / "may become a paid
  add-on" notices.

### Fixed
- No more stray `?` on redirect destinations: the request query string is only appended when there
  actually is one, and joins with `&` when the destination already carries a query string (#138).
- Feed Me mapping screen no longer errors on Craft 4/5: replaced the removed `{% for … if %}` loop
  syntax with `|filter` (#143).
- Deleting a catch-all 404 entry is now scoped to the site (and requires edit access), so a user can
  no longer delete another site's 404 log by ID.
- Element-index hit count and last-hit refresh immediately after a redirect fires, instead of showing
  stale values until a cache clear.
- `actionDeleteRedirect` referenced a missing service method (`deleteRedirectById`); deleting a
  redirect no longer errors.
- CSV import validates status codes (`301/302/307/308`, default `301`) and rejects uploads over 5 MB.
- The settings controller imported a missing `ForbiddenHttpException` (permission guards previously
  referenced an unresolved class).
- The catch-all URLs table is created with the correct name when a database table prefix is
  configured (a stray `%` previously double-applied the prefix).
- `Redirect::__toString()` always returns a string.
- Updated control-panel help/documentation links to the current repository.

### Security
- HTML-encode the source and destination URLs shown in the element index, so a value containing
  markup (e.g. `<catname>`) can no longer inject HTML into the control panel.

---

## 2.0.1 — 2023-01-25 (Craft 4)
### Changed
- Redirect index template extends Craft 4's `_layouts/elementindex` layout.
- Query-string parameters are passed through to the destination on a successful redirect.
### Fixed
- Elements not having an edit link in list view.
- _Thanks to JoshC96._

## 2.0.0 — 2022-06-24 (Craft 4)
### Changed
- Compatible with Craft 4.0 and PHP 8.0/8.1.

---

## 1.x (Craft 3)

Condensed from the release history (2017–2020); see git tags `v1.0.0`–`v1.1.1` for detail.

### 1.1.0 / 1.1.1 — 2020-08
- Feed Me support for importing redirects.
- German and Norwegian Bokmål translations; translation fixes.
- Event so other plugins can hook into redirect handling; general cleanup.

### 1.0.20 – 1.0.24 — 2019
- Fixed an integrity-constraint violation on save (`uid` null) under Craft 3.3+.
- Escaped the `hitAt` column name; raised the source/destination URL limit to 1000 characters.
- On save, removed the redirect for sites where it shouldn't exist (multi-site correctness).
- Verified the catch-all static template exists before redirecting to it.

### 1.0.x — 2017–2018
- Catch-all 404 handling with a configurable template and missed-URL logging.
- Multi-site redirect URLs.
- Named-parameter matching (`<name>`) with substitution into the destination.
- Encoded source URLs so `#` and purely numeric sources match correctly.
- Changed namespaces from `verbb` to `craft`; fixed a PHP < 7.1 error.

## 1.0.0 — 2017-06-01 (Craft 3)
- Initial release: a Redirect element type with exact and `<name>`-parameter matching, catch-all 404
  logging, and multi-site support.
