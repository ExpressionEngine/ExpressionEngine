<?php

require_once PATH_ADDONS . 'structure/addon.setup.php';
require_once PATH_ADDONS . 'structure/sql.structure.php';
require_once PATH_ADDONS . 'structure/helper.php';

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
class Structure_upd
{
    public $has_cp_backend = 'y';
    public $has_publish_fields = 'n';
    public $ext_settings = 'n';

    public $sql;
    public $version;
    public $page_title;
    public $nset;

    public function __construct($switch = true)
    {
        $this->version = STRUCTURE_VERSION;

        $this->sql = new Sql_structure();
        ee()->load->dbforge();
    }

    public function tabs()
    {
        return array('structure' => array(
            'parent_id' => array(
                'visible'       => true,
                'collapse'      => false,
                'htmlbuttons'   => 'true',
                'width'         => '100%'
            ),
            'uri' => array(
                'visible'       => true,
                'collapse'      => false,
                'htmlbuttons'   => 'true',
                'width'         => '100%'
            ),
            'template_id' => array(
                'visible'       => true,
                'collapse'      => false,
                'htmlbuttons'   => 'true',
                'width'         => '100%'
            ),
            'hidden' => array(
                'visible'       => true,
                'collapse'      => false,
                'htmlbuttons'   => 'true',
                'width'         => '100%'
            ),
            'listing_channel' => array(
                'visible'       => true,
                'collapse'      => false,
                'htmlbuttons'   => 'true',
                'width'         => '100%'
            )
        )
        );
    }

    /**
     * Install function sets up tables and populates them some data as well.
     *
     * @method install
     * @return TRUE on success
     */
    public function install()
    {
        $pages_check = ee()->db->query("SELECT * FROM exp_modules WHERE module_name = 'Pages'");
        if ($pages_check->num_rows > 0) {
            show_error('Please Uninstall the "Pages" module before installing Structure. You\'ll be happy you did.', 500, 'Ruh Roh!');

            return false;
        }

        ee()->load->dbforge();

        // Module data
        $data = array(
            'module_name' => 'Structure',
            'module_version' => $this->version,
            'has_cp_backend' => 'y',
            'has_publish_fields' => 'y'
        );

        ee()->db->insert('modules', $data);

        // Insert actions
        $data = array(
            'class' => 'Structure',
            'method' => 'ajax_move_set_data'
        );

        ee()->db->insert('actions', $data);

        $results = ee()->db->query("SELECT * FROM exp_sites");

        if (! in_array('site_pages', $results->result_array())) {
            // Make sure the site_pages column doesn't exist already.
            if (ee()->db->field_exists('site_pages', 'sites') === false) {
                $fields = array('site_pages' => array('type' => 'longtext'));

                ee()->dbforge->add_column('sites', $fields);
            }
        }

        // create tables and populate listings
        $this->create_table_structure_settings();
        $this->create_table_structure();
        $this->create_table_structure_channels();
        $this->create_table_structure_listings();
        $this->create_table_structure_members();
        $this->create_table_structure_nav_history();

        // populate listings
        $this->populate_listings();

        // Insert the root node
        $data = array('site_id' => '0', 'entry_id' => '0', 'parent_id' => '0', 'channel_id' => '0', 'listing_cid' => '0', 'lft' => '1', 'rgt' => '2', 'dead' => 'root', 'updated' => date('Y-m-d H:i:s'));
        $sql = ee()->db->insert_string('structure', $data);
        ee()->db->query($sql);

        // Insert the action id
        $action_id = ee()->cp->fetch_action_id('Structure', 'ajax_move_set_data');
        $data = array('site_id' => 0, 'var' => 'action_ajax_move', 'var_value' => $action_id);
        $sql = ee()->db->insert_string('structure_settings', $data);
        ee()->db->query($sql);

        // Insert the module id
        $results = ee()->db->query("SELECT * FROM exp_modules WHERE module_name = 'Structure'");
        $module_id = $results->row('module_id');

        $sql = array();
        $sql[] = "INSERT IGNORE INTO exp_structure_settings " .
                    "(site_id, var, var_value) VALUES " .
                    "(0, 'module_id', " . $module_id . ")";

        $sql[] = "INSERT IGNORE INTO exp_structure_settings " .
                    "(site_id, var, var_value) VALUES " .
                    "(1, 'show_picker', 'y')";

        $sql[] = "INSERT IGNORE INTO exp_structure_settings " .
                    "(site_id, var, var_value) VALUES " .
                    "(1, 'show_view_page', 'y')";

        $sql[] = "INSERT IGNORE INTO exp_structure_settings " .
                    "(site_id, var, var_value) VALUES " .
                    "(1, 'show_global_add_page', 'y')";

        $sql[] = "INSERT IGNORE INTO exp_structure_settings " .
                    "(site_id, var, var_value) VALUES " .
                    "(1, 'hide_hidden_templates', 'y')";

        $sql[] = "INSERT IGNORE INTO exp_structure_settings " .
                    "(site_id, var, var_value) VALUES " .
                    "(1, 'redirect_on_login', 'n')";

        $sql[] = "INSERT IGNORE INTO exp_structure_settings " .
                    "(site_id, var, var_value) VALUES " .
                    "(1, 'redirect_on_publish', 'n')";

        $sql[] = "INSERT IGNORE INTO exp_structure_settings " .
                    "(site_id, var, var_value) VALUES " .
                    "(1, 'add_trailing_slash', 'y')";

        foreach ($sql as $query) {
            ee()->db->query($query);
        }

        // made sure site_pages_type is set to LONG TEXT
        $this->confirm_site_pages_type();

        ee()->load->library('layout');
        ee()->layout->add_layout_tabs($this->tabs(), 'structure');

        return true;
    }

