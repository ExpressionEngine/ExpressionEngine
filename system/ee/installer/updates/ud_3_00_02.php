<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0.2
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Update Class
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Updater {

	var $version_suffix = '';

	/**
	 * Do Update
	 *
	 * @return TRUE
	 */
	public function do_update()
	{
		ee()->load->dbforge();

		$steps = new ProgressIterator(
			array(
				'_update_member_field_schema',
			)
		);

		foreach ($steps as $k => $v)
		{
			$this->$v();
		}

		return TRUE;
	}

	/**
	 * Sets three columns to allow NULL and to default to NULL. This matches
	 * the schema we install.
	 */
	private function _update_member_field_schema()
	{
		ee()->smartforge->modify_column('member_fields', array(
			'm_field_maxl' => array(
				'type' => 'smallint(3)',
				'null' => TRUE,
			),
			'm_field_width' => array(
				'type' => 'varchar(6)',
				'null' => TRUE,
			),
			'm_field_order' => array(
				'type' => 'int(3)',
				'null' => TRUE,
			),
		));

		foreach(array('m_field_maxl', 'm_field_width', 'm_field_order') as $col)
		{
			ee()->db->query("ALTER TABLE exp_member_fields ALTER COLUMN " . $col . " SET DEFAULT NULL");
		}
	}
}
/* END CLASS */

/* End of file ud_3_00_02.php */
/* Location: ./system/expressionengine/installer/updates/ud_3_00_02.php */
