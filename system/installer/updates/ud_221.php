<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2014, EllisLab, Inc.
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
		// 2.1.3 was missing this from its schema
		ee()->smartforge->add_column(
			'member_groups',
			array(
				'can_access_fieldtypes' => array(
					'type'			=> 'char',
					'constraint'	=> 1,
					'default'		=> 'n',
					'null'			=> FALSE
				)
			),
			'can_access_files'
		);

		ee()->db->set('can_access_fieldtypes', 'y');
		ee()->db->where('group_id', 1);
		ee()->db->update('member_groups');

		ee()->db->set('group_id', 4);
		ee()->db->where('group_id', 0);
		ee()->db->update('members');

		return TRUE;
    }
}
/* END CLASS */

/* End of file ud_221.php */
/* Location: ./system/expressionengine/installer/updates/ud_221.php */