    /**
     * Uninstalles the module
     *
     * @method uninstall
     * @return True upon completion
     */
    public function uninstall()
    {
        ee()->load->dbforge();
        ee()->db->select('module_id');
        $query = ee()->db->get_where('modules', array('module_name' => 'Structure'));

        ee()->db->where('module_name', 'Structure');
        ee()->db->delete('modules');

        ee()->db->where('class', 'ajax_move_set_data');
        ee()->db->delete('actions');

        ee()->db->where('class', 'Structure');
        ee()->db->delete('actions');

        ee()->db->where('class', 'Structure_mcp');
        ee()->db->delete('actions');

        // ee()->db->query("ALTER TABLE exp_sites DROP site_pages");

        ee()->dbforge->drop_table('structure_members');
        ee()->dbforge->drop_table('structure_settings');
        ee()->dbforge->drop_table('structure_channels');
        ee()->dbforge->drop_table('structure_listings');
        ee()->dbforge->drop_table('structure_nav_history');
        ee()->dbforge->drop_table('structure');

        ee()->load->library('layout');
        ee()->layout->delete_layout_tabs($this->tabs());

        return true;
    }

    /**
     * This function is named a little weird.. but it's actually creating the table named "Structure"
     *
     * @method create_table_structure
     * @return none
     */
    private function create_table_structure()
    {
        // Create Structure Table
        if (! ee()->db->table_exists('structure')) {
            $fields = array(
                'site_id'             => array('type' => 'int', 'constraint' => '4',  'unsigned' => true, 'null' => false, 'default' => '1'),
                'entry_id'            => array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'null' => false, 'default' => '0'),
                'parent_id'           => array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'null' => false, 'default' => '0'),
                'channel_id'          => array('type' => 'int', 'constraint' => '6',  'unsigned' => true, 'null' => false, 'default' => '0'),
                'listing_cid'         => array('type' => 'int', 'constraint' => '6',  'unsigned' => true, 'null' => false, 'default' => '0'),
                'lft'                 => array('type' => 'smallint', 'constraint' => '5',   'unsigned' => true, 'null' => false, 'default' => '0'),
                'rgt'                 => array('type' => 'smallint', 'constraint' => '5',   'unsigned' => true, 'null' => false, 'default' => '0'),
                'dead'                => array('type' => 'varchar',  'constraint' => '100', 'null' => false),
                'hidden'              => array('type' => 'char', 'null' => false, 'default' => 'n'),
                'structure_url_title' => array('type' => 'varchar', 'constraint' => '200', 'null' => true),
                'template_id'         => array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'null' => false, 'default' => '0'),
                'updated'             => array('type' => 'datetime')
            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('entry_id', true);
            ee()->dbforge->create_table('structure');
        }
    }

    /**
     * This function is used to create the structure_settings table
     *
     * @method create_table_structure_settings
     * @return none
     */
    private function create_table_structure_settings()
    {
        // Create Structure Settings Table
        if (! ee()->db->table_exists('structure_settings')) {
            $fields = array(
                'id'        =>  array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'null' => false, 'auto_increment' => true),
                'site_id'   =>  array('type' => 'int', 'constraint' => '8', 'unsigned' => true, 'null' => false, 'default' => '1'),
                'var'       =>  array('type' => 'varchar', 'constraint' => '60', 'null' => false),
                'var_value' =>  array('type' => 'varchar', 'constraint' => '100', 'null' => false)
            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('id', true);
            ee()->dbforge->create_table('structure_settings');
        }
    }

    /**
     * Function to create the structure_members table
     *
     * @method create_table_structure_members
     * @return none
     */
    private function create_table_structure_members()
    {
        // Create structure members table
        if (! ee()->db->table_exists('structure_members')) {
            $fields = array(
                'member_id'     =>  array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'null' => false, 'default' => '0'),
                'site_id'       =>  array('type' => 'int', 'constraint' => '4',  'unsigned' => true, 'null' => false, 'default' => '1'),
                'nav_state'     =>  array('type' => 'text', 'null' => false)
            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key(array('site_id', 'member_id'), true);
            ee()->dbforge->create_table('structure_members');
        }
    }

    /**
     * Function to create the structure channels table
     *
     * @method create_table_structure_channels
     * @return none
     */
    private function create_table_structure_channels()
    {
        // Create Structure Channels Table
        if (! ee()->db->table_exists('structure_channels')) {
            $fields = array(
                'site_id'               => array('type' => 'smallint',  'unsigned' => true, 'null' => false),
                'channel_id'            => array('type' => 'mediumint', 'unsigned' => true, 'null' => false),
                'template_id'           => array('type' => 'int', 'unsigned' => true, 'null' => false),
                'type'                  => array('type' => 'enum', 'constraint' => '"page", "listing", "asset", "unmanaged"', 'null' => false, 'default' => 'unmanaged'),
                'split_assets'          => array('type' => 'enum', 'constraint' => '"y", "n"', 'null' => false, 'default' => 'n'),
                'show_in_page_selector' => array('type' => 'char', 'null' => false, 'default' => 'y')
            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key(array('site_id', 'channel_id'), true);
            ee()->dbforge->create_table('structure_channels');
        }
    }

    /**
     * Function to create the navigation history table
     *
     * @method create_table_structure_nav_history
     * @return none
     */
    private function create_table_structure_nav_history()
    {
        // Create Structure navigation history table
        if (! ee()->db->table_exists('structure_nav_history')) {
            $fields = array(
                'id'                => array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'null' => false, 'auto_increment' => true),
                'site_id'           => array('type' => 'smallint',  'unsigned' => true, 'null' => false),
                'site_pages'        => array('type' => 'LONGTEXT'),
                'structure'         => array('type' => 'LONGTEXT'),
                'note'              => array('type' => 'TEXT'),
                'structure_version' => array('type' => 'VARCHAR', 'constraint' => '11', 'null' => false),
                'date'              => array('type' => 'datetime',                      'null' => false),
                'current'           => array('type' => 'smallint',  'unsigned' => true, 'null' => false, 'default' => 0),
                'restored_date'     => array('type' => 'datetime')

            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key(array('id', 'site_id'), true);
            ee()->dbforge->create_table('structure_nav_history');
        }
    }

    /**
     * Function creats the Structure Listings table
     *
     * @method create_table_structure_listings
     * @return [type] [description]
     */
    private function create_table_structure_listings()
    {

        // Create Structure Listing Table
        if (! ee()->db->table_exists('structure_listings')) {
            $site_id = ee()->config->item('site_id');
            $fields = array(
                'site_id'       =>  array('type' => 'int', 'constraint' => '4',  'unsigned' => true, 'null' => false, 'default' => $site_id),
                'entry_id'      =>  array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'null' => false, 'default' => '0'),
                'parent_id'     =>  array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'null' => false, 'default' => '0'),
                'channel_id'    =>  array('type' => 'int', 'constraint' => '6',  'unsigned' => true, 'null' => false, 'default' => '0'),
                'template_id'   =>  array('type' => 'int', 'constraint' => '6',  'unsigned' => true, 'null' => false, 'default' => '0'),
                'uri'           =>  array('type' => 'varchar', 'constraint' => '200', 'null' => false)
            );

            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('entry_id', true);
            ee()->dbforge->create_table('structure_listings');
        }
    }

    /**
     * Function makes sure the site_pages array column is large enough to support large sites
     *
     * @method confirm_site_pages_type
     * @return nothing
     */
    private function confirm_site_pages_type()
    {
        // Update the `exp_sites` table's `site_pages` to `LONGTEXT` so it doesn't truncate our Structure tree.
        $fields = ee()->db->field_data('sites');

        foreach ($fields as $field_data) {
            if ($field_data->name == 'site_pages') {
                if ($field_data->type == 'text') {
                    $updated_data = array('site_pages' => array('name' => 'site_pages', 'type' => 'LONGTEXT', 'null' => false));
                    ee()->dbforge->modify_column('sites', $updated_data);
                }
            }
        }
    }

    /**
     * Function to ensure that in EE4, any Structure managed channels have a preview url.
     *
     * @method confirm_preview_url
     * @return nothing
     */
    private function confirm_preview_url()
    {
        if (ee()->db->field_exists('preview_url', 'channels')) {
            ee()->db->query("UPDATE exp_channels c
                LEFT JOIN exp_structure_channels s ON s.channel_id=c.channel_id
                SET c.preview_url='Managed by Structure - Changes here will not have any effect'
                WHERE s.type!='unmanaged'
                AND s.type!='asset'
                AND (c.preview_url='' OR c.preview_url IS NULL)");
        }
    }

    /**
     * Update funciton updates the module
     *
     * @method update
     * @param  string $current current Structure version
     * @return BOOL true is success false is fail
     */
    public function update($current = '')
    {
        if ($current == '' || $current == $this->version) {
            return false;
        }

        if (version_compare($current, '3.0', "<") && ! ee()->db->table_exists('structure_members') && ! ee()->db->table_exists('structure_listings')) {
            $this->upgrade_to_ee2();
        }

        if (version_compare($current, '2.2.2', "<")) {
            $data = array(
                array(
                    'site_id' => 1,
                    'var' => 'redirect_on_login',
                    'var_value' => 'n'
                ),
                array(
                    'site_id' => 1,
                    'var' => 'redirect_on_publish',
                    'var_value' => 'n'
                )
            );

            ee()->db->insert_batch('structure_settings', $data);
        }

        if (version_compare($current, '3.0.3', "<") && ! ee()->db->field_exists('split_assets', 'structure_channels')) {
            $sql = "ALTER TABLE `exp_structure_channels` ADD `split_assets` enum('y','n') NOT NULL default 'n'";
            ee()->db->query($sql);
        }

        if (version_compare($current, '3.0.4', "<")) {
            $sql = array(
                "ALTER TABLE `exp_structure` ADD INDEX `lft` (`lft`)",
                "ALTER TABLE `exp_structure` ADD INDEX `rgt` (`rgt`)"
            );

            foreach ($sql as $query) {
                ee()->db->query($query);
            }
        }

        if (version_compare($current, '3.1', "<")) {
            $this->create_table_structure_members();

            if (! ee()->db->field_exists('hidden', 'structure')) {
                ee()->db->query("ALTER TABLE `exp_structure` ADD `hidden` char NOT NULL default 'n'");
            }

            if (! ee()->db->field_exists('show_in_page_selector', 'structure_channels')) {
                ee()->db->query("ALTER TABLE `exp_structure_channels` ADD `show_in_page_selector` char NOT NULL default 'y'");
            }

            ee()->load->library('layout');
            ee()->layout->delete_layout_tabs($this->tabs()); // Blow out the old tab
            ee()->layout->add_layout_tabs($this->tabs(), 'structure'); // Update to new tab for "Hide From Nav field"
        }

        if (version_compare($current, '3.2.2', "<")) {
            ee()->db->query("ALTER TABLE exp_structure_members drop primary key");
            ee()->db->query("ALTER TABLE exp_structure_members ADD primary key (site_id,member_id)");
        }

        // Strip trailing slashes on module update
        if (version_compare($current, '3.3', "<")) {
            $site_pages = $this->sql->get_site_pages();
            $uris = $site_pages['uris'];

            foreach ($uris as $entry_id => $uri) {
                if ($uri != "/") {
                    $site_pages['uris'][$entry_id] = rtrim($uri, '/');
                }
            }

            $this->sql->set_site_pages(ee()->config->item('site_id'), $site_pages);
        }

        if (version_compare($current, '3.3.1', "<")) {
            $data = array(
                'site_id' => 1,
                'var' => 'add_trailing_slash',
                'var_value' => 'y'
            );

            ee()->db->insert('structure_settings', $data);
        }

        // if were upgrading to Structure 4.0 we need to add the structure nav history
        if (version_compare($current, '4.0.0-a.1', "<")) {
            if (! ee()->db->table_exists('structure_nav_history')) {
                // Create Structure navigation history table
                $fields = array(
                    'id'                => array('type' => 'int', 'constraint' => '10', 'unsigned' => true, 'null' => false, 'auto_increment' => true),
                    'site_id'           => array('type' => 'smallint',  'unsigned' => true, 'null' => false),
                    'site_pages'        => array('type' => 'LONGTEXT'),
                    'structure'         => array('type' => 'LONGTEXT'),
                    'note'              => array('type' => 'TEXT'),
                    'structure_version' => array('type' => 'VARCHAR', 'constraint' => '11', 'null' => false),
                    'date'              => array('type' => 'datetime',                      'null' => false),
                    'current'           => array('type' => 'smallint',  'unsigned' => true, 'null' => false, 'default' => 0)
                );

                ee()->dbforge->add_field($fields);
                ee()->dbforge->add_key(array('id', 'site_id'), true);
                ee()->dbforge->create_table('structure_nav_history');
            }
        }

        if (version_compare($current, '4.0.0-a.4', "<")) {
            $sql = array(
                "ALTER TABLE `exp_structure_nav_history` ADD `restored_date` datetime"
            );

            foreach ($sql as $query) {
                ee()->db->query($query);
            }
        }

        if (version_compare($current, '4.0.0-b.3', "<")) {
            $sql = array(
                "ALTER TABLE exp_structure_nav_history MODIFY restored_date datetime"
            );

            foreach ($sql as $query) {
                ee()->db->query($query);
            }
        }

        if (version_compare($current, '4.1.0', "<")) {
            $addedColumn = false;

            // Update the `exp_sites` table's `site_pages` to `LONGTEXT` so it doesn't truncate our Structure tree.
            $this->confirm_site_pages_type();

            // Add a column for the `structure_url_title` for data integrity purposes.
            if (!ee()->db->field_exists('structure_url_title', 'structure')) {
                $addedColumn = true;

                $fields = array(
                    'structure_url_title' => array(
                        'type'       => 'varchar',
                        'constraint' => '200',
                        'default'    => ''
                    )
                );

                ee()->dbforge->add_column('structure', $fields);
            }

            // Add a column for the `template_id` for data integrity purposes.
            if (!ee()->db->field_exists('template_id', 'structure')) {
                $addedColumn = true;

                $fields = array(
                    'template_id' => array(
                        'type'       => 'int',
                        'constraint' => 10,
                        'unsigned'   => true,
                        'default'    => 0
                    )
                );

                ee()->dbforge->add_column('structure', $fields);
            }

            if ($addedColumn) {
                // Now that we have our integrity columns, pull any existing data we have into them.
                $this->sql->update_integrity_data();
            }
        }

        if (version_compare($current, '4.1.7', "<")) {
            // Add a column for the `updated` for data integrity purposes.
            // We only need this on the root node but it's fine for it to exist on the other rows.
            if (!ee()->db->field_exists('updated', 'structure')) {
                $fields = array(
                    'updated' => array(
                        'type'       => 'datetime'
                    )
                );

                ee()->dbforge->add_column('structure', $fields);

                // Update the timestamp on the root node so we can ensure another reorder with older data doesn't corrupt the tree.
                $updated_time = date('Y-m-d H:i:s');
                ee()->db->where('dead', 'root')->update('exp_structure', array('updated' => $updated_time));
            }
        }

        if (version_compare($current, '4.1.12', "<")) {
            // Update the timestamp on the root node so we can ensure another reorder with older data doesn't corrupt the tree.
            $updated_time = date('Y-m-d H:i:s');
            ee()->db->where('dead', 'root')->update('exp_structure', array('updated' => $updated_time));
        }

        // THIS IS NOT AN ERRONEOUS DUPLICATE - There was a bug installing new versions so if someone installed 4.1.12, Structure
        // didn't set the updated timestamp properly so we have to do the same update for 4.1.13 as we did for 4.1.12.
        if (version_compare($current, '4.1.13', "<")) {
            // Update the timestamp on the root node so we can ensure another reorder with older data doesn't corrupt the tree.
            $updated_time = date('Y-m-d H:i:s');
            ee()->db->where('dead', 'root')->update('exp_structure', array('updated' => $updated_time));
        }

        if (version_compare($current, '4.2.1', "<")) {
            // there was an issue where site_pages type wasn't changed to be LONGTEXT
            $this->confirm_site_pages_type();
        }

        if (version_compare($current, '4.3.11', "<")) {
            // Make sure that any Structure Managed channels have a Preview URL.
            $this->confirm_preview_url();
        }

        // Apparently there are some sites where the hook never got updated... Lets fix that
        if (version_compare($current, '4.3.12', "<")) {
            ee()->db->where('class', 'Structure_ext')
                ->where('method', 'channel_module_create_pagination')
                ->update('exp_extensions', array(
                    'method' => 'pagination_create',
                    'hook' => 'pagination_create',
                ));
        }

        if (version_compare($current, '4.4.4', "<")) {
            $structure_updated_data = array('structure_url_title' => array('name' => 'structure_url_title', 'type' => 'varchar', 'constraint' => '200', 'default' => ''));
            ee()->dbforge->modify_column('structure', $structure_updated_data);

            $structure_listings_updated_data = array('uri' => array('name' => 'uri', 'type' => 'varchar', 'constraint' => '200', 'default' => ''));
            ee()->dbforge->modify_column('structure_listings', $structure_listings_updated_data);
        }

        // A bug existed that messed up peoples channel_id column in the exp_structure table where if they moved channels it would break lots of stuff because exp_structure never updated is fixed but only on entry saves: so the update clean out anything that was previously wrong
        if (version_compare($current, '5.1.2', "<")) {
            add_structure_nav_revision(false, 'Pre 5.1.2 update structure nav');
            $sql = "UPDATE exp_structure s, exp_channel_titles t SET s.channel_id = t.channel_id WHERE s.entry_id = t.entry_id;";
            ee()->db->query($sql);
        }

        // Add a history revision every time Structure is upgraded.
        add_structure_nav_revision(false, 'Upgraded Structure to ' . $this->version);
    }

    public function populate_listings()
    {
        require_once('libraries/nestedset/structure_nestedset.php');
        require_once('libraries/nestedset/structure_nestedset_adapter_ee.php');

        $adapter = new Structure_Nestedset_Adapter_Ee('exp_structure', 'lft', 'rgt', 'entry_id');
        $this->nset = new Structure_Nestedset($adapter);

        $site_pages = $this->sql->get_site_pages();

        foreach ($site_pages['uris'] as $entry_id => $uri) {
            $slug = explode('/', $uri);

            // Knock the first and last elements off the array, they're blank.
            array_pop($slug);
            array_shift($slug);

            // Get the last segment, the Structure URI for the page.
            $slug = end($slug);

            // See if its a node or listing item
            $node = $this->nset->getNode($entry_id);

            // If we have an entry id but no node, we have listing entry
            if ($entry_id && ! $node) {
                $site_id = ee()->config->item('site_id');

                $pid = $this->sql->get_pid_for_listing_entry($entry_id);

                // Get the channel ID for the listing
                $results = ee()->db->select('channel_id')->from('channel_titles')->where('entry_id', $entry_id)->get();
                $channel_id = $results->row('channel_id');

                // Get the template ID for the listing
                if (is_array($site_pages['templates'][$entry_id])) {
                    $template_id = $site_pages['templates'][$entry_id][0];
                } else {
                    $template_id = $site_pages['templates'][$entry_id];
                }

                // Insert the root node
                $data = array(
                    'site_id' => $site_id,
                    'entry_id' => $entry_id,
                    'parent_id' => $pid,
                    'channel_id' => $channel_id,
                    'template_id' => $template_id,
                    'uri' => $slug
                );

                // If there was a previous navigation software installed, sometimes we get an empty array(){}
                // instead of an actual number (Because none exists) and so it fails to insert
                foreach ($data as $value) {
                    if (is_array($value) || is_null($value)) {
                        continue 2;
                    } // This will skip the current iteration of the foreach loop, so we dont try to insert
                }

                $sql = ee()->db->insert_string('structure_listings', $data);

                ee()->db->query($sql);
            }
        }
    }

    public function upgrade_to_ee2()
    {

        /*
        |--------------------------------------------------------------------------
        | Adios, sayonara, and farewell to "Weblogs"!
        |--------------------------------------------------------------------------
        |
        | We need to remap any references to weblogs or "wid"s to their proper
        | channel equivelants.
        |
        */

        if (! ee()->db->field_exists('channel_id', 'structure') && ! ee()->db->field_exists('listing_cid', 'structure')) {
            ee()->dbforge->modify_column('structure', array(
                'weblog_id' => array('name' => 'channel_id', 'type' => 'INT', 'constraint' => 6),
                'listing_wid' => array('name' => 'listing_cid', 'type' => 'INT', 'constraint' => 6)
            ));
        }

        /*
        |--------------------------------------------------------------------------
        | Table: Structure Channels
        |--------------------------------------------------------------------------
        |
        | EE1's table structure was heinous. We need to juggle data around for
        | a bit and reformat it before sticking it in the database.
        |
        */

        // Grab the EE1 settings and empty them out.
        $ee1_settings = ee()->db->get('structure_settings')->result_array();
        ee()->db->empty_table('structure_settings');

        // Prep the new table
        $this->create_table_structure_channels();
        $structure_channels = array();

        // Convert the old data format
        foreach ($ee1_settings as $setting) {
            if ($setting['var'] == "action_ajax_move" || $setting['var'] == "module_id" || $setting['var'] == "picker" || $setting['var'] == "url") {
                continue;
            }

            if (strpos($setting['var'], 'type_weblog_') !== false) {
                $channel_id = str_replace('type_weblog_', '', $setting['var']);
                $structure_channels[$channel_id]['channel_id'] = $channel_id;
                $structure_channels[$channel_id]['type'] = $this->resolve_channel_type($setting['var_value']);
            } elseif (strpos($setting['var'], 'template_weblog_') !== false) {
                $channel_id = str_replace('template_weblog_', '', $setting['var']);
                $structure_channels[$channel_id]['channel_id'] = $channel_id;
                $structure_channels[$channel_id]['template_id'] = $setting['var_value'];
            } else {
                // How...?
            }

            $structure_channels[$channel_id]['site_id'] = $setting['site_id'];
            $structure_channels[$channel_id]['channel_id'] = $channel_id;
            $structure_channels[$channel_id]['split_assets'] = 'n';
            $structure_channels[$channel_id]['show_in_page_selector'] = 'y';

            // @todo listing channel check
        }

        // Populate the Structure Channels table
        foreach ($structure_channels as $data) {
            ee()->db->insert('structure_channels', $data);
        }

        /*
        |--------------------------------------------------------------------------
        | Table: Structure Listings
        |--------------------------------------------------------------------------
        |
        | Structure for EE1 just jammed everything into two tables without respect
        | or a care in the world . Let's try to be better citizens, shall we?
        |
        */

        $this->create_table_structure_listings();

        //get all structure entries that have listings
        $structure_entries = ee()->db->from('structure as s')
            ->join('structure_channels as sc', 'sc.channel_id = s.listing_cid')
            ->where('listing_cid !=', 0)
            ->get()
            ->result_array();

        $site_pages = ee()->config->item('site_pages');

        foreach ($structure_entries as $listing_entry) {
            $data = array(
                'site_id'       => $listing_entry['site_id'],
                'parent_id'     => $listing_entry['channel_id'],
                'channel_id'    => $listing_entry['listing_cid'],
                'template_id'   => $listing_entry['template_id'],
            );

            //find all the entries for this listing
            $channel_entries = ee()->db->from('channel_titles')
                ->where('channel_id', $listing_entry['listing_cid'])
                ->get()
                ->result_array();

            foreach ($channel_entries as $channel_entry) {
                //if this entry is in site_pages get it uri and add it to the structure listings table
                if (isset($site_pages[$channel_entry['site_id']]['uris'][$channel_entry['entry_id']])) {
                    $listing_entry = array_merge($data, array(
                        'entry_id'  =>  $channel_entry['entry_id'],
                        'uri'       =>  Structure_Helper::get_slug($site_pages[$channel_entry['site_id']]['uris'][$channel_entry['entry_id']])
                    ));

                    ee()->db->insert('structure_listings', $listing_entry);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Extension
        |--------------------------------------------------------------------------
        |
        | We don't store any data in the extension, so it's much simpler to just
        | blow it out and reinstall it ourselves.
        |
        */

        ee()->db->delete('extensions', array('class' => 'Structure_ext'));

        require_once PATH_ADDONS . 'structure/ext.structure.php';
        $ext = new Structure_ext();
        $ext->activate_extension();

        /*
        |--------------------------------------------------------------------------
        | Publish Tab
        |--------------------------------------------------------------------------
        |
        | We need to tell ExpressionEngine that we have a pile of useful fields to
        | add into the Publish Tab.
        |
        */

        ee()->load->library('layout');
        ee()->layout->add_layout_tabs($this->tabs(), 'structure');

        ee()->db->where('module_name', "Structure");
        ee()->db->update('modules', array('has_publish_fields' => 'y'));
    }

    public function resolve_channel_type($type)
    {
        if ($type == "structure") {
            return "page";
        } elseif ($type == "asset") {
            return "asset";
        } else {
            return "unmanaged";
        }
    }
}
/* End of file upd.structure.php */
