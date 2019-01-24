<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once(EE_APPPATH.'models/template_model.php');

/**
 * Template Model
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
