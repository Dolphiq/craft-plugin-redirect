<?php

/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */


namespace dolphiq\redirect\models;

use craft\base\Model;

// use dolphiq\redirect\records\Redirect as RedirectRecord;

class Redirect extends Model
{
    /**
     * @var string|string[]|null
     */
    public $id;

    /**
     * @var string|null
     */
    public $sourceUrl;

    /**
     * @var string|null
     */
    public $destinationUrl;

    /**
     * @var string|null
     */
    public $statusCode;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['sourceUrl', 'destinationUrl', 'statusCode'], 'required'],
            [['sourceUrl', 'destinationUrl'], 'string', 'max' => 255],
        ];

        return $rules;
    }
}
