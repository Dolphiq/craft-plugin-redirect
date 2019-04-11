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
        $request = \GuzzleHttp\json_decode(Craft::$app->request->getRawBody(), true);
        $data = $request['data'] ?? [];
        $recordQuery = CatchAllUrl::find()->where(['ignored' => false]);
        $recordQuery->limit = $data['perPage'] ?? 10;

        // Handle sorting...
        if (isset($data['sort']['field'], $data['sort']['type'])) {
            $cols = [];
            $cols[$data['sort']['field']] = $data['sort']['type'] == 'asc' ? SORT_ASC : SORT_DESC;
            $recordQuery->addOrderBy($cols);
        }

        // Handle searching
        if(isset($data['searchTerm']) && $data['searchTerm'] != '') {
            $recordQuery->andFilterWhere(['like', 'uri', $data['searchTerm']]);
        }

        // Process the results
        $rows = [];
        $sites = [];
        foreach($recordQuery->all() as $record) {
            if (!isset($sites[$record->siteId])) {
                $sites[$record->siteId] = Craft::$app->sites->getSiteById($record->siteId)->name;
            }
            $siteName = $sites[$record->siteId];
            $row = $record->toArray();
            $row['siteName'] = $siteName;
            $rows[] = $row;
        }
        return $this->asJson(['totalRecords' => $recordQuery->count(), 'rows' => $rows]);
    }
}
