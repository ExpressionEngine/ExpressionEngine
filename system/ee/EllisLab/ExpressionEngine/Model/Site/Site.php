<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Site;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine Site Table
 *
 * The Site model stores preference sets for each site in this installation
 * of ExpressionEngine.  Each site can have a completely different set of
 * settings and prefereces.
 */
class Site extends Model {

	protected static $_primary_key = 'site_id';
	protected static $_table_name = 'sites';

	protected static $_hook_id = 'site';

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
		'TemplateGroups' => array(
			'model' => 'TemplateGroup',
			'type' => 'hasMany'
		),
		'Templates' => array(
			'model' => 'Template',
			'type' => 'hasMany'
		),
		'SpecialtyTemplates' => array(
			'model' => 'SpecialtyTemplate',
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
		'ChannelEntries' => array(
			'model' => 'ChannelEntry',
			'type' => 'hasMany'
		),
		'Comments' => array(
			'type' => 'hasMany',
			'model' => 'Comment'
		),
		'Files' => array(
			'model' => 'File',
			'type' => 'hasMany'
		),
		'UploadDestinations' => array(
			'model' => 'UploadDestination',
			'type' => 'hasMany'
		),
		'MemberGroups' => array(
			'model' => 'MemberGroup',
			'type' => 'hasMany'
		),
		'HTMLButtons' => array(
			'model' => 'HTMLButton',
			'type' => 'hasMany'
		),
		'Snippets' => array(
			'model' => 'Snippet',
			'type' => 'hasMany'
		)
	);

	protected static $_validation_rules = array(
		'site_name'  => 'required|validateShortName|unique',
		'site_label' => 'required',
	);

	protected static $_events = array(
		'beforeInsert',
		'afterInsert'
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

	public function onBeforeInsert()
	{
		$this->setDefaultPreferences('system');
		$this->setDefaultPreferences('channel');
		$this->setDefaultPreferences('template');
		$this->setDefaultPreferences('member');
	}

	public function onAfterInsert()
    {
		$this->createNewStats();
		$this->createHTMLButtons();
		$this->createSpecialtyTemplates();
		$this->createMemberGroups();
    }

	/**
	 * Given a type loops through config's divination method and sets the
	 * default property values as indicated.
	 *
	 * @param string $type The type of preference ('system', 'channel', 'template', or 'member')
	 * @return void
	 */
	protected function setDefaultPreferences($type)
	{
		$prefs = $this->getProperty('site_' . $type . '_preferences');

		foreach(ee()->config->divination($type) as $value)
		{
			$prefs->$value = ee()->config->item($value);
		}
	}

	/**
	 * Creates a new Stats object for this site. If this is not site id 1 then
	 * it will copy the member stats, since those are not site specific.
	 *
	 * @return void
	 */
	protected function createNewStats()
	{
		$data = array(
			'site_id' => $this->site_id
		);

		if ($this->site_id != 1)
		{
			$stats = $this->getModelFacade()->get('Stats')
				->fields('total_members', 'recent_member_id', 'recent_member')
				->filter('site_id', 1)
				->first();

			$data['total_members']    = $stats->total_members;
			$data['recent_member_id'] = $stats->recent_member_id;
			$data['recent_member']    = $stats->recent_member;
		}

		$this->getModelFacade()->make('Stats', $data)->save();
	}

	/**
	 * Creates HTML buttons for this site by cloning site 1's default HTML
	 * buttons.
	 *
	 * @return void
	 */
	protected function createHTMLButtons()
	{
		$buttons = $this->getModelFacade()->get('HTMLButton')
			->filter('site_id', 1)
			->filter('member_id', 0)
			->all();

		foreach($buttons as $button)
		{
			$data = $button->getValues();
			unset($data['id']);
			$data['site_id'] = $this->site_id;

			$this->getModelFacade()->make('HTMLButton', $data)->save();
		}
	}

	/**
	 * Creates specialty templates for this site by cloning site 1's specialty
	 * templates.
	 *
	 * @return void
	 */
	protected function createSpecialtyTemplates()
	{
		$templates = $this->getModelFacade()->get('SpecialtyTemplate')
			->filter('site_id', 1)
			->all();

		foreach($templates as $template)
		{
			$data = $template->getValues();
			unset($data['template_id']);
			$data['site_id'] = $this->site_id;

			$this->getModelFacade()->make('SpecialtyTemplate', $data)->save();
		}
	}

	/**
	 * Creates member groups for this site by cloning site 1's member groups
	 *
	 * @return void
	 */
	protected function createMemberGroups()
	{
		$groups = $this->getModelFacade()->get('MemberGroup')
			->filter('site_id', 1)
			->all();

		foreach($groups as $group)
		{
			$data = $group->getValues();
			$data['site_id'] = $this->site_id;

			$this->getModelFacade()->make('MemberGroup', $data)->save();
		}
	}
}

// EOF
