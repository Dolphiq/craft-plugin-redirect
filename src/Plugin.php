<?php

/**
 * Craft Redirect plugin
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace venveo\redirect;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use venveo\redirect\elements\FeedMeRedirect;
use venveo\redirect\models\Settings;
use venveo\redirect\services\CatchAll;
use venveo\redirect\services\Redirects;
use verbb\feedme\events\RegisterFeedMeElementsEvent;
use verbb\feedme\services\Elements;
use yii\base\Event;


class Plugin extends BasePlugin
{
    /** @var self $plugin */
    public static $plugin;

    /**
     * Returns the Redirects service.
     *
     * @return \venveo\redirect\services\Redirects The Redirects service
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
        if ($this->_catchAllService == null) {
            $this->_catchAllService = new CatchAll();
        }

        return $this->_catchAllService;
    }

    public $hasCpSection = true;
    public $hasCpSettings = true;

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
            'label' => Craft::t('vredirect', 'Site redirects'),
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
        $rules = [
            // register routes for the sub nav
            'redirect' => 'vredirect/settings/',
            'redirect/settings' => 'vredirect/settings/settings',
            'redirect/redirects' => 'vredirect/settings/redirects',
            'redirect/registered-catch-all-urls' => 'vredirect/settings/registered-catch-all-urls',
            'redirect/new' => 'vredirect/settings/edit-redirect',
            'redirect/<redirectId:\d+>' => 'vredirect/settings/edit-redirect',

            // register routes for the settings tab
            'settings/vredirect' => [
                'route' => 'vredirect/settings',
                'params' => ['source' => 'CpSettings']
            ],
            'settings/vredirect/settings' => [
                'route' => 'vredirect/settings/settings',
                'params' => ['source' => 'CpSettings']
            ],
            'settings/vredirect/redirects' => [
                'route' => 'vredirect/settings/redirects',
                'params' => ['source' => 'CpSettings']
            ],
            'settings/vredirect/registered-catch-all-urls' => [
                'route' => 'vredirect/settings/registered-catch-all-urls',
                'params' => ['source' => 'CpSettings']
            ],
            'settings/vredirect/new' => [
                'route' => 'vredirect/settings/edit-redirect',
                'params' => ['source' => 'CpSettings']
            ],
            'settings/vredirect/<redirectId:\d+>' => [
                'route' => 'vredirect/settings/edit-redirect',
                'params' => ['source' => 'CpSettings']
            ],
        ];
        $event->rules = array_merge($event->rules, $rules);
    }


    public function init()
    {
        parent::init();

        self::$plugin = $this;

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, [$this, 'registerCpUrlRules']);

        // Register FeedMe ElementType
        if (Craft::$app->plugins->isPluginEnabled('feed-me')) {
            Event::on(Elements::class, Elements::EVENT_REGISTER_FEED_ME_ELEMENTS, function(RegisterFeedMeElementsEvent $e) {
                $e->elements[] = FeedMeRedirect::class;
            });
        }

        $settings = self::$plugin->getSettings();
        if ($settings->redirectsActive) {
            Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) use ($settings) {

                // get rules from db!
                // please only if we are on the site and the redirects are active in the plugin settings
                if ($settings->redirectsActive) {
                    $siteId = Craft::$app->getSites()->currentSite->id;
                    $allRedirects = self::$plugin->getRedirects()->getAllRedirectsForSite($siteId);
                    foreach ($allRedirects as $redirect) {
                        $event->rules[$redirect['sourceUrl']] = [
                            'route' => 'vredirect/redirect/index',
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
                        'route' => 'vredirect/redirect/index',
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
