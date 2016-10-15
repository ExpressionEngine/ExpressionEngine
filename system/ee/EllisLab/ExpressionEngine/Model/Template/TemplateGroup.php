<?php

namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Service\Model\Model;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

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
 * ExpressionEngine Template Group Model
 *
 * @package		ExpressionEngine
 * @subpackage	TemplateGroup
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
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
		'group_name' => 'required|is_valid_group_name|unique',
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
			$count = $this->getFrontend()->get('TemplateGroup')
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
			$template_groups = $this->getFrontend()->get('TemplateGroup')
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
			$site = $this->getFrontend()->get('Site')
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

}

// EOF
