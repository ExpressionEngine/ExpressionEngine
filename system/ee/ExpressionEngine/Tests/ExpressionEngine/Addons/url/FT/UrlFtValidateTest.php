<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../../../../eeObjectMock.php';

if (!class_exists('EE_Fieldtype')) {
    require_once BASEPATH . 'fieldtypes/EE_Fieldtype.php';
}

require_once PATH_ADDONS . 'url/ft.url.php';

use PHPUnit\Framework\TestCase;

class UrlFtValidateTest extends TestCase
{
    public function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Build a URL fieldtype with the given allowed schemes.
     *
     * Some tests stub EE_Fieldtype without get_setting(), so read the setting directly.
     *
     * @param  array $schemes Allowed URL schemes
     * @return Url_Ft
     */
    private function makeFieldtype(array $schemes)
    {
        $fieldtype = new class () extends Url_Ft {
            public function get_setting($key, $default = false)
            {
                return $this->settings[$key] ?? $default;
            }
        };
        $fieldtype->settings = ['allowed_url_schemes' => $schemes];

        return $fieldtype;
    }

    public function testTelUriIsValidWhenTelSchemeIsAllowed()
    {
        $fieldtype = $this->makeFieldtype(['http://', 'https://', 'tel:']);

        $this->assertTrue($fieldtype->validate('tel:+1-555-555-1234'));
        $this->assertTrue($fieldtype->validate('tel:+15555551234'));
    }

    public function testTelUriIsInvalidWhenTelSchemeIsNotAllowed()
    {
        $fieldtype = $this->makeFieldtype(['http://', 'https://', 'mailto:']);

        $this->assertNotTrue($fieldtype->validate('tel:+15555551234'));
    }

    public function testTelSlashSlashSettingStillAcceptsTelSlashSlashUrls()
    {
        $fieldtype = $this->makeFieldtype(['tel://']);

        $this->assertTrue($fieldtype->validate('tel://+15555551234'));
    }

    public function testMailtoIsUnchanged()
    {
        $this->assertTrue($this->makeFieldtype(['mailto:'])->validate('mailto:someone@example.com'));
        $this->assertNotTrue($this->makeFieldtype(['tel:'])->validate('mailto:someone@example.com'));
    }

    public function testTelSchemeIsOffered()
    {
        $method = new ReflectionMethod(Url_Ft::class, 'getSchemes');
        $method->setAccessible(true);
        $schemes = $method->invoke($this->makeFieldtype([]));

        $this->assertArrayHasKey('tel:', $schemes);
        $this->assertArrayHasKey('tel://', $schemes);
    }
}
