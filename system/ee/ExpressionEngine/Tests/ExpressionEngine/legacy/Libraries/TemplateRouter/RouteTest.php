<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries\TemplateRouter;

require_once __DIR__ . '/../../../../eeObjectMock.php';
require_once BASEPATH . 'libraries/template_router/Route.php';

class RouteTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        ee()->setMock('Security/XSS', new class {
            public function clean($value)
            {
                return $value;
            }
        });
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
        ee()->config->resetConfig();
    }

    /**
     * @dataProvider complexRegexRouteProvider
     */
    public function testComplexRegexRoutesParseWithoutPhpWarnings(string $route): void
    {
        $warnings = [];

        set_error_handler(function ($severity, $message, $file = null, $line = null) use (&$warnings) {
            if ($severity === E_WARNING) {
                $warnings[] = sprintf('%s (%s:%d)', $message, (string) $file, (int) $line);
                return true;
            }

            return false;
        });

        $compiled = '';

        try {
            $compiled = (new \EE_Route($route, false))->compile();
        } finally {
            restore_error_handler();
        }

        $this->assertNotEmpty($compiled);
        $this->assertSame([], $warnings, "Unexpected warnings:\n" . implode("\n", $warnings));
    }

    public function testInvalidRegexThrowsInvalidRegexWithoutWarningSpam(): void
    {
        $warnings = [];
        $exception = null;

        set_error_handler(function ($severity, $message, $file = null, $line = null) use (&$warnings) {
            if ($severity === E_WARNING) {
                $warnings[] = sprintf('%s (%s:%d)', $message, (string) $file, (int) $line);
                return true;
            }

            return false;
        });

        try {
            new \EE_Route('/x/{slug:regex[((abc]}', false);
        } catch (\Exception $e) {
            $exception = $e;
        } finally {
            restore_error_handler();
        }

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('invalid_regex', $exception->getMessage());
        $this->assertSame([], $warnings, "Unexpected warnings:\n" . implode("\n", $warnings));
    }

    public function testSimpleRegexBehaviorRemainsUnchanged(): void
    {
        $route = new \EE_Route('/home/{url_title:regex[(foo|bar)]}', true);
        $compiled = $route->compile();
        $pattern = '#' . $compiled . '#i';

        $this->assertSame(1, preg_match($pattern, 'home/foo'));
        $this->assertSame(1, preg_match($pattern, 'home/bar'));
        $this->assertSame(0, preg_match($pattern, 'home/baz'));
        $this->assertSame(0, preg_match($pattern, 'other/foo'));
    }

    public function testRegexContainingUnescapedSlashIsRejectedWithoutWarningSpam(): void
    {
        $warnings = [];
        $exception = null;

        set_error_handler(function ($severity, $message, $file = null, $line = null) use (&$warnings) {
            if ($severity === E_WARNING) {
                $warnings[] = sprintf('%s (%s:%d)', $message, (string) $file, (int) $line);
                return true;
            }

            return false;
        });

        try {
            new \EE_Route('/x/{slug:regex[(foo/bar)]}', false);
        } catch (\Exception $e) {
            $exception = $e;
        } finally {
            restore_error_handler();
        }

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('invalid_regex', $exception->getMessage());
        $this->assertSame([], $warnings, "Unexpected warnings:\n" . implode("\n", $warnings));
    }

    public function testRegexContainingEscapedSlashParsesWithoutWarningSpam(): void
    {
        $warnings = [];

        set_error_handler(function ($severity, $message, $file = null, $line = null) use (&$warnings) {
            if ($severity === E_WARNING) {
                $warnings[] = sprintf('%s (%s:%d)', $message, (string) $file, (int) $line);
                return true;
            }

            return false;
        });

        $compiled = '';

        try {
            $compiled = (new \EE_Route('/x/{slug:regex[(foo\/bar)]}', false))->compile();
        } finally {
            restore_error_handler();
        }

        $this->assertNotEmpty($compiled);
        $this->assertSame([], $warnings, "Unexpected warnings:\n" . implode("\n", $warnings));
    }

    public function testRegexRuleCanBeFollowedByAnotherRule(): void
    {
        $route = new \EE_Route('/x/{slug:regex[(foo|bar)]|max_length[20]}', true);
        $compiled = $route->compile();
        $pattern = '#' . $compiled . '#i';

        $this->assertNotEmpty($compiled);
        $this->assertSame(1, preg_match($pattern, 'x/foo'));
        $this->assertSame(1, preg_match($pattern, 'x/bar'));
        $this->assertSame(0, preg_match($pattern, 'x/baz'));
        $this->assertSame(0, preg_match($pattern, 'x/abcdefghijklmnopqrstuvwxyz'));
    }

    public function testRegexValidationRestoresPreviousErrorHandlerOnSuccess(): void
    {
        $captured = [];

        set_error_handler(function ($severity, $message) use (&$captured) {
            if ($severity === E_USER_WARNING) {
                $captured[] = (string) $message;
                return true;
            }

            return false;
        });

        try {
            (new \EE_Route('/x/{slug:regex[(foo|bar)]}', false))->compile();
            trigger_error('outer-handler-success', E_USER_WARNING);
        } finally {
            restore_error_handler();
        }

        $this->assertSame(['outer-handler-success'], $captured);
    }

    public function testRegexValidationRestoresPreviousErrorHandlerOnException(): void
    {
        $captured = [];
        $exception = null;

        set_error_handler(function ($severity, $message) use (&$captured) {
            if ($severity === E_USER_WARNING) {
                $captured[] = (string) $message;
                return true;
            }

            return false;
        });

        try {
            new \EE_Route('/x/{slug:regex[((abc]}', false);
        } catch (\Exception $e) {
            $exception = $e;
            trigger_error('outer-handler-exception', E_USER_WARNING);
        } finally {
            restore_error_handler();
        }

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('invalid_regex', $exception->getMessage());
        $this->assertSame(['outer-handler-exception'], $captured);
    }

    public function testRegexContainingEscapedClosingBracketParsesWithoutWarningSpam(): void
    {
        $warnings = [];

        set_error_handler(function ($severity, $message, $file = null, $line = null) use (&$warnings) {
            if ($severity === E_WARNING) {
                $warnings[] = sprintf('%s (%s:%d)', $message, (string) $file, (int) $line);
                return true;
            }

            return false;
        });

        $compiled = '';

        try {
            $compiled = (new \EE_Route('/x/{slug:regex[([a-z\]])]}', false))->compile();
        } finally {
            restore_error_handler();
        }

        $this->assertNotEmpty($compiled);
        $this->assertSame([], $warnings, "Unexpected warnings:\n" . implode("\n", $warnings));
    }

    public function testMultipleRegexVariablesInOneRouteRemainIndependent(): void
    {
        $route = new \EE_Route('/x/{a:regex[(foo|bar)]}/{b:regex[(one|two)]}', true);
        $compiled = $route->compile();
        $pattern = '#' . $compiled . '#i';

        $this->assertSame(1, preg_match($pattern, 'x/foo/one'));
        $this->assertSame(1, preg_match($pattern, 'x/bar/two'));
        $this->assertSame(0, preg_match($pattern, 'x/foo/three'));
        $this->assertSame(0, preg_match($pattern, 'x/baz/one'));
    }

    public function testEscapedSlashWithLookaroundParsesWithoutWarningSpam(): void
    {
        $warnings = [];

        set_error_handler(function ($severity, $message, $file = null, $line = null) use (&$warnings) {
            if ($severity === E_WARNING) {
                $warnings[] = sprintf('%s (%s:%d)', $message, (string) $file, (int) $line);
                return true;
            }

            return false;
        });

        $compiled = '';

        try {
            $compiled = (new \EE_Route('/x/{slug:regex[(((?!(foo\/bar)).)+?)]}', false))->compile();
        } finally {
            restore_error_handler();
        }

        $this->assertNotEmpty($compiled);
        $this->assertSame([], $warnings, "Unexpected warnings:\n" . implode("\n", $warnings));
    }

    public function complexRegexRouteProvider(): array
    {
        return [
            ['/blog/{url_title:regex[(((?!(P\d+|rss-feed$|category\/)).)+?)]}'],
            ['/add-ons/{url_title:regex[(((?!(P\d+|add-on-developer\/|results\/|no-results\/|tag-search\/)).)+?)]}'],
            ['/knowledge-base/{url_title:regex[(((?!(P\d+|rss-feed$|category\/)).)+?)]}'],
            ['/home/{url_title:regex[(((?!(P\d+|category\/)).)+?)]}'],
            ['/{url_title:regex[(cookie\-policy|privacy\-policy|trademark\-use\-policy|terms\-of\-service)]}'],
        ];
    }
}
