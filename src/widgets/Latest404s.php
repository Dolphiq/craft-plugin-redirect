<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\widgets;

use Craft;
use craft\base\Widget;
use dolphiq\redirect\RedirectPlugin;

/**
 * Dashboard widget listing the most recently registered 404 (catch-all) URLs.
 */
class Latest404s extends Widget
{
    /**
     * @var int|null how many missed URLs to show
     */
    public ?int $limit = 10;

    public static function displayName(): string
    {
        return Craft::t('redirect', 'Latest 404s');
    }

    public static function icon(): ?string
    {
        return null;
    }

    public function getTitle(): ?string
    {
        return Craft::t('redirect', 'Latest 404s');
    }

    public function getBodyHtml(): ?string
    {
        $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        $urls = RedirectPlugin::$plugin->getCatchAll()->getLastUrls($this->limit ?? 10, $siteId);

        return Craft::$app->getView()->renderTemplate('redirect/_widgets/latest404s', [
            'urls' => $urls,
        ]);
    }
}
