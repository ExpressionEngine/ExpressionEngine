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
        ], 'Redactor Classic Basic');

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
                'topbar' => ['undo'],
                'plugins' => [],
            ],
        ], 'RedactorX Basic');

        $toolbar = $result['settings']['toolbar'];
        $this->assertSame($basicDefaults['toolbar_addbar'], $toolbar['toolbar_addbar']);
        $this->assertSame($basicDefaults['toolbar_context'], $toolbar['toolbar_context']);
        $this->assertContains('undo', $toolbar['extrabar']);
    }

    public function testMigrateLegacyRedactorClassicFullMapsToFullDefaults()
    {
        $service = new RedactorMigrationService();
        $fullDefaults = RedactorService::defaultToolbars()['Redactor Full'];

        $result = $service->migrateLegacyToolbar('redactorClassic', [
            'toolbar' => [
                'buttons' => ['bold', 'html'],
                'plugins' => ['alignment'],
            ],
        ], 'Redactor Classic Full');

        $toolbar = $result['settings']['toolbar'];
        $this->assertSame($fullDefaults['toolbar_extrabar'], $toolbar['toolbar_extrabar']);
        $this->assertSame($fullDefaults['toolbar_addbar'], $toolbar['toolbar_addbar']);
        $this->assertSame($fullDefaults['toolbar_context'], $toolbar['toolbar_context']);
        $this->assertContains('alignment', $toolbar['plugins']);
    }

    public function testLegacyBasicLabelPatternsMatchConsolidationTargets()
    {
        $method = new ReflectionMethod(RedactorMigrationService::class, 'isLegacyBasicLabel');
        $method->setAccessible(true);
        $service = new RedactorMigrationService();

        foreach (['RedactorX Basic', 'redactor classic basic', 'RedactorClassic Basic (legacy)', 'Redactor Basic (Migrated 3-1)'] as $name) {
            $this->assertTrue($method->invoke($service, strtolower($name)), "Expected basic label: {$name}");
        }

        $this->assertFalse($method->invoke($service, 'redactor full'));
        $this->assertFalse($method->invoke($service, 'my custom toolset'));
    }

    public function testLegacyFullLabelPatternsMatchConsolidationTargets()
    {
        $method = new ReflectionMethod(RedactorMigrationService::class, 'isLegacyFullLabel');
        $method->setAccessible(true);
        $service = new RedactorMigrationService();

        foreach (['RedactorX Full', 'redactor classic full', 'RedactorClassic Full (legacy)', 'Redactor Full (Migrated 5-2)'] as $name) {
            $this->assertTrue($method->invoke($service, strtolower($name)), "Expected full label: {$name}");
        }

        $this->assertFalse($method->invoke($service, 'redactor basic'));
        $this->assertFalse($method->invoke($service, 'legacy custom'));
    }

    public function testGridRteContentUsesChannelGridFieldStorageNaming()
    {
        $fieldId = 42;
        $colId = 7;

        $this->assertSame('channel_grid_field_42', 'channel_grid_field_' . $fieldId);
        $this->assertSame('col_id_7', 'col_id_' . $colId);
    }
}
