<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Service\Addon\Installer;

/**
 * Pages Module update class
 */
class Pages_upd extends Installer
{
    public $has_cp_backend = 'y';
    public $has_publish_fields = 'y';

    public function __construct()
    {
        parent::__construct();
    }

    public function tabs()
    {
        $tabs['pages'] = array(
            'pages_template_id' => array(
                'visible' => true,
                'collapse' => false,
                'htmlbuttons' => true,
                'width' => '100%'
            ),

            'pages_uri' => array(
                'visible' => true,
                'collapse' => false,
                'htmlbuttons' => true,
                'width' => '100%'
            )
        );

        return $tabs;
    }

    /**
     * Module Installer
     *
     * @access	public
     * @return	bool
     */
    public function install()
    {
        parent::install();

        if (! ee()->db->field_exists('site_pages', 'exp_sites')) {
            $sql[] = "ALTER TABLE `exp_sites` ADD `site_pages` TEXT NOT NULL";
        }

        $sql[] = "CREATE TABLE `exp_pages_configuration` (
				`configuration_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`site_id` INT( 8 ) UNSIGNED NOT NULL DEFAULT '1',
				`configuration_name` VARCHAR( 60 ) NOT NULL ,
				`configuration_value` VARCHAR( 100 ) NOT NULL
				) DEFAULT CHARACTER SET " . ee()->db->escape_str(ee()->db->char_set) . " COLLATE " . ee()->db->escape_str(ee()->db->dbcollat);

        foreach ($sql as $query) {
            ee()->db->query($query);
        }

        ee()->load->library('layout');
        ee()->layout->add_layout_tabs($this->tabs(), 'pages');

        return true;
    }

    /**
     * Module Uninstaller
     *
     * @access	public
     * @return	bool
     */
    public function uninstall()
    {
        parent::uninstall();
        $sql[] = "DROP TABLE `exp_pages_configuration`";

        foreach ($sql as $query) {
            ee()->db->query($query);
        }

        ee()->load->library('layout');
        ee()->layout->delete_layout_tabs($this->tabs());

        return true;
    }

    /**
     * Module Updater
     *
     * @access	public
     * @return	bool
     */
    public function update($current = '')
    {
        if ($current === $this->version) {
            return false;
        }

        if (version_compare($current, '2.1', '<')) {
            ee()->db->where('module_name', 'Pages');
            ee()->db->update('modules', array('has_publish_fields' => 'y'));
        }

        if (version_compare($current, '2.2', '<')) {
            $this->_do_22_update();
        }

        return true;
    }

    /**
     * This is basically identical to the forum update script.
     *
     * @return void
     */
    private function _do_22_update()
    {
        ee()->load->library('layout');

        $layouts = ee()->db->get('layout_publish');

        if ($layouts->num_rows() === 0) {
            return;
        }

        $layouts = $layouts->result_array();

        $old_pages_fields = array(
            'pages_uri',
            'pages_template_id',
        );

        foreach ($layouts as &$layout) {
            $old_layout = unserialize($layout['field_layout']);

            foreach ($old_layout as $tab => &$fields) {
                $field_keys = array_keys($fields);

                foreach ($field_keys as &$key) {
                    if (in_array($key, $old_pages_fields)) {
                        $key = 'pages__' . $key;
                    }
                }

                $fields = array_combine($field_keys, $fields);
            }

            $layout['field_layout'] = serialize($old_layout);
        }

        ee()->db->update_batch('layout_publish', $layouts, 'layout_id');

        return true;
    }
}
// END CLASS

// EOF
