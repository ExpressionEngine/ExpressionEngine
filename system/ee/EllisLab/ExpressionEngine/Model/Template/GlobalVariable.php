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

use FilesystemIterator;
use EllisLab\ExpressionEngine\Service\Model\FileSyncedModel;
use EllisLab\ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Global Variable Model
 */
class GlobalVariable extends FileSyncedModel {

	protected static $_primary_key = 'variable_id';
	protected static $_table_name  = 'global_variables';

	protected static $_hook_id = 'global_variable';

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

		if (ee()->config->item('save_tmpl_files') != 'y' || ee()->config->item('save_tmpl_globals') != 'y' || $basepath == '')
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

		if (ee()->config->item('save_tmpl_files') != 'y' || ee()->config->item('save_tmpl_globals') != 'y' || $basepath == '')
		{
			return NULL;
		}

		if ($this->site_id == 0)
		{
			return $basepath.'_global_variables';
		}

		if ( ! $site = ee()->session->cache('site/id/' . $this->site_id, 'site'))
		{
			$site = $this->getModelFacade()->get('Site')
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
	 * This method is run from a front-end context, so we are sensitive to having as few and light queries as possible.
	 *
	 * @return Collection of variabless
	 */
	public function loadAll()
	{
		// load up any variables
		$variables = $this->getModelFacade()->get('GlobalVariable')
			->filter('site_id', ee()->config->item('site_id'))
			->orFilter('site_id', 0)
			->all();

		$paths = [
			0 => PATH_TMPL.'_global_variables',
			ee()->config->item('site_id') => PATH_TMPL.ee()->config->item('site_short_name').'/_variables',
		];

		$names = $variables->pluck('variable_name');

		foreach ($paths as $site_id => $path)
		{
			foreach ($this->getNewVariablesFromFiles($path, $site_id, $names) as $new)
			{
				$variables[] = $new;
			}
		}

		return $variables;
	}

	/**
	 * Load all variables from the entire installation, including any not yet synced from files.
	 * Kinda brute force, this should not be something run on every request.
	 *
	 * @return object Collection of variables
	 */
	public function loadAllInstallWide()
	{
		$sites = ee('Model')->get('Site')
			->fields('site_id', 'site_name')
			->all();

		// always include the global partials
		$paths = [0 => PATH_TMPL.'_global_variables'];

		foreach ($sites as $site)
		{
			$paths[$site->site_id] = PATH_TMPL.$site->site_name.'/_variables';
		}

		$variables = $this->getModelFacade()->get('GlobalVariable')->all();

		$site_variables = [];
		foreach ($variables as $variable)
		{
			$site_variables[$variable->site_id][] = $variable->variable_name;
		}

		foreach ($paths as $site_id => $path)
		{
			$existing = ( ! empty($site_variables[$site_id])) ? $site_variables[$site_id] : [];
			foreach ($this->getNewVariablesFromFiles($path, $site_id, $existing) as $new)
			{
				$variables[] = $new;
			}
		}

		return $variables;
	}

	/**
	 * Get (and save) new variables from the file system
	 *
	 * @param  string $path Path to load variables from
	 * @param  int $site_id Site ID
	 * @param  array $existing Names of existing variables so we don't make duplicates
	 * @return array All newly created variables
	 */
	private function getNewVariablesFromFiles($path, $site_id, $existing)
	{
		$fs = new Filesystem;
		$variables = [];

		if ( ! $fs->isDir($path))
		{
			return $variables;
		}

		$files = new FilesystemIterator($path);

		foreach ($files as $item)
		{
			if ($item->isFile() && $item->getExtension() == 'html')
			{
				$name = $item->getBasename('.html');
				
				// limited to 50 characters in db
				if (strlen($name) > 50)
				{
					continue;
				}

				if ( ! in_array($name, $existing))
				{
					$contents = file_get_contents($item->getRealPath());

					$variable = $this->getModelFacade()->make('GlobalVariable', [
						'site_id' => $site_id,
						'variable_name' => $name,
						'variable_data' => $contents
					]);

					$variable->setModificationTime($item->getMTime());

					$variable->save();
					$variables[] = $variable;
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
