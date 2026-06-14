<?php

namespace dolphiq\redirect\tests\unit;

use Codeception\Test\Unit;
use Craft;

/**
 * Guards the plugin's i18n: every shipped locale must define exactly the same
 * keys as the canonical `en` source (no missing/extra keys, no empty values),
 * and Craft must resolve a plugin string to its translation for a given locale.
 *
 * If a new translatable string is added without updating every locale, this fails.
 */
class TranslationsTest extends Unit
{
    private const TRANSLATIONS_DIR = __DIR__ . '/../../src/translations';

    /** Locales the plugin ships, beyond the canonical `en` source. */
    private const LOCALES = [
        'nl', 'de', 'fr', 'es', 'it', 'da', 'nb', 'sv',
        'pt', 'pt-BR', 'pl', 'cs', 'fi', 'ja', 'zh-CN', 'ru',
    ];

    private function load(string $locale): array
    {
        $file = self::TRANSLATIONS_DIR . "/$locale/redirect.php";
        $this->assertFileExists($file, "Missing translation file for '$locale'.");
        return require $file;
    }

    public function testEnSourceIsNonEmpty(): void
    {
        $en = $this->load('en');
        $this->assertGreaterThan(50, count($en), 'Canonical en source looks too small.');
    }

    public function testEveryLocaleHasExactlyTheEnglishKeys(): void
    {
        $enKeys = array_keys($this->load('en'));
        sort($enKeys);

        foreach (self::LOCALES as $locale) {
            $keys = array_keys($this->load($locale));
            sort($keys);

            $missing = array_diff($enKeys, $keys);
            $extra = array_diff($keys, $enKeys);

            $this->assertSame([], array_values($missing), "Locale '$locale' is missing keys: " . implode(' | ', $missing));
            $this->assertSame([], array_values($extra), "Locale '$locale' has unknown keys: " . implode(' | ', $extra));
        }
    }

    public function testNoLocaleHasEmptyValues(): void
    {
        foreach (self::LOCALES as $locale) {
            foreach ($this->load($locale) as $key => $value) {
                $this->assertNotSame('', trim((string)$value), "Locale '$locale' has an empty value for '$key'.");
            }
        }
    }

    public function testCraftResolvesPluginTranslations(): void
    {
        // 'New redirect' is translated in every shipped locale.
        $this->assertSame('Nieuwe doorverwijzing', Craft::t('redirect', 'New redirect', [], 'nl'));
        $this->assertSame('Neue Weiterleitung', Craft::t('redirect', 'New redirect', [], 'de'));

        // Unknown strings fall back to the source text.
        $this->assertSame('definitely-not-a-key', Craft::t('redirect', 'definitely-not-a-key', [], 'de'));
    }

    public function testPlaceholdersArePreservedInTranslations(): void
    {
        $key = '{created} redirect(s) imported, {skipped} skipped.';
        foreach (self::LOCALES as $locale) {
            $value = $this->load($locale)[$key];
            $this->assertStringContainsString('{created}', $value, "Locale '$locale' dropped the {created} placeholder.");
            $this->assertStringContainsString('{skipped}', $value, "Locale '$locale' dropped the {skipped} placeholder.");
        }
    }
}
