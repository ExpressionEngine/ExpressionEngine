<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package     ExpressionEngine
 * @author      ExpressionEngine Dev Team
 * @copyright   Copyright (c) 2003 - 2010, EllisLab, Inc.
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

	var $version_suffix = '';

    function Updater()
    {
        $this->EE =& get_instance();
    }

    function do_update()
    {
		// update channel_data table changing text fields to NOT NULL
		
		// Get all the text fields in the table
		$fields = $this->EE->db->field_data('channel_data');
		$Q = array();
		
		$fields_to_alter = array();

		foreach ($fields as $field)
		{
			if (strncmp($field->name, 'field_id_', 9) == 0 && ($field->type == 'text' OR $field->type == 'blob'))
			{
				$fields_to_alter[] = array($field->name, $field->type);
			}
		} 		

 		if (count($fields_to_alter) > 0)
        {
			foreach ($fields_to_alter as $row)
            {
 				// We'll switch null values to empty string for our text fields
          		$Q[] = "UPDATE `exp_channel_data` SET {$row['0']} = '' WHERE {$row['0']} IS NULL";				
        	}
		}

		$count = count($Q);
		
		foreach ($Q as $num => $sql)
		{
			$this->EE->progress->update_state("Running Query $num of $count");
	        $this->EE->db->query($sql);
		}
		
		return TRUE;
	}
}   
/* END CLASS */

/* End of file ud_211.php */
/* Location: ./system/expressionengine/installer/updates/ud_211.php */