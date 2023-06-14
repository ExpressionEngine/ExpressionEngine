<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Publish\BulkEdit;

use CP_Controller;

/**
 * Abstract Bulk Edit Controller
 */
abstract class AbstractBulkEdit extends CP_Controller
{
    public function __construct()
    {
        parent::__construct();

        ee()->lang->loadfile('content');
    }

    /**
     * Given a collection of entries, lets us know if the logged-in member has
     * permission to edit all entries
     *
     * @param Collection $entries Entries to check editing permissions for
     * @return Boolean Whether or not the logged-in member has permission
     */
    protected function hasPermissionToEditEntries($entries)
    {
        $author_ids = array_unique($entries->Author->getIds());
        $member_id = ee()->session->userdata('member_id');

        // Can edit others' entries?
        if (! ee('Permission')->can('edit_other_entries')) {
            $other_authors = array_diff($author_ids, [$member_id]);

            if (count($other_authors)) {
                return false;
            }
        }

        // Can edit own entries?
        if (! ee('Permission')->can('edit_self_entries') &&
            in_array($member_id, $author_ids)) {
            return false;
        }

        // Finally, assigned channels
        $assigned_channel_ids = array_keys(ee()->session->userdata('assigned_channels'));
        $editing_channel_ids = $entries->Channel->getIds();

        $disallowed_channels = array_diff($editing_channel_ids, $assigned_channel_ids);

        return count($disallowed_channels) == 0;
    }

    /**
     * Renders the Fluid UI markup for a given set of fields
     *
     * @param Array $displayed_fields Fields that should be displayed on load
     * @param Array $template_fields Fields to keep off screen as available templates
     * @param Array $filter_fields Fields to display in the Add menu
     * @param Result $errors Validation result for the given fields, or NULL
     * @return String HTML markup of Fluid UI
     */
    protected function getFluidMarkupForFields($displayed_fields, $template_fields, $filter_fields, $errors = null)
    {
        $filters = '';
        if (! empty($filter_fields)) {
            $filter_options = array_map(function ($field) {
                return \ExpressionEngine\Addons\FluidField\Model\FluidFieldFilter::make([
                    'name' => $field->getShortName(),
                    'label' => $field->getItem('field_label'),
                    'icon' => $field->getIcon()
                ]);
            }, $filter_fields);
            $filters = ee('View')->make('fluid_field:filters')->render(['filters' => $filter_options]);
        }

        $displayed_fields_markup = '';
        foreach ($displayed_fields as $field_name => $field) {
            $displayed_fields_markup .= ee('View')->make('fluid_field:field')->render([
                'field' => $field,
                'field_name' => $field_name,
                'filters' => '',
                'errors' => $errors,
                'reorderable' => false,
                'show_field_type' => false,
                'is_bulk_edit' => true
            ]);
        }

        $template_fields_markup = '';
        foreach ($template_fields as $field_name => $field) {
            $template_fields_markup .= ee('View')->make('fluid_field:field')->render([
                'field' => $field,
                'field_name' => $field_name,
                'filters' => '',
                'errors' => null,
                'reorderable' => false,
                'show_field_type' => false,
                'is_bulk_edit' => true
            ]);
        }

        return ee('View')->make('fluid_field:publish')->render([
            'fields' => $displayed_fields_markup,
            'field_templates' => $template_fields_markup,
            'filters' => $filters,
            'is_bulk_edit' => true
        ]);
    }

    /**
     * Given an entry, returns the FieldFacades for the available FieldFacades
     * for that entry
     *
     * @param ChannelEntry $entry Channel entry object to render fields from
     * @return Array Associative array of FieldFacades
     */
    protected function getCategoryFieldsForEntry($entry)
    {
        $fields = [];
        foreach ($entry->Channel->CategoryGroups->getIds() as $cat_group) {
            $fields[] = 'categories[cat_group_id_' . $cat_group . ']';
        }

        $field_facades = $this->getFieldsForEntry($entry, $fields);
        foreach ($field_facades as $field) {
            // Cannot edit categories in this view
            $field->setItem('editable', false);
        }

        return $field_facades;
    }

    /**
     * Given an entry, returns the FieldFacades for the given field names
     *
     * @param ChannelEntry $entry Channel entry object to render fields from
     * @param Array $fields Array of field short names to render
     * @return Array Associative array of FieldFacades
     */
    protected function getFieldsForEntry($entry, $fields)
    {
        $fields = array_filter($fields, [$entry, 'hasCustomField']);

        $field_facades = [];
        foreach ($fields as $field) {
            $field_facades[$field] = $entry->getCustomField($field);
        }

        return $field_facades;
    }

    /**
     * Given a Collection of channels, returns a channel entry object assigned
     * to an intersected channel
     *
     * @param Collection $channels Collection of channels
     * @return ChannelEntry
     */
    protected function getMockEntryForIntersectedChannels($channels)
    {
        $entry = ee('Model')->make('ChannelEntry');
        $entry->entry_id = 0;
        $entry->author_id = ee()->session->userdata('member_id');
        $entry->Channel = $this->getIntersectedChannel($channels);

        return $entry;
    }

    /**
     * Given a Collection of channels, returns a channel object with traits each
     * channel has in common, currently category groups and statuses
     *
     * @param Collection $channels Collection of channels
     * @return Channel
     */
    protected function getIntersectedChannel($channels)
    {
        $channels = $channels->intersect();

        // All entries belong to the same channel, easy peasy!
        if ($channels->count() < 2) {
            return $channels->first();
        }

        $channel = ee('Model')->make('Channel');
        $channel->CategoryGroups = $channels->CategoryGroups->intersect();
        $channel->Statuses = $channels->Statuses->intersect();

        // Only enable if ALL channels have comments enabled
        $channel->comment_system_enabled = ! in_array(false, $channels->pluck('comment_system_enabled'), true);

        return $channel;
    }
}

// EOF
