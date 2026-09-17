<?php

namespace ExpressionEngine\Tests\Installer\Updater;

use ExpressionEngine\Updater\Controller\Updater\Updater;
use ExpressionEngine\Updater\Service\Updater\RequestAuthorization;
use ExpressionEngine\Updater\Service\Updater\UpdaterException;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
    private $globals;
    private $path;
    private $authorization;
    private $cp;
    private $controller;

    protected function setUp(): void
    {
        $this->globals = [$_GET, $_SERVER, $_COOKIE];
        $_GET = ['step' => 'updateFiles'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_CSRF_TOKEN'] = 'test-csrf-token';
        $_COOKIE = [];
        $this->path = sys_get_temp_dir() . '/ee-controller-auth-' . bin2hex(random_bytes(8));
        $this->authorization = new ControllerAuthorization($this->path);
        $this->cp = new ControlPanelAuthorizationStub();
        $this->controller = new TestableUpdaterController($this->authorization, $this->cp);
    }

    protected function tearDown(): void
    {
        [$_GET, $_SERVER, $_COOKIE] = $this->globals;
        @unlink($this->path);
        ee()->resetMocks();
    }

    public function testHandoffRequiresControlPanelAndCookieBeforeRunningFiles()
    {
        $this->assertTrue($this->controller->requiresControlPanel());
        $result = json_decode($this->controller->run(), true);
        $this->assertSame(1, $this->cp->calls);
        $this->assertSame('updateFiles', $result['nextStep']);
        $this->assertSame([], $this->controller->steps);

        $_COOKIE = $this->authorization->cookie;
        $this->assertFalse($this->controller->requiresControlPanel());
        $this->controller->run();
        $this->assertSame(['updateFiles'], $this->controller->steps);
        $this->assertTrue($this->authorization->hasStarted());
        $this->assertSame(1, $this->cp->calls);
    }

    public function testDeniedControlPanelAuthorizationDoesNotCreateCapability()
    {
        $this->cp->allowed = false;
        try {
            $this->controller->run();
            $this->fail('Expected CP authorization failure.');
        } catch (UpdaterException $e) {
            $this->assertSame(403, $e->getCode());
            $this->assertFileDoesNotExist($this->path);
            $this->assertSame([], $this->controller->steps);
        }
    }

    public function testFailedHandoffCanBeCancelledThroughControlPanel()
    {
        $this->controller->run();
        $_GET['step'] = 'rollback';
        $this->assertTrue($this->controller->requiresControlPanel());
        $result = json_decode($this->controller->run(), true);
        $this->assertSame(2, $this->cp->calls);
        $this->assertSame(1, $this->controller->cancellations);
        $this->assertFalse($result['nextStep']);
        $this->assertSame([], $this->controller->steps);
    }

    public function testActiveRollbackDoesNotNeedTheOriginalApplicationIdentity()
    {
        $this->startUpdate();
        $_GET['step'] = 'rollback';
        $this->cp->allowed = false;
        $this->assertFalse($this->controller->requiresControlPanel());
        $this->controller->run();
        $this->assertSame(['updateFiles', 'rollback'], $this->controller->steps);
    }

    /** @dataProvider cancellationProvider */
    public function testCancellationRestoresThePriorSiteState($previous)
    {
        $config = $this->mockCancellation($previous);
        $config->expects($previous === null ? $this->never() : $this->once())->method('_update_config')
            ->with(['is_system_on' => $previous], ['is_system_on_before_updater' => $previous])
            ->willReturn(true);
        $filesystem = $this->getMockBuilder(\stdClass::class)->addMethods(['deleteDir'])->getMock();
        $filesystem->expects($this->once())->method('deleteDir')->with(SYSPATH . 'ee/updater')->willReturn(true);
        ee()->setMock('Filesystem', $filesystem);

        $_GET['step'] = 'rollback';
        $result = json_decode((new Updater($this->authorization, $this->cp))->run(), true);
        $this->assertFalse($result['nextStep']);
        $this->assertSame(1, $this->cp->calls);
    }

    public function cancellationProvider()
    {
        return [['y'], ['n'], [null]];
    }

    public function testCancellationKeepsTheUpdaterIfRestoringConfigurationFails()
    {
        $config = $this->mockCancellation('y');
        $config->method('_update_config')->willReturn(false);
        $filesystem = $this->getMockBuilder(\stdClass::class)->addMethods(['deleteDir'])->getMock();
        $filesystem->expects($this->never())->method('deleteDir');
        ee()->setMock('Filesystem', $filesystem);
        $_GET['step'] = 'rollback';
        $this->expectExceptionMessage('Unable to restore the site configuration.');
        (new Updater($this->authorization, $this->cp))->run();
    }

    private function mockCancellation($previous)
    {
        $file = $this->getMockBuilder(\stdClass::class)->addMethods(['get'])->getMock();
        $file->method('get')->with('is_system_on_before_updater')->willReturn($previous);
        $service = $this->getMockBuilder(\stdClass::class)->addMethods(['getFile'])->getMock();
        $service->method('getFile')->willReturn($file);
        ee()->setMock('Config', $service);
        $config = $this->getMockBuilder(\stdClass::class)->addMethods(['_update_config'])->getMock();
        ee()->setMock('config', $config);

        return $config;
    }

    /** @dataProvider forwardStepProvider */
    public function testExpiredActiveCapabilityCannotRunForwardStepsOrBootstrapControlPanel($step)
    {
        $this->startUpdate();
        $this->expireAuthorization();
        $_GET['step'] = $step;
        $this->expectException(UpdaterException::class);
        $this->expectExceptionCode(403);
        $this->controller->requiresControlPanel();
    }

    public function forwardStepProvider()
    {
        return [
            ['updateFiles'], ['checkForDbUpdates'], ['backupDatabase[exp_members,100]'],
            ['updateDatabase[runUpdateFile[ud_7_05_00.php]]'], ['updateAddons'], ['selfDestruct'],
        ];
    }

    public function testExpiredAuthorizationCanCompleteRollbackWithTheOriginalCredentials()
    {
        $this->startUpdate();
        $this->expireAuthorization();
        $this->cp->allowed = false;
        foreach (['rollback', 'restoreDatabase', 'selfDestruct[rollback]'] as $step) {
            $_GET['step'] = $step;
            $this->assertFalse($this->controller->requiresControlPanel());
            $this->controller->run();
        }
        $this->assertSame(
            ['updateFiles', 'rollback', 'restoreDatabase', 'selfDestruct[rollback]'], $this->controller->steps
        );
        $this->assertSame(1, $this->cp->calls);
        $this->assertFalse($this->authorization->isAuthorized());
    }

    /** @dataProvider recoveryCredentialsProvider */
    public function testExpiredRecoveryStillRequiresBothOriginalCredentials($step, $credential)
    {
        $this->startUpdate();
        $this->expireAuthorization();
        $_GET['step'] = $step;
        if ($credential === 'cookie') {
            $_COOKIE = [];
        } else {
            $_SERVER['HTTP_X_CSRF_TOKEN'] = 'different-csrf-token';
        }
        $this->expectException(UpdaterException::class);
        $this->expectExceptionCode(403);
        $this->controller->requiresFullBootstrap();
    }

    public function recoveryCredentialsProvider()
    {
        return [
            ['rollback', 'cookie'], ['rollback', 'csrf'],
            ['restoreDatabase', 'cookie'], ['restoreDatabase', 'csrf'],
            ['selfDestruct[rollback]', 'cookie'], ['selfDestruct[rollback]', 'csrf'],
        ];
    }

    /** @dataProvider recoveryDeadlineProvider */
    public function testEveryRecoveryStageRejectsExpiredRecoveryCredentials($priorSteps, $step)
    {
        $this->startUpdate();
        foreach ($priorSteps as $prior) {
            $_GET['step'] = $prior;
            $this->controller->run();
        }
        $this->expireAuthorization(true);
        $_GET['step'] = $step;
        $this->expectException(UpdaterException::class);
        $this->expectExceptionCode(403);
        $this->controller->requiresFullBootstrap();
    }

    public function recoveryDeadlineProvider()
    {
        return [
            [[], 'rollback'],
            [['rollback'], 'restoreDatabase'],
            [['rollback', 'restoreDatabase'], 'selfDestruct[rollback]'],
        ];
    }

    private function expireAuthorization($recovery = false)
    {
        $state = json_decode(substr(file_get_contents($this->path), strlen(RequestAuthorization::STATE_PREFIX)), true);
        $state['expires'] = time() - 1;
        if ($recovery) {
            $state['recovery_expires'] = time() - 1;
        }
        file_put_contents($this->path, RequestAuthorization::STATE_PREFIX . json_encode($state));
    }

    public function testFileReplacementCannotBeRunTwice()
    {
        $this->startUpdate();
        $this->expectException(UpdaterException::class);
        $this->expectExceptionCode(409);
        $this->controller->run();
    }

    public function testOtherStepsCannotBootstrapWithoutACapability()
    {
        $_GET['step'] = 'checkForDbUpdates';
        $this->expectException(UpdaterException::class);
        $this->expectExceptionCode(403);
        $this->controller->requiresControlPanel();
    }

    public function testExistingContinuationProtocolFollowsTheReturnedNextStep()
    {
        $this->startUpdate();
        $next = 'addLegacyFiles';
        while ($next !== false) {
            $_GET['step'] = $next;
            $result = json_decode($this->controller->run(), true);
            $next = $result['nextStep'];
        }
        $this->assertSame([
            'updateFiles', 'addLegacyFiles', 'checkForDbUpdates', 'backupDatabase',
            'backupDatabase[exp_members,100]', 'updateDatabase',
            'updateDatabase[runUpdateFile[ud_2_11_09.php]]',
            'updateDatabase[runUpdateFile[ud_6_00_00_b_1.php]]', 'updateAddons', 'selfDestruct',
        ], $this->controller->steps);
        $this->assertFalse($this->authorization->isAuthorized());
        $this->assertFalse($this->authorization->isAuthorized(true));
    }

    public function testServerCanSkipAnUnnecessaryBackup()
    {
        $this->controller->nextSteps['checkForDbUpdates'] = 'updateDatabase';
        $this->runThrough('checkForDbUpdates');
        $_GET['step'] = 'updateDatabase';
        $this->controller->run();
        $this->assertNotContains('backupDatabase', $this->controller->steps);
    }

    /** @dataProvider outOfOrderProvider */
    public function testAllowlistedStepsCannotBeReordered($step)
    {
        $this->startUpdate();
        $this->assertRejectedStep($step);
        $this->assertSame(['updateFiles'], $this->controller->steps);
    }

    public function outOfOrderProvider()
    {
        return [
            ['checkForDbUpdates'], ['backupDatabase'], ['updateDatabase'], ['updateAddons'],
            ['restoreDatabase'], ['selfDestruct'], ['selfDestruct[rollback]'],
        ];
    }

    public function testContinuationParametersAndCompletedStepsCannotBeReplayed()
    {
        $this->runThrough('backupDatabase');
        $this->assertRejectedStep('backupDatabase[exp_members,101]');
        $this->assertRejectedStep('backupDatabase[exp_other_table,100]');
        $_GET['step'] = 'backupDatabase[exp_members,100]';
        $this->controller->run();
        $this->assertRejectedStep('backupDatabase[exp_members,100]');
        $this->assertRejectedStep('backupDatabase');

        $_GET['step'] = 'updateDatabase';
        $this->controller->run();
        $this->assertRejectedStep('updateDatabase[runUpdateFile[ud_6_00_00_b_1.php]]');
        $_GET['step'] = 'updateDatabase[runUpdateFile[ud_2_11_09.php]]';
        $this->controller->run();
        $this->assertRejectedStep('updateDatabase[runUpdateFile[ud_2_11_09.php]]');
    }

    public function testRollbackIsOneWayAndItsContinuationsCannotBeReplayed()
    {
        $this->startUpdate();
        $_GET['step'] = 'rollback';
        $this->controller->run();
        $this->assertRejectedStep('rollback');
        $this->assertRejectedStep('selfDestruct[rollback]');
        $this->assertRejectedStep('addLegacyFiles');
        $_GET['step'] = 'restoreDatabase';
        $this->controller->run();
        $this->assertRejectedStep('restoreDatabase');
        $this->assertRejectedStep('rollback');
        $_GET['step'] = 'selfDestruct[rollback]';
        $this->controller->run();
        $this->assertFalse($this->authorization->isAuthorized(true));
    }

    public function testAnInterruptedStepCannotBeRepeatedButCanBeRolledBack()
    {
        $this->startUpdate();
        $this->authorization->beginStep('addLegacyFiles');
        $this->assertRejectedStep('addLegacyFiles');
        $this->assertRejectedStep('checkForDbUpdates');
        $_GET['step'] = 'rollback';
        $this->controller->run();
        $this->assertSame(['updateFiles', 'rollback'], $this->controller->steps);
    }

    public function testFailedForwardStepRemainsConsumed()
    {
        $this->controller->failOnStep = 'addLegacyFiles';
        $this->startUpdate();
        $_GET['step'] = 'addLegacyFiles';
        try {
            $this->controller->run();
            $this->fail('Expected the fixture step to fail.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Fixture step failed', $e->getMessage());
        }
        $this->assertRejectedStep('addLegacyFiles');
        $_GET['step'] = 'rollback';
        $this->controller->run();
        $this->assertSame(['updateFiles', 'addLegacyFiles', 'rollback'], $this->controller->steps);
    }

    private function assertRejectedStep($step)
    {
        $_GET['step'] = $step;
        try {
            $this->controller->requiresFullBootstrap();
            $this->fail('Expected a rejected step: ' . $step);
        } catch (UpdaterException $e) {
            $this->assertSame(409, $e->getCode());
        }
    }

    private function runThrough($step)
    {
        $this->startUpdate();
        $next = 'addLegacyFiles';
        do {
            $_GET['step'] = $next;
            $result = json_decode($this->controller->run(), true);
            $current = $next;
            $next = $result['nextStep'];
        } while ($current !== $step && $next !== false);
        $this->assertSame($step, $current);
    }

    /** @dataProvider invalidRequestProvider */
    public function testInvalidRequestsAreRejectedBeforeBootstrap($method, $step)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_GET['step'] = $step;
        $this->expectException(UpdaterException::class);
        $this->controller->requiresControlPanel();
    }

    public function invalidRequestProvider()
    {
        return [
            ['GET', 'updateFiles'], ['POST', null], ['POST', []], ['POST', 'unknownStep'],
            ['POST', 'backupDatabase[exp_members,-1]'], ['POST', 'updateFiles[extra]'],
            ['POST', 'updateDatabase[unknownStep]'], ['POST', 'selfDestruct[extra]'],
        ];
    }

    private function startUpdate()
    {
        $this->controller->run();
        $_COOKIE = $this->authorization->cookie;
        $this->controller->run();
    }
}

