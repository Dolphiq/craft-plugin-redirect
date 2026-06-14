# Redirect Changelog

## 3.0.0 - unreleased
### Added
- **Craft 5 support.** Requires Craft CMS `^5.0` and PHP `^8.2`.

### Changed
- Renamed the element index method `tableAttributeHtml()` to `attributeHtml()` per Craft 5.

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
