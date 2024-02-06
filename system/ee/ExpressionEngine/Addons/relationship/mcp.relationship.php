<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

 /**
 * Relationship Fieldtype control panel
 */
class Relationship_mcp
{
    public function ajaxFilter()
    {
        ee()->load->library('EntryList');
        ee()->output->send_ajax_response(ee()->entrylist->ajaxFilter());
    }

    /**
     * AJAX endpoint for deferred loading of field component
     *
     * @return void
     */
    public function defer()
    {
        // get the entry
        if (empty(ee('Request')->get('entry_id'))) {
            ee()->output->send_ajax_response(['error' => 'No entry ID provided']);
        }
        $entry = ee('Model')->get('ChannelEntry', ee('Request')->get('entry_id'))->first();
        if (empty($entry)) {
            ee()->output->send_ajax_response(['error' => 'Entry not found']);
        }
        // are we dealing with field or Grid column?
        if (!empty(ee('Request')->get('field_name'))) {
            $field = $entry->getCustomField(ee('Request')->get('field_name'));
            return ee()->output->send_ajax_response(['content' => $field->getForm()]);
        } elseif (!empty(ee('Request')->get('grid_field_id')) && !empty(ee('Request')->get('grid_col_id')) && !empty(ee('Request')->get('grid_row_id'))) {
            // TODO
            // Grid support needs to be implemented
        } else {
            ee()->output->send_ajax_response(['error' => 'No field or column name provided']);
        }
        return array('html' => $entry->getCustomField(ee()->input->get('field_name'))->getForm());
    }
}

// EOF
