<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

// Bootstrap minimal EE environment so we can include the real class
if (!defined('APP_VER')) {
    define('APP_VER', '7.5.14');
}
if (!defined('BASEPATH')) {
    define('BASEPATH', __DIR__ . '/../../../legacy/');
}
// Determine addons path relative to this file when core bootstrap is not used
$__addons = (defined('SYSPATH') ? (SYSPATH . 'ee/ExpressionEngine/Addons/') : (__DIR__ . '/../../../../Addons/'));
if (!defined('PATH_ADDONS')) {
    define('PATH_ADDONS', $__addons);
}
if (!defined('PATH_PRO_ADDONS')) {
    define('PATH_PRO_ADDONS', PATH_ADDONS);
}
if (!defined('PATH_MOD')) {
    define('PATH_MOD', PATH_ADDONS);
}

// Include the eeObjectMock for testing
require_once __DIR__ . '/../../../eeObjectMock.php';
// Include the custom field helper for encode/decode functions
require_once __DIR__ . '/../../../../../legacy/helpers/custom_field_helper.php';
// Include the OptionFieldtypeTestBase
require_once __DIR__ . '/../OptionFieldtypeTestBase.php';

use PHPUnit\Framework\TestCase;
use Mockery as m;

/**
 * Base test class for Multi Select fieldtype tests
 */
abstract class MultiSelectTestBase extends OptionFieldtypeTestBase
{
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->createMockFieldtype();
    }

    /**
     * Create a mock fieldtype instance for Multi Select
     */
    protected function createMockFieldtype()
    {
        // Create mock and mark display settings as configured BEFORE calling base class methods
        $fieldtype = m::mock(\stdClass::class)->makePartial();

        // Mark as configured to prevent base class from setting up display_settings
        $this->markDisplaySettingsConfigured($fieldtype);

        // Set up display settings for multi_select BEFORE calling base class methods
        $fieldtype->shouldReceive('display_settings')->andReturn([
            'field_options_multi_select' => [
                'label' => 'field_options',
                'group' => 'multi_select',
                'settings' => []
            ]
        ]);

        $fieldtype->shouldReceive('grid_display_settings')->andReturn([
            'field_options' => [
                'label' => 'field_options',
                'group' => 'multi_select',
                'settings' => []
            ]
        ]);

        // Setup basic fieldtype mocks using base class methods
        $this->setupCommonFieldtypeMocks($fieldtype);

        // Multi-value specific mocks
        $fieldtype->shouldReceive('display_field')->andReturn('<div>Mock display</div>');
        $fieldtype->shouldReceive('grid_display_field')->andReturn('<div>Mock grid display</div>');
        $fieldtype->shouldReceive('validate')->andReturn(true);

        // Setup multi-value behaviors
        $this->setupSaveMock($fieldtype, true);
        $this->setupParseSingleMock($fieldtype, true);
        $this->setupReplaceTagMock($fieldtype, true);
        $this->setupParseMultiMock($fieldtype);
        $this->setupReplaceLengthMock($fieldtype);
        $this->setupRenderTableCellMock($fieldtype, true);
        $this->setupFlattenMock($fieldtype);

        return $fieldtype;
    }
}

// EOF
