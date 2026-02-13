<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use PHPUnit\Framework\TestCase;

class FluidFieldFtFrontendDisplayTest extends TestCase
{
    public function testDisplayFieldReturnsEmptyStringOutsideControlPanel()
    {
        $basePath = __DIR__ . '/../FluidFieldTestBase.php';
        $bootstrapPath = dirname(__DIR__, 4) . '/bootstrap.php';

        $script = <<<'PHP'
<?php
error_reporting(0);
ini_set('display_errors', '0');
require_once __BOOTSTRAP__;
if (!defined('REQ')) {
    define('REQ', 'PAGE');
}
require_once __BASE__;
ee()->resetMocks();
$session = new FluidFieldSessionStub();
$input = new FluidFieldInputStub();
$extensions = new FluidFieldExtensionsStub();
$db = new FluidFieldDbStub();
$load = new FluidFieldLoadStub();
$javascript = new FluidFieldJavascriptStub();
$cp = new FluidFieldCpStub();
$logger = new FluidFieldLoggerStub();
$localize = new FluidFieldLocalizeStub();
$config = new FluidFieldConfigStub();
$model = new FluidFieldModelServiceStub();
$view = new FluidFieldViewServiceStub();
ee()->setMock('session', $session);
ee()->setMock('input', $input);
ee()->setMock('extensions', $extensions);
ee()->setMock('db', $db);
ee()->setMock('load', $load);
ee()->setMock('javascript', $javascript);
ee()->setMock('cp', $cp);
ee()->setMock('logger', $logger);
ee()->setMock('localize', $localize);
ee()->setMock('config', $config);
ee()->setMock('Model', $model);
ee()->setMock('View', $view);
ee()->setMock('Request', new FluidFieldRequestStub());
ee()->setMock('LivePreview', new FluidFieldLivePreviewStub());
ee()->setMock('CP/Modal', new FluidFieldModalStub());
ee()->setMock('CP/Alert', new FluidFieldAlertServiceStub());
ee()->setMock('CP/URL', new FluidFieldUrlServiceStub());
ee()->setMock('Addon', new FluidFieldAddonServiceStub());
ee()->setMock('Validation', new FluidFieldValidationServiceStub());
ee()->setMock('fluid_field_parser', new FluidFieldParserStub());
ee()->setMock('grid_parser', new FluidFieldGridParserStub());
ee()->setMock('grid_lib', new FluidFieldGridLibStub());
$fieldtype = new Fluid_field_ft();
$fieldtype->_init([
    'id' => 10,
    'name' => 'fluid_content',
    'content_id' => 99,
    'content_type' => 'channel',
]);
$fieldtype->settings = [
    'field_channel_fields' => [],
    'field_channel_field_groups' => [],
    'field_short_name' => 'fluid_content',
    'field_label' => 'Fluid Content',
];
$result = $fieldtype->display_field('stored-value');
echo json_encode([
    'result' => $result,
    'cp_scripts' => count($cp->scripts),
]);
PHP;

        $script = str_replace(
            ['__BASE__', '__BOOTSTRAP__'],
            [var_export($basePath, true), var_export($bootstrapPath, true)],
            $script
        );

        $tmpScriptPath = tempnam(sys_get_temp_dir(), 'fluid-field-ft-');
        $this->assertNotFalse($tmpScriptPath);
        file_put_contents($tmpScriptPath, $script);

        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmpScriptPath) . ' 2>&1';
        $outputLines = [];
        $exitCode = 0;
        exec($command, $outputLines, $exitCode);

        @unlink($tmpScriptPath);

        $this->assertSame(0, $exitCode, "Command failed: {$command}\n" . implode("\n", $outputLines));
        $lines = array_values(array_filter(array_map('trim', $outputLines)));
        $decoded = json_decode((string) end($lines), true);
        $this->assertIsArray($decoded);
        $this->assertSame('', $decoded['result']);
        $this->assertSame(0, $decoded['cp_scripts']);
    }
}
