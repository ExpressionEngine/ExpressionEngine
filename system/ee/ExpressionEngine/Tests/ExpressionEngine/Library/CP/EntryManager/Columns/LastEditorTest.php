<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\CP\EntryManager\Columns;

use ExpressionEngine\Library\CP\EntryManager\Columns\LastEditor;
use PHPUnit\Framework\TestCase;

class LastEditorTest extends TestCase
{
    public function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testColumnMetadataMethods()
    {
        $column = new LastEditor('last_editor');

        $this->assertSame(['LastEditor'], $column->getEntryManagerColumnModels());
        $this->assertSame(
            ['edit_member_id', 'LastEditor.screen_name', 'LastEditor.username'],
            $column->getEntryManagerColumnFields()
        );
        $this->assertSame('edit_member_id', $column->getEntryManagerColumnSortField());
        $this->assertSame('last_editor', $column->getTableColumnLabel());
    }

    public function testRenderTableCellReturnsEmptyStringWhenEditMemberIdIsEmpty()
    {
        $column = new LastEditor('last_editor');

        $entry = new class {
            public $edit_member_id = 0;
            public $LastEditor;
        };

        $this->assertSame('', $column->renderTableCell([], '', $entry));
    }

    public function testRenderTableCellReturnsEmptyStringWhenRelationshipIsMissing()
    {
        $column = new LastEditor('last_editor');

        $entry = new class {
            public $edit_member_id = 10;
            public $LastEditor = null;
        };

        $this->assertSame('', $column->renderTableCell([], '', $entry));
    }

    public function testRenderTableCellFormatsMemberNameFromLastEditorRelation()
    {
        $column = new LastEditor('last_editor');

        $formatMock = new class {
            public $calls = [];

            public function make($type, $value)
            {
                $this->calls[] = [$type, $value];

                return 'formatted:' . $value;
            }
        };
        ee()->setMock('Format', $formatMock);

        $entry = new class {
            public $edit_member_id = 42;
            public $LastEditor;
        };
        $entry->LastEditor = new class {
            public function getMemberName()
            {
                return 'Jane Doe';
            }
        };

        $result = $column->renderTableCell([], '', $entry);

        $this->assertSame('formatted:Jane Doe', $result);
        $this->assertSame([['Text', 'Jane Doe']], $formatMock->calls);
    }
}

// EOF
