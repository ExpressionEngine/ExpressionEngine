<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager\Columns;

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;

/**
 * Structure URI Column
 */
class StructureUri extends Column
{
    public function getEntryManagerColumnModels()
    {
        return ['structure:Structure as Structure', 'structure:StructureListing as StructureListing'];
    }

    public function getEntryManagerColumnFields()
    {
        return ['Structure.structure_uri', 'StructureListing.structure_uri'];
    }

    public function getEntryManagerModelAliases()
    {
        return ['Structure' => 'structure:Structure', 'StructureListing' => 'structure:StructureListing'];
    }

    public function getEntryManagerColumnSortField()
    {
        return 'structure_uri';
    }

    public function getTableColumnLabel()
    {
        return 'structure_uri';
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        if (!is_null($entry->Structure) && $entry->Structure->structure_uri != '') {
            return $entry->Structure->structure_uri;
        }
        if (!is_null($entry->StructureListing) && $entry->StructureListing->structure_uri != '') {
            return $entry->StructureListing->structure_uri;
        }
        return '';
    }
}
