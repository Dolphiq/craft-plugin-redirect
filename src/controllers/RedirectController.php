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

      // are there parameters in the destination url?
      if (strpos($destinationUrl, '<') !== false && preg_match_all('/<([\w._-]+)>/', $destinationUrl, $matches)) {

          // a bug in Craft cms overwrites the parameters parsed by Yii-UrlRule.
          // Please get them again

          $parseRule = new UrlRule([
            'pattern'=> $sourceUrl,
            'route' => 'templates/render'
          ]);

          $request = Craft::$app->getRequest();
          $sourceParameters = $parseRule->parseRequestParams($request);

          foreach ($matches[1] as $name) {
              // $this->_routeParams[$name] = "<$name>";
            if (isset($sourceParameters[$name])) {
                // replace the string with this value
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
        $this->redirect($destinationUrl, $statusCode);
    }
}
