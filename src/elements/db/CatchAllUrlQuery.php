<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\elements\db;

use Craft;
use craft\db\QueryAbortedException;
use dolphiq\redirect\elements\CatchAllUrl;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use yii\db\Connection;

class CatchAllUrlQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var bool Whether to only return global sets that the user has permission to edit.
     */
    public $editable = false;

    /**
     * @var string|string[]|null The handle(s) that the resulting global sets must have.
     */
    public $catchedUri;

    /**
     * @var datetime|null dateCreated
     */
    public $dateCreated;
    /**
     * @var datetime|null dateUpdated
     */
    public $dateUpdated;
    /**
     * @var integer|null hitCount
     */
    public $hitCount;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'catchedUri';
        }

        parent::__construct($elementType, $config);
    }

    /**
     * Sets the [[editable]] property.
     *
     * @param bool $value The property value (defaults to true)
     *
     * @return static self reference
     */
    public function editable(bool $value = true)
    {
        $this->editable = $value;

        return $this;
    }

    /**
     * Sets the [[handle]] property.
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function catchedUri($value)
    {
        $this->catchedUri = $value;

        return $this;
    }



    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        Craft::info('dolphiq/catchall beforePrepare', __METHOD__);
        $this->joinElementTable('dolphiq_redirects_catch_all_urls');

        $this->query->select([
            'elements_sites.siteId',
            'dolphiq_redirects_catch_all_urls.catchedUri',
            'dolphiq_redirects_catch_all_urls.dateCreated',
            'dolphiq_redirects_catch_all_urls.dateUpdated',
            'dolphiq_redirects_catch_all_urls.hitCount'
        ]);

        // $this->subQuery->andWhere(Db::parseParam('status', null));

        if ($this->catchedUri) {
            $this->subQuery->andWhere(Db::parseParam('catchedUri', $this->catchedUri));
        }


       // $this->subQuery->andWhere(Db::parseParam('elements_sites.siteId', null));
       // $this->_applyEditableParam();

        return parent::beforePrepare();
    }

    // Private Methods
    // =========================================================================

    /**
     * Applies the 'editable' param to the query being prepared.
     *
     * @throws QueryAbortedException
     */
    private function _applyEditableParam()
    {
        if ($this->editable) {
            // Limit the query to only the global sets the user has permission to edit
            $editableSetIds = Craft::$app->getGlobals()->getEditableSetIds();
            $this->subQuery->andWhere(['elements.id' => $editableSetIds]);
        }
    }
}
