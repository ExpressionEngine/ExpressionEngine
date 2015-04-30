<?php

namespace EllisLab\ExpressionEngine\Model\Template;

use EllisLab\ExpressionEngine\Service\Model\Model;

use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

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
 * ExpressionEngine Template Group Model
 *
 * @package		ExpressionEngine
 * @subpackage	TemplateGroup
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class TemplateGroup extends Model {

	protected static $_primary_key = 'group_id';
	protected static $_table_name = 'template_groups';

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
		'afterDelete',
		'afterInsert',
		'afterUpdate'
	);

	protected $group_id;
	protected $site_id;
	protected $group_name;
	protected $group_order;
	protected $is_site_default;

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

			$fs = new Filesystem();
			$fs->rename($old_path, $new_path);
		}

		$this->ensureFolderExists();
	}

	/**
	 * Make sure the group folder exists. Needs to be public
	 * so that the template post-save can have access to it.
	 */
	public function ensureFolderExists()
	{
		$fs = new Filesystem();
		$path = $this->getFolderPath();

		if (isset($path) && $fs->isDir($fs->dirname($path)) && ! $fs->isDir($path))
		{
			$fs->mkDir($path);
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

		$basepath = rtrim(ee()->config->item('tmpl_file_basepath'), '/');

		if (ee()->config->item('save_tmpl_files') != 'y' || $basepath == '')
		{
			return NULL;
		}

		$site = ee()->config->item('site_short_name');
		return $basepath.'/'.$site.'/'.$this->group_name . '.group';
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