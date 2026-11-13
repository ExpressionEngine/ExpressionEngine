<?php

namespace ExpressionEngine\Controller\Categories;

use ExpressionEngine\Service\Model\Facade;
use ExpressionEngine\Service\Model\Query\Builder;
use ExpressionEngine\Service\Permission\Permission;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * Isolate the AJAX constant and controller error response double.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CategoriesReorderTest extends TestCase
{
    private $controller;
    private $group;
    private $query;
    private $input;
    private $output;

    /**
     * Prepare the controller and its request and persistence dependencies.
     *
     * @return void
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        require_once APPPATH . 'core/Controller.php';

        /**
         * Capture rejected requests without rendering an error page.
         *
         * @param string $message
         * @param int $status
         * @return void
         * @throws CategoriesReorderResponse
         */
        function show_error($message, $status = 500)
        {
            throw new CategoriesReorderResponse($message, $status);
        }

        ee()->resetMocks();
        $this->controller = (new ReflectionClass(Categories::class))->newInstanceWithoutConstructor();
        $this->group = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['save', 'getCategories'])
            ->getMock();
        $this->group->can_edit_categories = '7|';
        $this->group->sort_order = 'a';

        $this->query = $this->createMock(Builder::class);
        $this->query->method('filter')->with('group_id', 2)->willReturnSelf();
        $model = $this->createMock(Facade::class);
        $model->method('get')->with('CategoryGroup')->willReturn($this->query);
        ee()->setMock('Model', $model);

        $this->input = $this->getMockBuilder(\stdClass::class)->addMethods(['post'])->getMock();
        $this->output = $this->getMockBuilder(\stdClass::class)->addMethods(['send_ajax_response'])->getMock();
        ee()->setMock('input', $this->input);
        ee()->setMock('output', $this->output);
    }

    /**
     * Clear container doubles after each request.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    /**
     * Reject unauthorized or invalid requests before any persistence occurs.
     *
     * @param bool $can_edit Whether the generic permission is granted.
     * @param string|null $group_roles The group's authorized role list.
     * @param bool $group_exists Whether the requested group exists.
     * @param array $order The submitted category tree.
     * @param bool $ajax_request Whether this is an AJAX request.
     * @return void
     * @dataProvider deniedRequestProvider
     */
    public function testRejectedRequestDoesNotWrite($can_edit, $group_roles, $group_exists, array $order, $ajax_request): void
    {
        define('AJAX_REQUEST', $ajax_request);
        ee()->setMock('Permission', new Permission(null, [], $can_edit ? ['can_edit_categories' => true] : [], [7 => 'Editor']));
        $this->group->can_edit_categories = $group_roles;
        $this->query->method('first')->willReturn($group_exists ? $this->group : null);
        $this->input->method('post')->with('order')->willReturn($order);
        $this->group->expects($this->never())->method('save');
        $this->group->expects($this->never())->method('getCategories');
        $this->output->expects($this->never())->method('send_ajax_response');

        try {
            $this->controller->reorder(2);
            $this->fail('Expected the reorder request to be rejected.');
        } catch (CategoriesReorderResponse $exception) {
            $this->assertSame(403, $exception->getCode());
            $this->assertSame('unauthorized_access', $exception->getMessage());
        }

        $this->assertSame('a', $this->group->sort_order);
    }

    /**
     * Supply permission failures and invalid requests that must not write.
     *
     * @return array
     */
    public function deniedRequestProvider(): array
    {
        $order = [['id' => 7], ['id' => 6]];

        return [
            'role excluded from group' => [true, '99|', true, $order, true],
            'empty group role list' => [true, '', true, $order, true],
            'null group role list' => [true, null, true, $order, true],
            'generic permission missing' => [false, '7|', true, $order, true],
            'group missing' => [true, '7|', false, $order, true],
            'empty order' => [true, '7|', true, [], true],
            'non-AJAX request' => [true, '7|', true, $order, false],
        ];
    }

    /**
     * Preserve ordering and nesting for authorized roles and Super Admins.
     *
     * @param array $roles The acting member's roles.
     * @param string $group_roles The group's authorized role list.
     * @param bool $nested Whether to re-parent the first category.
     * @return void
     * @dataProvider authorizedRequestProvider
     */
    public function testAuthorizedRequestPersistsChanges(array $roles, $group_roles, $nested): void
    {
        define('AJAX_REQUEST', true);
        ee()->setMock('Permission', new Permission(null, [], ['can_edit_categories' => true], $roles));
        $this->group->can_edit_categories = $group_roles;
        $this->query->method('first')->willReturn($this->group);
        $order = $nested ? [['id' => 7, 'children' => [['id' => 6]]]] : [['id' => 7], ['id' => 6]];
        $this->input->method('post')->with('order')->willReturn($order);

        $first = $this->getMockBuilder(\stdClass::class)->addMethods(['save'])->getMock();
        $first->cat_id = 6;
        $first->parent_id = 0;
        $first->cat_order = 1;
        $first->expects($this->once())->method('save');
        $second = $this->getMockBuilder(\stdClass::class)->addMethods(['save'])->getMock();
        $second->cat_id = 7;
        $second->parent_id = 0;
        $second->cat_order = 2;
        $second->expects($this->once())->method('save');
        $this->group->expects($this->once())->method('save');
        $this->group->expects($this->once())->method('getCategories')->willReturn([$first, $second]);

        // Stop at the response boundary before the controller exits the process.
        $response = new CategoriesReorderResponse('AJAX response sent');
        $this->output->expects($this->once())->method('send_ajax_response')
            ->with($this->isNull())->willThrowException($response);

        try {
            $this->controller->reorder(2);
            $this->fail('Expected a successful AJAX response.');
        } catch (CategoriesReorderResponse $exception) {
            $this->assertSame($response, $exception);
        }

        $this->assertSame('c', $this->group->sort_order);
        $this->assertSame($nested ? 7 : 0, $first->parent_id);
        $this->assertSame($nested ? 1 : 2, $first->cat_order);
        $this->assertSame(0, $second->parent_id);
        $this->assertSame(1, $second->cat_order);
    }

    /**
     * Supply delegated access, secondary-role access, and Super Admin access.
     *
     * @return array
     */
    public function authorizedRequestProvider(): array
    {
        return [
            'authorized role reorders' => [[7 => 'Editor'], '7|', false],
            'authorized role re-parents' => [[7 => 'Editor'], '7|', true],
            'secondary role authorized' => [[7 => 'Editor', 8 => 'Category editor'], '99|8|', false],
            'Super Admin excluded from list' => [[1 => 'Super Admin'], '99|', false],
            'Super Admin with empty list' => [[1 => 'Super Admin'], '', true],
        ];
    }
}

class CategoriesReorderResponse extends RuntimeException
{
}
