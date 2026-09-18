<?php

namespace ExpressionEngine\Tests\ExpressionEngine\Addons\grid\Library;

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class GridLibApplySettingsTest extends TestCase
{
    /**
     * Load the Grid settings classes and reset EE dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        require_once BASEPATH . 'core/Model.php';
        require_once BASEPATH . 'models/grid_model.php';
        require_once PATH_ADDONS . 'grid/libraries/Grid_lib.php';

        ee()->resetMocks();
    }

    /**
     * Release singleton mocks after each settings save.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ee()->resetMocks();

        parent::tearDown();
    }

    /**
     * Keep source column IDs out of new tables while preserving normal edits.
     *
     * @dataProvider settingsSaveProvider
     * @param bool $newField Whether the destination data table was just created.
     * @param bool $cloning Whether this request still carries the clone action.
     * @param array $expectedIds Column IDs passed to persistence, or false for new columns.
     * @return void
     */
    public function testApplySettingsUsesIndependentColumnsForNewFields($newField, $cloning, $expectedIds): void
    {
        define('CLONING_MODE', $cloning);

        $columns = [];
        foreach ([31 => ['file', 'file'], 32 => ['topic', 'text'], 33 => ['link', 'text']] as $id => $definition) {
            $columns[$id] = [
                'col_id' => $id,
                'col_type' => $definition[1],
                'col_name' => $definition[0],
                'col_label' => ucfirst($definition[0]),
                'col_instructions' => '',
                'col_required' => 'n',
                'col_search' => 'n',
                'col_width' => 0,
                'col_settings' => [],
            ];
        }

        $posted = [];
        foreach ($columns as $id => $column) {
            $posted['col_id_' . $id] = $column;
        }
        $posted['new_0'] = array_merge($columns[32], ['col_name' => 'extra', 'col_label' => 'Extra']);

        $model = $this->getMockBuilder(\Grid_model::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create_field', 'get_columns_for_field', 'save_col_settings', 'update_grid_search', 'delete_columns'])
            ->getMock();
        $model->expects($this->once())->method('create_field')->with(22, 'channel')->willReturn($newField);
        $model->expects($this->once())->method('get_columns_for_field')
            ->with(22, 'channel', false)->willReturn($newField ? [] : $columns);
        $model->expects($this->once())->method('update_grid_search')->with([22]);
        $model->expects($this->never())->method('delete_columns');

        $savedIds = [];
        $savedColumns = [];
        $model->expects($this->exactly(4))->method('save_col_settings')
            ->willReturnCallback(function ($column, $id, $contentType) use (&$savedIds, &$savedColumns) {
                $this->assertSame('channel', $contentType);
                $savedIds[] = $id;
                $savedColumns[] = $column;

                return $id ?: 100 + count($savedIds);
            });
        ee()->setMock('grid_model', $model);

        $library = $this->getMockBuilder(\Grid_lib::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['_save_settings'])
            ->getMock();
        $library->method('_save_settings')->willReturn([]);
        $library->field_id = 22;
        $library->content_type = 'channel';
        $library->apply_settings(['field_id' => 22, 'grid' => ['cols' => $posted]]);

        $this->assertSame($expectedIds, $savedIds);
        $this->assertSame([22, 22, 22, 22], array_column($savedColumns, 'field_id'));
        $this->assertSame(['file', 'topic', 'link', 'extra'], array_column($savedColumns, 'col_name'));
        $this->assertSame([0, 1, 2, 3], array_column($savedColumns, 'col_order'));
    }

    /**
     * Cover a clone retry, an uninterrupted clone, and an existing field edit.
     *
     * @return array Settings save scenarios and expected persistence IDs.
     */
    public function settingsSaveProvider(): array
    {
        return [
            'clone retry after validation error' => [true, false, [false, false, false, false]],
            'normal clone' => [true, true, [false, false, false, false]],
            'existing field edit' => [false, false, ['31', '32', '33', false]],
        ];
    }
}
