<?php

namespace EllisLab\ExpressionEngine\Model\Site;

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
 * ExpressionEngine Site Table
 *
 * The Site model stores preference sets for each site in this installation
 * of ExpressionEngine.  Each site can have a completely different set of
 * settings and prefereces.
 *
 * @package		ExpressionEngine
 * @subpackage	Site
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Site extends Model {

	protected static $_primary_key = 'site_id';
	protected static $_table_name = 'sites';

	protected static $_type_classes = array(
		'ChannelPreferences' => 'EllisLab\ExpressionEngine\Model\Site\Column\ChannelPreferences',
		'MemberPreferences' => 'EllisLab\ExpressionEngine\Model\Site\Column\MemberPreferences',
		'SystemPreferences' => 'EllisLab\ExpressionEngine\Model\Site\Column\SystemPreferences',
		'TemplatePreferences' => 'EllisLab\ExpressionEngine\Model\Site\Column\TemplatePreferences',
	);

	protected static $_typed_columns = array(
		'site_channel_preferences' => 'ChannelPreferences',
		'site_member_preferences' => 'MemberPreferences',
		'site_system_preferences' => 'SystemPreferences',
		'site_template_preferences' => 'TemplatePreferences',
		'site_bootstrap_checksums' => 'base64Serialized',
		'site_pages' => 'base64Serialized',
	);

	protected static $_relationships = array(
		'GlobalVariables' => array(
			'model' => 'GlobalVariable',
			'type' => 'hasMany'
		),
		'Stats' => array(
			'type' => 'HasOne'
		),
		'Statuses' => array(
			'model' => 'Status',
			'type' => 'hasMany'
		),
		'StatusGroups' => array(
			'model' => 'StatusGroup',
			'type' => 'hasMany'
		),
		'TemplateGroups' => array(
			'model' => 'TemplateGroup',
			'type' => 'hasMany'
		),
		'SearchLogs' => array(
			'model' => 'SearchLog',
			'type' => 'hasMany'
		),
		'CpLogs' => array(
			'model' => 'CpLog',
			'type' => 'hasMany'
		),
		'Channels' => array(
			'model' => 'Channel',
			'type' => 'hasMany'
		),
		'Files' => array(
			'model' => 'File',
			'type' => 'hasMany'
		)
	);

	protected static $_validation_rules = array(
		'site_name'  => 'required|validateShortName|unique',
		'site_label' => 'required',
	);

	// Properties
	protected $site_id;
	protected $site_label;
	protected $site_name;
	protected $site_description;
	protected $site_system_preferences;
	protected $site_member_preferences;
	protected $site_template_preferences;
	protected $site_channel_preferences;
	protected $site_bootstrap_checksums;
	protected $site_pages;

	public function validateShortName($key, $value, $params, $rule)
	{
		if (preg_match('/[^a-z0-9\-\_]/i', $value))
		{
			return 'invalid_short_name';
		}

		return TRUE;
	}

}
