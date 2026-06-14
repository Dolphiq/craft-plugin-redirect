<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\services;

use Craft;
use craft\helpers\Db;
use dolphiq\redirect\elements\Redirect;
use yii\base\Component;
use yii\caching\TagDependency;
use yii\db\Expression;

/**
 * Class Redirects service.
 *
 */
class Redirects extends Component
{
    /**
     * Cache tag for resolved redirect lookups; invalidated when a redirect changes.
     */
    public const CACHE_TAG = 'dolphiq-redirect';

    // Public Methods
    // =========================================================================

    /**
     * Invalidates all cached redirect resolutions.
     */
    public function invalidateCache(): void
    {
        TagDependency::invalidate(Craft::$app->getCache(), self::CACHE_TAG);
    }

    /**
     * Returns the redirects defined in `config/redirects.php`
     *
     * @return array
     */
    public function getConfigFileRedirects(): array
    {
        $path = Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . 'redirects.php';

        if (file_exists($path)) {
            $routes = require $path;

            if (is_array($routes)) {
                // Check for any site-specific routes
                $siteHandle = Craft::$app->getSites()->currentSite->handle;

                if (
                    isset($routes[$siteHandle]) &&
                    is_array($routes[$siteHandle]) &&
                    !isset($routes[$siteHandle]['route']) &&
                    !isset($routes[$siteHandle]['template'])
                ) {
                    $localizedRoutes = $routes[$siteHandle];
                    unset($routes[$siteHandle]);

                    // Merge them so that the localized routes come first
                    $routes = array_merge($localizedRoutes, $routes);
                }

                return $routes;
            }
        }

        return [];
    }

    /**
     * Returns the routes defined in the CP.
     *
     * @return array
     */
    public function getAllRedirectsForSite($siteId = null): array
    {
        $results = Redirect::find()->andWhere(Db::parseParam('elements_sites.siteId', $siteId))->all();
        return $results;
    }

    /**
     * Returns a site's redirects as plain scalar rows, for GraphQL or export.
     *
     * @return array<int, array{id: int, sourceUrl: string, destinationUrl: string, statusCode: string, hitCount: int}>
     */
    public function getRedirectDataForSite(int $siteId): array
    {
        $rows = [];
        foreach ($this->getAllRedirectsForSite($siteId) as $redirect) {
            $rows[] = [
                'id' => (int)$redirect->id,
                'sourceUrl' => (string)$redirect->sourceUrl,
                'destinationUrl' => (string)$redirect->destinationUrl,
                'statusCode' => (string)$redirect->statusCode,
                'hitCount' => (int)$redirect->hitCount,
            ];
        }

        return $rows;
    }

    /**
     * Resolves a requested URI to a matching redirect for the given site.
     *
     * Matches an exact source URL or a named-parameter pattern (e.g.
     * `category/<catname>/overview.php`), substituting captured parameters into
     * the destination URL. Returns the destination/status/id, or null on no match.
     *
     * @return array{destinationUrl: string, statusCode: string, redirectId: int}|null
     */
    public function resolveForUri(string $uri, int $siteId): ?array
    {
        $uri = trim($uri, '/');
        $cache = Craft::$app->getCache();
        $cacheKey = "dolphiq-redirect:resolve:{$siteId}:{$uri}";

        $cached = $cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached['match'];
        }

        $match = $this->matchUri($uri, $siteId);
        $cache->set($cacheKey, ['match' => $match], null, new TagDependency(['tags' => [self::CACHE_TAG]]));

