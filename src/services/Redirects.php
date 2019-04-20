<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\services;

use Craft;
use dolphiq\redirect\elements\Redirect;
use yii\base\Component;
use craft\helpers\Db;

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
}
