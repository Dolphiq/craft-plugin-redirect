<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\models;

use craft\base\Model;

class Settings extends Model
{
    /**
     * @var bool
     */
    public $redirectsActive = true;
    public $catchAllActive = false;
    public $catchAllTemplate = '';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['redirectsActive', 'catchAllActive'], 'required'],
        ];
    }
}
