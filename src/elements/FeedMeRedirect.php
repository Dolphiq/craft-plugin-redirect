<?php
/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2019 Venveo
 */
namespace venveo\redirect\elements;

use verbb\feedme\FeedMe;
use verbb\feedme\base\Element;
use verbb\feedme\base\ElementInterface;

use Craft;
use craft\base\Element as BaseElement;
use craft\db\Query;
use craft\elements\User as UserElement;
use craft\helpers\Db;

use Cake\Utility\Hash;
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

    public function setModel($settings)
    {
        $this->element = new RedirectElement();

        $siteId = Hash::get($settings, 'siteId');

        if ($siteId) {
            $this->element->siteId = $siteId;
        }

        return $this->element;
    }

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
