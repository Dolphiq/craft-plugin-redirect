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
use craft\base\Plugin;
use dolphiq\redirect\models\Settings;
use dolphiq\redirect\services\Redirects;

use craft\events\RegisterCpNavItemsEvent;
use craft\web\twig\variables\Cp;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

use dolphiq\redirect\controllers\RedirectController;


use craft\web\UrlRuleInterface;
use craft\base\Object;

class RedirectPlugin extends \craft\base\Plugin
{
    public static $plugin;

    private $_redirectsService;

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

    public $controllerMap = [
     // 'redirect' => RedirectController::class,
    ];

    public $hasCpSection = true;
    public $hasCpSettings = true;

    // table schema version
    public $schemaVersion = '1.0.3';

    /*
    *
    *  The Craft plugin documentation points to the EVENT_REGISTER_CP_NAV_ITEMS event to register navigation items.
    *  The getCpNavItem was found in the source and will check the user privilages already.
    *
    */
    public function getCpNavItem()
    {
        return [
        'url'=> 'redirect',
        'label'=>Craft::t('redirect', 'Site redirects'),
        'icon' => 'share-alt'
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
        $url = \craft\helpers\UrlHelper::cpUrl('settings/redirect');
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
            'redirect' => 'redirect/settings',
            'redirect/new' => 'redirect/settings/edit-redirect',
            'redirect/<redirectId:\d+>' => 'redirect/settings/edit-redirect',

            // register routes for the settings tab
            'settings/redirect' => [
                'route'=>'redirect/settings',
                'params'=>['source' => 'CpSettings']],
            'settings/redirect/settings' => [
                'route'=>'redirect/settings/settings',
                'params'=>['source' => 'CpSettings']],
            'settings/redirect/new' => [
                'route'=>'redirect/settings/edit-redirect',
                'params'=>['source' => 'CpSettings']],
            'settings/redirect/<redirectId:\d+>' => [
                'route'=>'redirect/settings/edit-redirect',
                'params'=>['source' => 'CpSettings']],
        ];
        $event->rules = array_merge($event->rules, $rules);
    }


    public function init()
    {
        parent::init();

        self::$plugin = $this;

        // only register CP URLs if the user is logged in
        if (\Craft::$app->user->identity) {
            Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, [$this, 'registerCpUrlRules']);
        }

        $settings = RedirectPlugin::$plugin->getSettings();
        if ($settings->redirectsActive) {
            Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function (RegisterUrlRulesEvent $event) {

            // get rules from db!
            // please only if we are on the site
            $siteId = Craft::$app->getSites()->currentSite->id;
            $allRedirects = self::$plugin->getRedirects()->getAllRedirectsForSite($siteId);
                foreach ($allRedirects as $redirect) {
                    $event->rules[$redirect['sourceUrl']] = [
                'route'=>'redirect/redirect/index',
                'params'=>[
                  'sourceUrl' => $redirect['sourceUrl'],
                  'destinationUrl' => $redirect['destinationUrl'],
                  'statusCode' => $redirect['statusCode'],
                  'redirectId' => $redirect['id']
                ]
              ];
                }
            });
        }

        Craft::info('dolphiq/redirect Plugin plugin loaded', __METHOD__);
    }
}
