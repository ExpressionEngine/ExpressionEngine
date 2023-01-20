<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager;

use ExpressionEngine\Library\CP\EntryManager;

/**
 * Entry Manager Column Interface
 *
 * Interface for any piece of data associated with an entry to display their
 * data in the entry manager
 */
interface ColumnInterface
{
    /**
     * Return a unique column identifier, this is how your column will be
     * referenced as a stored view, so it's best not to change it and to create
     * a title that won't clash with other identifiers
     *
     * @return string
     */
    public function getTableColumnIdentifier();

    /**
     * Return the column's label that will appear in the table's heading
     *
     * @return string
     */
    public function getTableColumnLabel();

    /**
     * Return the column's CP/Table service config settings, refer to the
     * documentation for the CP/Table service for a list of available
     * configuration items
     *
     * @return array CP/Table service individual column config,
     * single-dimensional, associative array
     */
    public function getTableColumnConfig();

    /**
     * Renders table cell for Entry Manager
     *
     * @return string
     */
    public function renderTableCell($data, $field_id, $entry);

    /**
     * @return array names of extra columns
     * that need to be eager loaded with entries query
     */
    public function getEntryManagerColumnModels();

    /**
     * @return array names of extra fields
     * that need to be fetched with entries query
     * (from eager loaded model)
     */
    public function getEntryManagerColumnFields();

    /**
     * Get name of database field to sort by
     * (if different than column identifier)
     * @return string
     */
    public function getEntryManagerColumnSortField();
}
