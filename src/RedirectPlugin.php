<?php

/**
 * Craft Redirect plugin
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect;

use Craft;
use dolphiq\redirect\elements\FeedMeRedirect;
use dolphiq\redirect\models\Settings;
use dolphiq\redirect\services\Redirects;
use dolphiq\redirect\services\CatchAll;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;


class RedirectPlugin extends \craft\base\Plugin
{
    public static $plugin;

    private $_redirectsService;
    private $_catchAallService;

    /**
     * Returns the Redirects service.
     *
     * @return \dolphiq\redirect\services\Redirects The Redirects service
     */
    public function getRedirects()
    {
        if ($this->_redirectsService == null) {
            $this->_redirectsService = new Redirects();
        }
        /** @var WebApplication|ConsoleApplication $this */
        return $this->_redirectsService;
    }

    public function getCatchAll()
    {
        if ($this->_catchAallService == null) {
            $this->_catchAallService = new CatchAll();
        }
        /** @var WebApplication|ConsoleApplication $this */
        return $this->_catchAallService;
    }

    public $hasCpSection = true;
    public $hasCpSettings = true;

    // table schema version
    public $schemaVersion = '1.0.5';

    /*
    *
    *  The Craft plugin documentation points to the EVENT_REGISTER_CP_NAV_ITEMS event to register navigation items.
    *  The getCpNavItem was found in the source and will check the user privilages already.
    *
    */
    public function getCpNavItem()
    {
        return [
            'url' => 'redirect',
            'label' => Craft::t('redirect', 'Site redirects'),
            'fontIcon' => 'share'
        ];
    }


    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * Return the settings response (if some one clicks on the settings/plugin icon)
     *
     */

    public function getSettingsResponse()
    {
        $url = \craft\helpers\UrlHelper::cpUrl('settings/redirect/settings');
        return \Craft::$app->controller->redirect($url);
    }

    /**
     * Register CP URL rules
     *
     * @param RegisterUrlRulesEvent $event
     */

    public function registerCpUrlRules(RegisterUrlRulesEvent $event)
    {
        // only register CP URLs if the user is logged in
        if (!\Craft::$app->user->identity)
            return;
        $rules = [
            // register routes for the sub nav
            'redirect' => 'redirect/settings/',
            'redirect/settings' => 'redirect/settings/settings',
            'redirect/redirects' => 'redirect/settings/redirects',
            'redirect/registered-catch-all-urls' => 'redirect/settings/registered-catch-all-urls',
            'redirect/new' => 'redirect/settings/edit-redirect',
            'redirect/<redirectId:\d+>' => 'redirect/settings/edit-redirect',

            // register routes for the settings tab

            'settings/redirect' => [
                'route' => 'redirect/settings',
                'params' => ['source' => 'CpSettings']],
            'settings/redirect/settings' => [
                'route' => 'redirect/settings/settings',
                'params' => ['source' => 'CpSettings']],
            'settings/redirect/redirects' => [
                'route' => 'redirect/settings/redirects',
                'params' => ['source' => 'CpSettings']],
            'settings/redirect/registered-catch-all-urls' => [
                'route' => 'redirect/settings/registered-catch-all-urls',
                'params' => ['source' => 'CpSettings']],
            'settings/redirect/new' => [
                'route' => 'redirect/settings/edit-redirect',
                'params' => ['source' => 'CpSettings']],
            'settings/redirect/<redirectId:\d+>' => [
                'route' => 'redirect/settings/edit-redirect',
                'params' => ['source' => 'CpSettings']],
        ];
        $event->rules = array_merge($event->rules, $rules);
    }

    /**
     * Registers our custom feed import logic if feed-me is enabled. Also note, we're checking for craft\feedme
     */
    private function registerFeedMeElement()
    {
        if (Craft::$app->plugins->isPluginEnabled('feed-me') && class_exists(\craft\feedme\Plugin::class)) {
            Event::on(\craft\feedme\services\Elements::class, \craft\feedme\services\Elements::EVENT_REGISTER_FEED_ME_ELEMENTS, function (\craft\feedme\events\RegisterFeedMeElementsEvent $e) {
                $e->elements[] = FeedMeRedirect::class;
            });
        }
    }


    public function init()
    {
        parent::init();

        self::$plugin = $this;

        // only register CP URLs if the user is logged in
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, [$this, 'registerCpUrlRules']);

        // Register FeedMe ElementType
       $this->registerFeedMeElement();

        $settings = RedirectPlugin::$plugin->getSettings();
        if ($settings->redirectsActive) {
            Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function (RegisterUrlRulesEvent $event) use ($settings) {

                // get rules from db!
                // please only if we are on the site and the redirects are active in the plugin settings
                if ($settings->redirectsActive) {
                    $siteId = Craft::$app->getSites()->currentSite->id;
                    $allRedirects = self::$plugin->getRedirects()->getAllRedirectsForSite($siteId);

                    foreach ($allRedirects as $redirect) {
                        $sourceUrl = $redirect['sourceUrl'];
                        if (strpos($redirect['sourceUrl'], '#') !== false) {
                            $sourceUrl = current(explode('#', $sourceUrl));
                        }
                        if (is_numeric($sourceUrl)) {
                            $sourceUrl = '/' . $sourceUrl . '/';
                        }

                        $event->rules[$sourceUrl] = [
                            'route' => 'redirect/redirect/index',
                            'params' => [
                                'sourceUrl' => $redirect['sourceUrl'],
                                'destinationUrl' => $redirect['destinationUrl'],
                                'statusCode' => $redirect['statusCode'],
                                'redirectId' => $redirect['id']
                            ]
                        ];
                    }
                }
                // 404?

                if ($settings->catchAllActive) {
                    $event->rules['<all:.+>'] = [
                        'route' => 'redirect/redirect/index',
                        'params' => [
                            'sourceUrl' => '',
                            'destinationUrl' => '/404/',
                            'statusCode' => 404,
                            'redirectId' => null
                        ]
                    ];
                }

            });
        }

        Craft::info('dolphiq/redirect Plugin plugin loaded', __METHOD__);
    }
}