class ControllerAuthorization extends RequestAuthorization
{
    public $cookie;

    protected function sendCookie($name, $value, array $options)
    {
        $this->cookie = [$name => $value];
        return true;
    }
}

class ControlPanelAuthorizationStub
{
    public $calls = 0;
    public $allowed = true;

    public function authorize()
    {
        $this->calls++;
        if (! $this->allowed) {
            throw new UpdaterException('Unauthorized updater request.', 403);
        }
    }
}

class TestableUpdaterController extends Updater
{
    public $steps = [];
    public $cancellations = 0;
    public $failOnStep;
    public $nextSteps = [
        'updateFiles' => 'addLegacyFiles',
        'addLegacyFiles' => 'checkForDbUpdates',
        'checkForDbUpdates' => 'backupDatabase',
        'backupDatabase' => 'backupDatabase[exp_members,100]',
        'backupDatabase[exp_members,100]' => 'updateDatabase',
        'updateDatabase' => 'updateDatabase[runUpdateFile[ud_2_11_09.php]]',
        'updateDatabase[runUpdateFile[ud_2_11_09.php]]' => 'updateDatabase[runUpdateFile[ud_6_00_00_b_1.php]]',
        'updateDatabase[runUpdateFile[ud_6_00_00_b_1.php]]' => 'updateAddons',
        'updateAddons' => 'selfDestruct',
        'selfDestruct' => false,
        'rollback' => 'restoreDatabase',
        'restoreDatabase' => 'selfDestruct[rollback]',
        'selfDestruct[rollback]' => false,
    ];

    protected function cancelBeforeReplacement()
    {
        $this->cancellations++;
    }

    protected function makeRunner()
    {
        return new class($this) {
            private $controller;

            public function __construct($controller)
            {
                $this->controller = $controller;
            }

            public function runStep($step)
            {
                $this->controller->steps[] = $step;
                if ($this->controller->failOnStep === $step) {
                    throw new \RuntimeException('Fixture step failed');
                }
            }

            public function getNextStep()
            {
                $steps = $this->controller->steps;
                return $this->controller->nextSteps[end($steps)];
            }

            public function getLanguageForStep($step)
            {
                return '';
            }
        };
    }
}
