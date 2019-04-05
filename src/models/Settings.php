<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
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

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['redirectsActive', 'catchAllActive'], 'required'],
        ];
    }
}
