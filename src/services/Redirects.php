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
use craft\helpers\UrlHelper;
use venveo\redirect\elements\db\RedirectQuery;
use venveo\redirect\elements\Redirect;
use yii\base\Component;
use yii\base\ExitException;
use yii\web\HttpException;

/**
 * Class Redirects service.
 *
 */
class Redirects extends Component
{
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


    public function handle404(HttpException $exception)
    {
        $siteId = Craft::$app->getSites()->currentSite->id;

        $matchedRedirect = null;

        // Just the URI
        $path = Craft::$app->request->fullPath;
        // Path with query params
        $fullPath = ltrim(Craft::$app->request->getUrl(), '/');

        $query = new RedirectQuery(Redirect::class);
        $query->matchingUri = $fullPath;
        $matchedRedirect = $query->one();
        if (!$matchedRedirect) {
            return;
        }
        try {
            $this->doRedirect($matchedRedirect, $fullPath);
        } catch (\Exception $e) {
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

    /**
     * Performs the actual redirect
     *
     * @param Redirect $redirect
     * @param $uri
     */
    public function doRedirect(Redirect $redirect, $uri)
    {
        $destinationUrl = null;
        if ($redirect->type === Redirect::TYPE_STATIC) {
            $processedUrl = $redirect->destinationUrl;
        } elseif ($redirect->type === Redirect::TYPE_DYNAMIC) {
            $sourceUrl = $redirect->sourceUrl;
            if (!starts_with($redirect->sourceUrl, '/')) {
                $sourceUrl = '/'.$sourceUrl;
            }
            if (!ends_with($redirect->sourceUrl, '/')) {
                $sourceUrl .= '/';
            }
            // Ignore case
            $sourceUrl .= 'i';
            $processedUrl = preg_replace($sourceUrl, $redirect->destinationUrl, $uri);
        } else {
            return;
        }

        // Saving elements takes a while - we're going to do our incrementing
        // directly on the record instead.
        /** @var \venveo\redirect\records\Redirect $redirect */
        $redirectRecord = \venveo\redirect\records\Redirect::findOne($redirect->id);

        if ($redirectRecord) {
            $redirectRecord->hitCount++;
            $redirectRecord->hitAt = Db::prepareDateForDb(new \DateTime());
            $redirectRecord->save();
        }

        Craft::$app->response->redirect(UrlHelper::url($processedUrl), $redirect->statusCode)->send();

        try {
            Craft::$app->end();
        } catch (ExitException $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }
    }
}
