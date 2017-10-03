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
use dolphiq\redirect\elements\Redirect;

use craft\db\Query;

class SettingsController extends Controller
{

    // Public Methods
    // =========================================================================

    /**
     * Called before displaying the redirect settings index page.
     *
     * @return Response
     */
    public function actionIndex(): craft\web\Response
    {
        $this->requireLogin();

      //  $allRedirects = RedirectPlugin::$plugin->getRedirects()->getAllRedirects();

         $routeParameters = Craft::$app->getUrlManager()->getRouteParams();

         $source = (isset($routeParameters['source'])?$routeParameters['source']:'CpSection');

        $variables = [
          'settings' => RedirectPlugin::$plugin->getSettings(),
           'source' => $source,
           'pathPrefix' => ($source == 'CpSettings' ? 'settings/': ''),
          // 'allRedirects' => $allRedirects
        ];

        // Get the site
        // ---------------------------------------------------------------------
        if (Craft::$app->getIsMultiSite()) {
            // Only use the sites that the user has access to
            $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        } else {
            $variables['siteIds'] = [Craft::$app->getSites()->getPrimarySite()->id];
        }
        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites');
        }

        return $this->renderTemplate('redirect/index', $variables);
    }


    /**
     * Called before displaying the plugin settings section.
     *
     * @return Response
     */
    public function actionSettings(): craft\web\Response
    {
        $this->requireAdmin();

        $routeParameters = Craft::$app->getUrlManager()->getRouteParams();
        $source = (isset($routeParameters['source'])?$routeParameters['source']:'CpSection');
        $settings = RedirectPlugin::$plugin->getSettings();
        return $this->renderTemplate('redirect/settings', [
          'settings' => $settings,
          'source' => $source,
          'pathPrefix' => ($source == 'CpSettings' ? 'settings/': '')
       ]);
    }

    /**
     * Called when saving the settings.
     *
     * @return Response
     */
    public function actionSaveSettings(): craft\web\Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();
        $request = Craft::$app->getRequest();

        $pluginHandle = $request->getRequiredBodyParam('pluginHandle');
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

        if ($plugin === null) {
            throw new NotFoundHttpException('Plugin not found');
        }

        $newSettings = [
        'redirectsActive' => (bool)$request->getBodyParam('redirectsActive'),
      ];

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $newSettings)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save plugin settings.'));

        // Send the plugin back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'plugin' => $plugin
        ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl($newSettings);

    }

    /**
     * Edit a redirect
     *
     * @param int|null  $redirectId The redirect's ID, if editing an existing site
     * @param Redirect|null $redirect   The redirect being edited, if there were any validation errors
     *
     * @return Response
     * @throws NotFoundHttpException if the requested redirect cannot be found
     */
    public function actionEditRedirect(int $redirectId = null, Redirect $redirect = null): craft\web\Response
    {
        $variables = [];

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('app', 'Settings'),
                'url' => UrlHelper::url('settings')
            ],
            [
                'label' => Craft::t('redirect', 'Redirects'),
                'url' => UrlHelper::url('settings/redirect')
            ]
        ];
        $editableSitesOptions = [
        ];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
          $editableSitesOptions[$site['id']] = $site->name;
        }

        $statusCodesOptions = [
          '301' => 'Permanent redirect (301)',
          '302' => 'Temporarily redirect (302)',
        ];

        $variables['statusCodeOptions'] = $statusCodesOptions;
        $variables['editableSitesOptions'] = $editableSitesOptions;


        $variables['brandNewRedirect'] = false;

        if ($redirectId !== null) {
            if ($redirect === null) {
              $siteId = Craft::$app->request->get('siteId');
              if($siteId == null) {
                $siteId = Craft::$app->getSites()->currentSite->id;
              }
              $redirect = RedirectPlugin::$plugin->getRedirects()->getRedirectById($redirectId, $siteId);

                if (!$redirect) {
                    throw new NotFoundHttpException('Redirect not found');
                }
            }

            $variables['title'] = $redirect->sourceUrl;
        } else {
            if ($redirect === null) {
                $redirect = new Redirect;
                $variables['brandNewRedirect'] = true;
            }

            $variables['title'] = Craft::t('app', 'Create a new redirect');
        }

        $variables['redirect'] = $redirect;

        $routeParameters = Craft::$app->getUrlManager()->getRouteParams();
        $source = (isset($routeParameters['source'])?$routeParameters['source']:'CpSection');

        $variables['source'] = $source;
        $variables['pathPrefix'] = ($source == 'CpSettings' ? 'settings/': '');
        $variables['currentSiteId'] = $redirect->siteId;
        return $this->renderTemplate('redirect/edit', $variables);
    }

    /**
     * Saves a redirect.
     *
     * @return Response|null
     */
    public function actionSaveRedirectOld()
    {
        $this->requirePostRequest();
        $this->requireLogin();

        $request = Craft::$app->getRequest();

        $redirect = new Redirect();

        // Set request values to the Redirect model
        $redirect->sourceUrl = $request->getBodyParam('sourceUrl');
        $redirect->destinationUrl = $request->getBodyParam('destinationUrl');
        $redirect->statusCode = $request->getBodyParam('statusCode');
        $redirect->id = $request->getBodyParam('redirectId');

        // Save it
        if (!RedirectPlugin::$plugin->getRedirects()->saveRedirect($redirect)) {
            Craft::$app->getSession()->setError(Craft::t('redirect', 'Couldn’t save the redirect.'));

            // Send the redirect back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'redirect' => $redirect
            ]);

            return null;
        }
        Craft::$app->getSession()->setNotice(Craft::t('redirect', 'Redirect saved.'));

        $url = $request->getBodyParam('redirectUrl');
        return $this->redirect($url);
    }


    /**
     * Saves a redirect.
     *
     * @return Response|null
     */
    public function actionSaveRedirect()
    {
        $this->requirePostRequest();
        $this->requireLogin();

       // $groupId = Craft::$app->getRequest()->getRequiredBodyParam('groupId');
       /* if (($group = Craft::$app->getTags()->getTagGroupById($groupId)) === null) {
            throw new BadRequestHttpException('Invalid tag group ID: '.$groupId);
        }
      */

        $request = Craft::$app->getRequest();
        $redirect = new Redirect();
        // $tag->groupId = $group->id;
        // $tag->fieldLayoutId = $group->fieldLayoutId;
        $redirect->id = $request->getBodyParam('redirectId');
        $redirect->sourceUrl = $request->getBodyParam('sourceUrl');
        $redirect->destinationUrl = $request->getBodyParam('destinationUrl');
        $redirect->statusCode = $request->getBodyParam('statusCode');
        $redirect->validateCustomFields = false;
        $siteId = $request->getBodyParam('siteId');
        if($siteId == null) {
          $siteId = Craft::$app->getSites()->currentSite->id;
        }

        $redirect->siteId = $siteId;

        // public function saveElement(ElementInterface $element, bool $runValidation = true, bool $propagate = true): bool

        $res = Craft::$app->getElements()->saveElement($redirect, true, false);
        if ($request->getAcceptsJson()) {
        if ($res) {
            return $this->asJson([
                'success' => true,
                'id' => $redirect->id
            ]);
        } else {
            return $this->asJson([
                'success' => false
            ]);

        }
        // die('test');
         /*   return $this->asJson([
                'success' => true,
                'id' => $category->id,
                'title' => $category->title,
                'status' => $category->getStatus(),
                'url' => $category->getUrl(),
                'cpEditUrl' => $category->getCpEditUrl()
            ]);*/
        }

        Craft::$app->getSession()->setNotice(Craft::t('redirect', 'Redirect saved.'));
        // return $this->redirectToPostedUrl($category);


        $url = $request->getBodyParam('redirectUrl');
        return $this->redirect($url);
    }



    /**
     * Deletes a route.
     *
     * @return Response
     */
    public function actionDeleteRedirect()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();

        $redirectId = $request->getRequiredBodyParam('id');
        RedirectPlugin::$plugin->getRedirects()->deleteRedirectById($redirectId);

        return $this->asJson(['success' => true]);
    }
}
