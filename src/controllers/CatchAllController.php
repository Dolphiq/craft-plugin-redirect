<?php

/**
 * @author    Venveo
 * @copyright Copyright (c) 2019 Venveo
 * @link      https://www.venveo.com
 */

namespace venveo\redirect\controllers;

use Craft;
use craft\web\Controller;
use craft\web\Response;
use venveo\redirect\Plugin;
use venveo\redirect\records\CatchAllUrl;

class CatchAllController extends Controller
{

    // Public Methods
    // =========================================================================

    /**
     * Called before displaying the redirect settings index page.
     *
     * @return Response
     * @throws \craft\errors\SiteNotFoundException
     */
    public function actionIndex()
    {
        return $this->renderTemplate('vredirect/catch-all/index', [
            'catchAllQuery' => CatchAllUrl::find()->orderBy('hitCount DESC')
        ]);
    }

    public function actionDelete() {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $catchAllId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Plugin::$plugin->catchAll->deleteUrlById($catchAllId);

        return $this->asJson(['success' => true]);
    }
}
