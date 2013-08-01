<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Channel Parser Component (Switch)
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Core
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class EE_Channel_switch_parser implements EE_Channel_parser_component {

	/**
	 * Quick check if they're using switch
	 *
	 * @param array		A list of "disabled" features
	 * @return Boolean	Is disabled?
	 */
	public function disabled(array $disabled, EE_Channel_preparser $pre)
	{
		return ! $pre->has_tag('switch');
	}

	// ------------------------------------------------------------------------

	/**
	 * No preprocessing required.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The preparser object.
	 * @return void
	 */
	public function pre_process($tagdata, EE_Channel_preparser $pre)
	{
		return NULL;
	}

	// ------------------------------------------------------------------------

	/**
	 * Replace the switch tag based on what step of the loop we're in.
	 *
	 * @param String	The tagdata to be parsed
	 * @param Object	The channel parser object
	 * @param Mixed		The results from the preparse method
	 *
	 * @return String	The processed tagdata
	 */
	public function replace($tagdata, EE_Channel_data_parser $obj, $pre)
	{
		return ee()->TMPL->parse_switch($tagdata, $obj->count(), $obj->prefix());
	}
}