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
$redirects->createUriChangeRedirect('old', 'new', $siteId);   // auto-redirect (loop-safe)
$csv      = $redirects->exportCsv($siteId);                    // string
$result   = $redirects->importCsv($csv, $siteId);             // ['created' => int, 'skipped' => int]
$redirects->deleteRedirectById($id);                          // bool

$catchAll = RedirectPlugin::getInstance()->getCatchAll();
$missed   = $catchAll->getLastUrls(10, $siteId);              // recent 404s
```

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

---

← Back to the [README](README.md).
