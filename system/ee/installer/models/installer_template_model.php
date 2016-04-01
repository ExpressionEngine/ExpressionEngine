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

// ------------------------------------------------------------------------


require_once(EE_APPPATH.'models/template_model.php');


// ------------------------------------------------------------------------
/**
 * ExpressionEngine Template Model
 *
 * @package		ExpressionEngine
 * @subpackage	Core
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Installer_template_model extends Template_model {

	/**
	 *   Save to database
	 *
	 * @access	public
	 * @param  Template_Entity	$entity
 	 * @return	boolean	TRUE on success, FALSE on failure.
	 */
	public function save_to_database(Template_Entity $entity)
	{
		// Check for fields and add as necessary
		$this->_add_protect_javascript_col();

		return parent::save_to_database($entity);
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

// EOF
