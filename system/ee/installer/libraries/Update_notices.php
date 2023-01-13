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
 * Update Notices Library
 */
class Update_notices
{
    private $table = 'update_notices';
    private $version;

    /**
     * Clear all notices
     *
     * @return void
     */
    public function clear()
    {
        $this->ensure_table_exists();
        ee()->db->truncate($this->table);
    }

    /**
     * Get All Notices
     *
     * @return	array
     */
    public function get()
    {
        $this->ensure_table_exists();

        return ee()->db->get($this->table)->result();
    }

    /**
     * Set version we're working on.
     *
     * @param String $version Version string
     * @return void
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Add a header
     *
     * @param String $message Content of the header
     * @return void
     */
    public function header($message)
    {
        $this->save($message, true);
    }

    /**
     * Add a notice item
     *
     * @param String $message Content of the notice
     * @return void
     */
    public function item($message)
    {
        $this->save($message);
    }

    /**
     * Store it
     *
     * @param String $message Content of the notice
     * @param Bool   $is_header Is a header?
     * @return void
     */
    private function save($message, $is_header = false)
    {
        $this->ensure_table_exists();

        $data = array(
            'version' => $this->version,
            'message' => $message,
            'is_header' => (int) $is_header
        );

        ee()->db->insert($this->table, $data);
    }

    /**
     * Make sure the table exists
     *
     * @return void
     */
    private function ensure_table_exists()
    {
        // Clear the table cache
        ee()->db->data_cache = array();

        if (ee()->db->table_exists($this->table)) {
            return;
        }

        ee()->load->dbforge();
        ee()->load->library('smartforge');

        ee()->dbforge->add_field(
            array(
                'notice_id' => array(
                    'type' => 'int',
                    'constraint' => 10,
                    'null' => false,
                    'unsigned' => true,
                    'auto_increment' => true
                ),
                'message' => array(
                    'type' => 'text'
                ),
                'version' => array(
                    'type' => 'varchar',
                    'constraint' => 20,
                    'null' => false
                ),
                'is_header' => array(
                    'type' => 'tinyint',
                    'constaint' => 1,
                    'null' => false,
                    'default' => 0
                )
            )
        );

        ee()->dbforge->add_key('notice_id', true);
        ee()->smartforge->create_table($this->table);
    }
}

// EOF
