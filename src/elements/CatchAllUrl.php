<?php
/**
 *
 * @author    dolphiq
 * @copyright Copyright (c) 2017 dolphiq
 * @link      https://dolphiq.nl/
 */

namespace dolphiq\redirect\elements;

use Craft;
use craft\base\Element;
use dolphiq\redirect\elements\db\CatchAllUrlQuery;
use dolphiq\redirect\elements\actions\DeleteCatchAllUrls;
use dolphiq\redirect\records\CatchAllUrl as CatchAllUrlRecord;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Edit;

use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;


class CatchAllUrl extends Element
{
    // Static
    // =========================================================================

    /**
     * @var string|null uri
     */
    public $catchedUri;
    /**
     * @var string|null $dateCreated
     */
    public $dateCreated;
    /**
     * @var string|null dateUpdated
     */
    public $dateUpdated;

    /**
     * @var string|null hitCount
     */
    public $hitCount;
    /**
     * @var int|null siteId
     */

    public $siteId;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('redirect', 'Catch All Url');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'catchallurl';
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasStatuses(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     *
     * @return UserQuery The newly created [[UserQuery]] instance.
     */
    public static function find(): ElementQueryInterface
    {
        return new CatchAllUrlQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSources(string $context = null): array
    {
        if ($context === 'index') {
            $sources = [
                [
                    'key' => '*',
                    'label' => Craft::t('redirect', 'All Catch all Urls'),
                    'criteria' => []
                ]
            ];
        }
        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['url'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'catchedUri' => Craft::t('redirect', 'Catched URL'),
            'dateCreated' => Craft::t('redirect', 'First hit'),
            'dateUpdated' => Craft::t('redirect', 'Last hit'),
            'hitCount' => Craft::t('redirect', 'Hit count'),
        ];
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'catchedUri' => ['label' => Craft::t('redirect', 'Catched URL')],
            'dateCreated' => ['label' => Craft::t('redirect', 'First hit')],
            'dateUpdated' => ['label' => Craft::t('redirect', 'Last hit')],
            'hitCount' => ['label' => Craft::t('app', 'Hit count')],

        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineActions(string $source = null): array
    {
        $actions = [];

        // Edit
        $actions[] = Craft::$app->getElements()->createAction(
            [
                'type' => Edit::class,
                'label' => Craft::t('redirect', 'Create redirect rule'),
            ]
        );

        // Delete
        $actions[] = DeleteCatchAllUrls::class;

        return $actions;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['catchedUri',  'dateCreated', 'dateUpdated', 'hitCount'];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return true;
    }

    public function getSupportedSites(): array
    {
        $supportedSites = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            //if($this->siteId < 1 || $this->siteId == $site->id) {
            $supportedSites[] = ['siteId' => $site->id, 'enabledByDefault' => false];
            //}
        }
        return $supportedSites;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('redirect/' . $this->id . '?siteId=' . $this->siteId);

        return $url;
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $statusCodesOptions = [
            '301' => 'Permanent redirect (301)',
            '302' => 'Temporarily redirect (302)',
        ];

        $html = Craft::$app->getView()->renderTemplate('redirect/_redirectfields', [
            'redirect' => $this,
            'isNewRedirect' => false,
            'meta' => false,
            'statusCodeOptions' => $statusCodesOptions,

        ]);

        $html .= parent::getEditorHtml();

        return $html;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['hitCount'], 'number', 'integerOnly' => true];
        $rules[] = [['catchedUri', ], 'string', 'max' => 255];
        $rules[] = [['catchedUri'], 'required'];
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew)
    {
        // Get the redirect record
        if (!$isNew) {
            $record = CatchAllUrlRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid redirect ID: '.$this->id);
            }
        } else {
            $record = new CatchAllUrlRecord();
            $record->id = $this->id;

            if ($this->hitCount > 0) {
                $record->hitCount = $this->hitCount;
            } else {
                $record->hitCount = 1;
            }

        }


        $record->catchedUri = $this->catchedUri;

        $record->save(false);

        parent::afterSave($isNew);
    }


    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function formatUrl(string $url): string
    {
        $resultUrl = $url;
        // trim spaces
        $resultUrl = trim($resultUrl);

        if (stripos($resultUrl, '://') !== false) {
            // complete url
            // check if the base url is there and strip if it does
            $resultUrl = str_ireplace($this->getSite()->baseUrl, '', $resultUrl);
        } else {
            // strip leading slash
            $resultUrl = ltrim($resultUrl, '/');
        }
        return $resultUrl;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function __toString()
    {
        try {
            return $this->getName();
        } catch (\Throwable $e) {
            ErrorHandler::convertExceptionToError($e);
        }
    }

    /**
     * Returns the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->catchedUri;
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'dateCreated';
        $names[] = 'dateUpdated';
        return $names;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {

            case 'baseUrl':

                return Html::encodeParams('<a href="{baseUrl}" target="_blank">test</a>', ['baseUrl' => $this->getSite()->baseUrl . $this->catchedUri]);

        }

        return parent::tableAttributeHtml($attribute);
    }
}
