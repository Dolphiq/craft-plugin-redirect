<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

class CatchAllUrl extends ActiveRecord
{
    // Public Methods
    // =========================================================================



    public static function tableName(): string
    {
        return '{{%dolphiq_redirects_catch_all_urls}}';
    }

    /**
     * Returns the tag’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }


}
