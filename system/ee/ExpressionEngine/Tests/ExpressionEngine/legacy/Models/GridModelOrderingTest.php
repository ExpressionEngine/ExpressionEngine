<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../eeObjectMock.php';

if (! class_exists('CI_Model')) {
    require_once BASEPATH . 'core/Model.php';
}

if (! function_exists('element')) {
    require_once BASEPATH . 'helpers/array_helper.php';
}

if (! class_exists('Grid_model')) {
    require_once BASEPATH . 'models/grid_model.php';
}

class GridModelOrderingTestDouble extends Grid_model
{
    private $columns;

    public function __construct(array $columns)
    {
        parent::__construct();

        $this->columns = $columns;
    }

    /**
     * Validate Grid tag parameters for tests.
     *
     * @param array $params Raw Grid tag parameters.
     * @return array Validated Grid tag parameters.
     */
    public function validateParams(array $params)
    {
        return $this->_validate_params($params, 9, 'channel');
    }

    /**
     * Return deterministic Grid columns for tests.
     *
     * @param mixed $field_ids Grid field ID or IDs.
     * @param string $content_type Grid content type.
     * @param bool $cache Whether cached columns may be used.
     * @return array Grid column definitions.
     */
    public function get_columns_for_field($field_ids, $content_type, $cache = true)
    {
        return $this->columns;
    }
}

class GridModelOrderingDbMock extends FakeDb
{
    public $orders = array();

    /**
     * Record query builder ordering calls.
     *
     * @param string $field Field or expression being ordered.
     * @param string $direction Sort direction.
     * @param mixed $escape Query builder escape flag.
     * @return $this
     */
    public function order_by($field, $direction = '', $escape = null)
    {
        $this->orders[] = array(
            'field' => $field,
            'direction' => $direction,
            'escape' => $escape,
        );

        return $this;
    }
}

class GridModelOrderingTest extends TestCase
{
    protected function setUp(): void
    {
        ee()->resetMocks();

        ee()->setMock('load', new class {
            public function helper($name)
            {
                return;
            }

            public function model($name)
            {
                return;
            }
        });

        ee()->setMock('extensions', new class {
            public function active_hook($name)
            {
                return false;
            }
        });

        ee()->setMock('functions', new class {
            public function ar_andor_string($str, $field)
            {
                return;
            }
        });

        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return false;
            }

