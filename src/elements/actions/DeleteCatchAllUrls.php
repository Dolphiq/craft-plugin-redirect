<?php

namespace dolphiq\redirect\elements\actions;

use Craft;
use craft\base\ElementAction;
use dolphiq\redirect\elements\CatchAllUrl;
use dolphiq\redirect\records\CatchAllUrl as CatchAllUrlRecord;
use craft\elements\db\ElementQueryInterface;
use yii\base\Exception;

/**
 * DeleteRedirects represents a Delete URL element action.
 *
 */
class DeleteCatchAllUrls extends ElementAction
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('app', 'Delete…');
    }

    /**
     * @inheritdoc
     */
    public static function isDestructive(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getConfirmationMessage()
    {
        return Craft::t('redirect', 'Are you sure you want to delete the selected registered urls?');
    }

    /**
     * Performs the action on any elements that match the given criteria.
     *
     * @param ElementQueryInterface $query The element query defining which elements the action should affect.
     *
     * @return bool Whether the action was performed successfully.
     */
    public function performAction(ElementQueryInterface $query): bool
    {
        try {
            foreach ($query->all() as $url) {

                $res = Craft::$app->getElements()->deleteElement($url);

                if ($res) {
                    $record = CatchAllUrlRecord::findOne($url->id);
                    if($record) {
                        $record->delete();
                    }

                }

            }
        } catch (Exception $exception) {
            $this->setMessage($exception->getMessage());

            return false;
        }

        $this->setMessage(Craft::t('redirect', 'Registered urls deleted.'));

        return true;
    }
}
