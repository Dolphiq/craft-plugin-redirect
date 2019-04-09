<?php

/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect;

use Craft;
use craft\base\Element;
use craft\base\Plugin as BasePlugin;
use craft\events\ExceptionEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\UrlHelper;
use craft\services\Gc;
use craft\services\UserPermissions;
use craft\web\ErrorHandler;
use craft\web\UrlManager;
use venveo\redirect\elements\FeedMeRedirect;
use venveo\redirect\models\Settings;
use venveo\redirect\services\CatchAll;
use venveo\redirect\services\Redirects;
use verbb\feedme\events\RegisterFeedMeElementsEvent;
use verbb\feedme\services\Elements;
use yii\base\Event;
use yii\base\ModelEvent;


/**
 * @property mixed $settingsResponse
 * @property Redirects $redirects
 * @property array $cpNavItem
 * @property CatchAll $catchAll
 * @property mixed _redirectsService
 * @property mixed _catchAllService
 */
class Plugin extends BasePlugin
{
    /** @var self $plugin */
    public static $plugin;

    protected $_redirectsService;
    protected $_catchAllService;

    /**
     * Returns the Redirects service.
     *
     * @return Redirects The Redirects service
     */
    public function getRedirects(): Redirects
    {
        if ($this->_redirectsService == null) {
            $this->_redirectsService = new Redirects();
        }
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
            'label' => Craft::t('vredirect', 'Site Redirects'),
            'fontIcon' => 'share',
            'subnav' => [
//                'dashboard' => [
//                    'label' => 'Dashboard',
//                    'url' => UrlHelper::cpUrl('redirect/dashboard')
//                ],
                'redirects' => [
                    'label' => Craft::t('vredirect', 'Redirects'),
                    'url' => 'redirect/redirects'
                ],
                'catch-all' => [
                    'label' => Craft::t('vredirect', 'Registered 404s'),
                    'url' => 'redirect/catch-all'
                ]
            ]
        ];
    }


    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'vredirect/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    private function registerCpRoutes() {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'redirect' => ['template' => 'vredirect/index'],

                'redirect/catch-all' => 'vredirect/catch-all/index',

                'redirect/dashboard' => 'vredirect/dashboard/index',

                'redirect/redirects' => 'vredirect/redirects/index',
                'redirect/redirects/new' => 'vredirect/redirects/edit-redirect',
                'redirect/redirects/<redirectId:\d+>' => 'vredirect/redirects/edit-redirect',
            ]);
        });
    }

    private function registerFeedMeElement() {
        if (Craft::$app->plugins->isPluginEnabled('feed-me')) {
            Event::on(Elements::class, Elements::EVENT_REGISTER_FEED_ME_ELEMENTS, function(RegisterFeedMeElementsEvent $e) {
                $e->elements[] = FeedMeRedirect::class;
            });
        }
    }
//    // TODO: Finish this
//    private function registerPermissions() {
//        $sitePermissions = [];
//        $sites = Craft::$app->getSites()->getAllSites();
//        foreach($sites as $site) {
//            $sitePermissions
//        }
//
//
//        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
//            $event->permissions[\Craft::t('vredirect', 'Redirects')] = [
//                'create' => [
//                    'label' => \Craft::t('vredirect', 'Create Redirects'),
//                    'nested' => [
//
//                    ]
//                ]
//            ];
//        });
//    }


    public function init()
    {
        parent::init();
        self::$plugin = $this;
        $settings = self::$plugin->getSettings();

        $this->registerCpRoutes();
        $this->registerFeedMeElement();

        // Remove our soft-deleted redirects when Craft is ready
        Event::on(Gc::class, Gc::EVENT_RUN, function() {
            Craft::$app->gc->hardDelete('{{%dolphiq_redirects}}');
        });

        if (!$settings->redirectsActive) {
            // Return early.
            return;
        }

        // Start lookin' for some 404s!
        Event::on(
            ErrorHandler::class,
            ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            static function (ExceptionEvent $event) {
                $request = Craft::$app->request;
                // We don't care about requests that aren't on our site frontend
                if(!$request->getIsSiteRequest() || $request->getIsLivePreview()) {
                    return;
                }
                $exception = $event->exception;

                if ($exception instanceof \Twig\Error\RuntimeError &&
                    ($previousException = $exception->getPrevious()) !== null) {
                    $exception = $previousException;
                }

                if ($exception instanceof \yii\web\HttpException && $exception->statusCode === 404) {
                    self::$plugin->redirects->handle404($exception);
                }
            }
        );
    }
}
