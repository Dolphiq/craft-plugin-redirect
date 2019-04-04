<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace venveo\redirect\services;

use Craft;
use venveo\redirect\records\CatchAllUrl as CatchAllUrlRecord;
use yii\base\Component;

/**
 * Class Redirects service.
 *
 */
class CatchAll extends Component
{

    /**
     * Register a hit to the catch all uri by its uri.
     *
     * @param string $uri
     *
     * @param int|null $siteId
     * @return bool
     */
    public function registerHitByUri(string $uri, $siteId = null): bool
    {

        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        // search the redirect by its uri
        $catchAllURL = CatchAllUrlRecord::findOne([
            'uri' => $uri,
            'siteId' => $siteId,
        ]);

        if (!$catchAllURL) {
            // not found, new one!
            $catchAllURL = new CatchAllUrlRecord();
            $catchAllURL->uri = $uri;
            $catchAllURL->hitCount = 1;
            $catchAllURL->siteId = $siteId;
        } else {
            ++$catchAllURL->hitCount;
        }
        $catchAllURL->save();
        return true;
    }

    public function getLastUrls(int $limit = 100, int $siteId = 0): array
    {

        if ($siteId == 0) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        $query = CatchAllUrlRecord::find()->where([
            'siteId' => $siteId,
        ])->orderBy('dateUpdated DESC')->limit($limit);
        return $query->all();
    }

    public function deleteUrlById(int $id): bool
    {
        if (!$id) {
            return false;
        }
        $siteId = Craft::$app->getSites()->currentSite->id;
        // search the redirect by its id

        // TODO check if the user has rights in the siteId..
        $catchAllURL = CatchAllUrlRecord::findOne($id);

        if (!$catchAllURL) {
            return false;
        }

        $catchAllURL->delete();
        return true;
    }

    public function getUrlByUid(string $uid): CatchAllUrlRecord
    {
        // search the redirect by its uri
        $catchAllurl = CatchAllUrlRecord::findOne([
            'uid' => $uid,
        ]);


        return $catchAllurl;
    }


}
