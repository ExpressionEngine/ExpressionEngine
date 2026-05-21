<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Addons\Rte;

use ExpressionEngine\Addons\Rte\RteHelper;
use ExpressionEngine\Addons\Rte\Service\RedactorMigrationService;
use ExpressionEngine\Addons\Rte\Service\RedactorService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RedactorMigrationServiceTest extends TestCase
{
    public function testLegacyToolsetTypeIsMappedToRedactorService()
    {
        $this->assertSame('redactor', RteHelper::normalizeToolsetType('redactorClassic'));
        $this->assertSame('redactor', RteHelper::normalizeToolsetType('redactorX'));
        $this->assertSame('RedactorService', RteHelper::getServiceNameForToolsetType('redactorClassic'));
        $this->assertSame('RedactorService', RteHelper::getServiceNameForToolsetType('redactorX'));
    }

    public function testMigrateLegacyToolbarMapsBasicToolsetToBasicDefaults()
    {
        $service = new RedactorMigrationService();

        $result = $service->migrateLegacyToolbar('redactorClassic', [
            'toolbar' => [
                'buttons' => ['bold', 'ul', 'ol'],
                'plugins' => [],
            ],
        ], 'Completely Arbitrary Name');

        $toolbar = $result['settings']['toolbar'];
        $this->assertSame('n', $toolbar['toolbar_addbar']);
        $this->assertSame('n', $toolbar['toolbar_context']);
        $this->assertContains('bulletlist', $toolbar['format']);
        $this->assertContains('numberedlist', $toolbar['format']);
    }

    public function testMigrateLegacyToolbarMapsSelectorAndAuditsUnsupportedButtons()
    {
        $service = new RedactorMigrationService();

        $result = $service->migrateLegacyToolbar('redactorClassic', [
            'toolbar' => [
                'buttons' => ['bold', 'indent', 'outdent', 'ol', 'ul'],
                'plugins' => ['selector', 'readmore', 'clips'],
            ],
        ], 'Legacy Custom');

        $plugins = $result['settings']['toolbar']['plugins'];
        $features = array_column($result['audit'], 'feature');

        $this->assertContains('blockid', $plugins);
        $this->assertContains('blockclass', $plugins);
        $this->assertContains('readmore', $plugins);
        $this->assertContains('button:indent', $features);
        $this->assertContains('button:outdent', $features);
        $this->assertContains('plugin:clips', $features);
    }

    public function testNormalizeReadMoreMarkupAddsLabelSpanWhenMissing()
    {
        $service = new RedactorMigrationService();

        $result = $service->normalizeReadMoreMarkup('<div class="readmore">Read More</div>');

        $this->assertTrue($result['changed']);
        $this->assertStringContainsString('<span class="readmore__label">', $result['html']);
        $this->assertStringContainsString('Read More', $result['html']);
    }

    public function testNormalizeReadMoreMarkupAddsClassToExistingSpan()
    {
        $service = new RedactorMigrationService();

        $result = $service->normalizeReadMoreMarkup(
            '<div class="readmore"><span>Read more</span></div>'
        );

        $this->assertTrue($result['changed']);
        $this->assertStringContainsString('class="readmore__label"', $result['html']);
    }

    public function testNormalizeReadMoreMarkupLeavesValidMarkupUntouched()
    {
        $service = new RedactorMigrationService();
        $html = '<div class="readmore"><span class="readmore__label">Read more</span></div>';

        $result = $service->normalizeReadMoreMarkup($html);

        $this->assertFalse($result['changed']);
        $this->assertSame($html, $result['html']);
    }

    public function testMigrateLegacyRedactorXBasicMapsToBasicDefaults()
    {
        $service = new RedactorMigrationService();
        $basicDefaults = RedactorService::defaultToolbars()['Redactor Basic'];

        $result = $service->migrateLegacyToolbar('redactorX', [
            'toolbar' => [
                'toolbar_addbar' => 'n',
                'toolbar_context' => 'n',
                'toolbar_control' => 'n',
                'toolbar_extrabar' => 'n',
                'editor' => ['format', 'bold', 'italic', 'link'],
                'format' => ['text', 'bulletlist', 'numberedlist'],
                'plugins' => ['filebrowser', 'rte_definedlinks', 'pages', 'blockclass'],
            ],
        ], 'Arbitrary Toolset');

        $this->assertSame('basic', $result['variant']);
        $toolbar = $result['settings']['toolbar'];
        $this->assertSame($basicDefaults['toolbar_addbar'], $toolbar['toolbar_addbar']);
        $this->assertSame($basicDefaults['toolbar_context'], $toolbar['toolbar_context']);
        $this->assertSame($basicDefaults['addbar'], $toolbar['addbar']);
        $this->assertSame($basicDefaults['plugins'], $toolbar['plugins']);
    }

    public function testMigrateLegacyRedactorClassicFullMapsToFullDefaultsWithoutNameHint()
    {
        $service = new RedactorMigrationService();
        $fullDefaults = RedactorService::defaultToolbars()['Redactor Full'];

        $result = $service->migrateLegacyToolbar('redactorClassic', [
            'toolbar' => [
                'buttons' => ['bold', 'html'],
                'plugins' => ['alignment'],
            ],
        ], 'Legacy Custom');

        $this->assertSame('full', $result['variant']);
        $toolbar = $result['settings']['toolbar'];
        $this->assertSame($fullDefaults['toolbar_extrabar'], $toolbar['toolbar_extrabar']);
        $this->assertSame($fullDefaults['toolbar_addbar'], $toolbar['toolbar_addbar']);
        $this->assertSame($fullDefaults['toolbar_context'], $toolbar['toolbar_context']);
        $this->assertContains('alignment', $toolbar['plugins']);
    }

    public function testMigrateLegacyRedactorXFullMapsToFullDefaultsWithoutNameHint()
    {
        $service = new RedactorMigrationService();
        $fullDefaults = RedactorService::defaultToolbars()['Redactor Full'];

        $result = $service->migrateLegacyToolbar('redactorX', [
            'toolbar' => [
                'toolbar_extrabar' => 'y',
                'toolbar_addbar' => 'y',
                'toolbar_context' => 'y',
                'toolbar_control' => 'y',
                'topbar' => ['undo', 'redo', 'hotkeys'],
                'addbar' => ['text', 'heading', 'table', 'line'],
                'context' => ['bold', 'italic', 'deleted', 'link'],
                'editor' => ['html', 'format', 'bold', 'italic', 'deleted', 'list', 'link'],
                'format' => ['text', 'h1', 'bulletlist', 'numberedlist'],
                'plugins' => ['underline', 'alignment', 'blockid', 'blockclass', 'blockcode', 'rte_definedlinks', 'pages', 'readmore', 'filebrowser', 'imageposition', 'imageresize'],
            ],
        ], 'Another Arbitrary Name');

        $this->assertSame('full', $result['variant']);
        $toolbar = $result['settings']['toolbar'];
        $this->assertSame($fullDefaults['toolbar_addbar'], $toolbar['toolbar_addbar']);
        $this->assertSame($fullDefaults['toolbar_context'], $toolbar['toolbar_context']);
        $this->assertSame($fullDefaults['toolbar_control'], $toolbar['toolbar_control']);
        $this->assertSame($fullDefaults['extrabar'], $toolbar['extrabar']);
        $this->assertSame($fullDefaults['plugins'], $toolbar['plugins']);
        $this->assertNotContains('image', $toolbar['addbar']);
    }

    public function testSettingsMatchCanonicalIdentifiesDefaultToolbarsFromSavedData()
    {
        $method = new ReflectionMethod(RedactorMigrationService::class, 'settingsMatchCanonical');
        $method->setAccessible(true);
        $service = new RedactorMigrationService();
        $basicSettings = array_merge(
            RedactorService::defaultConfigSettings(),
            ['toolbar' => RedactorService::defaultToolbars()['Redactor Basic']]
        );
        $fullSettings = array_merge(
            RedactorService::defaultConfigSettings(),
            ['toolbar' => RedactorService::defaultToolbars()['Redactor Full']]
        );

        $this->assertTrue($method->invoke($service, $basicSettings, $basicSettings));
        $this->assertTrue($method->invoke($service, $fullSettings, $fullSettings));
        $this->assertFalse($method->invoke($service, $basicSettings, $fullSettings));
    }

    public function testResolveAssignedToolsetIdPreservesValidSelectionsAndFallsBackByContext()
    {
        $method = new ReflectionMethod(RedactorMigrationService::class, 'resolveAssignedToolsetId');
        $method->setAccessible(true);
        $service = new RedactorMigrationService();
        $validToolsetIds = [
            11 => true,
            22 => true,
            33 => true,
        ];

        $this->assertSame(22, $method->invoke($service, 22, false, $validToolsetIds, 11, 33));
        $this->assertSame(11, $method->invoke($service, 0, false, $validToolsetIds, 11, 33));
        $this->assertSame(33, $method->invoke($service, 999, true, $validToolsetIds, 11, 33));
    }

    public function testGridRteContentUsesChannelGridFieldStorageNaming()
    {
        $fieldId = 42;
        $colId = 7;

        $this->assertSame('channel_grid_field_42', 'channel_grid_field_' . $fieldId);
        $this->assertSame('col_id_7', 'col_id_' . $colId);
    }
}
