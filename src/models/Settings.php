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
     * Whether to automatically create a 301 redirect when an element's URI changes.
     *
     * @var bool
     */
    public $autoCreateRedirectOnUriChange = true;

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
