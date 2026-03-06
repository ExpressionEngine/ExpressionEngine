<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Controllers\Categories {

use ExpressionEngine\Controller\Categories\Categories;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CategoriesTest extends TestCase
{
    private $controller;
    private $db;
    private $output;
    private $extensions;

    public static function setUpBeforeClass(): void
    {
        if (! defined('AJAX_REQUEST')) {
            define('AJAX_REQUEST', true);
        }

        if (! defined('SYSPATH')) {
            define('SYSPATH', realpath(__DIR__ . '/../../../../../../') . '/');
        }

        if (! defined('APPPATH')) {
            define('APPPATH', SYSPATH . 'ee/ExpressionEngine/');
        }

        if (! defined('BASEPATH')) {
            define('BASEPATH', SYSPATH . 'ee/legacy/');
        }

        if (! class_exists('EE_Controller')) {
            require_once SYSPATH . 'ee/legacy/core/Controller.php';
        }

        require_once SYSPATH . 'ee/ExpressionEngine/Tests/eeObjectMock.php';
        require_once SYSPATH . 'ee/ExpressionEngine/Controller/Categories/AbstractCategories.php';
        require_once SYSPATH . 'ee/ExpressionEngine/Controller/Categories/Categories.php';
    }

    protected function setUp(): void
    {
        ee()->resetMocks();

        $this->controller = (new \ReflectionClass(Categories::class))->newInstanceWithoutConstructor();
        $this->db = new CategoriesTestDb([
            ['cat_id' => 1, 'parent_id' => 0, 'cat_order' => 1],
            ['cat_id' => 2, 'parent_id' => 0, 'cat_order' => 2],
            ['cat_id' => 3, 'parent_id' => 1, 'cat_order' => 1],
        ]);
        $this->output = new CategoriesTestOutput();
        $this->extensions = new CategoriesTestExtensions();

        ee()->setMock('Permission', new CategoriesTestPermission());
        ee()->setMock('Model', new CategoriesTestModelService(new \stdClass()));
        ee()->setMock('input', new CategoriesTestInput($this->validOrderPayload()));
        ee()->setMock('db', $this->db);
        ee()->setMock('output', $this->output);
        ee()->setMock('extensions', $this->extensions);
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testReorderSucceedsWithoutCallingHookWhenInactive(): void
    {
        ee()->setMock('input', new CategoriesTestInput($this->validOrderPayload(), null));

        $this->controller->reorder(5);

        $this->assertSame([['payload' => null, 'error' => false]], $this->output->responses);
        $this->assertSame([], $this->extensions->calls);
        $this->assertSame([
            [
                ['cat_id' => 1, 'parent_id' => 0, 'cat_order' => 2],
                ['cat_id' => 2, 'parent_id' => 0, 'cat_order' => 1],
            ],
        ], $this->db->updateBatchCalls);
    }

    public function testReorderCallsHookWithChangedRowsWhenActive(): void
    {
        $this->extensions->activeHooks = ['category_reorder_end' => true];

        $this->controller->reorder(5);

        $this->assertCount(1, $this->extensions->calls);
        $this->assertSame('category_reorder_end', $this->extensions->calls[0]['hook']);
        $this->assertSame([
            ['cat_id' => 1, 'parent_id' => 0, 'cat_order' => 2],
            ['cat_id' => 2, 'parent_id' => 0, 'cat_order' => 1],
        ], $this->extensions->calls[0]['args'][0]);
        $this->assertSame(5, $this->extensions->calls[0]['args'][1]);
        $this->assertSame([['payload' => null, 'error' => false]], $this->output->responses);
    }

    public function testReorderSkipsHookWhenTransactionFails(): void
    {
        $this->extensions->activeHooks = ['category_reorder_end' => true];
        $this->db->transactionStatus = false;

        $this->controller->reorder(5);

        $this->assertSame([
            ['payload' => ['error' => 'Unable to save category reorder changes.'], 'error' => true],
        ], $this->output->responses);
        $this->assertSame([], $this->extensions->calls);
    }

    public function testReorderSucceedsWithJsonPayloadWithoutCallingHookWhenInactive(): void
    {
        ee()->setMock('input', new CategoriesTestInput(null, $this->validOrderPayloadJson()));

        $this->controller->reorder(5);

        $this->assertSame([['payload' => null, 'error' => false]], $this->output->responses);
        $this->assertSame([], $this->extensions->calls);
        $this->assertSame([
            [
                ['cat_id' => 1, 'parent_id' => 0, 'cat_order' => 2],
                ['cat_id' => 2, 'parent_id' => 0, 'cat_order' => 1],
            ],
        ], $this->db->updateBatchCalls);
    }

    public function testReorderRejectsMalformedJsonPayloadWithoutCallingHook(): void
    {
        $this->extensions->activeHooks = ['category_reorder_end' => true];
        ee()->setMock('input', new CategoriesTestInput(null, '{"id":'));

        $this->controller->reorder(5);

        $this->assertSame([
            ['payload' => ['error' => 'Category reorder payload was invalid. No changes were saved.'], 'error' => true],
        ], $this->output->responses);
        $this->assertSame([], $this->extensions->calls);
        $this->assertSame([], $this->db->updateBatchCalls);
    }

    public function testReorderRejectsIncompletePayloadWithoutCallingHook(): void
    {
        $this->extensions->activeHooks = ['category_reorder_end' => true];
        ee()->setMock('input', new CategoriesTestInput([
            ['id' => 2],
            ['id' => 1],
        ]));

        $this->controller->reorder(5);

        $this->assertSame([
            ['payload' => ['error' => 'Category reorder payload was incomplete. No changes were saved.'], 'error' => true],
        ], $this->output->responses);
        $this->assertSame([], $this->extensions->calls);
        $this->assertSame([], $this->db->updateBatchCalls);
    }

    private function validOrderPayload(): array
    {
        return [
            ['id' => 2],
            [
                'id' => 1,
                'children' => [
                    ['id' => 3],
                ],
            ],
        ];
    }

    private function validOrderPayloadJson(): string
    {
        return json_encode($this->validOrderPayload()) ?: '[]';
    }
}

class CategoriesTestPermission
{
    public function can($permission)
    {
        return $permission === 'edit_categories';
    }
}

class CategoriesTestModelService
{
    private $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    public function get($modelName)
    {
        return new CategoriesTestModelQuery($this->result);
    }
}

class CategoriesTestModelQuery
{
    private $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    public function filter($field, $value)
    {
        return $this;
    }

    public function first()
    {
        return $this->result;
    }
}

class CategoriesTestInput
{
    private $postData = [];

    public function __construct(?array $order = null, $order_json = null)
    {
        if (! is_null($order)) {
            $this->postData['order'] = $order;
        }

        if (! is_null($order_json)) {
            $this->postData['order_json'] = $order_json;
        }
    }

    public function post($key)
    {
        return $this->postData[$key] ?? null;
    }
}

class CategoriesTestOutput
{
    public $responses = [];

    public function send_ajax_response($payload, $error = false)
    {
        $this->responses[] = [
            'payload' => $payload,
            'error' => $error,
        ];
    }
}

class CategoriesTestExtensions
{
    public $activeHooks = [];
    public $calls = [];

    public function active_hook($hook)
    {
        return $this->activeHooks[$hook] ?? false;
    }

    public function call($hook, ...$args)
    {
        $this->calls[] = [
            'hook' => $hook,
            'args' => $args,
        ];
    }
}

class CategoriesTestDb
{
    private $rows;
    public $transactionStatus = true;
    public $whereCalls = [];
    public $updateCalls = [];
    public $updateBatchCalls = [];

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function select($fields)
    {
        return $this;
    }

    public function from($table)
    {
        return $this;
    }

    public function where($field, $value)
    {
        $this->whereCalls[] = [$field, $value];
        return $this;
    }

    public function get()
    {
        return new CategoriesTestDbResult($this->rows);
    }

    public function trans_start()
    {
        return true;
    }

    public function update($table, $data)
    {
        $this->updateCalls[] = [$table, $data];
        return true;
    }

    public function update_batch($table, $rows, $index)
    {
        $this->updateBatchCalls[] = $rows;
        return true;
    }

    public function trans_complete()
    {
        return true;
    }

    public function trans_status()
    {
        return $this->transactionStatus;
    }
}

class CategoriesTestDbResult
{
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function result_array()
    {
        return $this->rows;
    }
}

}

namespace ExpressionEngine\Controller\Categories {
    if (! function_exists(__NAMESPACE__ . '\show_error')) {
        function show_error($message, $code = 500)
        {
            throw new \Exception($message, $code);
        }
    }
}

namespace {
    if (! function_exists('lang')) {
        function lang($key)
        {
            return $key;
        }
    }

    if (! function_exists('show_error')) {
        function show_error($message, $code = 500)
        {
            throw new \Exception($message, $code);
        }
    }
}
