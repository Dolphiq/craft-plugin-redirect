<?php

namespace dolphiq\redirect\elements;

use craft\feedme\Base\Element;
use craft\feedme\Base\ElementInterface;

use Craft;
use craft\base\Element as BaseElement;

use Cake\Utility\Hash;
use dolphiq\redirect\elements\Redirect as RedirectElement;

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
        return 'redirect/_feed-me/groups';
    }

    public function getColumnTemplate()
    {
        return 'redirect/_feed-me/column';
    }

    public function getMappingTemplate()
    {
        return 'redirect/_feed-me/map';
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
