<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Service\Model\Model;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Template Group Model
 */
class TemplateGroup extends Model {

	protected static $_primary_key = 'group_id';
	protected static $_table_name = 'template_groups';

	protected static $_hook_id = 'template_group';

	protected static $_typed_columns = array(
		'is_site_default' => 'boolString'
	);

	protected static $_relationships = array(
		'MemberGroups' => array(
			'type'     => 'HasAndBelongsToMany',
			'model'    => 'MemberGroup',
			'from_key' => 'group_id',
			'pivot' => array(
				'table' => 'template_member_groups',
				'left'  => 'template_group_id',
				'right' => 'group_id'
			)
		),
		'Templates' => array(
			'type' => 'HasMany',
			'model' => 'Template'
		),
		'Site' => array(
			'type' => 'BelongsTo'
		)
	);

	protected static $_validation_rules = array(
		'is_site_default' => 'enum[y,n]',
		'group_name' => 'required|alphaDashPeriodEmoji|validateTemplateGroupName|unique[site_id]',
	);

	protected static $_events = array(
		'beforeInsert',
		'afterDelete',
		'afterInsert',
		'afterUpdate',
		'afterSave',
	);

	protected $group_id;
	protected $site_id;
	protected $group_name;
	protected $group_order;
	protected $is_site_default;

	/**
	 * For new groups, make sure the group order is set
	 */
	public function onBeforeInsert()
	{
		$group_order = $this->getProperty('group_order');
		if (empty($group_order))
		{
			$count = $this->getModelFacade()->get('TemplateGroup')
				->count();
			$this->setProperty('group_order', $count + 1);
		}
	}

	/**
	 * For a new group, make sure the folder exists if it needs to
	 */
	public function onAfterInsert()
	{
		$this->ensureFolderExists();
	}

	/**
	 * After updating we need to check if the group name changed.
	 * If it did we rename the folder.
	 *
	 * @param Array The old values that have been changed
	 */
	public function onAfterUpdate($previous)
	{
		if (isset($previous['group_name']))
		{
			$this->set($previous);
			$old_path = $this->getFolderPath();
			$this->restore();

			$new_path = $this->getFolderPath();

			if ($old_path !== NULL && $new_path !== NULL)
			{
				$fs = new Filesystem();
				$fs->rename($old_path, $new_path);
			}
		}

		$this->ensureFolderExists();
	}

	/**
	 * After saving, if this template group is makred as the site default,
	 * then we need to ensure that all other template groups for this
	 * site are not set as the default
	 */
	public function onAfterSave()
	{
		if ($this->getProperty('is_site_default'))
		{
			$template_groups = $this->getModelFacade()->get('TemplateGroup')
				->filter('site_id', $this->site_id)
				->filter('is_site_default', 'y')
				->filter('group_id', '!=', $this->group_id)
				->all();

			if ($template_groups)
			{
				$template_groups->is_site_default = FALSE;
				$template_groups->save();
			}
		}

	}

	/**
	 * Make sure the group folder exists. Needs to be public
	 * so that the template post-save can have access to it.
	 */
	public function ensureFolderExists()
	{
		$fs = new Filesystem();
		$path = $this->getFolderPath();

		if (isset($path) && ! $fs->isDir($path))
		{
			$fs->mkDir($path, FALSE);
		}
	}

	/**
	 * Get the full folder path
	 */
	public function getFolderPath()
	{
		if ($this->group_name == '')
		{
			return NULL;
		}

		$basepath = PATH_TMPL;

		if (ee()->config->item('save_tmpl_files') != 'y' || $basepath == '')
		{
			return NULL;
		}

		// Cache the sites as we query
		if ( ! $site = ee()->session->cache('site/id/' . $this->site_id, 'site'))
		{
			$site = $this->getModelFacade()->get('Site')
				->fields('site_name')
				->filter('site_id', $this->site_id)
				->first();

			ee()->session->set_cache('site/id/' . $this->site_id, 'site', $site);
		}

		return $basepath.$site->site_name.'/'.$this->group_name . '.group';
	}

	/**
	 * If we group is deleted we need to remove the folder
	 */
	public function onAfterDelete()
	{
		$fs = new Filesystem();
		$path = $this->getFolderPath();

		if (isset($path) && $fs->isDir($path))
		{
			$fs->deleteDir($path);
		}
	}

	/**
	 * Validates the template name checking for reserved names.
	 */
	public function validateTemplateGroupName($key, $value, $params, $rule)
	{
		$reserved_names = array('act', 'css');

		if (in_array($value, $reserved_names))
		{
			return 'reserved_name';
		}

		return TRUE;
	}

	/**
	 * Override of the parent validateUnique to alter the lang key if it's a failure.
	 *
	 * @param String $key    Property name
	 * @param String $value  Property value
	 * @param Array  $params Rule parameters
	 * @return Mixed String if error, TRUE if success
	 */
	public function validateUnique($key, $value, array $params = array())
	{
		$return = parent::validateUnique($key, $value, $params);
		if (is_bool($return))
		{
			// Don't allow case insensitive matches on template group names
			if (strcasecmp($value, $this->getBackup($key)) == 0)
			{
				return 'template_group_taken';
			}

			return $return;
		}

		return 'template_group_taken';
	}

}

// EOF
