<?php

namespace ExpressionEngine\Controller\Settings;

use ExpressionEngine\Core\Request;
use ExpressionEngine\Service\Model\Facade;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * Keep the controller response double local to each test process.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MenuManagerRequestTest extends TestCase
{
    private $request;
    private $model;

    /**
     * Prepare the controller's request and model dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        require_once APPPATH . 'core/Controller.php';

        /**
         * Capture the controller's error response without rendering it.
         *
         * @param string $message
         * @param int $status
         * @return void
         * @throws RuntimeException
         */
        function show_error($message, $status = 500)
        {
            throw new RuntimeException($message, $status);
        }

        $this->request = $this->createMock(Request::class);
        $this->request->expects($this->any())->method('post')->with('content_id')->willReturn('42');
        $this->model = $this->createMock(Facade::class);
        ee()->setMock('Request', $this->request);
        ee()->setMock('Model', $this->model);
    }

    /**
     * Clear the container doubles after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Reject unsupported requests before looking up an item.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testUnsupportedRequestStopsBeforeItemLookup()
    {
        $this->request->expects($this->any())->method('isPost')->willReturn(false);
        $this->model->expects($this->never())->method('get');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unauthorized_access');
        $this->expectExceptionCode(403);

        (new ReflectionClass(MenuManager::class))->newInstanceWithoutConstructor()->removeItem();
    }

    /**
     * Preserve the item lookup for submitted requests.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function testSubmittedRequestReachesItemLookup()
    {
        $this->request->expects($this->any())->method('isPost')->willReturn(true);
        $this->model->expects($this->once())
            ->method('get')
            ->with('MenuItem', '42')
            ->willThrowException(new RuntimeException('Item lookup reached'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Item lookup reached');

        (new ReflectionClass(MenuManager::class))->newInstanceWithoutConstructor()->removeItem();
    }
}
