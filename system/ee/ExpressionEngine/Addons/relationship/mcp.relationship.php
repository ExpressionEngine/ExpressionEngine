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
        $entry = ee('Model')->get('ChannelEntry', (int) ee('Request')->get('entry_id'))->first();
        if (empty($entry)) {
            ee()->output->send_ajax_response(['error' => 'Entry not found']);
        }
        // are we dealing with field or Grid column?
        if (!empty(ee('Request')->get('field_name'))) {
            // regular field
            $field = $entry->getCustomField(ee('Request')->get('field_name'));
            return ee()->output->send_ajax_response(['content' => $field->getForm()]);
        } elseif (!empty(ee('Request')->get('field_id')) && !empty(ee('Request')->get('fluid_field_data_id'))) {
            // Fluid field
            $fluid_field_data_id = (int) ee('Request')->get('fluid_field_data_id');
            $fluid_field_data = ee('Model')->get('fluid_field:FluidField')
                ->with('ChannelField')
                ->filter('field_id', (int) ee('Request')->get('field_id'))
                ->filter('id', $fluid_field_data_id)
                ->filter('entry_id', $entry->getId())
                ->first();
            $field = $fluid_field_data->getField();
            return ee()->output->send_ajax_response(['content' => $field->getForm()]);
        } elseif (!empty(ee('Request')->get('grid_field_id')) && !empty(ee('Request')->get('grid_col_id')) && !empty(ee('Request')->get('grid_row_id'))) {
            // Grid and Grid in Fluid
            ee()->load->library('grid_parser');
            ee()->load->model('grid_model');
            $grid_field_id = (int) ee('Request')->get('grid_field_id');
            $grid_col_id = (int) ee('Request')->get('grid_col_id');
            $grid_row_id = (int) ee('Request')->get('grid_row_id');
            $fluid_field_data_id = (int) ee('Request')->get('fluid_field_data_id');
            $columns = ee()->grid_model->get_columns_for_field($grid_field_id, 'channel');
            if (!isset($columns[$grid_col_id])) {
                ee()->output->send_ajax_response(['error' => 'Column not found']);
            }
            $fieldtype = ee()->grid_parser->instantiate_fieldtype(
                $columns[$grid_col_id],
                null,
                $grid_field_id,
                $entry->getId(),
                'channel',
                $fluid_field_data_id,
                false //$this->in_modal_context
            );
            $fieldtype->settings['grid_row_id'] = $grid_row_id;
            $rows = ee()->grid_model->get_entry_rows(
                $entry->getId(),
                $grid_field_id,
                'channel',
                [],
                false,
                $fluid_field_data_id
            );
            if (!isset($rows[$entry->getId()][$grid_row_id])) {
                ee()->output->send_ajax_response(['error' => 'Row not found']);
            }

            // Call the fieldtype's field display method and capture the output
            $display_field = ee()->grid_parser->call('display_field', $rows[$entry->getId()][$grid_row_id]);
            return ee()->output->send_ajax_response(['content' => $display_field]);
        } else {
            ee()->output->send_ajax_response(['error' => 'No field or column name provided'], true);
        }
        return array('html' => $entry->getCustomField(ee()->input->get('field_name'))->getForm());
    }
}

// EOF
