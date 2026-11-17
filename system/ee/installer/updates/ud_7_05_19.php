<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Updater\Version_7_5_19;

/**
 * Update
 */
class Updater
{
    public $version_suffix = '';

    /**
     * Do Update
     *
     * @return TRUE
     */
    public function do_update()
    {
        $steps = new \ProgressIterator([
            'addActionsIndexes',
            'addCategoryPostsIndex',
            'addChannelTitlesIndex',
            'addCommentsIndexes',
            'addTemplatesIndex',
            'addFluidFieldDataIndex',
            'addRelationshipsIndex',
            'addGridFieldIndexes',
        ]);

        foreach ($steps as $k => $v) {
            $this->$v();
        }

        return true;
    }

    public function addActionsIndexes()
    {
        // Add index for actions.class
        ee()->smartforge->add_key('actions', 'class', 'class');

        // Add index for actions.method
        ee()->smartforge->add_key('actions', 'method', 'method');

        return true;
    }

    public function addCategoryPostsIndex()
    {
        // Add index for exp_category_posts.cat_id to optimize "entries in category X" queries
        ee()->smartforge->add_key('category_posts', 'cat_id', 'cat_id');

        return true;
    }

    public function addChannelTitlesIndex()
    {
        // Add composite index for exp_channel_titles (channel_id, status, entry_date)
        // to optimize common channel:entries loops
        // Composite key on: channel_id, status, entry_date
        ee()->smartforge->add_key('channel_titles', 'channel_id_status_entry_date', ['channel_id', 'status', 'entry_date']);

        return true;
    }

    public function addCommentsIndexes()
    {
        // Skip if comments table doesn't exist (Comment module not installed)
        if (! ee()->db->table_exists('comments')) {
            return true;
        }

        // Add index for exp_comments.author_id to optimize "all comments by user X" queries
        ee()->smartforge->add_key('comments', 'author_id', 'author_id');

        // Add composite index for exp_comments (site_id, comment_date) to optimize Recent Comments widgets
        ee()->smartforge->add_key('comments', 'site_id_comment_date', ['site_id', 'comment_date']);

        return true;
    }

    public function addTemplatesIndex()
    {
        // Add composite index for exp_templates (group_id, template_name) to optimize template lookup
        // Composite key on: group_id, template_name
        ee()->smartforge->add_key('templates', 'group_id_template_name', ['group_id', 'template_name']);

        return true;
    }

    public function addFluidFieldDataIndex()
    {
        // Replace existing (fluid_field_id, entry_id) index with extended composite index
        // to allow sorting by group and order without filesort
        // Composite key on: fluid_field_id, entry_id, group, order
        ee()->smartforge->drop_key('fluid_field_data', 'fluid_field_id_entry_id');
        ee()->smartforge->add_key('fluid_field_data', 'fluid_field_id_entry_id_group_order', ['fluid_field_id', 'entry_id', 'group', 'order']);

        return true;
    }

    public function addRelationshipsIndex()
    {
        // Add composite index for exp_relationships (parent_id, field_id)
        // to optimize relationship field queries
        // Composite key on: parent_id, field_id
        ee()->smartforge->add_key('relationships', 'parent_id_field_id', ['parent_id', 'field_id']);

        return true;
    }

    public function addGridFieldIndexes()
    {
        // Add composite index (entry_id, fluid_field_data_id) to all existing Grid field tables
        // This optimizes Grid fields used inside Fluid Fields
        // Composite key on: entry_id, fluid_field_data_id
        $tables = ee()->db->list_tables();

        foreach ($tables as $table) {
            if (preg_match('/^' . preg_quote(ee()->db->dbprefix, '/') . 'channel_grid_field_\d+$/', $table)) {
                $table_name = str_replace(ee()->db->dbprefix, '', $table);

                // Drop existing entry_id index if it exists
                ee()->smartforge->drop_key($table_name, 'entry_id');

                // Add composite index
                ee()->smartforge->add_key($table_name, 'entry_id_fluid_field_data_id', ['entry_id', 'fluid_field_data_id']);
            }
        }

        return true;
    }
}

// EOF
