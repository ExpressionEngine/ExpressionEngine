<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use ExpressionEngine\Service\Addon\Installer;

class Mailinglist_upd extends Installer {

    public $has_cp_backend = 'y';

    public $actions = [
	        [
	            'method' => 'insert_new_email'
	        ],
	        [
	            'method' => 'authorize_email'
	        ],	
	        [
	            'method' => 'unsubscribe'
					]					
	    ];

	public function __construct()
	{
	    parent::__construct();
	}


	function install()
	{

        parent::install();

        ee()->load->dbforge();		
		
		ee()->dbforge->add_field(array(
			'list_id' => array(
				'type'           => 'int',
				'constraint'     => 7,
				'unsigned'       => TRUE,
				'null'           => FALSE,
				'auto_increment' => TRUE
			),
			'list_name' => array(
				'type'       => 'varchar',
				'constraint' => '40',
				'null'       => FALSE
			),
			'list_title' => array(
				'type'       => 'varchar',
				'constraint' => '100',
				'null'       => FALSE
			),
			'list_template' => array(
				'type' => 'text',
				'null' => FALSE
			)
		));
		ee()->dbforge->add_key('list_id', TRUE);
		ee()->dbforge->add_key('list_name');
		ee()->dbforge->create_table('mailing_lists', TRUE);

		ee()->dbforge->add_field(array(
			'user_id' => array(
				'type'           => 'int',
				'constraint'     => 10,
				'unsigned'       => TRUE,
				'null'           => FALSE,
				'auto_increment' => TRUE
			),
			'list_id' => array(
				'type'       => 'int',
				'constraint' => 7,
				'unsigned'   => TRUE,
				'null'       => FALSE,
			),
			'authcode' => array(
				'type'       => 'varchar',
				'constraint' => '10',
				'null'       => FALSE
			),
			'email' => array(
				'type'       => 'varchar',
				'constraint' => '75',
				'null'       => FALSE
			),
			'ip_address' => array(
				'type'       => 'varchar',
				'constraint' => '45',
				'null'       => FALSE
			),
		));
		ee()->dbforge->add_key('user_id', TRUE);
		ee()->dbforge->add_key('list_id');
		ee()->dbforge->create_table('mailing_list', TRUE);

		ee()->dbforge->add_field(array(
			'queue_id' => array(
				'type'           => 'int',
				'constraint'     => 10,
				'unsigned'       => TRUE,
				'null'           => FALSE,
				'auto_increment' => TRUE
			),
			'email' => array(
				'type'       => 'varchar',
				'constraint' => '75',
				'null'       => FALSE
			),
			'list_id' => array(
				'type'       => 'int',
				'constraint' => 7,
				'unsigned'   => TRUE,
				'null'       => FALSE,
				'default'    => 0
			),
			'authcode' => array(
				'type'       => 'varchar',
				'constraint' => '10',
				'null'       => FALSE
			),
			'date' => array(
				'type'       => 'int',
				'constraint' => '10',
				'null'       => FALSE
			),
		));
		ee()->dbforge->add_key('queue_id', TRUE);
		ee()->dbforge->create_table('mailing_list_queue', TRUE);

		if ( ! function_exists('mailinglist_template'))
		{
			if ( ! file_exists(SYSPATH . 'ee/language/english/email_data.php'))
			{
				return FALSE;
			}

			require_once SYSPATH . 'ee/language/english/email_data.php';
		}

		//ee()->db->insert('mailing_lists', array(
		//	'list_name'     => 'default',
		//	'list_title'    => 'Default Mailing List',
		//	'list_template' => addslashes(mailinglist_template())
		//));

		//ee()->db->insert('modules', array(
		//	'module_name'    => 'Mailinglist',
		//	'module_version' => $this->version,
		//	'has_cp_backend' => 'y'
		//));


		return TRUE;
	}
	

	



	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
        parent::uninstall();

        ee()->load->dbforge();
		
		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => 'Mailinglist'));

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_roles');

		ee()->db->where('module_name', 'Mailinglist');
		ee()->db->delete('modules');

		ee()->db->where('class', 'Mailinglist');
		ee()->db->delete('actions');

		ee()->db->where('class', 'Mailinglist_mcp');
		ee()->db->delete('actions');

		ee()->dbforge->drop_table('mailing_lists');
		ee()->dbforge->drop_table('mailing_list');
		ee()->dbforge->drop_table('mailing_list_queue');

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update($current='')
	{
		return TRUE;
	}
}
// END CLASS

/* End of file upd.mailinglist.php */
/* Location: ./system/expressionengine/modules/mailinglist/upd.mailinglist.php */
