<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2018, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Model\Member;

use EllisLab\ExpressionEngine\Model\Content\StructureModel;

/**
 * Member Group Model
 */
class MemberGroup extends StructureModel {

	protected static $_primary_key = 'group_id';
	protected static $_table_name = 'member_groups';

	protected static $_hook_id = 'member_group';

	protected static $_events = array(
		'beforeInsert',
		'afterInsert',
		'afterUpdate',
		'afterDelete'
	);

	protected static $_typed_columns = array(
		'is_locked'                      => 'boolString',
		'exclude_from_moderation'        => 'boolString',
		'include_in_authorlist'          => 'boolString',
		'include_in_memberlist'          => 'boolString',
	);


	protected static $_relationships = array(
		'Site' => array(
			'type' => 'belongsTo'
		),
		'Members' => array(
			'type' => 'hasMany',
			'model' => 'Member',
			'weak' => TRUE
		),
		'AssignedChannels' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Channel',
			'pivot' => array(
				'table' => 'channel_member_groups'
			)
		),
		'AssignedTemplateGroups' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'TemplateGroup',
			'pivot' => array(
				'table' => 'template_member_groups',
				'left'  => 'group_id',
				'right' => 'template_group_id'
			)
		),
		'AssignedModules' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Module',
			'pivot' => array(
				'table' => 'module_member_groups'
			)
		),
		'NoTemplateAccess' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Template',
			'pivot' => array(
				'table' => 'template_no_access',
				'right'  => 'template_id',
				'left' => 'member_group'
			)
		),
		'NoUploadAccess' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'UploadDestination',
			'pivot' => array(
				'table' => 'upload_no_access',
				'left' => 'member_group',
				'right' => 'upload_id'
			)
		),
		'NoStatusAccess' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'Status',
			'pivot' => array(
				'table' => 'status_no_access',
				'left' => 'member_group',
				'right' => 'status_id'
			)
		),
		'ChannelLayouts' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'ChannelLayout',
			'pivot' => array(
				'table' => 'layout_publish_member_groups',
				'key' => 'layout_id',
			)
		),
		'EmailCache' => array(
			'type' => 'hasAndBelongsToMany',
			'model' => 'EmailCache',
			'pivot' => array(
				'table' => 'email_cache_mg'
			)
		),
		'MenuSet' => array(
			'type' => 'belongsTo',
			'from_key' => 'menu_set_id'
		),
	);

	protected static $_validation_rules = array(
		'group_id' => 'required|integer',
		'site_id'  => 'required|integer',
	);

	// Properties
	protected $group_id;
	protected $site_id;
	protected $group_title;
	protected $group_description;
	protected $is_locked;
	protected $menu_set_id;
	protected $mbr_delete_notify_emails;
	protected $exclude_from_moderation;
	protected $search_flood_control;
	protected $prv_msg_send_limit;
	protected $prv_msg_storage_limit;
	protected $include_in_authorlist;
	protected $include_in_memberlist;
	protected $cp_homepage;
	protected $cp_homepage_channel;
	protected $cp_homepage_custom;

	/**
	 * Ensure group ID is set for new records
	 * @return void
	 */
	public function onBeforeInsert()
	{
		if ( ! $this->group_id)
		{
			$id = ee('db')->query('SELECT MAX(group_id) as id FROM exp_member_groups')->row('id');
			$this->setRawProperty('group_id', $id + 1);
		}
	}

	public function onAfterDelete()
	{
		$this->prunePivotTables();
	}

	protected function prunePivotTables()
	{
		foreach (self::$_relationships as $name => $info)
		{
			if (array_key_exists('pivot', $info))
			{
				$table = 'exp_' . $info['pivot']['table'];
				$column = (array_key_exists('left', $info['pivot'])) ? $info['pivot']['left'] : 'group_id';

				$sql = "SELECT DISTINCT({$table}.{$column}) AS group_id FROM {$table} LEFT JOIN exp_member_groups ON {$table}.{$column} = exp_member_groups.group_id WHERE exp_member_groups.group_id is NULL;";
				$query = ee('db')->query($sql);

				$groups = array();

				foreach ($query->result_array() as $row)
				{
					$groups[] = $row['group_id'];
				}

				if ( ! empty($groups))
				{
					ee('db')->query("DELETE FROM {$table} WHERE {$column} IN (" . implode(',', $groups) . ")");
				}
			}
		}
	}

	/**
	 * Only set ID if we're being passed a number other than 0 or NULL
	 * @param Integer/String $new_id ID of the record
	 */
	public function setId($new_id)
	{
		if ($new_id !== '0' && $new_id !== 0)
		{
			parent::setId($new_id);
		}
	}

	/**
	 * Ensure member group records are created for each site
	 * @return void
	 */
	public function onAfterInsert()
	{
		$this->setId($this->group_id);

		$sites = $this->getModelFacade()->get('Site')
			->fields('site_id')
			->all()
			->pluck('site_id');

		foreach ($sites as $site_id)
		{
			$group = $this->getModelFacade()->get('MemberGroup')
				->filter('group_id', $this->group_id)
				->filter('site_id', $site_id)
				->first();

			if ( ! $group)
			{
				$data = $this->getValues();
				$data['site_id'] = (int) $site_id;
				$this->getModelFacade()->make('MemberGroup', $data)->save();
			}
		}
	}

	protected function constrainQueryToSelf($query)
	{
		if ($this->isDirty('site_id'))
		{
			throw new \LogicException('Cannot modify site_id.');
		}

		$query->filter('site_id', $this->site_id);
		parent::constrainQueryToSelf($query);
	}

	/**
	 * Update common attributes (group_title, group_description, is_locked)
	 * @return void
	 */
	public function onAfterUpdate()
	{
		ee('db')->update(
			'member_groups',
			array(
				'group_title' => $this->group_title,
				'group_description' => $this->group_description,
				'is_locked' => $this->is_locked,
				'menu_set_id' => $this->menu_set_id
			),
			array('group_id' => $this->group_id)
		);
	}

	/**
	 * Returns array of field models; implements StructureModel interface
	 */
	public function getAllCustomFields()
	{
		$member_cfields = ee()->session->cache('EllisLab::MemberGroupModel', 'getCustomFields');

		// might be empty, so need to be specific
		if ( ! is_array($member_cfields))
		{
			$member_cfields = $this->getModelFacade()->get('MemberField')->all()->asArray();
			ee()->session->set_cache('EllisLab::MemberGroupModel', 'getCustomFields', $member_cfields);
		}

		return $member_cfields;
	}

	/**
	 * Returns name of content type for these fields; implements StructureModel interface
	 */
	public function getContentType()
	{
		return 'member';
	}

	/**
	 * Assigns channels to this group for this site without destroying this
	 * group's channel assignments on the other sites. The pivot table does not
	 * take into account the site_id so we we'll do that here.
	 *
	 * @param  array  $channel_ids An array of channel ids for this group
	 * @return void
	 */
	public function assignChannels(array $channel_ids)
	{
		// First, get the channel ids for all the other sites
		$other_channels = $this->getModelFacade()->get('Channel')
			->fields('channel_id')
			->filter('site_id', '!=', $this->site_id)
			->all()
			->pluck('channel_id');

		// Get all the assignments for the other sites
		$current_assignments = array_values(array_intersect($other_channels, $this->AssignedChannels->pluck('channel_id')));

		// Make the assignment!
		$this->AssignedChannels = $this->getModelFacade()->get('Channel', array_merge($current_assignments, $channel_ids))->all();
	}
}

// EOF
