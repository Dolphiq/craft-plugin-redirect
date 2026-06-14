<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use dolphiq\redirect\events\RedirectEvent;
use dolphiq\redirect\helpers\UrlRule;
use dolphiq\redirect\RedirectPlugin;
use yii\web\NotFoundHttpException;

class RedirectController extends Controller
{
    private $_sourceRouteParams = [];
    protected array|int|bool $allowAnonymous = ['index'];

    public const FILE_EXTENSIONS = [
        'gif',
        'jpg',
        'jpeg',
        'png',
        'tiff',
        'svg',
        'ttf',
        'woff',
        'woff2',
        'otf',
        'ico',
        'js',
        'css',
    ];

    public const EVENT_BEFORE_CATCHALL = 'beforeCatchall';

    public function actionIndex()
    {
        // first check if there is a static template.. it should be rendered by the Templates controller.
        /* @see \craft\controllers\TemplatesController */
        $tplController = craft::$app->createControllerByID('templates');
        if ($tplController) {
            $tplcView = $tplController->getView();
            $tplPath = Craft::$app->request->getFullPath();
            if ($tplcView->doesTemplateExist($tplPath)) {
                return $tplController->actionRender($tplPath);
            }
        }

        $request = Craft::$app->getRequest();
        $siteId = Craft::$app->getSites()->getCurrentSite()->id;

        // Resolve a matching redirect on demand (exact or parameter pattern, cached).
        $match = RedirectPlugin::$plugin->getRedirects()->resolveForUri($request->getFullPath(), $siteId);

        if ($match !== null) {
            $destinationUrl = $match['destinationUrl'];

            // add the site domain if the destination is not an absolute URL
            if (strpos($destinationUrl, '://') === false) {
                $destinationUrl = UrlHelper::baseUrl() . ltrim($destinationUrl, '/');
            }

            $queryString = $request->getQueryStringWithoutPath();
            if ($queryString !== '') {
                $destinationUrl .= '?' . $queryString;
            }

            if (!empty($match['redirectId'])) {
                RedirectPlugin::$plugin->getRedirects()->registerHitById($match['redirectId'], $destinationUrl);
            }

            return $this->redirect($destinationUrl, (int)$match['statusCode']);
        }

        // No redirect matched — this is a genuine 404.
        $uri = current(explode('?', $_SERVER['REQUEST_URI']));
        $uriParts = pathinfo($uri);

        Craft::$app->response->statusCode = 404;

        // known static file types are left to Craft's normal 404 handling
        if (
            is_array($uriParts) &&
            isset($uriParts['extension']) &&
            $uriParts['extension'] !== '' &&
            in_array($uriParts['extension'], self::FILE_EXTENSIONS)
        ) {
            throw new NotFoundHttpException(Craft::t('yii', 'Page not found.'), 404);
        }

        $settings = RedirectPlugin::$plugin->getSettings();

        // catch-all logging/template is opt-in; otherwise let it be a normal 404
        if (!$settings->catchAllActive) {
            throw new NotFoundHttpException(Craft::t('yii', 'Page not found.'), 404);
        }

        $event = new RedirectEvent([
            'uri' => $uri,
        ]);
        $this->trigger(self::EVENT_BEFORE_CATCHALL, $event);

        RedirectPlugin::$plugin->getCatchAll()->registerHitByUri($uri);

        if ($settings->catchAllTemplate != '') {
            return $this->renderTemplate($settings->catchAllTemplate, ['request' => [
                'requestUri' => $_SERVER['REQUEST_URI'],
                'uriParts' => $uriParts,
            ]]);
        }

        return 'This page does not exist.';
    }
}
