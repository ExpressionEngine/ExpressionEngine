<?php

namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Specialty Templates Model
 *
 * @package		ExpressionEngine
 * @subpackage	Template
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class SpecialtyTemplate extends Model {

	protected static $_primary_key = 'template_id';
	protected static $_table_name = 'specialty_templates';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'BelongsTo'
		)
	);

	protected $template_id;
	protected $site_id;
	protected $enable_template;
	protected $template_name;
	protected $data_title;
	protected $template_data;

	/**
	 * A setter for the enable_template property
	 *
	 * @param str|bool $new_value Accept TRUE or 'y' for 'yes' or FALSE or 'n'
	 *   for 'no'
	 * @throws InvalidArgumentException if the provided argument is not a
	 *   boolean or is not 'y' or 'n'.
	 * @return void
	 */
	protected function set__enable_template($new_value)
	{
		if ($new_value === TRUE || $new_value == 'y')
		{
			$this->enable_template = 'y';
		}

		elseif ($new_value === FALSE || $new_value == 'n')
		{
			$this->enable_template = 'n';
		}

		else
		{
			throw new InvalidArgumentException('enable_template must be TRUE or "y", or FALSE or "n"');
		}
	}

	/**
	 * A getter for the enable_template property
	 *
	 * @return bool TRUE if this is the default; FALSE if not
	 */
	protected function get__enable_template()
	{
		return ($this->enable_template == 'y');
	}

}
