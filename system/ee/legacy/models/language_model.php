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
 * Language Model
 */
class Language_model extends CI_Model {

	/**
	 * Language Pack Names
	 *
	 * @return	array
	 */
	public function language_pack_names()
	{
		ee()->logger->deprecated('3.0', 'EE_lang::language_pack_names()');
		ee()->load->model('language_model');
		return ee()->lang->language_pack_names();
	}

}

// EOF
