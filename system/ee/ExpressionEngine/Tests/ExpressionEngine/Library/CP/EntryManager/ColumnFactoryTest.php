<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Tests\Library\CP\EntryManager;

use ExpressionEngine\Library\CP\EntryManager\ColumnFactory;
use ExpressionEngine\Library\CP\EntryManager\Columns\LastEditor;
use PHPUnit\Framework\TestCase;

class ColumnFactoryTest extends TestCase
{
    public function tearDown(): void
    {
        ee()->resetMocks();
    }

    public function testGetColumnReturnsLastEditorColumnForLastEditorIdentifier()
    {
        $column = ColumnFactory::getColumn('last_editor');

        $this->assertInstanceOf(LastEditor::class, $column);
        $this->assertSame('last_editor', $column->getTableColumnIdentifier());
    }
}

// EOF
