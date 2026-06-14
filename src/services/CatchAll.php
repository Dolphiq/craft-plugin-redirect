<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\services;

use Craft;
use dolphiq\redirect\records\CatchAllUrl as CatchAllUrlRecord;
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
     * @return bool
     */
    public function registerHitByUri(string $uri, int $siteId = 0): bool
    {
        if ($siteId == 0) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }
        // search the redirect by its uri
        $catchAllurl = CatchAllUrlRecord::findOne([
            'uri' => $uri,
            'siteId' => $siteId,
        ]);

        if ($catchAllurl == null) {
            // not found, new one!
            $catchAllurl = new CatchAllUrlRecord();
            $catchAllurl->uri = $uri;
            $catchAllurl->hitCount = 1;
            $catchAllurl->siteId = $siteId;
        } else {
            $catchAllurl->hitCount = $catchAllurl->hitCount + 1;
        }
        $catchAllurl->save();
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

    public function deleteUrlById(int $id, ?int $siteId = null): bool
    {
        if (!$id) {
            return false;
        }

        if ($siteId === null) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        // Scope the lookup to the site so a user can't delete another site's 404 log.
        $catchAllurl = CatchAllUrlRecord::findOne([
            'id' => $id,
            'siteId' => $siteId,
        ]);

        if ($catchAllurl == null) {
            return false;
        }

        $catchAllurl->delete();
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
