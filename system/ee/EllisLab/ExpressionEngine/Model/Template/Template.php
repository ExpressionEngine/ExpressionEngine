<?php

namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Service\Model\FileSyncedModel;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
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
 * @link		https://ellislab.com
 */
class Template extends FileSyncedModel {

	protected static $_primary_key = 'template_id';
	protected static $_table_name = 'templates';

	protected static $_hook_id = 'template';

	protected static $_typed_columns = array(
		'cache'              => 'boolString',
		'enable_http_auth'   => 'boolString',
		'allow_php'          => 'boolString',
		'protect_javascript' => 'boolString',
		'refresh'            => 'int',
		'hits'               => 'int',
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
		),
		'DeveloperLogItems' => array(
			'type' => 'hasMany',
			'model' => 'DeveloperLog'
		),
		'Channel' => array(
			'type' => 'belongsTo',
			'to_key' => 'live_look_template',
			'from_key' => 'template_id',
			'weak' => TRUE,
		),
		'Versions' => array(
			'type' => 'hasMany',
			'model' => 'RevisionTracker',
			'to_key' => 'item_id',
		)
	);

	protected static $_validation_rules = array(
		'site_id'            => 'required|isNatural',
		'group_id'           => 'required|isNatural',
		'template_name'      => 'required|unique[group_id]|validateTemplateName',
		'template_type'      => 'required',
		'cache'              => 'enum[y,n]',
		'refresh'            => 'isNatural',
		'enable_http_auth'   => 'enum[y,n]',
		'allow_php'          => 'enum[y,n]',
		'php_parse_location' => 'enum[i,o]',
		'hits'               => 'isNatural',
		'protect_javascript' => 'enum[y,n]',
	);

	protected static $_events = array(
		'afterSave',
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

	/**
	 * Get the full filesystem path to the template file
	 *
	 * @return String Filesystem path to the template file
	 */
	public function getFilePath()
	{
		static $group;

		if (ee()->config->item('save_tmpl_files') != 'y')
		{
			return NULL;
		}

		if ( ! $group || $group->group_id != $this->group_id)
		{
			$group = $this->getTemplateGroup();
		}

		if ( ! isset($group))
		{
			return NULL;
		}

		$group->ensureFolderExists();

		$path = $group->getFolderPath();
		$file = $this->template_name;
		$ext  = $this->getFileExtension();

		if ($path == '' || $file == '' || $ext == '')
		{
			return NULL;
		}

		return $path.'/'.$file.$ext;
	}

	/**
	 * Get the data to be stored in the file
	 */
	protected function serializeFileData()
	{
		return $this->template_data;
	}

	/**
	 * Set the model based on the data in the file
	 */
	protected function unserializeFileData($str)
	{
		$this->setProperty('template_data', $str);
	}

	/**
	 * Make the last modified time available to the parent class
	 */
	public function getModificationTime()
	{
		return $this->edit_date;
	}

	/**
	 * Allow our parent class to set the modification time
	 */
	public function setModificationTime($mtime)
	{
		$this->setProperty('edit_date', $mtime);
	}

	/**
	 * Get the file extension for a given template type
	 *
	 * @param String $template_type Used by onAfterUpdate to divine the old path
	 * @return String File extension (including the .)
	 */
	public function getFileExtension($template_type = NULL)
	{
		$type = $template_type ?: $this->template_type;

		ee()->load->library('api');
		ee()->legacy_api->instantiate('template_structure');
		return ee()->api_template_structure->file_extensions($type);
	}

	/**
	 * Get the old template path, so that we can delete it if
	 * the path changed.
	 */
	protected function getPreviousFilePath($prev)
	{
		$values = $this->getValues();
		$parts = array_merge($values, $prev);

		if ($parts['group_id'] != $this->group_id)
		{
			// TODO there must be a better way
			$group = $this->getFrontend()->get('TemplateGroup', $parts['group_id'])->first();
		}
		else
		{
			$group = $this->getTemplateGroup();
		}

		$path = $group->getFolderPath();
		$file = $parts['template_name'];
		$ext  = $this->getFileExtension($parts['template_type']);

		if ($path == '' || $file == '' || $ext == '')
		{
			return NULL;
		}

		return $path.'/'.$file.$ext;
	}

	/**
	 * Validates the template name checking for illegal characters and
	 * reserved names.
	 */
	public function validateTemplateName($key, $value, $params, $rule)
	{
		if ( ! preg_match("#^[a-zA-Z0-9_\.\-/]+$#i", $value))
		{
			return 'illegal_characters';
		}

		$reserved_names = array('act', 'css');

		if (in_array($value, $reserved_names))
		{
			return 'reserved_name';
		}

		return TRUE;
	}

	public function onAfterSave()
	{
		parent::onAfterSave();
		ee()->functions->clear_caching('all');
	}
}

// EOF
