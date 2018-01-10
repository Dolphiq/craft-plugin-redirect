<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\controllers;

use Craft;
use craft\web\Controller;
use craft\helpers\UrlHelper;

use dolphiq\redirect\RedirectPlugin;

use \dolphiq\redirect\helpers\UrlRule;
use \dolphiq\redirect\records\CatchAllUrl as CatchAllUrlRecord;

class RedirectController extends Controller
{
    private $_sourceRouteParams = [];
    protected $allowAnonymous = ['index'];

    public function actionIndex()
    {
        // var_dump(Craft::$app->getRequest());
      $routeParameters = Craft::$app->getUrlManager()->getRouteParams();
        $sourceUrl = $routeParameters['sourceUrl'];
        $destinationUrl = $routeParameters['destinationUrl'];
        $statusCode = $routeParameters['statusCode'];
        $redirectId = $routeParameters['redirectId'];

      // are there parameters in the destination url?
      if ($statusCode != 404 && strpos($destinationUrl, '<') !== false && preg_match_all('/<([\w._-]+)>/', $destinationUrl, $matches)) {

          // a bug in Craft cms overwrites the parameters parsed by Yii-UrlRule.
          // Please get them again
          $parseRule = new UrlRule([
            'pattern'=> $sourceUrl,
            'route' => 'templates/render'
          ]);

          $request = Craft::$app->getRequest();
          $sourceParameters = $parseRule->parseRequestParams($request);
          // insert the parameters into the destination url
          foreach ($matches[1] as $name) {
              if (isset($sourceParameters[$name])) {
                  $destinationUrl = str_ireplace("<$name>", $sourceParameters[$name], $destinationUrl);
              } elseif (isset($_GET[$name])) {
                  $destinationUrl = str_ireplace("<$name>", $_GET[$name], $destinationUrl);
              }
          }
      }

      // check if there is a full domain.. if not, please add the site domain
      if (strpos($destinationUrl, '://') === false) {
          $destinationUrl = UrlHelper::baseUrl() . ltrim($destinationUrl, '/');
      }

      // register the hit to the database
      if ($redirectId != null && $statusCode != 404) {
          RedirectPlugin::$plugin->getRedirects()->registerHitById($redirectId, $destinationUrl);
          $this->redirect($destinationUrl, $statusCode);
      } else {
          // this is a not existing page, please register the hit to a catch all element

          $uri = $_SERVER['REQUEST_URI'];

          RedirectPlugin::$plugin->getCatchAll()->registerHitByUri($uri);


          $settings = RedirectPlugin::$plugin->getSettings();
          Craft::$app->response->statusCode = $statusCode;
          if ($settings->catchAllTemplate != '') {
              return $this->renderTemplate($settings->catchAllTemplate, ['request' => [
                  'requestUri' => $_SERVER['REQUEST_URI']
              ]]);
          } else {
              return ('this page does not exists');
          }
      }
    }
}
