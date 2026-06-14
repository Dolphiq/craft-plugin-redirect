<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\records\Element as ElementRecord;
use craft\web\Controller;
use dolphiq\redirect\elements\Redirect;
use dolphiq\redirect\RedirectPlugin;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

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

        $source = (isset($routeParameters['source']) ? $routeParameters['source'] : 'CpSection');
        $navItems = $this->getMenuItems();

        unset($navItems['redirects']);
        $variables = [
            'settings' => RedirectPlugin::$plugin->getSettings(),
            'navItems' => $navItems,
            'source' => $source,
            'pathPrefix' => ($source == 'CpSettings' ? 'settings/' : ''),
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

        return $this->renderTemplate('redirect/redirects', $variables);
    }

    /**
     * Called before displaying the redirect settings registered-catch-all-urls  page.
     *
     * @return Response
     */
    public function actionDeleteCatchAllUrls(): craft\web\Response
    {
        $this->requireLogin();
        $urlId = (int)Craft::$app->getRequest()->getRequiredBodyParam('id');

        // Only allow deleting 404 entries for the requested site if the user can edit it.
        $siteId = (int)Craft::$app->getRequest()->getBodyParam('siteId', Craft::$app->getSites()->getCurrentSite()->id);
        if (!in_array($siteId, Craft::$app->getSites()->getEditableSiteIds(), true)) {
            throw new ForbiddenHttpException('User not permitted to edit content for this site.');
        }

        $success = RedirectPlugin::$plugin->getCatchAll()->deleteUrlById($urlId, $siteId);

        return $this->asJson(['success' => $success]);
    }

    /**
     * Called before displaying the redirect settings registered-catch-all-urls  page.
     *
     * @return Response
     */
    public function actionRegisteredCatchAllUrls(): craft\web\Response
    {
        $this->requireLogin();

        //  $allRedirects = RedirectPlugin::$plugin->getRedirects()->getAllRedirects();

        $routeParameters = Craft::$app->getUrlManager()->getRouteParams();

        $source = (isset($routeParameters['source']) ? $routeParameters['source'] : 'CpSection');
        $navItems = $this->getMenuItems();

        $siteId = Craft::$app->getRequest()->getQueryParam('siteId', Craft::$app->getSites()->currentSite->id);


        $variables = [
            'settings' => RedirectPlugin::$plugin->getSettings(),
            'urlItems' => RedirectPlugin::$plugin->getCatchAll()->getLastUrls(100, $siteId),
            'navItems' => $navItems,
            'source' => $source,
            'selectedSiteId' => $siteId,
            'pathPrefix' => ($source == 'CpSettings' ? 'settings/' : ''),
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

        return $this->renderTemplate('redirect/registeredcatchallurls', $variables);
    }

    /**
     * Per-404 analytics detail (daily trend, top referrers, browser families).
     */
    public function actionCatchAllStats(int $id): \yii\web\Response
    {
        $this->requireLogin();

        $siteId = Craft::$app->getSites()->currentSite->id;
        $record = \dolphiq\redirect\records\CatchAllUrl::findOne(['id' => $id, 'siteId' => $siteId]);
        if ($record === null) {
            throw new NotFoundHttpException('Missed URL not found');
        }

        $analytics = RedirectPlugin::$plugin->getAnalytics();
        $routeParameters = Craft::$app->getUrlManager()->getRouteParams();
        $source = $routeParameters['source'] ?? 'CpSection';

        return $this->renderTemplate('redirect/404stats', [
            'navItems' => $this->getMenuItems(),
            'pathPrefix' => ($source == 'CpSettings' ? 'settings/' : ''),
            'uri' => $record->uri,
            'trend' => $analytics->dailyTrend($id, 30),
            'referrers' => $analytics->topReferrers($id, 10),
            'agents' => $analytics->agentBreakdown($id),
        ]);
    }

    /**
     * Import / Export sub-page (CSV).
     */
    public function actionImportExport(): \yii\web\Response
    {
        $this->requireLogin();

        $routeParameters = Craft::$app->getUrlManager()->getRouteParams();
        $source = $routeParameters['source'] ?? 'CpSection';
        $siteId = Craft::$app->getRequest()->getQueryParam('siteId', Craft::$app->getSites()->currentSite->id);

        return $this->renderTemplate('redirect/importexport', [
            'navItems' => $this->getMenuItems(),
            'source' => $source,
            'selectedSiteId' => $siteId,
            'pathPrefix' => ($source == 'CpSettings' ? 'settings/' : ''),
        ]);
    }

    /**
     * Downloads all redirects for a site as a CSV file.
     */
    public function actionExportRedirects(): \yii\web\Response
    {
        $this->requireLogin();

        $siteId = (int)Craft::$app->getRequest()->getQueryParam('siteId', Craft::$app->getSites()->currentSite->id);
        $csv = RedirectPlugin::$plugin->getRedirects()->exportCsv($siteId);

        return Craft::$app->getResponse()->sendContentAsFile($csv, 'redirects.csv', ['mimeType' => 'text/csv']);
    }

    /**
     * Imports redirects from an uploaded CSV file.
     */
    public function actionImportRedirects(): ?\yii\web\Response
    {
        $this->requirePostRequest();
        $this->requireLogin();

        $siteId = (int)Craft::$app->getRequest()->getBodyParam('siteId', Craft::$app->getSites()->currentSite->id);
        $file = UploadedFile::getInstanceByName('file');

        if ($file === null) {
            Craft::$app->getSession()->setError(Craft::t('redirect', 'No CSV file was uploaded.'));
            return null;
        }

        // Guard against oversized uploads exhausting memory.
        if ($file->size > 5 * 1024 * 1024) {
            Craft::$app->getSession()->setError(Craft::t('redirect', 'The CSV file is too large (max 5 MB).'));
            return null;
        }

        $result = RedirectPlugin::$plugin->getRedirects()->importCsv(file_get_contents($file->tempName), $siteId);

        Craft::$app->getSession()->setNotice(Craft::t('redirect', '{created} redirect(s) imported, {skipped} skipped.', $result));

        return $this->redirectToPostedUrl();
    }


    private function getMenuItems()
    {
        $routeParameters = Craft::$app->getUrlManager()->getRouteParams();

        $source = (isset($routeParameters['source']) ? $routeParameters['source'] : 'CpSection');

        $settings = RedirectPlugin::$plugin->getSettings();

        $navItems = [
            'settings' => [
                'label' => "Settings",
                'url' => UrlHelper::url(($source == 'CpSettings' ? 'settings/' : '') . 'redirect/settings'),
            ],
        ];

        if ($settings['catchAllActive']) {
            $navItems['registeredcatchall'] = [
                'label' => "Registered catch all urls",
                'url' => UrlHelper::url(($source == 'CpSettings' ? 'settings/' : '') . 'redirect/registered-catch-all-urls'),
            ];
        }
        $navItems['redirects'] = [
            'label' => "Redirect entries",
            'url' => UrlHelper::url(($source == 'CpSettings' ? 'settings/' : '') . 'redirect'),
        ];
        $navItems['importexport'] = [
            'label' => "Import / Export",
            'url' => UrlHelper::url(($source == 'CpSettings' ? 'settings/' : '') . 'redirect/import-export'),
        ];

        return $navItems;
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
        $source = (isset($routeParameters['source']) ? $routeParameters['source'] : 'CpSection');
        $settings = RedirectPlugin::$plugin->getSettings();

        $navItems = $this->getMenuItems();

        return $this->renderTemplate('redirect/settings', [
            'settings' => $settings,
            'navItems' => $navItems,
            'source' => $source,
            'pathPrefix' => ($source == 'CpSettings' ? 'settings/' : ''),
        ]);
    }

    /**
     * Called when saving the settings.
     *
     * @return Response
     */
    public function actionSaveSettings(): ?craft\web\Response
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
            'catchAllActive' => (bool)$request->getBodyParam('catchAllActive'),
            'catchAllTemplate' => (string)$request->getBodyParam('catchAllTemplate'),
            'autoCreateRedirectOnUriChange' => (bool)$request->getBodyParam('autoCreateRedirectOnUriChange'),
            'analyticsEnabled' => (bool)$request->getBodyParam('analyticsEnabled'),
            'analyticsRetentionDays' => (int)$request->getBodyParam('analyticsRetentionDays', 90),

        ];

        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $newSettings)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save plugin settings.'));

            // Send the plugin back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'plugin' => $plugin,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl((object)$newSettings);
    }

    /**
     * Live-tests a redirect definition against a URL (no DB write). Returns JSON
     * `{matched, destination, error}` for the edit-form "test this redirect" box.
     */
    public function actionTest(): \yii\web\Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $request = Craft::$app->getRequest();

        return $this->asJson(RedirectPlugin::$plugin->getRedirects()->testMatch(
            (string)$request->getBodyParam('matchType', 'exact'),
            (string)$request->getBodyParam('sourceUrl', ''),
            (string)$request->getBodyParam('destinationUrl', ''),
            (string)$request->getBodyParam('testUrl', ''),
        ));
    }

    /**
     * Edit a redirect
     *
     * @param int|null $redirectId The redirect's ID, if editing an existing site
     * @param Redirect|null $redirect The redirect being edited, if there were any validation errors
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
                'url' => UrlHelper::url('settings'),
            ],
            [
                'label' => Craft::t('redirect', 'Redirects'),
                'url' => UrlHelper::url('settings/redirect'),
            ],
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
        $variables['matchTypeOptions'] = [
            'exact' => Craft::t('redirect', 'Exact match'),
            'prefix' => Craft::t('redirect', 'Prefix — path starts with the source'),
            'wildcard' => Craft::t('redirect', 'Wildcard — * matches any segments'),
            'pattern' => Craft::t('redirect', 'Pattern — <name> / <name:regex>'),
        ];


        $variables['brandNewRedirect'] = false;

        if ($redirectId !== null) {
            if ($redirect === null) {
                $siteId = Craft::$app->request->get('siteId');
                if ($siteId == null) {
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
                $redirect = new Redirect();

                // is there a sourceCatchALlUrlID ?

                $sourceCatchAllUrlId = Craft::$app->getRequest()->getQueryParam('sourceCatchAllUrlId', '');
                if ($sourceCatchAllUrlId !== '') {
                    // load some settings from the url
                    $url = RedirectPlugin::$plugin->getCatchAll()->getUrlByUid($sourceCatchAllUrlId);
                    if ($url !== null) {
                        $redirect->sourceUrl = $url->uri;
                        $redirect->siteId = $url->siteId;
                        $redirect->matchType = 'exact';
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
        return $this->renderTemplate('redirect/edit', $variables);
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

        $request = Craft::$app->getRequest();
        $redirect = new Redirect();
        $redirect->id = $request->getBodyParam('redirectId');
        $redirect->uid = $request->getBodyParam('uid');
        $redirect->sourceUrl = $request->getBodyParam('sourceUrl');
        $redirect->destinationUrl = $request->getBodyParam('destinationUrl');
        $redirect->statusCode = $request->getBodyParam('statusCode');
        $redirect->matchType = $request->getBodyParam('matchType');
        $redirect->priority = (int)$request->getBodyParam('priority', 0);
        $siteId = $request->getBodyParam('siteId');
        if ($siteId == null) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        $elementRecord = ElementRecord::findOne($redirect->id);
        if ($elementRecord) {
            $redirect->uid = $elementRecord->uid;
        }

        $redirect->siteId = $siteId;

        // ElementInterface $element, bool $runValidation = true, bool $propagate = true): bool
        $res = Craft::$app->getElements()->saveElement($redirect, true, false);

        if (!$res) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                ]);
            }
            // else, normal result
            Craft::$app->getSession()->setError(Craft::t('redirect', 'Couldn’t save the redirect.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'redirect' => $redirect,
            ]);

            return null;
        } else {
            // remove form other sites
            Craft::$app->getDb()->createCommand()
                ->delete('{{%elements_sites}}', [
                    'AND',
                    ['elementId' => $redirect->id],
                    ['!=', 'siteId', $siteId],
                ])
                ->execute();

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => true,
                    'id' => $redirect->id,
                ]);
            }
            // else, normal result
            Craft::$app->getSession()->setNotice(Craft::t('redirect', 'Redirect saved.'));
            // return $this->redirectToPostedUrl($category);

            $url = $request->getBodyParam('redirectUrl');
            return $this->redirect($url);
        }
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
