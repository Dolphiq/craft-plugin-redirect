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

    /**
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionDelete() {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $catchAllId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Plugin::$plugin->catchAll->deleteUrlById($catchAllId);

        return $this->asJson(['success' => true]);
    }


    /**
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionIgnore() {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $catchAllId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Plugin::$plugin->catchAll->ignoreUrlById($catchAllId);

        return $this->asJson(['success' => true]);
    }

    public function actionGetFiltered() {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $data = Craft::$app->request->getBodyParam('data');
        $recordQuery = CatchAllUrl::find()->where(['ignored' => false]);
        $recordQuery->limit = $data['perPage'] ?? 10;

        return $this->asJson(['totalRecords' => $recordQuery->count(), 'rows' => $recordQuery->all()]);
    }
}
