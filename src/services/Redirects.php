<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\services;

use Craft;
use craft\db\Query;
use craft\helpers\Json;
use dolphiq\redirect\records\Redirect as RedirectRecord;
use dolphiq\redirect\models\Redirect;
use yii\web\NotFoundHttpException;
use yii\base\Component;

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
    public function getAllRedirects(): array
    {
        $results = (new Query())
            ->select(['id', 'sourceUrl', 'destinationUrl', 'statusCode', 'hitCount', 'hitAt'])
            ->from(['{{%dolphiq_redirects}}'])
            ->where([
                'or',
                ['siteId' => null],
                ['siteId' => Craft::$app->getSites()->currentSite->id]
            ])
            ->all();

        if (empty($results)) {
            return [];
        }

        return $results;
    }

    /**
     * Returns a redirect by its ID.
     *
     * @param int $redirectId
     *
     * @return RedirectRecord|null
     */
    public function getRedirectById(int $redirectId)
    {
        if (!$redirectId) {
            return null;
        }


        $record = RedirectRecord::findOne($redirectId);
        if (!$record) {
            throw new Exception('Invalid record ID: ' . $redirectId);
        }
        return $record;
    }

    /**
     * Saves a redirect.
     *
     * @param Redirect $redirect        The redirect to be saved
     * @param bool     $runValidation   Whether the redirect should be validated
     *
     * @return bool Whether the redirect was saved successfully
     * @throws NotFoundException if $redirect->id is invalid
     */
    public function saveRedirect(Redirect $redirect, bool $runValidation = true): bool
    {
        if ($runValidation && !$redirect->validate()) {
            Craft::info('Redirect not saved due to validation error.', __METHOD__);
            return false;
        }
        $isNewRedirect = !$redirect->id;

        if (!$isNewRedirect) {
            $redirectRecord = RedirectRecord::findOne($redirect->id);
            if (!$redirectRecord) {
                throw new NotFoundHttpException("No redirect exists with the ID '{$redirect->id}'");
            }
        } else {
            $redirectRecord = new RedirectRecord();
        }
        $redirectRecord->sourceUrl = $redirect->sourceUrl;
        $redirectRecord->destinationUrl = $redirect->destinationUrl;
        $redirectRecord->statusCode = $redirect->statusCode;

        // store to db
        $redirectRecord->save();

        return true;
    }

    /**
     * Deletes a redirect by its ID.
     *
     * @param int $redirectId
     *
     * @return bool
     */
    public function deleteRedirectById(int $redirectId): bool
    {
        $redirectRecord = RedirectRecord::findOne($redirectId);

        if (!$redirectRecord) {
            return true;
        }
        $redirectRecord->delete();
        return true;
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
        if($redirectId<1) {
          return false;
        }
        $res = \Yii::$app->db->createCommand()
          ->update(
            'dolphiq_redirects',
            [
              'hitAt'=>new \yii\db\Expression('now()'),
              'hitCount'=>new \yii\db\Expression('hitCount + 1'),
            ],
            ['id'=>$redirectId]
          )
          ->execute();

        return true;
    }

}
