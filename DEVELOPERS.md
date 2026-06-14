# Developer reference

API and integration points for **Redirect Manager**. For matching syntax see [RULES.md](RULES.md).

## Settings

Settings are editable in the control panel (**Site redirects → Settings**) and can be overridden
in `config/redirect.php`:

```php
<?php
return [
    'redirectsActive' => true,
    'catchAllActive' => false,
    'catchAllTemplate' => '_errors/404',
    'autoCreateRedirectOnUriChange' => true,
];
```

| Setting | Type | Default | Description |
|---------|------|---------|-------------|
| `redirectsActive` | bool | `true` | Master switch for all redirects. |
| `catchAllActive` | bool | `false` | Log 404s and serve the catch-all template. |
| `catchAllTemplate` | string | `''` | Twig template rendered for unmatched URLs. |
| `autoCreateRedirectOnUriChange` | bool | `true` | Auto-create a 301 when an element's URI changes. |

## Service API

Access the service via the plugin instance:

```php
use dolphiq\redirect\RedirectPlugin;

$redirects = RedirectPlugin::getInstance()->getRedirects();

$match    = $redirects->resolveForUri('old-page', $siteId);   // ?array{destinationUrl,statusCode,redirectId}
$test     = $redirects->testMatch('pattern', 'item/<id>', 'products/<id>', 'item/42'); // {matched,destination,error}
$redirects->createUriChangeRedirect('old', 'new', $siteId);   // auto-redirect (loop-safe)
$csv      = $redirects->exportCsv($siteId);                    // string
$result   = $redirects->importCsv($csv, $siteId);             // ['created' => int, 'skipped' => int]
$redirects->deleteRedirectById($id);                          // bool

$catchAll = RedirectPlugin::getInstance()->getCatchAll();
$missed   = $catchAll->getLastUrls(10, $siteId);              // recent 404s
```

## Match types

Each redirect stores a `matchType` (`exact | prefix | wildcard | pattern`), chosen on the edit form.
If omitted it's inferred from the source syntax (`*` → wildcard, `<…>` → pattern, otherwise exact)
via `Redirect::inferMatchType()`. See [RULES.md](RULES.md) for behavior per type.

## Config-file redirects

Static redirects can be defined in `config/redirects.php` (separate from CP-managed ones), with
optional per-site keys:

```php
<?php
return [
    'old/path' => 'new/path',
    'default' => [/* site-handle-specific routes */],
];
```

Read them with `$redirects->getConfigFileRedirects()`.

## Events

```php
use dolphiq\redirect\controllers\RedirectController;
use dolphiq\redirect\events\RedirectEvent;
use yii\base\Event;

Event::on(
    RedirectController::class,
    RedirectController::EVENT_BEFORE_CATCHALL,
    function (RedirectEvent $event) {
        // $event->uri — the URL about to be logged/handled as a 404
    }
);
```

## 404 analytics (privacy-first)

Opt-in (`analyticsEnabled`, off by default). When on, each 404 records **aggregate** counts only —
**no IP addresses and no raw User-Agents are stored**:

- daily hit counts, top **referrers** (host + path, query string stripped), and coarse **browser
  family** (derived from the UA, e.g. `Chrome`/`Bot`; the raw UA is discarded).
- Tables: `dolphiq_redirect_404_daily`, `…_referrers`, `…_agents` (all cascade-delete with the 404 URL).
- Retained for `analyticsRetentionDays` (default 90); old daily rows are pruned during Craft's garbage collection.
- View per-URL stats at **Registered catch all urls → View**.

Disable/purge: turn `analyticsEnabled` off (collection stops); deleting a missed URL removes its stats;
lower `analyticsRetentionDays` to prune sooner. Service: `RedirectPlugin::getInstance()->getAnalytics()`.

## Caching

Resolved redirect lookups are cached and tagged. The cache is invalidated automatically when a
redirect is saved or deleted. To clear it manually:

```php
RedirectPlugin::getInstance()->getRedirects()->invalidateCache();
```

## GraphQL

```graphql
{
  redirects(siteId: 1) {
    sourceUrl
    destinationUrl
    statusCode
    hitCount
  }
}
```

## Feed Me

When the [Feed Me](https://plugins.craftcms.com/feed-me) plugin is installed, redirects can be
imported as a Feed Me element type.

## Localization

Translations live in `src/translations/<locale>/redirect.php`, each returning an
`English source => translation` array. `src/translations/en/redirect.php` is the **canonical source**:
its keys are the exact English strings used in `Craft::t('redirect', …)` / `|t('redirect')`.

Shipped locales: `en, nl, de, fr, es, it, da, nb, sv, pt, pt-BR, pl, cs, fi, ja, zh-CN, ru`.

Adding or changing strings:

1. Add the string in code with `Craft::t('redirect', '…')` or `'…'|t('redirect')`.
2. Add the same key to **every** locale file (and to `en`).
3. Run the test suite — `TranslationsTest` fails if any locale is missing a key, has an unknown
   key, or has an empty value, so strings can't ship untranslated.

Keep ICU placeholders (`{name}`, `{n, plural, …}`), pattern tokens (`<name>`, `*`) and technical
tokens (`URL`, `CSV`, `301`, …) identical across locales. Translations are machine-assisted; native
review through a pull request is welcome.

---

← Back to the [README](README.md).
