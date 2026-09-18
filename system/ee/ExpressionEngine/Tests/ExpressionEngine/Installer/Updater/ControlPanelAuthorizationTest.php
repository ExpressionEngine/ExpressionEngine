<?php

namespace ExpressionEngine\Tests\Installer\Updater;

use ExpressionEngine\Core\Application;
use ExpressionEngine\Core\Request;
use ExpressionEngine\Core\Response;
use ExpressionEngine\Legacy\Facade;
use ExpressionEngine\Updater\Service\Updater\ControlPanelAuthorization;
use PHPUnit\Framework\TestCase;

class ControlPanelAuthorizationTest extends TestCase
{
    private $globals;
    private $core;
    private $app;
    private $router;
    private $uri;
    private $facade;
    private $placeholder;

    protected function setUp(): void
    {
        require_once APPPATH . 'core/Controller.php';
        require_once APPPATH . 'core/Router.php';
        require_once __DIR__ . '/Fixtures/LegacyAuthorization.inc';
        $this->globals = [$_GET, $_SERVER];
        $_GET = ['step' => 'updateFiles'];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Use the real registry: the default ee() mock silently accepts duplicate controllers.
        $this->facade = new Facade();
        ee()->setMock('', $this->facade);

        $this->core = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['bootstrap', 'run_ee', 'run_cp'])->getMock();
        ee()->setMock('core', $this->core);
        $extensions = $this->getMockBuilder(\stdClass::class)->addMethods(['active_hook'])->getMock();
        $extensions->method('active_hook')->willReturn(false);
        ee()->setMock('extensions', $extensions);

        $this->uri = new \stdClass();
        $this->router = (new \ReflectionClass(\EE_Router::class))->newInstanceWithoutConstructor();
        $this->router->uri = $this->uri;
        $this->facade->set('router', $this->router);
        ee()->setMock('router', $this->router);
        ee()->setMock('uri', $this->uri);
        $this->app = $this->createMock(Application::class);
        $this->app->expects($this->once())->method('setRequest')->willReturnCallback(function ($request) {
            $this->assertInstanceOf(Request::class, $request);
            $this->assertSame('POST', $request->method());
            ee()->setMock('Request', $request);
        });
        $this->app->expects($this->once())->method('setResponse')->with($this->isInstanceOf(Response::class));
        ee()->setMock('App', $this->app);

        $permission = $this->getMockBuilder(\stdClass::class)->addMethods(['isSuperAdmin'])->getMock();
        $permission->method('isSuperAdmin')->willReturn(true);
        ee()->setMock('Permission', $permission);
        ee()->session->setUserdata('group_id', 1);
    }

    protected function tearDown(): void
    {
        [$_GET, $_SERVER] = $this->globals;
        ee()->session->resetUserdata();
        ee()->resetMocks();
    }

    /** @dataProvider namespaceProvider */
    public function testHandoffRunsFullControlPanelAuthorization($namespace)
    {
        $this->core->expects($this->exactly(2))->method('bootstrap');
        $this->core->expects($this->once())->method('run_ee');
        $this->core->expects($this->once())->method('run_cp')->willReturnCallback(function () {
            $this->assertSame('updater', $this->router->fetch_class());
            $this->assertSame('run', $this->router->fetch_method());
            $this->assertSame([1 => 'cp', 2 => 'updater', 3 => 'run'], $this->uri->segments);
        });

        $this->authorize($namespace);
        $this->assertSame('updateFiles', $_GET['step']);
        $this->assertInstanceOf($namespace . '\Controller\Updater\Updater', $this->facade->get('__legacy_controller'));
        $this->assertNotSame($this->placeholder, $this->facade->get('__legacy_controller'));
        $this->assertSame($this->router, $this->facade->get('router'));
    }

    /** @dataProvider namespaceProvider */
    public function testControlPanelDenialStopsTheHandoff($namespace)
    {
        $this->core->expects($this->once())->method('run_ee');
        $this->core->expects($this->once())->method('run_cp')
            ->willThrowException(new \RuntimeException('Control Panel authentication required'));
        $this->expectExceptionMessage('Control Panel authentication required');
        $this->authorize($namespace);
    }

    /** @dataProvider namespaceProvider */
    public function testCsrfDenialStopsBeforeControlPanelAuthorization($namespace)
    {
        $this->core->expects($this->once())->method('run_ee')
            ->willThrowException(new \RuntimeException('CSRF validation required'));
        $this->core->expects($this->never())->method('run_cp');
        $this->expectExceptionMessage('CSRF validation required');
        $this->authorize($namespace);
    }

    /** @dataProvider namespaceProvider */
    public function testSourceControllerRejectsNonSuperAdmins($namespace)
    {
        $permission = $this->getMockBuilder(\stdClass::class)->addMethods(['isSuperAdmin'])->getMock();
        $permission->method('isSuperAdmin')->willReturn(false);
        ee()->setMock('Permission', $permission);
        ee()->session->setUserdata('group_id', 6);
        $this->expectExceptionCode(403);
        $this->authorize($namespace);
    }

    public function namespaceProvider()
    {
        return [['ExpressionEngine'], ['ExpressionEngine\Tests\Installer\Updater\Fixtures\Legacy']];
    }

    private function authorize($namespace)
    {
        // Core::bootOnly() registers this placeholder before the handoff authorizer runs.
        $this->placeholder = new \Base_Controller();
        $this->assertSame($this->placeholder, $this->facade->get('__legacy_controller'));

        $adapter = new class($namespace) extends ControlPanelAuthorization {
            private $namespace;

            public function __construct($namespace)
            {
                $this->namespace = $namespace;
            }

            protected function getApplicationNamespace()
            {
                return $this->namespace;
            }
        };
        $adapter->authorize();
    }
}

namespace ExpressionEngine\Controller\Updater;

function show_error($message, $code)
{
    throw new \RuntimeException($message, $code);
}
