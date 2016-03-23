<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Text Fieldtype Class
 *
 * @package		ExpressionEngine
 * @subpackage	Fieldtypes
 * @category	Fieldtypes
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Hidden_ft extends EE_Fieldtype {

	var $info = array(
		'name'		=> 'Hidden Field',
		'version'	=> '1.0.0'
	);

	// Parser Flag (preparse pairs?)
	var $has_array_data = FALSE;


	function display_field($data)
	{
		ee()->javascript->set_global('publish.hidden_fields', array($this->field_id => $this->field_name));
		return form_hidden($this->field_name, $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Update the fieldtype
	 *
	 * @param string $version The version being updated to
	 * @return boolean TRUE if successful, FALSE otherwise
	 */
	public function update($version)
	{
		return TRUE;
	}
}

// END Hidden_Ft class

// EOF
