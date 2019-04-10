<?php
/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\elements;

use Cake\Utility\Hash;
use Craft;
use craft\base\Element as BaseElement;
use craft\feedme\base\Element;
use craft\feedme\base\ElementInterface;
use venveo\redirect\elements\Redirect as RedirectElement;

/**
 *
 * @property string $mappingTemplate
 * @property array $groups
 * @property mixed $model
 * @property string $groupsTemplate
 * @property string $columnTemplate
 */
class FeedMeRedirect extends Element implements ElementInterface
{
    // Properties
    // =========================================================================

    public static $name = 'Redirect';
    public static $class = RedirectElement::class;

    public $element;


    // Templates
    // =========================================================================

    public function getGroupsTemplate()
    {
        return 'vredirect/_feed-me/groups';
    }

    public function getColumnTemplate()
    {
        return 'vredirect/_feed-me/column';
    }

    public function getMappingTemplate()
    {
        return 'vredirect/_feed-me/map';
    }


    // Public Methods
    // =========================================================================

    public function getGroups()
    {
        return [];
    }

    /**
     * @param $settings
     * @param array $params
     * @return \craft\elements\db\ElementQueryInterface|db\RedirectQuery
     */
    public function getQuery($settings, $params = [])
    {
        $query = RedirectElement::find();

        $siteId = Hash::get($settings, 'siteId');


        $criteria = array_merge([
            'status' => null,
        ], $params);


        if ($siteId) {
            $criteria['siteId'] = $siteId;
        }

        Craft::configure($query, $criteria);

        return $query;
    }

    /**
     * @param $settings
     * @return Redirect
     */
    public function setModel($settings)
    {
        $this->element = new RedirectElement();

        $siteId = Hash::get($settings, 'siteId');

        if ($siteId) {
            $this->element->siteId = $siteId;
        }

        return $this->element;
    }

    /**
     * @param $element
     * @param $settings
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function save($element, $settings)
    {
        $this->element = $element;

        $propagate = !(isset($settings['siteId']) && $settings['siteId']);

        $this->element->setScenario(BaseElement::SCENARIO_ESSENTIALS);

        // We have to turn off validation - otherwise Spam checks will kick in
        if (!Craft::$app->getElements()->saveElement($this->element, false, $propagate)) {
            return false;
        }

        return true;
    }
}
