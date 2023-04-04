<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\FileManager;

use ExpressionEngine\Library\CP\EntryManager;

/**
 * File Manager Column Renderer
 */
class ColumnRenderer extends EntryManager\ColumnRenderer
{
    /**
     * Constructor
     *
     * @param array[ColumnInterface] $columns Array of objects implementing ColumnInterface
     */
    public function __construct(array $columns)
    {
        $availableColumnKeys = array_keys(ColumnFactory::getAvailableColumns());
        foreach ($columns as $key => $column) {
            if (!in_array($key, $availableColumnKeys)) {
                continue;
            }
            $this->columns[$key] = $column;
        }
    }
}