        return $match;
    }

    /**
     * Matches a (already normalised) URI against the site's redirects.
     *
     * @return array{destinationUrl: string, statusCode: string, redirectId: int}|null
     */
    private function matchUri(string $uri, int $siteId): ?array
    {
        foreach ($this->getAllRedirectsForSite($siteId) as $redirect) {
            $source = trim((string)$redirect->sourceUrl, '/');
            $named = [];
            $wildcards = [];

            if ($source === $uri) {
                // exact match, no parameters
            } elseif (str_contains($source, '<') || str_contains($source, '*')) {
                if (!preg_match($this->sourceUrlToRegex($source), $uri, $matches)) {
                    continue;
                }
                foreach ($matches as $key => $value) {
                    if (!is_string($key)) {
                        continue;
                    }
                    if (str_starts_with($key, 'wild')) {
                        $wildcards[] = $value;
                    } else {
                        $named[$key] = $value;
                    }
                }
            } else {
                continue;
            }

            $destinationUrl = (string)$redirect->destinationUrl;
            foreach ($named as $name => $value) {
                $destinationUrl = str_replace("<$name>", $value, $destinationUrl);
            }
            if ($wildcards !== []) {
                $i = 0;
                $destinationUrl = preg_replace_callback('/\*/', static function() use (&$i, $wildcards) {
                    return $wildcards[$i++] ?? '';
                }, $destinationUrl);
            }

            return [
                'destinationUrl' => $destinationUrl,
                'statusCode' => (string)$redirect->statusCode,
                'redirectId' => (int)$redirect->id,
            ];
        }

        return null;
    }

    /**
     * Turns a source URL pattern into an anchored regex. `<name>` placeholders
     * become named groups matching a single path segment; `*` becomes a wildcard
     * group (`wild0`, `wild1`, …) matching across segments.
     */
    private function sourceUrlToRegex(string $source): string
    {
        $source = trim($source, '/');
        $parts = preg_split('/(<[\w._-]+>|\*)/', $source, -1, PREG_SPLIT_DELIM_CAPTURE);

        $regex = '';
        $wildcardIndex = 0;
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (preg_match('/^<([\w._-]+)>$/', $part, $m)) {
                $regex .= '(?P<' . $m[1] . '>[^/]+)';
            } elseif ($part === '*') {
                $regex .= '(?P<wild' . $wildcardIndex . '>.*)';
                $wildcardIndex++;
            } else {
                $regex .= preg_quote($part, '#');
            }
        }

        return '#^' . $regex . '$#';
    }


    /**
     * Creates a 301 redirect from an old URI to a new one after an element's URI
     * changes. No-ops when the URI is unchanged/empty or a redirect from the old URI
     * already exists. Any reverse redirect (new -> old) is removed to avoid a loop.
     *
     * @return Redirect|null the created redirect, or null when nothing was created
     */
    public function createUriChangeRedirect(string $oldUri, string $newUri, int $siteId): ?Redirect
    {
        $oldUri = trim($oldUri, '/');
        $newUri = trim($newUri, '/');

        if ($oldUri === '' || $newUri === '' || $oldUri === $newUri) {
            return null;
        }

        // Already have a redirect from the old URI on this site? Leave it alone.
        $existing = Redirect::find()
            ->andWhere(['dolphiq_redirects.sourceUrl' => $oldUri])
            ->andWhere(Db::parseParam('elements_sites.siteId', $siteId))
            ->one();
        if ($existing !== null) {
            return null;
        }

        // Remove any reverse redirect (new -> old) so renaming back and forth can't loop.
        $reverse = Redirect::find()
            ->andWhere([
                'dolphiq_redirects.sourceUrl' => $newUri,
                'dolphiq_redirects.destinationUrl' => $oldUri,
            ])
            ->andWhere(Db::parseParam('elements_sites.siteId', $siteId))
            ->one();
        if ($reverse !== null) {
            Craft::$app->getElements()->deleteElement($reverse, true);
        }

        $redirect = new Redirect();
        $redirect->siteId = $siteId;
        $redirect->sourceUrl = $oldUri;
        $redirect->destinationUrl = $newUri;
        $redirect->statusCode = '301';

        if (!Craft::$app->getElements()->saveElement($redirect)) {
            return null;
        }

        return $redirect;
    }

    /**
     * Exports all redirects for a site as CSV (sourceUrl, destinationUrl, statusCode).
     */
    public function exportCsv(int $siteId): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['sourceUrl', 'destinationUrl', 'statusCode']);

        foreach ($this->getAllRedirectsForSite($siteId) as $redirect) {
            fputcsv($handle, [$redirect->sourceUrl, $redirect->destinationUrl, $redirect->statusCode]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Imports redirects from CSV. Columns: sourceUrl, destinationUrl, [statusCode].
     * A header row is detected and skipped; blank/incomplete rows are skipped.
     *
     * @return array{created: int, skipped: int}
     */
    public function importCsv(string $csv, int $siteId): array
    {
        $created = 0;
        $skipped = 0;
        $lines = preg_split('/\r\n|\r|\n/', trim($csv));

        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $columns = str_getcsv($line);

            // skip a header row
            if ($index === 0 && strtolower(trim($columns[0] ?? '')) === 'sourceurl') {
                continue;
            }

            $source = trim($columns[0] ?? '');
            $destination = trim($columns[1] ?? '');
            $statusCode = trim($columns[2] ?? '') ?: '301';

            if ($source === '' || $destination === '') {
                $skipped++;
                continue;
            }

            $redirect = new Redirect();
            $redirect->siteId = $siteId;
            $redirect->sourceUrl = $source;
            $redirect->destinationUrl = $destination;
            $redirect->statusCode = $statusCode;

            if (Craft::$app->getElements()->saveElement($redirect)) {
                $created++;
            } else {
                $skipped++;
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Returns a redirect by its ID.
     *
     * @param int $redirectId
     * @param int|null $siteId
     *
     * @return Redirect|null
     */
    public function getRedirectById(int $redirectId, int $siteId = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($redirectId, Redirect::class, $siteId);
    }


    /**
     * Register a hit to the redirect by its ID.
     *
     * @param int $redirectId
     *
     * @return bool
     */
    public function registerHitById(int $redirectId, $destinationUrl = ''): bool
    {
        // simple update to keep it fast
        if ($redirectId < 1) {
            return false;
        }
        $res = \Yii::$app->db->createCommand()
            ->update(
                '{{%dolphiq_redirects}}',
                [
                    'hitAt' => new Expression('now()'),
                    'hitCount' => new Expression('{{hitCount}} + 1'),
                ],
                ['id' => $redirectId]
            )
            ->execute();

        return true;
    }
}
