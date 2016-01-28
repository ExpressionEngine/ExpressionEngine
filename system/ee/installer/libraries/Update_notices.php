<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.9
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Survey Library
 *
 * @package		ExpressionEngine
 * @subpackage	Installer
 * @category	Update Notices
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Update_notices {

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
		$this->save($message, TRUE);
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
	private function save($message, $is_header = FALSE)
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

		if (ee()->db->table_exists($this->table))
		{
			return;
		}

		ee()->load->dbforge();
		ee()->load->library('smartforge');

		ee()->dbforge->add_field(
			array(
				'notice_id' => array(
					'type'			 => 'int',
					'constraint'     => 10,
					'null'			 => FALSE,
					'unsigned'		 => TRUE,
					'auto_increment' => TRUE
				),
				'message' => array(
					'type'			=> 'text'
				),
				'version' => array(
					'type'			=> 'varchar',
					'constraint'    => 20,
					'null'			=> FALSE
				),
				'is_header' => array(
					'type'			=> 'tinyint',
					'constaint'		=> 1,
					'null'			=> FALSE,
					'default'		=> 0
				)
			)
		);

		ee()->dbforge->add_key('notice_id', TRUE);
		ee()->smartforge->create_table($this->table);
	}
}

// EOF
