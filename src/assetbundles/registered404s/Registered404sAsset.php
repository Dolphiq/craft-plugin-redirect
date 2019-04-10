<?php
/**
 * Gutenberg plugin for Craft CMS 3.x
 *
 * Adds fieldtype for Gutenberg editor from Wordpress
 *
 * @link      https://www.venveo.com
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\assetbundles\registered404s;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Venveo
 * @package   Gutenberg
 * @since     1.0.0
 */
class Registered404sAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@venveo/redirect/assetbundles/registered404s/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'main.js',
        ];

        $this->css = [
            'main.css',
        ];

        parent::init();
    }
}
