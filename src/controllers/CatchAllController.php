<?php

/**
 * @author    Venveo
 * @copyright Copyright (c) 2019 Venveo
 * @link      https://www.venveo.com
 */

namespace venveo\redirect\controllers;

use craft\web\Controller;
use craft\web\Response;

class CatchAllController extends Controller
{

    // Public Methods
    // =========================================================================

    /**
     * Called before displaying the redirect settings index page.
     *
     * @return Response
     * @throws \craft\errors\SiteNotFoundException
     */
    public function actionIndex()
    {
        return $this->renderTemplate('vredirect/catch-all/index', []);
    }
}
