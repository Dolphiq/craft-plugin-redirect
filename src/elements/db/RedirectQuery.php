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
use dolphiq\redirect\elements\Redirect;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use yii\db\Connection;

class RedirectQuery extends ElementQuery
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
    public $sourceUrl;

    /**
     * @var string|string[]|null The handle(s) that the resulting global sets must have.
     */
    public $destinationUrl;

    /**
     * @var string|string[]|null The handle(s) that the resulting global sets must have.
     */
    public $statusCode;
    /**
     * @var string|null hitAt
     */
    public $hitAt;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($elementType, array $config = [])
    {
        // Default orderBy
        if (!isset($config['orderBy'])) {
            $config['orderBy'] = 'sourceUrl';
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
    public function sourceUrl($value)
    {
        $this->sourceUrl = $value;

        return $this;
    }

    /**
     * Sets the [[handle]] property.
     *
     * @param string|string[]|null $value The property value
     *
     * @return static self reference
     */
    public function destinationUrl($value)
    {
        $this->destinationUrl = $value;

        return $this;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        Craft::info('dolphiq/redirect beforePrepare', __METHOD__);
        $this->joinElementTable('dolphiq_redirects');


        //   $this->joinElementTable('elements_sites');

        $this->query->select([
            'elements_sites.siteId',
            'dolphiq_redirects.sourceUrl',
            'dolphiq_redirects.destinationUrl',
            'dolphiq_redirects.hitAt',
            'dolphiq_redirects.hitCount',
            'dolphiq_redirects.statusCode',
        ]);

        // $this->subQuery->andWhere(Db::parseParam('status', null));

        if ($this->sourceUrl) {
            $this->subQuery->andWhere(Db::parseParam('dolphiq_redirects.sourceUrl', $this->sourceUrl));
        }
        if ($this->destinationUrl) {
            $this->subQuery->andWhere(Db::parseParam('dolphiq_redirects.destinationUrl', $this->destinationUrl));
        }
        if ($this->statusCode) {
            $this->subQuery->andWhere(Db::parseParam('dolphiq_redirects.statusCode', $this->statusCode));
        }
        if ($this->hitAt && $this->hitAt > 0) {
            $inactiveDate = new \DateTime();
            $inactiveDate->modify("-60 days");

            $this->subQuery->andWhere('(dolphiq_redirects.hitAt < :calculatedDate AND dolphiq_redirects.hitAt IS NOT NULL)', [':calculatedDate' => $inactiveDate->format("Y-m-d H:m:s")]);
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
