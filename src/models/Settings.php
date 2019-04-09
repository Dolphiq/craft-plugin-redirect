<?php

/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\models;

use craft\base\Model;

class Settings extends Model
{
    /**
     * @var bool
     */
    public $redirectsActive = true;
    public $catchAllActive = true;
    public $autoRedirect = true;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['redirectsActive', 'catchAllActive', 'autoRedirect'], 'boolean'],
        ];
    }
}
