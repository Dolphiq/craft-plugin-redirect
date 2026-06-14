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
use craft\elements\actions\Edit;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;
use dolphiq\redirect\elements\actions\DeleteRedirects;
use dolphiq\redirect\elements\db\RedirectQuery;
use dolphiq\redirect\records\Redirect as RedirectRecord;
use Throwable;

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
    public static function refHandle(): ?string
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
        return true;
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
    public function canView(User $user): bool
    {
        return true;
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
    public function getCpEditUrl(): ?string
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
                    'criteria' => [],
                ],
                [
                    'key' => 'permanent',
                    'label' => Craft::t('redirect', 'Permanent redirects'),
                    'criteria' => ['statusCode' => 301],
                ],
                [
                    'key' => 'temporarily',
                    'label' => Craft::t('redirect', 'Temporarily redirects'),
                    'criteria' => ['statusCode' => 302],
                ],
                /*
                 * @todo: add a toggle to set a redirect inactive
                 * [
                    'key' => 'inactive',
                    'label' => Craft::t('redirect', 'Inactive redirects'),
                    'criteria' => ['hitAt' => 60]

                ],*/
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
            'matchType' => Craft::t('redirect', 'Match type'),
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
            'matchType' => ['label' => Craft::t('redirect', 'Match type')],
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'sourceUrl':
                return Html::encode((string)$this->sourceUrl);

            case 'destinationUrl':
                return Html::encode((string)$this->destinationUrl);

            case 'statusCode':

                $statusCodesOptions = [
                    '301' => 'Permanent redirect (301)',
                    '302' => 'Temporarily redirect (302)',
                ];

                return $this->statusCode ? Html::encodeParams('{statusCode}', ['statusCode' => Craft::t('redirect', $statusCodesOptions[$this->statusCode])]) : '';

            case 'matchType':
                return Html::encode(ucfirst((string)$this->matchType));

            case 'baseUrl':

                return Html::encodeParams('<a href="{baseUrl}" target="_blank">test</a>', ['baseUrl' => $this->getSite()->baseUrl . $this->sourceUrl]);

        }

        return parent::attributeHtml($attribute);
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

        // Enable / disable (bulk)
        $actions[] = SetStatus::class;

        // Delete
        $actions[] = DeleteRedirects::class;

        return $actions;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['hitAt'], DateTimeValidator::class];
        $rules[] = [['hitCount'], 'number', 'integerOnly' => true];
        $rules[] = [['sourceUrl', 'destinationUrl'], 'string', 'max' => 1000];
        $rules[] = [['sourceUrl', 'destinationUrl'], 'required'];
        $rules[] = [['matchType'], 'in', 'range' => self::MATCH_TYPES];
        $rules[] = [['priority'], 'number', 'integerOnly' => true];
        return $rules;
    }

    /**
     * Supported match types.
     */
    public const MATCH_TYPES = ['exact', 'prefix', 'wildcard', 'pattern', 'regex'];

    /**
     * Infers a match type from a source URL's syntax (used as the default when one
     * isn't explicitly chosen). `prefix` is never inferred — it's an explicit choice.
     */
    public static function inferMatchType(string $sourceUrl): string
    {
        if (str_contains($sourceUrl, '*')) {
            return 'wildcard';
        }
        if (str_contains($sourceUrl, '<')) {
            return 'pattern';
        }
        return 'exact';
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
        // default the match type by inferring it from the source syntax
        if (empty($this->matchType)) {
            $this->matchType = self::inferMatchType((string)$this->sourceUrl);
        }

        return parent::beforeSave($isNew);
    }

    /**
     * @inheritdoc
     * @throws Exception if reasons
     */
    public function afterSave(bool $isNew): void
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
        $record->matchType = $this->matchType ?: self::inferMatchType((string)$this->sourceUrl);
        $record->priority = (int)$this->priority;
        $record->postDate = $this->postDate ? Db::prepareDateForDb($this->postDate) : null;
        $record->expiryDate = $this->expiryDate ? Db::prepareDateForDb($this->expiryDate) : null;

        $record->save(false);

        \dolphiq\redirect\RedirectPlugin::getInstance()->getRedirects()->invalidateCache();

        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterDelete(): void
    {
        \dolphiq\redirect\RedirectPlugin::getInstance()->getRedirects()->invalidateCache();

        parent::afterDelete();
    }


    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = ['sourceUrl', 'destinationUrl', 'matchType', 'statusCode', 'hitAt', 'hitCount', 'dateCreated'];

        return $attributes;
    }


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
    }

    /**
     * Use the sourceUrl as the string representation.
     *
     * @return string
     */
    /** @noinspection PhpInconsistentReturnPointsInspection */
    public function __toString(): string
    {
        try {
            return $this->getName();
        } catch (Throwable $e) {
            ErrorHandler::convertExceptionToError($e);
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        $names = parent::datetimeAttributes();
        $names[] = 'hitAt';
        $names[] = 'postDate';
        $names[] = 'expiryDate';
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
     * @var string|null matchType — exact | prefix | wildcard | pattern
     */
    public $matchType;

    /**
     * @var int priority — lower number = evaluated first on overlap
     */
    public $priority = 0;

    /**
     * @var \DateTime|null postDate — redirect only resolves from this moment
     */
    public $postDate;

    /**
     * @var \DateTime|null expiryDate — redirect stops resolving from this moment
     */
    public $expiryDate;

    /**
     * @var int|null siteId
     */

    public ?int $siteId;
}
