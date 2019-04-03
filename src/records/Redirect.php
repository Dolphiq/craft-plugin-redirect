<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace venveo\redirect\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\RedirectGroup;
use yii\db\ActiveQueryInterface;

/**
 *
 * @property \yii\db\ActiveQueryInterface $element
 * @property \yii\db\ActiveQueryInterface $group
 * @property integer|null hitAt
 * @property integer|null hitCount
 * @property int|null id
 * @property string sourceUrl
 * @property string destinationUrl
 * @property string statusCode
 */
class Redirect extends ActiveRecord
{
    // Public Methods
    // =========================================================================

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


    /**
     * Returns the redirect group.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getGroup(): ActiveQueryInterface
    {
        return $this->hasOne(RedirectGroup::class, ['id' => 'groupId']);
    }
}
