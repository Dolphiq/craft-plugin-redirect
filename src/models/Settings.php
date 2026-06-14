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
     * Whether to collect privacy-safe 404 analytics (aggregate counts; no IPs/UA stored).
     *
     * @var bool
     */
    public $analyticsEnabled = false;

    /**
     * How many days of daily 404 analytics to keep before pruning.
     *
     * @var int
     */
    public $analyticsRetentionDays = 90;

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
