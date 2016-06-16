<?php

namespace EllisLab\ExpressionEngine\Model\Site;

use EllisLab\ExpressionEngine\Service\Model\Model;

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
 * @link		https://ellislab.com
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
		$current_number_of_sites = $this->getModelFacade()->get('Site')->count();

		$can_add = ee('License')->getEELicense()
			->canAddSites($current_number_of_sites);

		if ( ! $can_add)
		{
			throw new \Exception("Site limit reached.");
		}

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
		$this->createDefaultStatuses();
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

		if ($type == 'template')
		{
			$prefs->save_tmpl_files    = 'n';
			$prefs->tmpl_file_basepath = '';
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

	/**
	 * Creates a "Default" status group and the "open" and "closed" statuses
	 * in that group as needed.
	 *
	 * @return void
	 */
	public function createDefaultStatuses()
	{
		$group = $this->getModelFacade()->get('StatusGroup')
			->filter('site_id', $this->site_id)
			->filter('group_name', 'Default')
			->first();

		if ( ! $group)
		{
			$group = $this->getModelFacade()->make('StatusGroup', array(
				'site_id'    => $this->site_id,
				'group_name' => 'Default'
			))->save();
		}

		$statuses = ($group->Statuses) ? $group->Statuses->indexBy('status') : array();

		if ( ! array_key_exists('open', $statuses))
		{
			$this->getModelFacade()->make('Status', array(
				'site_id'      => $this->site_id,
				'group_id'     => $group->group_id,
				'status'       => 'open',
				'status_order' => 1,
				'highlight'    => '009933',
			))->save();
		}

		if ( ! array_key_exists('closed', $statuses))
		{
			$this->getModelFacade()->make('Status', array(
				'site_id'      => $this->site_id,
				'group_id'     => $group->group_id,
				'status'       => 'closed',
				'status_order' => 2,
				'highlight'    => '990000',
			))->save();
		}
	}
}

// EOF
