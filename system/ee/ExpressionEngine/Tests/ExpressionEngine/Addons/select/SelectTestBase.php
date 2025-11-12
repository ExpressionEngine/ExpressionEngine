<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
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
 * Base test class for Select fieldtype tests
 */
class SelectTestBase extends OptionFieldtypeTestBase
{
    protected $fieldtype;

    public function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = $this->createMockFieldtype();
    }

    /**
     * Create a mock fieldtype instance for Select
     */
    protected function createMockFieldtype()
    {
        // Use the base class method to get a single-value fieldtype
        $fieldtype = $this->getMockFieldtypeWithSettings();

        // Select specific display settings - set before base class setup
        $fieldtype->shouldReceive('display_settings')->andReturn([
            'field_options_select' => [
                'label' => 'field_options',
                'group' => 'select',
                'settings' => []
            ]
        ]);

        $fieldtype->shouldReceive('grid_display_settings')->andReturn([
            'field_options' => [
                'label' => 'field_options',
                'group' => 'select',
                'settings' => []
            ]
        ]);

        // Mark display settings as configured to prevent base class override
        $this->markDisplaySettingsConfigured($fieldtype);

        return $fieldtype;
    }
}

// EOF
