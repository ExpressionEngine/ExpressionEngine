<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license     http://expressionengine.com/user_guide/license.html
 * @link        http://expressionengine.com
 * @since       Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package     ExpressionEngine
 * @subpackage  Core
 * @category    Core
 * @author      ExpressionEngine Dev Team
 * @link        http://expressionengine.com
 */
class Updater {

	private $EE;
	var $version_suffix = '';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
	}

	// --------------------------------------------------------------------
	
	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		$this->_create_remember_me();
		$this->_fix_emoticon_config();
				
		return TRUE;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Adds the new remember_me table and drops the remember_me column
	 * from the member table
	 *
	 * @return 	void
	 */
	private function _create_remember_me()
	{
		$this->EE->load->dbforge();
		
		// Hotness coming up, drop it!
		$this->EE->dbforge->drop_column('members', 'remember_me');
		
		
		// This has the same structure as sessions, except for the
		// primary key and "last_activity" fields. Also added site_id back
		// for this table so that we can count active remember me's per
		// member per site
		$this->dbforge->add_field(array(
			'remember_me_id'	=> array(
				'type'				=> 'VARCHAR',
				'constraint'		=> 40,
				'default'			=> '0'
			),
			'member_id'			=> array(
				'type'				=> 'INT',
				'constraint'		=> 10,
				'default'			=> '0'
			),
			'ip_address'		=> array(
				'type'				=> 'VARCHAR',
				'constraint'		=> 16,
				'default'			=> '0'
			),
			'user_agent'		=> array(
				'type'				=> 'VARCHAR',
				'constraint'		=> 120,
				'default'			=> ''
			),
			'admin_sess'		=> array(
				'type'				=> 'TINYINT',
				'constraint'		=> 1,
				'default'			=> '0'
			),
			'site_id'			=> array(
				'type'				=> 'INT',
				'constraint'		=> 4,
				'default'			=> '1'
			),
			'created_at'		=> array(
				'type'				=> 'INT',
				'constraint'		=> 10,
				'default'			=> '0'
			)
		));
		
		$this->EE->dbforge->add_key('remember_me_id', TRUE);
		$this->EE->dbforge->add_key('member_id');
		
		$this->EE->dbforge->create_table('remember_me');
	}

}   
/* END CLASS */

/* End of file ud_231.php */
/* Location: ./system/expressionengine/installer/updates/ud_231.php */