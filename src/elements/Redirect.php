<?php
/**
 * Craft Redirect plugin
 *
 * @author    Venveo
 * @copyright Copyright (c) 2017 dolphiq
 * @copyright Copyright (c) 2019 Venveo
 */

namespace venveo\redirect\elements;

use Craft;
use craft\base\Element;
use craft\elements\actions\Edit;
use craft\elements\actions\Restore;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;
use craft\web\ErrorHandler;
use Throwable;
use venveo\redirect\elements\actions\DeleteRedirects;
use venveo\redirect\elements\db\RedirectQuery;
use venveo\redirect\models\Settings;
use venveo\redirect\Plugin;
use venveo\redirect\records\Redirect as RedirectRecord;
use yii\db\Exception;
use yii\db\StaleObjectException;

class Redirect extends Element
{
    public const TYPE_STATIC = 'static';
    public const TYPE_DYNAMIC = 'dynamic';

    public const STATUS_CODE_OPTIONS = [
        '301' => 'Permanent redirect (301)',
        '302' => 'Temporarily redirect (302)'
    ];

    public const TYPE_OPTIONS = [
        'static' => 'Static',
        'dynamic' => 'Dynamic (RegExp)',
    ];

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('vredirect', 'Redirect');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('vredirect', 'Redirects');
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
     * @return RedirectQuery The newly created [[RedirectQuery]] instance.
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
        return true;
    }


    public function getSupportedSites(): array
    {
        $supportedSites = [];
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $supportedSites[] = ['siteId' => $site->id, 'enabledByDefault' => false];
        }
        return $supportedSites;
    }

    /**
     * @inheritdoc
     */
    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('redirect/redirects/'.$this->id.'?siteId='.$this->siteId);
    }

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplate('vredirect/redirects/_redirectfields', [
            'redirect' => $this,
            'isNewRedirect' => false,
            'meta' => false,
            'statusCodeOptions' => self::STATUS_CODE_OPTIONS,
            'typeOptions' => self::TYPE_OPTIONS
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
        $sources = [];
        if ($context === 'index') {
            $sources = [
                [
                    'key' => '*',
                    'label' => Craft::t('vredirect', 'All Redirects'),
                    'criteria' => []
                ],
                [
                    'key' => 'permanent',
                    'label' => Craft::t('vredirect', 'Permanent (301) Redirects'),
                    'criteria' => ['statusCode' => 301]
                ],
                [
                    'key' => 'temporarily',
                    'label' => Craft::t('vredirect', 'Temporary (302) Redirects'),
                    'criteria' => ['statusCode' => 302]
                ],
                [
                    'key' => 'inactive',
                    'label' => Craft::t('vredirect', 'Stale Redirects'),
                    'criteria' => ['hitAt' => 60]
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
        return ['sourceUrl', 'destinationUrl'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        $attributes = [
            'dolphiq_redirects.sourceUrl' => Craft::t('vredirect', 'Source URL'),
            'dolphiq_redirects.type' => Craft::t('vredirect', 'Type'),
            'dolphiq_redirects.destinationUrl' => Craft::t('vredirect', 'Destination URL'),
            'dolphiq_redirects.hitAt' => Craft::t('vredirect', 'Last hit'),
            'dolphiq_redirects.statusCode' => Craft::t('vredirect', 'Redirect type'),
            'dolphiq_redirects.hitCount' => Craft::t('vredirect', 'Hit count'),
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
            'sourceUrl' => ['label' => Craft::t('vredirect', 'Source URL')],
            'type' => ['label' => Craft::t('vredirect', 'Type')],
            'destinationUrl' => ['label' => Craft::t('vredirect', 'Destination URL')],
            'hitAt' => ['label' => Craft::t('vredirect', 'Last hit')],
            'hitCount' => ['label' => Craft::t('vredirect', 'Hit count')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'statusCode' => ['label' => Craft::t('vredirect', 'Redirect type')],
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

                return $this->statusCode ? Html::encodeParams('{statusCode}', ['statusCode' => Craft::t('vredirect', self::STATUS_CODE_OPTIONS[$this->statusCode])]) : '';

            case 'baseUrl':

                return Html::encodeParams('<a href="{baseUrl}" target="_blank">test</a>', ['baseUrl' => $this->getSite()->baseUrl.$this->sourceUrl]);
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
                'label' => Craft::t('vredirect', 'Edit redirect'),
            ]
        );

        // Delete
        $actions[] = DeleteRedirects::class;


        // Restore
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Restore::class,
            'successMessage' => Craft::t('vredirect', 'Redirects restored.'),
            'partialSuccessMessage' => Craft::t('vredirect', 'Some redirects restored.'),
            'failMessage' => Craft::t('vredirect', 'Redirects not restored.'),
        ]);

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
        $rules[] = [['sourceUrl', 'destinationUrl'], 'string', 'max' => 255];
        $rules[] = [['sourceUrl', 'destinationUrl', 'type'], 'required'];
        $rules[] = [['type'], 'in', 'range' => [self::TYPE_STATIC, self::TYPE_DYNAMIC]];
        $rules[] = [['statusCode'], 'in', 'range' => ['301', '302']];
        return $rules;
    }

    /**
     * Cleans a URL by removing its base URL if it's a relative one
     * Also strip leading slashes from absolute URLs
     * @param string $url
     * @param bool $isSource
     * @return string
     */
    public function formatUrl(string $url, $isSource = false): string
    {
        /** @var Settings $settings */
        $settings = Plugin::getInstance()->getSettings();

        $resultUrl = $url;
        $urlInfo = parse_url($resultUrl);
        $siteUrlHost = parse_url($this->site->baseUrl, PHP_URL_HOST);
        // If we're the source and we're static or we're not the source, we should check for relative URLs
        if ($this->type === self::TYPE_STATIC || !$isSource) {
            // If our redirect source or destination has our site URL, let's strip it out
            if (isset($urlInfo['host']) && $urlInfo['host'] === $siteUrlHost) {
                unset($urlInfo['scheme'], $urlInfo['host'], $urlInfo['port']);
            }

            // We're down to a relative URL, let's strip the leading slash from the path
            if (!isset($urlInfo['host']) && isset($urlInfo['path'])) {
                $urlInfo['path'] = ltrim($urlInfo['path'], '/');
            }

            // Remove the trailing slash from the path if enabled
            if (isset($urlInfo['path']) && $settings->trimTrailingSlashFromPath) {
                $urlInfo['path'] = rtrim($urlInfo['path'], '/');
            }

            // Rebuild our URL
            $resultUrl = self::unparse_url($urlInfo);
        }
        return $resultUrl;
    }


    /**
     * Source: https://www.php.net/manual/en/function.parse-url.php#106731
     * @param array $parsed_url
     * @return string
     */
    private static function unparse_url($parsed_url): string
    {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = $parsed_url['host'] ?? '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = $parsed_url['user'] ?? '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $parsed_url['path'] ?? '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Soft-delete the record with the element
     *
     * @return bool
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function beforeDelete(): bool
    {
        $record = RedirectRecord::findOne($this->id);
        if ($record) {
            $record->softDelete();
        }
        return parent::beforeDelete();
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function afterSave(bool $isNew)
    {
        // Get the redirect record
        if (!$isNew) {
            $record = RedirectRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid redirect ID: '.$this->id);
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


        $record->sourceUrl = $this->formatUrl(trim($this->sourceUrl), true);
        $record->destinationUrl = $this->formatUrl(trim($this->destinationUrl), false);
        $record->statusCode = $this->statusCode;
        $record->type = $this->type;

        $record->save(false);

        // remove form other sites
        Craft::$app->getDb()->createCommand()
            ->delete('{{%elements_sites}}', [
                'AND',
                ['elementId' => $record->id],
                ['!=', 'siteId', $this->siteId]
            ])
            ->execute();
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
        } catch (Throwable $e) {
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
     * @var string type
     */
    public $type;

    /**
     * @var int|null siteId
     */
    public $siteId;
}
