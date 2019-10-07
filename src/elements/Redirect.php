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
use dolphiq\redirect\elements\db\RedirectQuery;
use craft\elements\db\ElementQueryInterface;
use craft\elements\actions\Edit;
use dolphiq\redirect\elements\actions\DeleteRedirects;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;
use dolphiq\redirect\records\Redirect as RedirectRecord;

class Redirect extends Element
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('redirect', 'Redirect');
    }

    /**
     * @inheritdoc
     */
    public static function refHandle()
    {
        return 'redirect';
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
        return new RedirectQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return false;
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

    /**
     * Returns the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->sourceUrl;
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
                    'label' => Craft::t('redirect', 'All redirects'),
                    'criteria' => []
                ],
                [
                    'key' => 'permanent',
                    'label' => Craft::t('redirect', 'Permanent redirects'),
                    'criteria' => ['statusCode' => 301]
                ],
                [
                    'key' => 'temporarily',
                    'label' => Craft::t('redirect', 'Temporarily redirects'),
                    'criteria' => ['statusCode' => 302]
                ],
                [
                    'key' => 'inactive',
                    'label' => Craft::t('redirect', 'Inactive redirects'),
                    'criteria' => ['hitAt' => 60]

                ],
            ];
        }
        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected static function defineSearchableAttributes(): array
    {
        return ['sourceUrl', 'destinationUrl'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'sourceUrl' => Craft::t('redirect', 'Source URL'),
            'destinationUrl' => Craft::t('redirect', 'Destination URL'),
            'hitAt' => Craft::t('redirect', 'Last hit'),
            'statusCode' => Craft::t('redirect', 'Redirect type'),
            'hitCount' => Craft::t('redirect', 'Hit count'),
            'elements.dateCreated' => Craft::t('app', 'Date Created'),
        ];
        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'sourceUrl' => ['label' => Craft::t('redirect', 'Source URL')],
            'destinationUrl' => ['label' => Craft::t('redirect', 'Destination URL')],
            'hitAt' => ['label' => Craft::t('redirect', 'Last hit')],
            'hitCount' => ['label' => Craft::t('redirect', 'Hit count')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'statusCode' => ['label' => Craft::t('redirect', 'Redirect type')],
            // 'baseUrl' => ['label' => Craft::t('redirect', '')],

        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'statusCode':

                $statusCodesOptions = [
                    '301' => 'Permanent redirect (301)',
                    '302' => 'Temporarily redirect (302)',
                ];

                return $this->statusCode ? Html::encodeParams('{statusCode}', ['statusCode' => Craft::t('redirect', $statusCodesOptions[$this->statusCode])]) : '';

            case 'baseUrl':

                return Html::encodeParams('<a href="{baseUrl}" target="_blank">test</a>', ['baseUrl' => $this->getSite()->baseUrl . $this->sourceUrl]);

        }

        return parent::tableAttributeHtml($attribute);
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
                'label' => Craft::t('redirect', 'Edit redirect'),
            ]
        );

        // Delete
        $actions[] = DeleteRedirects::class;

        return $actions;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['hitAt'], DateTimeValidator::class];
        $rules[] = [['hitCount'], 'number', 'integerOnly' => true];
        $rules[] = [['sourceUrl', 'destinationUrl'], 'string', 'max' => 1000];
        $rules[] = [['sourceUrl', 'destinationUrl'], 'required'];
        return $rules;
    }

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
            $record = RedirectRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid redirect ID: ' . $this->id);
            }
        } else {
            $record = new RedirectRecord();
            $record->id = $this->id;

            if ($this->hitCount > 0) {
                $record->hitCount = $this->hitCount;
            } else {
                $record->hitCount = 0;
            }

            if ($record->hitAt != null) {
                $record->hitAt = $this->hitAt;
            } else {
                $record->hitAt = null;
            }
        }


        $record->sourceUrl = $this->formatUrl($this->sourceUrl);
        $record->destinationUrl = $this->formatUrl($this->destinationUrl);
        $record->statusCode = $this->statusCode;

        $record->save(false);

        parent::afterSave($isNew);
    }


    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['sourceUrl', 'destinationUrl', 'statusCode', 'hitAt', 'hitCount', 'dateCreated'];

        return $attributes;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Use the sourceUrl as the string representation.
     *
     * @return string
     */
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
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'hitAt';
        return $names;
    }


    // Properties
    // =========================================================================

    /**
     * @var string|null sourceUrl
     */
    public $sourceUrl;

    /**
     * @var string|null destinationUrl
     */
    public $destinationUrl;

    /**
     * @var string|null hitAt
     */
    public $hitAt;

    /**
     * @var string|null hitCount
     */
    public $hitCount;

    /**
     * @var string|null statusCode
     */
    public $statusCode;

    /**
     * @var int|null siteId
     */

    public $siteId;
}
