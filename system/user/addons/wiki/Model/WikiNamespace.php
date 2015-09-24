<?php

namespace User\addons\Wiki\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2014, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Wiki Namespace Model
 *
 * A model representing a Namespace in the Wiki module.
 *
 * @package		ExpressionEngine
 * @subpackage	Wiki Module
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class WikiNamespace extends Model {

	protected static $_primary_key = 'namespace_id';
	protected static $_table_name = 'wiki_namespaces';

	protected static $_typed_columns = array(
		'namespace_admins'     => 'pipeDelimited',
		'namespace_users'     => 'pipeDelimited'
	);

	protected static $_relationships = array(
		'Wiki' => array(
			'type' => 'belongsTo'
		)
	);


	protected static $_validation_rules = array(
		'namespace_name' => 'required|validateShortName|uniqueWithinSiblings[Wiki,WikiNamespaces]',
		'namespace_label'       => 'required|uniqueWithinSiblings[Wiki,WikiNamespaces]]'
	);



	protected $namespace_id;
	protected $wiki_id;
	protected $namespace_name;
	protected $namespace_label;
	protected $namespace_users;
	protected $namespace_admins;


	public function validateShortName($key, $value, $params, $rule)
	{
		if (preg_match('/[^a-z0-9\-\_]/i', $value))
		{
			return 'invalid_short_name';
		}

		return TRUE;
	}


}
