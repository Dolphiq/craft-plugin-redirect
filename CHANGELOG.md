# Redirect Changelog

## 3.0.0 - unreleased
### Added
- **Craft 5 support.** Requires Craft CMS `^5.0` and PHP `^8.2`.
- **Event-based redirect resolution.** Redirects are now resolved on demand only when a URL would otherwise 404, instead of registering a URL rule per redirect on every request. Resolved matches are cached and invalidated automatically when a redirect changes. This is faster and means a redirect no longer shadows a real page that exists at the same path.
- **Automatic redirects on URI change.** When an element's URI changes, a 301 from the old URI to the new one is created automatically (reverse redirects are removed to prevent loops). Toggle with the `autoCreateRedirectOnUriChange` setting.
- **CSV import/export.** Bulk import redirects from CSV (`sourceUrl, destinationUrl, statusCode`; header and blank/incomplete rows are skipped) and export all redirects for a site.
- **Wildcard source URLs.** A `*` in a source URL matches across path segments and is substituted into the matching `*` in the destination (e.g. `docs/*` → `help/*`), alongside the existing `<name>` parameter patterns.
- **"Latest 404s" dashboard widget** showing the most recently missed URLs and their hit counts.
- **GraphQL support.** A `redirects(siteId)` query exposes `sourceUrl`, `destinationUrl`, `statusCode` and `hitCount`.
- Documentation: rewrote `RULES.md` to cover every match type and added a `DEVELOPERS.md` reference (settings, service API, events, caching, GraphQL).

### Changed
- Renamed the element index method `tableAttributeHtml()` to `attributeHtml()` per Craft 5.
- Matching now supports `<name:regex>` constraints and `*` wildcards, and still fills `<name>` destination placeholders from the query string — preserved through the move to event-based resolution.

### Fixed
- `actionDeleteRedirect` referenced a non-existent service method (`deleteRedirectById`); the method now exists, so deleting a redirect no longer errors.
- CSV import now validates status codes (`301/302/307/308`, default `301`) and rejects uploads over 5 MB.
- Deleting a catch-all 404 entry is now scoped to the site (and requires edit access to it), so a user can no longer delete another site's 404 log by ID.
- Element-index hit count and last-hit now refresh immediately after a redirect fires (caches are invalidated on hit) instead of showing stale values until a cache clear.
- Imported the missing `ForbiddenHttpException` class in the settings controller (the permission guards previously referenced an unresolved class).

## Unreleased
### Security
- HTML-encode the source and destination URLs shown in the redirect element index, so a URL value containing markup (e.g. `<catname>`) can no longer inject HTML into the control panel.

### Added
- Codeception test suite (unit) running against a real Craft test app, plus PHPStan (level 4) and ECS tooling.

### Fixed
- `Redirect::__toString()` now always returns a string.
- The catch-all URLs table is now created with the correct name when a database table prefix is configured (a stray `%` in the install migration previously double-applied the prefix).
- Updated control-panel help/documentation links to the current repository (they pointed at the old `craft3-plugin-redirect` repo).

### Changed
- Require PHP `^8.0.2`; fixed the malformed Composer platform constraint so the plugin installs cleanly on PHP 8.1–8.3.
- Rewrote the README: clearer feature overview, installation and usage instructions, and refreshed control-panel screenshots.
- Redirect Manager is actively maintained again — removed the "discontinued" notice and the "may become a paid add-on" note.

## 2.0.1 - 2023-01-25
### Fixed
- Fix elements not having an edit link on list view
### Changed
- Set redirects template to extend new craft 4 "_layouts/elementindex" layout
- Allow for query parameters to be passed through to successful redirects
- Special thanks to JoshC96 

## 2.0.0 - 2022-06-24
### Changed
- Make compatible with Craft 4.0
- Make compatible php 8.0

## 1.0.0 - 2017-06-01
- Initial release.
