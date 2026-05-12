<?php

require_once SYSPATH . 'ee/legacy/database/DB_result.php';

use PHPUnit\Framework\TestCase;

class DBResultTest extends TestCase
{
    public function testResultWrapperReturnsObjectAndArrayRepresentations(): void
    {
        $result = new CI_DB_result();
        $result->result_object = [(object) ['id' => 1, 'name' => 'alpha']];

        $this->assertEquals([(object) ['id' => 1, 'name' => 'alpha']], $result->result('object'));
        $this->assertSame([['id' => 1, 'name' => 'alpha']], $result->result('array'));
    }

    public function testResultObjectReturnsCachedRowsWhenPresent(): void
    {
        $result = new CI_DB_result();
        $result->result_object = [(object) ['id' => 3]];

        $this->assertCount(1, $result->result_object());
    }

    public function testResultObjectReturnsEmptyForFalseStatementOrZeroRows(): void
    {
        $falseStatement = new CI_DB_result(false);
        $this->assertSame([], $falseStatement->result_object());

        $zeroRows = new CI_DB_result(new stdClass());
        $zeroRows->num_rows = 0;
        $this->assertSame([], $zeroRows->result_object());
    }

    public function testResultObjectFetchesRowsFromFetchObjectMethod(): void
    {
        $result = new DBResultFetchHarness(new stdClass());
        $result->num_rows = 2;
        $result->fetchObjectQueue = [
            (object) ['id' => 10],
            (object) ['id' => 11],
            false,
        ];

        $rows = $result->result_object();

        $this->assertCount(2, $rows);
        $this->assertSame(10, $rows[0]->id);
        $this->assertSame(11, $rows[1]->id);
    }

    public function testResultArrayCastsObjectRowsToArrays(): void
    {
        $result = new DBResultFetchHarness(new stdClass());
        $result->result_object = [(object) ['id' => 1], (object) ['id' => 2]];

        $this->assertSame([['id' => 1], ['id' => 2]], $result->result_array());
    }

    public function testRowSupportsNamedFieldsAndFallbackToFirstRow(): void
    {
        $result = new CI_DB_result();
        $result->result_object = [(object) ['id' => 1, 'title' => 'news']];

        $this->assertSame('news', $result->row('title'));
        $this->assertEquals((object) ['id' => 1, 'title' => 'news'], $result->row('missing'));
    }

    public function testRowSupportsNumericIndexAndArrayType(): void
    {
        $result = new CI_DB_result();
        $result->result_object = [
            (object) ['id' => 1, 'title' => 'one'],
            (object) ['id' => 2, 'title' => 'two'],
        ];

        $this->assertSame(['id' => 2, 'title' => 'two'], $result->row(1, 'array'));
    }

    public function testSetRowHandlesArrayScalarAndIgnoredAssignments(): void
    {
        $result = new CI_DB_result();
        $result->result_object = [(object) ['id' => 1, 'title' => 'old']];

        $result->set_row(['extra' => 'value']);
        $this->assertSame('value', $result->row_data['extra']);

        $result->set_row('title', 'new');
        $this->assertSame('new', $result->row_data['title']);

        $result->set_row('', 'nope');
        $result->set_row('ignored', null);
        $this->assertArrayNotHasKey('ignored', $result->row_data);
    }

    public function testRowObjectRowArrayAndCurrentRowTracking(): void
    {
        $empty = new CI_DB_result();
        $this->assertSame([], $empty->row_object());

        $result = new CI_DB_result();
        $result->result_object = [
            (object) ['id' => 1],
            (object) ['id' => 2],
        ];

        $rowObject = $result->row_object(1);
        $this->assertSame(2, $rowObject->id);
        $this->assertSame(1, $result->current_row);
        $this->assertSame(['id' => 2], $result->row_array(1));
    }

    public function testFirstLastNextAndPreviousRowsHandleEmptyAndPopulatedResults(): void
    {
        $empty = new CI_DB_result();
        $this->assertSame([], $empty->first_row());
        $this->assertSame([], $empty->last_row());
        $this->assertSame([], $empty->next_row());
        $this->assertSame([], $empty->previous_row());

        $result = new CI_DB_result();
        $result->result_object = [
            (object) ['id' => 1],
            (object) ['id' => 2],
            (object) ['id' => 3],
        ];

        $this->assertSame(['id' => 1], $result->first_row('array'));
        $this->assertEquals((object) ['id' => 3], $result->last_row());

        $result->current_row = 0;
        $this->assertSame(2, $result->next_row()->id);
        $this->assertSame(3, $result->next_row()->id);
        $this->assertSame(3, $result->next_row()->id);

        $result->current_row = 2;
        $this->assertSame(2, $result->previous_row()->id);
        $this->assertSame(1, $result->previous_row()->id);
        $this->assertSame(1, $result->previous_row()->id);
    }

    public function testFallbackMethodsReturnExpectedDefaults(): void
    {
        $result = new CI_DB_result();
        $result->num_rows = 9;

        $this->assertSame(9, $result->num_rows());
        $this->assertSame(0, $result->num_fields());
        $this->assertSame([], $result->list_fields());
        $this->assertSame([], $result->field_data());
        $this->assertTrue($result->free_result());
        $this->assertTrue($result->_data_seek());
        $this->assertSame([], $result->_fetch_assoc());
        $this->assertSame([], $result->_fetch_object());
    }
}

class DBResultFetchHarness extends CI_DB_result
{
    public $fetchObjectQueue = [];

    public function _fetch_object()
    {
        return array_shift($this->fetchObjectQueue);
    }
}
