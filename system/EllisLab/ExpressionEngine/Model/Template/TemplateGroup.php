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
		'afterSave'
	);

	protected $group_id;
	protected $site_id;
	protected $group_name;
	protected $group_order;
	protected $is_site_default;

	/**
	 * A setter for the is_site_default property
	 *
	 * @param str|bool $new_value Accept TRUE or 'y' for 'yes' or FALSE or 'n'
	 *   for 'no'
	 * @throws InvalidArgumentException if the provided argument is not a
	 *   boolean or is not 'y' or 'n'.
	 * @return void
	 */
	protected function set__is_site_default($new_value)
	{
		if ($new_value === TRUE || $new_value == 'y')
		{
			$this->is_site_default = 'y';
		}

		elseif ($new_value === FALSE || $new_value == 'n')
		{
			$this->is_site_default = 'n';
		}

		else
		{
			throw new InvalidArgumentException('is_site_default must be TRUE or "y", or FALSE or "n"');
		}
	}

	/**
	 * A getter for the is_site_default property
	 *
	 * @return bool TRUE if this is the default; FALSE if not
	 */
	protected function get__is_site_default()
	{
		return ($this->is_site_default == 'y');
	}

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
	 *
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

	// TODO allow for renaming?
	public function onAfterSave()
	{
		$this->ensureFolderExists();
	}

	/**
	 *
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