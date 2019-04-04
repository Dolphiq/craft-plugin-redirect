<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace venveo\redirect\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use venveo\redirect\elements\Redirect;
use venveo\redirect\Plugin;
use venveo\redirect\records\CatchAllUrl;

class RedirectsController extends Controller
{

    // Public Methods
    // =========================================================================

    /**
     * Called before displaying the redirect settings index page.
     *
     * @return Response
     * @throws \craft\errors\SiteNotFoundException
     */
    public function actionIndex(): craft\web\Response
    {

        $variables = [
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

        return $this->renderTemplate('vredirect/redirects/index', $variables);
    }

    /**
     * Called before displaying the redirect settings registered-catch-all-urls  page.
     *
     * @return Response
     */
    public function actionDeleteCatchAllUrls(): craft\web\Response
    {

        $this->requireLogin();
        $urlId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Plugin::$plugin->getCatchAll()->deleteUrlById($urlId);

        return $this->asJson(['success' => true]);
    }


    /**
     * Edit a redirect
     *
     * @param int|null $redirectId The redirect's ID, if editing an existing site
     * @param Plugin|null $redirect The redirect being edited, if there were any validation errors
     *
     * @return Response
     * @throws NotFoundHttpException if the requested redirect cannot be found
     */
    public function actionEditRedirect(int $redirectId = null, Redirect $redirect = null): craft\web\Response
    {
        $fromCatchAllId = Craft::$app->request->getQueryParam('from');
        if ($fromCatchAllId) {
            $catchAllRecord = CatchAllUrl::findOne($fromCatchAllId);
        }

        $variables = [];

        if ($catchAllRecord) {
            $variables['catchAllRecord'] = $catchAllRecord;
        }

        // Breadcrumbs
        $variables['crumbs'] = [
            [
                'label' => Craft::t('vredirect', 'Redirects'),
                'url' => UrlHelper::cpUrl('redirect/redirects')
            ]
        ];
        $editableSitesOptions = [];

        foreach (Craft::$app->getSites()->getEditableSites() as $site) {
            $editableSitesOptions[$site['id']] = $site->name;
        }

        $statusCodesOptions = [
            '301' => 'Permanent redirect (301)',
            '302' => 'Temporarily redirect (302)',
        ];

        $typeOptions = [
            'static' => 'Static',
            'dynamic' => 'Dynamic (RegExp)',
        ];

        $variables['statusCodeOptions'] = $statusCodesOptions;
        $variables['typeOptions'] = $typeOptions;
        $variables['editableSitesOptions'] = $editableSitesOptions;


        $variables['brandNewRedirect'] = false;

        if ($redirectId !== null) {
            if ($redirect === null) {
                $siteId = Craft::$app->request->get('siteId');
                if ($siteId == null) {
                    $siteId = Craft::$app->getSites()->currentSite->id;
                }
                $redirect = Plugin::$plugin->getRedirects()->getRedirectById($redirectId, $siteId);

                if (!$redirect) {
                    throw new NotFoundHttpException('Redirect not found');
                }
            }

            $variables['title'] = $redirect->sourceUrl;
        } else {
            if ($redirect === null) {
                $redirect = new Redirect();

                // is there a sourceCatchALlUrlID ?

                $sourceCatchAllUrlId = Craft::$app->getRequest()->getQueryParam('sourceCatchAllUrlId', '');
                if ($sourceCatchAllUrlId !== '') {
                    // load some settings from the url
                    $url = Plugin::$plugin->getCatchAll()->getUrlByUid($sourceCatchAllUrlId);
                    if ($url !== null) {
                        $redirect->sourceUrl = $url->uri;
                        $redirect->siteId = $url->siteId;
                    }
                }

                $variables['brandNewRedirect'] = true;
            }

            $variables['title'] = Craft::t('app', 'Create a new redirect');
        }

        $variables['redirect'] = $redirect;

        $routeParameters = Craft::$app->getUrlManager()->getRouteParams();
        $source = (isset($routeParameters['source']) ? $routeParameters['source'] : 'CpSection');

        $variables['source'] = $source;
        $variables['pathPrefix'] = ($source == 'CpSettings' ? 'settings/' : '');
        $variables['currentSiteId'] = $redirect->siteId;
        return $this->renderTemplate('vredirect/redirects/edit', $variables);
    }


    /**
     * Saves a redirect.
     *
     * @return \yii\web\Response
     */
    public function actionSaveRedirect()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $redirect = new Redirect();
        $redirect->id = $request->getBodyParam('redirectId');
        $redirect->sourceUrl = $request->getBodyParam('sourceUrl');
        $redirect->destinationUrl = $request->getBodyParam('destinationUrl');
        $redirect->statusCode = $request->getBodyParam('statusCode');
        $siteId = $request->getBodyParam('siteId');
        $redirect->type = $request->getBodyParam('type');
        if ($siteId == null) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        $redirect->siteId = $siteId;

        $res = Craft::$app->getElements()->saveElement($redirect, true, false);

        if (!$res) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false
                ]);
            }
            // else, normal result
            Craft::$app->getSession()->setError(Craft::t('vredirect', 'Couldn’t save the redirect.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'redirect' => $redirect
            ]);

            return Craft::$app->response;
        }

        $fromCatchAllId = Craft::$app->request->getBodyParam('catchAllRecordId');
        if ($fromCatchAllId) {
            $catchAllRecord = CatchAllUrl::findOne($fromCatchAllId);
            if ($catchAllRecord) {
                $catchAllRecord->delete();
            }
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $redirect->id
            ]);
        }
        // else, normal result
        Craft::$app->getSession()->setNotice(Craft::t('vredirect', 'Redirect saved.'));
        return $this->redirectToPostedUrl();
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
        Plugin::$plugin->getRedirects()->deleteRedirectById($redirectId);

        return $this->asJson(['success' => true]);
    }
}
