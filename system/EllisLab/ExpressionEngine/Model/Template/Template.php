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
 * ExpressionEngine Template Model
 *
 * A model representing a template.  Templates contain a mix of EECode and HTML
 * and are parsed to become the front end pages of sites built with
 * ExpressionEngine.
 *
 * @package		ExpressionEngine
 * @subpackage	Template
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Template extends Model {

	protected static $_primary_key = 'template_id';
	protected static $_table_name = 'templates';

	protected static $_typed_columns = array(
		'cache'              => 'boolString',
		'enable_http_auth'   => 'boolString',
		'allow_php'          => 'boolString',
		'protect_javascript' => 'boolString'
	);

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'BelongsTo'
		),
		'TemplateGroup' => array(
			'type' => 'BelongsTo'
		),
		'LastAuthor' => array(
			'type'     => 'BelongsTo',
			'model'    => 'Member',
			'from_key' => 'last_author_id'
		),
		'NoAccess' => array(
			'type'  => 'HasAndBelongsToMany',
			'model' => 'MemberGroup',
			'pivot' => array(
				'table' => 'template_no_access',
				'left'  => 'template_id',
				'right' => 'member_group'
			)
		),
		'TemplateRoute' => array(
			'type' => 'HasOne'
		)
	);

	protected static $_validation_rules = array(
		'site_id'            => 'required|isNatural',
		'group_id'           => 'required|isNatural',
		'template_name'      => 'required|alphaDash',
		'cache'              => 'enum[y,n]',
		'enable_http_auth'   => 'enum[y,n]',
		'allow_php'          => 'enum[y,n]',
		'protect_javascript' => 'enum[y,n]',
	);

	protected $template_id;
	protected $site_id;
	protected $group_id;
	protected $template_name;
	protected $template_type;
	protected $template_data;
	protected $template_notes;
	protected $edit_date;
	protected $last_author_id;
	protected $cache;
	protected $refresh;
	protected $no_auth_bounce;
	protected $enable_http_auth;
	protected $allow_php;
	protected $php_parse_location;
	protected $hits;
	protected $protect_javascript;

	/**
	 * Returns the path to this template i.e. "site/index"
	 *
	 * @return string The path to this template
	 */
	public function getPath()
	{
		return $this->getTemplateGroup()->group_name . '/' . $this->template_name;
	}

}