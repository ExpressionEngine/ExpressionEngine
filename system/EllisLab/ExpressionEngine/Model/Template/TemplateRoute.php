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
 * ExpressionEngine Template Route Model
 *
 * A model representing a template route.
 *
 * @package		ExpressionEngine
 * @subpackage	Template
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class TemplateRoute extends Model {

	protected static $_primary_key = 'route_id';
	protected static $_table_name = 'template_routes';

	protected static $_type_columns = array(
		'route_required' => 'boolString'
	);

	protected static $_relationships = array(
		'Template' => array(
			'type' => 'BelongsTo'
		)
	);

	protected static $_validation_rules = array(
		'template_id'    => 'required|isNatural',
		'route_required' => 'enum[y,n]',
	);

	protected $route_id;
	protected $template_id;
	protected $order;
	protected $route;
	protected $route_parsed;
	protected $route_required;

}