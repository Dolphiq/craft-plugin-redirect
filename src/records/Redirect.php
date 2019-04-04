<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace venveo\redirect\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\records\Element;
use yii\db\ActiveQueryInterface;

/**
 *
 * @property \yii\db\ActiveQueryInterface $element
 * @property Date|null hitAt
 * @property integer|null hitCount
 * @property int|null id
 * @property string sourceUrl
 * @property string destinationUrl
 * @property string statusCode
 * @property string type
 */
class Redirect extends ActiveRecord
{
    use SoftDeleteTrait;
    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%dolphiq_redirects}}';
    }

    /**
     * Returns the redirect’s element.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
