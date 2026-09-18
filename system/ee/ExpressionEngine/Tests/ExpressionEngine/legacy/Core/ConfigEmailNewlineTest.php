<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Core;

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ConfigEmailNewlineTest extends TestCase
{
    /**
     * Release the shared ExpressionEngine test doubles.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
        ee()->config->resetConfig();
        parent::tearDown();
    }

    /**
     * Apply the selected newline to email encoding unless explicitly overridden.
     *
     * @dataProvider emailNewlineProvider
     * @param array $preferences
     * @param array $overrides
     * @param string $expectedNewline
     * @param string $expectedCrlf
     * @return void
     */
    public function testEmailLineEndings(array $preferences, array $overrides, $expectedNewline, $expectedCrlf): void
    {
        require_once BASEPATH . 'core/Config.php';
        if (!defined('APP_NAME')) {
            define('APP_NAME', 'ExpressionEngine');
        }
        require_once BASEPATH . 'libraries/Email.php';

        $row = ['site_id' => 1, 'site_name' => 'default_site', 'site_label' => 'Default Site'];
        $query = $this->getMockBuilder(\stdClass::class)->addMethods(['num_rows', 'row_array'])->getMock();
        $query->method('num_rows')->willReturn(1);
        $query->method('row_array')->willReturn($row);

        $db = $this->getMockBuilder(\stdClass::class)->addMethods(['get_where', 'table_exists'])->getMock();
        $db->method('get_where')->with('sites', ['site_id' => 1])->willReturn($query);
        $db->method('table_exists')->with('config')->willReturn(true);
        ee()->setMock('db', $db);

        $model = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['get', 'filter', 'all', 'getDictionary'])->getMock();
        $model->method('get')->with('Config')->willReturnSelf();
        $model->method('filter')->with('site_id', 'IN', [0, 1])->willReturnSelf();
        $model->method('all')->willReturnSelf();
        $model->method('getDictionary')->with('key', 'value')->willReturn($preferences);
        ee()->setMock('Model', $model);

        $config = $this->getMockBuilder(\EE_Config::class)
            ->disableOriginalConstructor()->onlyMethods(['item'])->getMock();
        $config->default_ini = array_merge(['multiple_sites_enabled' => 'n', 'charset' => 'UTF-8'], $overrides);
        $config->config = $config->default_ini;
        $config->method('item')->willReturnCallback(function ($key) use ($config) {
            return $config->config[$key] ?? false;
        });
        ee()->setMock('config', $config);

        $config->config = $config->site_prefs('', 1, false);
        $this->assertSame($expectedNewline, $config->config['email_newline']);
        $this->assertSame($expectedCrlf, $config->config['email_crlf']);

        $email = (new \ReflectionClass(\EE_Email::class))->newInstanceWithoutConstructor();
        $email->EE_initialize();
        $this->assertSame($expectedNewline, $email->newline);
        $this->assertSame($expectedCrlf, $email->crlf);

        $encoder = new \ReflectionMethod(\EE_Email::class, '_prep_quoted_printable');
        if (PHP_VERSION_ID < 80100) {
            $encoder->setAccessible(true);
        }
        $encoded = $encoder->invoke($email, '<p>' . str_repeat('Example HTML content. ', 12) . '</p>');
        $this->assertStringContainsString('=' . $expectedCrlf, $encoded);
        if ($expectedCrlf === "\r\n") {
            $this->assertSame(0, preg_match('/(?<!\r)\n/', $encoded));
        }
    }

    /**
     * Provide stored preferences and explicit configuration overrides.
     *
     * @return array
     */
    public static function emailNewlineProvider(): array
    {
        return [
            'default LF' => [[], [], "\n", "\n"],
            'CP LF' => [['email_newline' => '\n'], [], "\n", "\n"],
            'CP CRLF' => [['email_newline' => '\r\n'], [], "\r\n", "\r\n"],
            'CP CR' => [['email_newline' => '\r'], [], "\r", "\r"],
            'literal CRLF' => [['email_newline' => "\r\n"], [], "\r\n", "\r\n"],
            'newline override' => [['email_newline' => '\n'], ['email_newline' => "\r\n"], "\r\n", "\r\n"],
            'explicit LF override' => [['email_newline' => '\r\n'], ['email_crlf' => "\n"], "\r\n", "\n"],
            'explicit CRLF override' => [['email_newline' => '\n'], ['email_crlf' => '\r\n'], "\n", "\r\n"],
            'explicit CR override' => [['email_newline' => '\r\n'], ['email_crlf' => '\r'], "\r\n", "\r"],
            'invalid override keeps LF fallback' => [['email_newline' => '\r\n'], ['email_crlf' => 'invalid'], "\r\n", "\n"],
        ];
    }
}
