<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Class Redirect record.
 *
 * @property int $id            ID
 * @property int $fieldLayoutId Field layout ID
 * @property string $name          Name
 * @property string $handle        Handle
 * @property FieldLayout $fieldLayout   Field layout
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RedirectGroup extends ActiveRecord
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
        return '{{%dolphiq_redirects_groups}}';
    }

    /**
     * Returns the redirect fieldLayout.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class,
            ['id' => 'fieldLayoutId']);
    }

    /**
     * Returns the redirect group’s redirects.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getRedirects(): ActiveQueryInterface
    {
        return $this->hasMany(Redirect::class, ['groupId' => 'id']);
    }
}