            public function getEntryData()
            {
                return array();
            }
        });
    }

    protected function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testOrdersByMultipleGridColumns(): void
    {
        list($model, $db) = $this->makeModelWithDbRows(array(
            array('row_id' => 1, 'entry_id' => 100, 'row_order' => 0, 'fluid_field_data_id' => 0),
        ));

        $model->get_entry_rows(100, 9, 'channel', array(
            'orderby' => 'first|second',
            'sort' => 'desc|asc',
        ), true);

        $this->assertSame(array(
            array('field' => 'col_id_11', 'direction' => 'desc', 'escape' => null),
            array('field' => 'col_id_22', 'direction' => 'asc', 'escape' => null),
        ), $db->orders);
    }

    public function testMissingSortDirectionsDefaultToAsc(): void
    {
        $model = $this->makeModel();

        $params = $model->validateParams(array(
            'orderby' => 'first|second',
            'sort' => 'desc',
        ));

        $this->assertSame(array('desc', 'asc'), $params['sorts']);
    }

    public function testInvalidSortDirectionsDefaultToAsc(): void
    {
        $model = $this->makeModel();

        $params = $model->validateParams(array(
            'orderby' => 'first|second',
            'sort' => 'sideways|asc',
        ));

        $this->assertSame(array('asc', 'asc'), $params['sorts']);
    }

    public function testInvalidOrderbyTokensFallBackToRowOrder(): void
    {
        $model = $this->makeModel();

        $params = $model->validateParams(array(
            'orderby' => 'missing|second',
            'sort' => 'desc|asc',
        ));

        $this->assertSame(array('row_order', 'col_id_22'), $params['orderbys']);
    }

    public function testRandomOrderbyCollapsesToRowOrderForSql(): void
    {
        $model = $this->makeModel();

        $params = $model->validateParams(array(
            'orderby' => 'random|first',
            'sort' => 'desc|asc',
        ));

        $this->assertSame('random', $params['orderby']);
        $this->assertSame(array('row_order'), $params['orderbys']);
        $this->assertSame(array('desc'), $params['sorts']);
    }

    public function testEmptyOrderbyFallsBackToRowOrderForSql(): void
    {
        list($model, $db) = $this->makeModelWithDbRows(array(
            array('row_id' => 1, 'entry_id' => 100, 'row_order' => 0, 'fluid_field_data_id' => 0),
        ));

        $model->get_entry_rows(100, 9, 'channel', array(), true);

        $this->assertSame(array(
            array('field' => 'row_order', 'direction' => 'asc', 'escape' => null),
        ), $db->orders);
    }

    public function testRandomOrderbyUsesRowOrderForSqlButPreservesRandomParam(): void
    {
        list($model, $db) = $this->makeModelWithDbRows(array(
            array('row_id' => 1, 'entry_id' => 100, 'row_order' => 0, 'fluid_field_data_id' => 0),
        ));

        $entry_data = $model->get_entry_rows(100, 9, 'channel', array(
            'orderby' => 'random',
            'sort' => 'desc',
        ), true);

        $this->assertSame(array(
            array('field' => 'row_order', 'direction' => 'desc', 'escape' => null),
        ), $db->orders);
        $this->assertSame('random', $entry_data['params']['orderby']);
    }

    public function testFixedOrderRemainsFirstOrderingRule(): void
    {
        list($model, $db) = $this->makeModelWithDbRows(array(
            array('row_id' => 1, 'entry_id' => 100, 'row_order' => 0, 'fluid_field_data_id' => 0),
        ));

        $model->get_entry_rows(100, 9, 'channel', array(
            'fixed_order' => '3|2',
            'orderby' => 'second',
            'sort' => 'desc',
        ), true);

        $this->assertSame(array(
            array('field' => 'FIELD(row_id, 3, 2)', 'direction' => 'desc', 'escape' => false),
            array('field' => 'col_id_22', 'direction' => 'desc', 'escape' => null),
        ), $db->orders);
    }

    public function testCacheMarkerIncludesSecondaryOrderingRules(): void
    {
        list($model, $db) = $this->makeModelWithDbRows(array(
            array('row_id' => 1, 'entry_id' => 100, 'row_order' => 0, 'fluid_field_data_id' => 0),
        ));

        $model->get_entry_rows(100, 9, 'channel', array(
            'orderby' => 'first|second',
            'sort' => 'asc|desc',
        ));
        $model->get_entry_rows(100, 9, 'channel', array(
            'orderby' => 'first|third',
            'sort' => 'asc|desc',
        ));

        $this->assertSame(array(
            array('field' => 'col_id_11', 'direction' => 'asc', 'escape' => null),
            array('field' => 'col_id_22', 'direction' => 'desc', 'escape' => null),
            array('field' => 'col_id_11', 'direction' => 'asc', 'escape' => null),
            array('field' => 'col_id_33', 'direction' => 'desc', 'escape' => null),
        ), $db->orders);
    }

    public function testLivePreviewUsesSecondarySortForTies(): void
    {
        ee()->setMock('LivePreview', new class {
            public function hasEntryData()
            {
                return true;
            }

            public function getEntryData()
            {
                return array(
                    'entry_id' => 100,
                    'field_id_9' => array(
                        'rows' => array(
                            'new_row_1' => array('col_id_11' => 'Beta', 'col_id_22' => '2'),
                            'new_row_2' => array('col_id_11' => 'Alpha', 'col_id_22' => '2'),
                            'new_row_3' => array('col_id_11' => 'Alpha', 'col_id_22' => '1'),
                        ),
                    ),
                );
            }
        });

        list($model) = $this->makeModelWithDbRows(array());

        $entry_data = $model->get_entry_rows(100, 9, 'channel', array(
            'orderby' => 'first|second',
            'sort' => 'asc|desc',
        ), true);

        $this->assertSame(array('new_row_2', 'new_row_3', 'new_row_1'), array_column($entry_data[100], 'orig_row_id'));
    }

    /**
     * Create a Grid model test double.
     *
     * @return GridModelOrderingTestDouble Grid model test double.
     */
    private function makeModel()
    {
        return new GridModelOrderingTestDouble($this->gridColumns());
    }

    /**
     * Create a Grid model test double with a DB mock.
     *
     * @param array $rows Rows returned by the DB mock.
     * @return array Grid model and DB mock.
     */
    private function makeModelWithDbRows(array $rows)
    {
        $db = new GridModelOrderingDbMock();
        $db->setRows($rows);
        ee()->setMock('db', $db);

        return array($this->makeModel(), $db);
    }

    /**
     * Return shared Grid column fixtures.
     *
     * @return array Grid column fixtures.
     */
    private function gridColumns()
    {
        return array(
            11 => array('col_id' => 11, 'col_name' => 'first'),
            22 => array('col_id' => 22, 'col_name' => 'second'),
            33 => array('col_id' => 33, 'col_name' => 'third'),
        );
    }
}
