<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Hidden Fieldtype Class
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
