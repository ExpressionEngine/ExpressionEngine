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
		)
	);

	protected static $_validation_rules = array(
		'site_id'            => 'required|isNatural',
		'group_id'           => 'required|isNatural',
		'template_name'      => 'required|alphaDash',
		'save_template_file' => 'enum[y,n]',
		'cache'              => 'enum[y,n]',
		'enable_http_auth'   => 'enum[y,n]',
		'allow_php'          => 'enum[y,n]',
		'protect_javascript' => 'enum[y,n]',
	);

	protected $template_id;
	protected $site_id;
	protected $group_id;
	protected $template_name;
	protected $save_template_file;
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

	public function set__save_template_file($new_value)
	{
		$this->set_y_n('save_template_file', $new_value);
	}

	public function get__save_template_file()
	{
		return $this->save_template_file == 'y';
	}

	public function set__cache($new_value)
	{
		$this->set_y_n('cache', $new_value);
	}

	public function get__cache()
	{
		return $this->cache == 'y';
	}

	public function set__enable_http_auth($new_value)
	{
		$this->set_y_n('enable_http_auth', $new_value);
	}

	public function get__enable_http_auth()
	{
		return $this->enable_http_auth == 'y';
	}

	public function set__allow_php($new_value)
	{
		$this->set_y_n('allow_php', $new_value);
	}

	public function get__allow_php()
	{
		return $this->allow_php == 'y';
	}

	public function set__protect_javascript($new_value)
	{
		$this->set_y_n('protect_javascript', $new_value);
	}

	public function get__protect_javascript()
	{
		return $this->protect_javascript == 'y';
	}

	private function set_y_n($property, $new_value)
	{
		if ($new_value == TRUE || $new_value == 'y')
		{
			$this->$property = 'y';
		}

		if ($new_value == FALSE || $new_value == 'n')
		{
			$this->$property = 'n';
		}
	}

}