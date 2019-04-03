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
use craft\events\ExceptionEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\web\ErrorHandler;
use craft\web\UrlManager;
use HttpException;
use venveo\redirect\elements\FeedMeRedirect;
use venveo\redirect\models\Settings;
use venveo\redirect\services\CatchAll;
use venveo\redirect\services\Redirects;
use verbb\feedme\events\RegisterFeedMeElementsEvent;
use verbb\feedme\services\Elements;
use yii\base\Event;


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
        $url = UrlHelper::cpUrl('settings/redirect/settings');
        return Craft::$app->controller->redirect($url);
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

        // Register control panel URLs
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, [$this, 'registerCpUrlRules']);

        // Register FeedMe ElementType
        if (Craft::$app->plugins->isPluginEnabled('feed-me')) {
            Event::on(Elements::class, Elements::EVENT_REGISTER_FEED_ME_ELEMENTS, function(RegisterFeedMeElementsEvent $e) {
                $e->elements[] = FeedMeRedirect::class;
            });
        }

        $settings = self::$plugin->getSettings();
        if (!$settings->redirectsActive) {
            // Return early.
            return;
        }

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
