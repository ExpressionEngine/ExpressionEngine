<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      EllisLab Dev Team
 * @copyright   Copyright (c) 2003 - 2012, EllisLab, Inc.
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
		if ( ! $this->EE->db->field_exists('can_access_fieldtypes', 'member_groups'))
		{
			$Q[] = "ALTER TABLE `exp_member_groups` ADD `can_access_fieldtypes` char(1) NOT NULL DEFAULT 'n' AFTER `can_access_files`";            
		}

		$Q[] = "UPDATE `exp_member_groups` SET `can_access_fieldtypes` = 'y' WHERE `group_id` = 1";
		$Q[] = "UPDATE `exp_members` SET `group_id` = 4 WHERE `group_id` = 0";
		

		$count = count($Q);
		$num = 1;
		
		foreach ($Q as $sql)
		{
			$this->EE->progress->update_state("Running Query $num of $count");
	        $this->EE->db->query($sql);
			$num++;
		}
		
		return TRUE;
    }
}   
/* END CLASS */

/* End of file ud_221.php */
/* Location: ./system/expressionengine/installer/updates/ud_221.php */