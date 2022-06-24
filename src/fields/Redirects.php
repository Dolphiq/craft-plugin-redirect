<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\fields;

use Craft;
use craft\fields\BaseRelationField;
use dolphiq\redirect\elements\Redirect;

class Redirects extends BaseRelationField
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('redirect', 'Redirect');
    }

    /**
     * @inheritdoc
     */
    public static function elementType():string
    {
        return Redirect::class;
    }

    /**
     * @inheritdoc
     */
    public static function defaultSelectionLabel(): string
    {
        return Craft::t('redirect', 'Add a redirect');
    }


    // Properties
    // =========================================================================

    // Public Methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->allowMultipleSources = false;
        $this->allowLimit = false;
    }
}
