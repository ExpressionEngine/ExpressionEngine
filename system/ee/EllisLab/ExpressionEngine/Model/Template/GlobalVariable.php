<?php

namespace EllisLab\ExpressionEngine\Model\Template;

use FilesystemIterator;
use EllisLab\ExpressionEngine\Service\Model\FileSyncedModel;
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
 * ExpressionEngine Global Variable Model
 *
 * @package		ExpressionEngine
 * @subpackage	Template
 * @category	Model
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class GlobalVariable extends FileSyncedModel {

	protected static $_primary_key = 'variable_id';
	protected static $_table_name  = 'global_variables';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'belongsTo'
		)
	);

	protected static $_events = array(
		'afterSave',
	);

	protected $variable_id;
	protected $site_id;
	protected $variable_name;
	protected $variable_data;
	protected $edit_date;

	/**
	 * Get the full filesystem path to the variable file
	 *
	 * @return String Filesystem path to the variable file
	 */
	public function getFilePath()
	{
		if ($this->variable_name == '')
		{
			return NULL;
		}

		$basepath = PATH_TMPL;

		if (ee()->config->item('save_tmpl_files') != 'y' || $basepath == '')
		{
			return NULL;
		}

		$this->ensureFolderExists();

		$path = $this->getFolderPath();
		$file = $this->variable_name;
		$ext  = '.html';

		if ($path == '' || $file == '' || $ext == '')
		{
			return NULL;
		}

		return $path.'/'.$file.$ext;
	}

	/**
	 * Get the old variable path, so that we can delete it if
	 * the path changed.
	 */
	protected function getPreviousFilePath($previous)
	{
		$backup_site = $this->site_id;
		$backup_name = $this->variable_name;

		$previous = array_merge($this->getValues(), $previous);

		$this->site_id = $previous['site_id'];
		$this->variable_name = $previous['variable_name'];

		$path = $this->getFilePath();

		$this->site_id = $backup_site;
		$this->variable_name = $backup_name;

		return $path;
	}

	/**
	 * Get the data to be stored in the file
	 */
	protected function serializeFileData()
	{
		return $this->variable_data;
	}

	/**
	 * Set the model based on the data in the file
	 */
	protected function unserializeFileData($str)
	{
		$this->setProperty('variable_data', $str);
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
	 * Get the full folder path
	 */
	protected function getFolderPath()
	{
		if ($this->variable_name == '')
		{
			return NULL;
		}

		$basepath = PATH_TMPL;

		if (ee()->config->item('save_tmpl_files') != 'y' || $basepath == '')
		{
			return NULL;
		}

		if ($this->site_id == 0)
		{
			return $basepath.'_global_variables';
		}

		if ( ! $site = ee()->session->cache('site/id/' . $this->site_id, 'site'))
		{
			$site = $this->getFrontend()->get('Site')
				->fields('site_name')
				->filter('site_id', $this->site_id)
				->first();

			ee()->session->set_cache('site/id/' . $this->site_id, 'site', $site);
		}

		return $basepath.$site->site_name.'/_variables';
	}

	/**
	 * Make sure the folder exists
	 */
	protected function ensureFolderExists()
	{
		$fs = new Filesystem();
		$path = $this->getFolderPath();

		if (isset($path) && ! $fs->isDir($path))
		{
			$fs->mkDir($path, FALSE);
		}
	}

	/**
	 * Load all variabless available on this site, including global variabless and
	 * any that are currently only available as files.
	 *
	 * @return Collection of variabless
	 */
	public function loadAll()
	{
		$fs = new Filesystem();

		// load up any variables
		$variables = $this->getModelFacade()->get('GlobalVariable')
			->filter('site_id', ee()->config->item('site_id'))
			->orFilter('site_id', 0)
			->all();

		$path_site_ids = array(
			PATH_TMPL.'_global_variables' => 0,
			PATH_TMPL.ee()->config->item('site_short_name').'/_variables' => ee()->config->item('site_id')
		);

		$names = $variables->pluck('variable_name');

		foreach ($path_site_ids as $path => $site_id)
		{
			if ( ! $fs->isDir($path))
			{
				continue;
			}

			$files = new FilesystemIterator($path);

			foreach ($files as $item)
			{
				if ($item->isFile() && $item->getExtension() == 'html')
				{
					$name = $item->getBasename('.html');

					if ( ! in_array($name, $names))
					{
						$contents = file_get_contents($item->getRealPath());

						$new_gv = ee('Model')->make('GlobalVariable', array(
							'site_id' => $site_id,
							'variable_name' => $name,
							'variable_data' => $contents
						));

						$new_gv->setModificationTime($item->getMTime());

						$new_gv->save();
						$variables[] = $new_gv;
					}
				}
			}
		}

		return $variables;
	}

	public function onAfterSave()
	{
		parent::onAfterSave();
		ee()->functions->clear_caching('all');
	}
}

// EOF
