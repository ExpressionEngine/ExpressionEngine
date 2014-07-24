<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------


// We've already extended this library in the other app,
// so instead of maintaining the code in both, we'll just
// do an include and create a small meta class that
// CI can instantiate using the proper prefix.

require_once(EE_APPPATH.'model/template_model'.EXT);


// ------------------------------------------------------------------------
/**
 * ExpressionEngine Template Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */

class Installer_Template_model Extends Template_model {

	/**
	 *   Fetch a specific line of text
	 *
	 * @access	public
	 * @param  Template_Entity	$entity
 	 * @return	boolean	TRUE on success, FALSE on failure.
	 */
	public function save_to_database(Template_Entity $entity)
	{
		// Check for fields and add as necessary
		
		$this->_add_protect_javascript_col();

		$out = parent::save_to_database($entity);
		return $out;
	}

	private function _add_protect_javascript_col()
	{	
		// Add a yes/no column, and flip the all to no by default
		// Smartforge will check whether the column exists before adding it
		ee()->smartforge->add_column(
			'templates',
			array(
				'protect_javascript' => array(
					'type'			=> 'char',
					'constraint'    => 1,
					'null'			=> FALSE,
					'default'		=> 'n'
				)
			)
		);
	}
	
}

/* End of file template_model.php */
/* Location: ./system/expressionengine/installer/model/template_model.php */