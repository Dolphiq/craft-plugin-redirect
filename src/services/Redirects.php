<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace venveo\redirect\services;

use Craft;
use craft\helpers\Db;
use venveo\redirect\elements\db\RedirectQuery;
use venveo\redirect\elements\Redirect;
use venveo\redirect\Plugin;
use yii\base\Component;
use yii\web\HttpException;

/**
 * Class Redirects service.
 *
 */
class Redirects extends Component
{

    // Public Methods
    // =========================================================================

    /**
     * Returns the redirects defined in `config/redirects.php`
     *
     * @return array
     */
    public function getConfigFileRedirects(): array
    {
        $path = Craft::$app->getPath()->getConfigPath().DIRECTORY_SEPARATOR.'redirects.php';

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
                    'hitAt' => new \yii\db\Expression('now()'),
                    'hitCount' => new \yii\db\Expression('{{hitCount}} + 1'),
                ],
                ['id' => $redirectId]
            )
            ->execute();

        return true;
    }

    public function handle404(HttpException $exception): void
    {
        $request = Craft::$app->request;
        $siteId = Craft::$app->getSites()->currentSite->id;
        $allRedirects = Plugin::$plugin->getRedirects()->getAllRedirectsForSite($siteId);

        $matchedRedirect = null;

        // Just the URI
        $path = Craft::$app->request->fullPath;
        // Path with query params
        $fullPath = Craft::$app->request->getUrl();

        $query = new RedirectQuery(Redirect::class);


        var_dump($allRedirects);
        die();
        foreach ($allRedirects as $redirect) {

        }
        if (!$matchedRedirect) {
            return;
        }
        // 404?
//
//        if ($settings->catchAllActive) {
//            $event->rules['<all:.+>'] = [
//                'route' => 'vredirect/redirect/index',
//                'params' => [
//                    'sourceUrl' => '',
//                    'destinationUrl' => '/404/',
//                    'statusCode' => 404,
//                    'redirectId' => null
//                ]
//            ];
//        }
    }
}
