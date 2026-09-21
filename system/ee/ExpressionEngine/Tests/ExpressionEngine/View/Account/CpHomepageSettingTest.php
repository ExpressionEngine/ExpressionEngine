<?php

namespace ExpressionEngine\Tests\View\Account;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

/**
 * Isolate the real helpers from other tests' global function stubs.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CpHomepageSettingTest extends TestCase
{
    /**
     * @dataProvider homepageValues
     */
    public function testStoredCustomHomepageStaysInOneInputValue($mode, $value)
    {
        require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
        if (!defined('REQ')) {
            define('REQ', 'CP');
        }
        require_once BASEPATH . 'helpers/form_helper.php';

        ee()->config->setItem('multiple_sites_enabled', 'n');
        ee()->config->setItem('site_id', 1);
        $member = (object) ['cp_homepage' => $mode, 'cp_homepage_custom' => $value];
        $allowed_channels = [1 => 'News'];
        $selected_channel = 1;

        ob_start();
        try {
            include SYSPATH . 'ee/ExpressionEngine/View/account/cp_homepage_setting.php';
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        $document = new DOMDocument();
        $this->assertTrue($document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new DOMXPath($document);
        $inputs = $xpath->query('//input[@name="cp_homepage_custom"]');
        $this->assertSame(1, $inputs->length);
        $input = $inputs->item(0);
        $this->assertFalse($input->hasAttribute('data-ee-check'));
        $this->assertSame($value, $input->getAttribute('value'));
        $this->assertSame('text', $input->getAttribute('type'));
        $this->assertSame(3, $input->attributes->length);
        $this->assertSame(1, $input->parentNode->getElementsByTagName('*')->length);

        $selected = $xpath->query('//input[@name="cp_homepage" and @checked]');
        $this->assertSame(1, $selected->length);
        $this->assertSame($mode, $selected->item(0)->getAttribute('value'));
    }

    public static function homepageValues(): array
    {
        $marker = 'sample" data-ee-check="retained';

        return [
            'ordinary custom route' => ['custom', 'publish/edit'],
            'stored marker with overview selected' => ['overview', $marker],
            'stored marker with custom selected' => ['custom', $marker],
            'punctuation round trip' => ['custom', 'A "quote", O\'Brien & <notes>'],
            'empty value with default selected' => ['', ''],
        ];
    }
}
