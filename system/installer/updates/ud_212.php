<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license     http://ellislab.com/expressionengine/user-guide/license.html
 * @link        http://ellislab.com
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
 * @author      EllisLab Dev Team
 * @link        http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

    function Updater()
    {
        $this->EE =& get_instance();
    }

    function do_update()
    {
		$fields = array(
					'show_sidebar' 	=> array(
								'type'			=> 'char',
								'constraint'	=> 1,
								'null'			=> FALSE,
								'default'		=> 'y'
								));

		$this->EE->smartforge->add_column('members', $fields, 'quick_tabs');


		$fields = array(
					'm_field_cp_reg' 	=> array(
								'type'			=> 'char',
								'constraint'	=> 1,
								'null'			=> FALSE,
								'default'		=> 'n'
								));

		$this->EE->smartforge->add_column('member_fields', $fields, 'm_field_reg');


		$fields = array(
					'member_groups' 	=> array(
								'name'			=> 'member_groups',
								'type'			=> 'varchar',
								'constraint'	=> 255,
								'null'			=> FALSE
								));

		$this->EE->smartforge->modify_column('accessories', $fields);


		$fields = array(
					'can_edit_html_buttons' 	=> array(
								'type'			=> 'char',
								'constraint'	=> 1,
								'null'			=> FALSE,
								'default'		=> 'n'
								));

		$this->EE->smartforge->add_column('member_groups', $fields, 'can_view_profiles');

		$this->EE->db->set('can_edit_html_buttons', 'y');
		$this->EE->db->where('can_access_cp', 'y');
		$this->EE->db->update('member_groups');


		if ($this->EE->db->table_exists('comments'))
		{
			$this->EE->db->set('location', '');
			$this->EE->db->where('location', '0');
			$this->EE->db->update('comments');
		}
		
		// Remove allow_multi_emails from config
		$this->EE->config->_update_config(array(), array('allow_multi_emails' => ''));
		
		return TRUE;
	}
}   
/* END CLASS */

/* End of file ud_212.php */
/* Location: ./system/expressionengine/installer/updates/ud_212.php */