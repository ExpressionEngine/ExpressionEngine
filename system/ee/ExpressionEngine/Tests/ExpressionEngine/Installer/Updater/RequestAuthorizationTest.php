<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Installer\Updater;

use ExpressionEngine\Updater\Service\Updater\RequestAuthorization;
use ExpressionEngine\Updater\Service\Updater\UpdaterException;
use PHPUnit\Framework\TestCase;

class RequestAuthorizationTest extends TestCase
{
    /**
     * Disposable authorization state path.
     *
     * @var string
     */
    private $statePath;
    private $lockPath;

    /**
     * Original server parameters.
     *
     * @var array
     */
    private $server;

    /**
     * Original request cookies.
     *
     * @var array
     */
    private $cookies;
    private $get;

    /**
     * Prepare isolated request state.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->statePath = sys_get_temp_dir() . '/ee-updater-auth-' . bin2hex(random_bytes(8)) . '.php';
        $this->lockPath = $this->statePath . '.lock';
        $this->server = $_SERVER;
        $this->cookies = $_COOKIE;
        $this->get = $_GET;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'test-csrf-token';
        $_COOKIE = array();
        $_GET = array();
    }

    /**
     * Restore request state and remove disposable files.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (is_file($this->statePath)) {
            unlink($this->statePath);
        }

        $_SERVER = $this->server;
        $_COOKIE = $this->cookies;
        $_GET = $this->get;
        @unlink($this->lockPath);
    }

    /**
     * Bind cookie-free requests to the authenticated session and CSRF credentials.
     *
     * @param bool $csrfDisabled
     * @return void
     * @dataProvider csrfModeProvider
     */
    public function testSessionOnlyRequestsDoNotRequireCookies($csrfDisabled)
    {
        $_GET['S'] = str_repeat('a', 40);
        if ($csrfDisabled) {
            $_SERVER['HTTP_X_CSRF_TOKEN'] = '';
            $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        }
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare($csrfDisabled, $_GET['S']);
        $this->assertNull($authorization->cookie);
        $this->assertTrue($authorization->isAuthorized());
        $this->assertStringNotContainsString($_GET['S'], file_get_contents($this->statePath));

        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        foreach ([null, '', [], 0, str_repeat('b', 40)] as $invalidSession) {
            $_GET['S'] = $invalidSession;
            $this->assertFalse($authorization->isAuthorized());
        }
        $_GET['S'] = str_repeat('a', 40);
        $this->assertTrue($authorization->isAuthorized());
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFalse($authorization->isAuthorized());
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_SERVER['HTTP_X_CSRF_TOKEN'], $_SERVER['HTTP_X_REQUESTED_WITH']);
        $this->assertFalse($authorization->isAuthorized());
    }

    /**
     * Exercise both supported CSRF configurations.
     *
     * @return array
     */
    public function csrfModeProvider()
    {
        return [[false], [true]];
    }

    /**
     * Refuse session preparation unless the authenticated credential is returned in S.
     *
     * @param mixed $sessionId
     * @param mixed $requestSession
     * @return void
     * @dataProvider invalidSessionPreparationProvider
     */
    public function testSessionPreparationRequiresTheAuthenticatedSession($sessionId, $requestSession)
    {
        $_GET['S'] = $requestSession;
        try {
            (new TestableRequestAuthorization($this->statePath, $this->lockPath))->prepare(false, $sessionId);
            $this->fail('Expected invalid session credentials to be rejected.');
        } catch (UpdaterException $e) {
            $this->assertSame(403, $e->getCode());
            $this->assertFileDoesNotExist($this->statePath);
        }
    }

    /**
     * Include malformed credentials and a request belonging to a different session.
     *
     * @return array
     */
    public function invalidSessionPreparationProvider()
    {
        $sessionId = str_repeat('a', 40);

        return [
            [false, $sessionId], [[], $sessionId], ['', ''], [0, '0'],
            [$sessionId, null], [$sessionId, []], [$sessionId, str_repeat('b', 40)],
        ];
    }

    /**
     * Keep the persisted transport authoritative even when the other carries the same token.
     *
     * @return void
     */
    public function testCredentialsCannotSwitchBetweenCookiesAndSessions()
    {
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare();
        $token = $authorization->cookie['value'];
        $_GET['S'] = $token;
        $this->assertFalse($authorization->isAuthorized());
        $_COOKIE[$authorization->cookie['name']] = $token;
        $this->assertTrue($authorization->isAuthorized());

        unlink($this->statePath);
        $authorization->prepare(false, $token);
        unset($_GET['S']);
        $this->assertFalse($authorization->isAuthorized());
        $_GET['S'] = $token;
        $this->assertTrue($authorization->isAuthorized());
    }

    /**
     * Preserve older cookie state while rejecting malformed authorization modes.
     *
     * @return void
     */
    public function testLegacyStateDefaultsToCookiesAndInvalidModesFailClosed()
    {
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare();
        $_COOKIE[$authorization->cookie['name']] = $authorization->cookie['value'];
        $state = $this->readState();
        unset($state['session_only']);
        $this->writeState($state);
        $this->assertTrue($authorization->isAuthorized());

        foreach ([null, '', 's', 0, 1, []] as $invalidMode) {
            $state['session_only'] = $invalidMode;
            $this->writeState($state);
            $this->assertFalse($authorization->isAuthorized());
        }
    }

    /**
     * Require the browser to return the prepared credentials.
     *
     * @return void
     */
    public function testPreparedRequestRequiresMatchingBrowserCredentials()
    {
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare();

        $contents = file_get_contents($this->statePath);
        $this->assertStringStartsWith(RequestAuthorization::STATE_PREFIX, $contents);
        $this->assertStringNotContainsString($authorization->cookie['value'], $contents);
        $this->assertStringNotContainsString($_SERVER['HTTP_X_CSRF_TOKEN'], $contents);
        $this->assertFalse($authorization->isAuthorized());
        $this->assertTrue($authorization->cookie['options']['httponly']);
        $this->assertSame('Strict', $authorization->cookie['options']['samesite']);
        $this->assertSame(0, $authorization->cookie['options']['expires']);

        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->assertSame(0600, fileperms($this->statePath) & 0777);
        }

        $_COOKIE[$authorization->cookie['name']] = $authorization->cookie['value'];
        $this->assertTrue($authorization->isAuthorized());

        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'different-token';
        $this->assertFalse($authorization->isAuthorized());
    }

    /**
     * Reject preparation without the updater client's CSRF header.
     *
     * @return void
     */
    public function testPreparationRequiresCsrfHeader()
    {
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);

        $this->expectException(UpdaterException::class);
        $this->expectExceptionCode(403);

        (new TestableRequestAuthorization($this->statePath, $this->lockPath))->prepare();
    }

    /** @dataProvider emptyCsrfProvider */
    public function testCsrfDisabledRequestsRequireTheCookieAndAjaxHeader($csrf)
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $csrf;
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare(true);
        $this->assertFalse($authorization->isAuthorized());
        $_COOKIE[$authorization->cookie['name']] = $authorization->cookie['value'];

        // A fresh request has no application configuration loaded.
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $this->assertTrue($authorization->isAuthorized());
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        $this->assertFalse($authorization->isAuthorized());
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'invalid';
        $this->assertFalse($authorization->isAuthorized());
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue($authorization->isAuthorized());
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFalse($authorization->isAuthorized());
    }

    public function emptyCsrfProvider()
    {
        return [[''], [null]];
    }

    /** @dataProvider rejectedAjaxPreparationProvider */
    public function testAjaxFallbackMustBeEnabledAndHaveTheExpectedHeader($csrfDisabled, $ajaxHeader)
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = '';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = $ajaxHeader;
        $this->expectException(UpdaterException::class);
        $this->expectExceptionCode(403);
        (new TestableRequestAuthorization($this->statePath, $this->lockPath))->prepare($csrfDisabled);
    }

    public function rejectedAjaxPreparationProvider()
    {
        return [[false, 'XMLHttpRequest'], [true, null], [true, ''], [true, 'invalid'], [true, []]];
    }

    public function testExistingCsrfBindingCannotBeReplacedByTheAjaxHeader()
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare(true);
        $_COOKIE[$authorization->cookie['name']] = $authorization->cookie['value'];
        $this->assertTrue($authorization->isAuthorized());
        $_SERVER['HTTP_X_CSRF_TOKEN'] = '';
        $this->assertFalse($authorization->isAuthorized());
    }

    public function testAjaxBindingPreservesBoundedRecoveryAndCookieAcknowledgement()
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = '';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare(true);
        $original = file_get_contents($this->statePath);
        try {
            $authorization->prepare(true);
            $this->fail('Expected missing cookie acknowledgement.');
        } catch (UpdaterException $e) {
            $this->assertStringContainsString('cookie was not returned', $e->getMessage());
            $this->assertSame($original, file_get_contents($this->statePath));
        }
        $_COOKIE[$authorization->cookie['name']] = $authorization->cookie['value'];
        $authorization->beginStep('updateFiles');
        $state = $this->readState();
        $state['expires'] = time() - 1;
        $this->writeState($state);
        $this->assertFalse($authorization->isAuthorized());
        $this->assertTrue($authorization->isAuthorized(true));
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
        $this->assertFalse($authorization->isAuthorized(true));
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $state['recovery_expires'] = time() - 1;
        $this->writeState($state);
        $this->assertFalse($authorization->isAuthorized(true));
    }

    /**
     * Stop before file replacement when the browser does not return its cookie.
     *
     * @return void
     */
    public function testPreparationRequiresCookieAcknowledgement()
    {
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare();

        $this->expectException(UpdaterException::class);
        $this->expectExceptionMessage('The updater authorization cookie was not returned.');

        $authorization->prepare();
    }

    public function testAcknowledgementFailureDoesNotReplaceThePreparedCredential()
    {
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare();
        $original = file_get_contents($this->statePath);
        try {
            $authorization->prepare();
            $this->fail('Expected missing cookie acknowledgement.');
        } catch (UpdaterException $e) {
            $this->assertSame($original, file_get_contents($this->statePath));
        }
    }

    public function testAnotherBrowserCannotReplaceLiveCredentials()
    {
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare();
        $original = file_get_contents($this->statePath);
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'another-browser';
        try {
            $authorization->prepare();
            $this->fail('Expected an existing update conflict.');
        } catch (UpdaterException $e) {
            $this->assertSame(409, $e->getCode());
            $this->assertSame($original, file_get_contents($this->statePath));
        }
    }

    public function testUnreadableStateDoesNotPermitASecondHandoff()
    {
        file_put_contents($this->statePath, 'incomplete-state');
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $this->assertFalse($authorization->isAuthorized());
        $this->assertTrue($authorization->hasStarted());
        $this->expectException(UpdaterException::class);
        $authorization->prepare();
    }

    public function testStartRequiresReturnedCredentials()
    {
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare();
        $this->expectException(UpdaterException::class);
        $this->expectExceptionCode(403);
        $authorization->beginStep('updateFiles');
    }

    public function testConcurrentRequestsAreSerialized()
    {
        $first = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $second = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $first->acquireLock();
        $this->expectException(UpdaterException::class);
        $this->expectExceptionCode(409);
        $second->acquireLock();
    }

    public function testExpiredPreparationCanBeReplacedBeforeFilesAreMoved()
    {
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare();
        $contents = file_get_contents($this->statePath);
        $state = json_decode(substr($contents, strlen(RequestAuthorization::STATE_PREFIX)), true);
        $state['expires'] = time() - 1;
        file_put_contents($this->statePath, RequestAuthorization::STATE_PREFIX . json_encode($state));
        $_COOKIE[$authorization->cookie['name']] = $authorization->cookie['value'];
        $this->assertFalse($authorization->isAuthorized());
        $this->assertFalse($authorization->isAuthorized(true));
        $this->assertFalse($authorization->hasStarted());
        $authorization->prepare();
        $this->assertFalse($authorization->isAuthorized());
        $_COOKIE[$authorization->cookie['name']] = $authorization->cookie['value'];
        $this->assertTrue($authorization->isAuthorized());
    }

    public function testFailedCookieDeliveryDoesNotLeavePreparedState()
    {
        $authorization = new class($this->statePath, $this->lockPath) extends RequestAuthorization {
            protected function sendCookie($name, $value, array $options)
            {
                return false;
            }
        };
        try {
            $authorization->prepare();
            $this->fail('Expected failed cookie delivery.');
        } catch (UpdaterException $e) {
            $this->assertFileDoesNotExist($this->statePath);
        }
    }

    public function testFinishedRequestReleasesItsLock()
    {
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->acquireLock();
        unset($authorization);
        $next = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $next->acquireLock();
        $this->assertFileExists($this->lockPath);
    }

    public function testDefaultLockSurvivesUpdaterAndWorkingDirectoryCleanup()
    {
        $property = new \ReflectionProperty(RequestAuthorization::class, 'lockPath');
        $property->setAccessible(true);
        $this->assertSame(SYSPATH . 'user/cache/.ee-updater.lock', $property->getValue(new RequestAuthorization()));

        $directory = $this->statePath . '-updater';
        mkdir($directory, 0700);
        $authorization = new TestableRequestAuthorization($directory . '/.authorization.php', $this->lockPath);
        $authorization->acquireLock();
        try {
            $authorization->prepare();
            unlink($directory . '/.authorization.php');
            $this->assertTrue(rmdir($directory));
            $this->assertFileExists($this->lockPath);

            $next = new TestableRequestAuthorization($directory . '/.authorization.php', $this->lockPath);
            $this->expectException(UpdaterException::class);
            $this->expectExceptionCode(409);
            $next->acquireLock();
        } finally {
            @unlink($directory . '/.authorization.php');
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function testRecoveryCredentialsHaveAFiniteDeadline()
    {
        $authorization = $this->prepareStartedRequest();
        $state = $this->readState();
        $this->assertSame(RequestAuthorization::RECOVERY_LIFETIME, $state['recovery_expires'] - $state['expires']
            + RequestAuthorization::LIFETIME);
        $state['expires'] = time() - 1;
        $state['recovery_expires'] = time() - 1;
        $this->writeState($state);
        $this->assertFalse($authorization->isAuthorized());
        $this->assertFalse($authorization->isAuthorized(true));
    }

    public function testSuccessfulLongStepRenewsBothDeadlines()
    {
        $authorization = $this->prepareStartedRequest();
        // Model a step taking longer than the original authorization windows.
        $state = $this->readState();
        $state['expires'] = time() - 1;
        $state['recovery_expires'] = time() - 1;
        $this->writeState($state);
        $before = time();
        $authorization->completeStep('updateFiles', 'addLegacyFiles');
        $state = $this->readState();
        $this->assertGreaterThanOrEqual($before + RequestAuthorization::LIFETIME, $state['expires']);
        $this->assertGreaterThanOrEqual($before + RequestAuthorization::RECOVERY_LIFETIME, $state['recovery_expires']);
        $this->assertTrue($authorization->isAuthorized());
        $this->assertTrue($authorization->isAuthorized(true));
    }

    public function testRejectedAndUnfinishedStepsDoNotRenewDeadlines()
    {
        $authorization = $this->prepareStartedRequest();
        $state = $this->readState();
        $state['expires'] = time() + 30;
        $state['recovery_expires'] = time() + 60;
        $this->writeState($state);
        try {
            $authorization->assertStep('updateFiles');
            $this->fail('Expected the running step to be rejected.');
        } catch (UpdaterException $e) {
            $this->assertSame(409, $e->getCode());
        }
        $authorization->beginStep('rollback');
        $authorization->beginStep('rollback');
        $pending = $this->readState();
        $this->assertSame($state['expires'], $pending['expires']);
        $this->assertSame($state['recovery_expires'], $pending['recovery_expires']);
    }

    public function testSuccessfulCleanupDoesNotRecreateDeletedState()
    {
        $authorization = $this->prepareStartedRequest();
        $authorization->completeStep('updateFiles', 'selfDestruct');
        $authorization->beginStep('selfDestruct');
        unlink($this->statePath);
        $authorization->completeStep('selfDestruct', false);
        $this->assertFileDoesNotExist($this->statePath);
        $this->assertFalse($authorization->isAuthorized(true));
    }

    private function prepareStartedRequest()
    {
        $authorization = new TestableRequestAuthorization($this->statePath, $this->lockPath);
        $authorization->prepare();
        $_COOKIE[$authorization->cookie['name']] = $authorization->cookie['value'];
        $authorization->beginStep('updateFiles');

        return $authorization;
    }

    private function readState()
    {
        $contents = file_get_contents($this->statePath);

        return json_decode(substr($contents, strlen(RequestAuthorization::STATE_PREFIX)), true);
    }

    private function writeState($state)
    {
        file_put_contents($this->statePath, RequestAuthorization::STATE_PREFIX . json_encode($state));
    }
}

/**
 * Captures authorization cookies without modifying response headers.
 */
class TestableRequestAuthorization extends RequestAuthorization
{
    /**
     * Last authorization cookie sent by the service.
     *
     * @var array|null
     */
    public $cookie;

    /**
     * Capture an authorization cookie.
     *
     * @param string $name
     * @param string $value
     * @param array $options
     * @return bool
     */
    protected function sendCookie($name, $value, array $options)
    {
        $this->cookie = compact('name', 'value', 'options');

        return true;
    }
}
