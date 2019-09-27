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
 * Snippet Model
 */
class Snippet extends FileSyncedModel {

	protected static $_primary_key = 'snippet_id';
	protected static $_table_name = 'snippets';

	protected static $_hook_id = 'snippet';

	protected static $_relationships = array(
		'Site' => array(
			'type' => 'BelongsTo'
		)
	);

	protected static $_events = array(
		'afterSave',
	);

	protected $snippet_id;
	protected $site_id;
	protected $snippet_name;
	protected $snippet_contents;
	protected $edit_date;

	/**
	 * Get the full filesystem path to the snippet file
	 *
	 * @return String Filesystem path to the snippet file
	 */
	public function getFilePath()
	{
		if ($this->snippet_name == '')
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
		$file = $this->snippet_name;
		$ext  = '.html';

		if ($path == '' || $file == '' || $ext == '')
		{
			return NULL;
		}

		return $path.'/'.$file.$ext;
	}

	/**
	 * Get the old snippet path, so that we can delete it if
	 * the path changed.
	 */
	protected function getPreviousFilePath($previous)
	{
		$backup_site = $this->site_id;
		$backup_name = $this->snippet_name;

		$previous = array_merge($this->getValues(), $previous);

		$this->site_id = $previous['site_id'];
		$this->snippet_name = $previous['snippet_name'];

		$path = $this->getFilePath();

		$this->site_id = $backup_site;
		$this->snippet_name = $backup_name;

		return $path;
	}

	/**
	 * Get the data to be stored in the file
	 */
	protected function serializeFileData()
	{
		return $this->snippet_contents;
	}

	/**
	 * Set the model based on the data in the file
	 */
	protected function unserializeFileData($str)
	{
		$this->setProperty('snippet_contents', $str);
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
		if ($this->snippet_name == '')
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
			return $basepath.'_global_partials';
		}

		if ( ! $site = ee()->session->cache('site/id/' . $this->site_id, 'site'))
		{
			$site = $this->getModelFacade()->get('Site')
				->fields('site_name')
				->filter('site_id', $this->site_id)
				->first();

			ee()->session->set_cache('site/id/' . $this->site_id, 'site', $site);
		}

		return $basepath.$site->site_name.'/_partials';
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
	 * Load all snippets available on this site, including global snippets and
	 * any that are currently only available as files.
	 *
	 * This method is run from a front-end context, so we are sensitive to having as few and light queries as possible.
	 *
	 * @return Collection of snippets
	 */
	public function loadAll()
	{
		// load up any Snippets
		$snippets = $this->getModelFacade()->get('Snippet')
			->filter('site_id', ee()->config->item('site_id'))
			->orFilter('site_id', 0)
			->all();

		$paths = [
			0 => PATH_TMPL.'_global_partials',
			ee()->config->item('site_id') => PATH_TMPL.ee()->config->item('site_short_name').'/_partials',
		];

		$names = $snippets->pluck('snippet_name');

		foreach ($paths as $site_id => $path)
		{
			foreach ($this->getNewSnippetsFromFiles($path, $site_id, $names) as $new)
			{
				$snippets[] = $new;
			}
		}

		return $snippets;
	}

	/**
	 * Load all snippets from the entire installation, including any not yet synced from files.
	 * Kinda brute force, this should not be something run on every request.
	 *
	 * @return object Collection of snippets
	 */
	public function loadAllInstallWide()
	{
		$sites = ee('Model')->get('Site')
			->fields('site_id', 'site_name')
			->all();

		// always include the global partials
		$paths = [0 => PATH_TMPL.'_global_partials'];

		foreach ($sites as $site)
		{
			$paths[$site->site_id] = PATH_TMPL.$site->site_name.'/_partials';
		}

		$snippets = $this->getModelFacade()->get('Snippet')->all();

		$site_snippets = [];
		foreach ($snippets as $snippet)
		{
			$site_snippets[$snippet->site_id][] = $snippet->snippet_name;
		}

		foreach ($paths as $site_id => $path)
		{
			$existing = ( ! empty($site_snippets[$site_id])) ? $site_snippets[$site_id] : [];
			foreach ($this->getNewSnippetsFromFiles($path, $site_id, $existing) as $new)
			{
				$snippets[] = $new;
			}
		}

		return $snippets;
	}

	/**
	 * Get (and save) new snippets from the file system
	 *
	 * @param  string $path Path to load snippets from
	 * @param  int $site_id Site ID
	 * @param  array $existing Names of existing snippets so we don't make duplicates
	 * @return array All newly created snippets
	 */
	private function getNewSnippetsFromFiles($path, $site_id, $existing)
	{
		$fs = new Filesystem;
		$snippets = [];

		if ( ! $fs->isDir($path))
		{
			return $snippets;
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

					$snippet = $this->getModelFacade()->make('Snippet', [
						'site_id' => $site_id,
						'snippet_name' => $name,
						'snippet_contents' => $contents
					]);

					$snippet->setModificationTime($item->getMTime());

					$snippet->save();
					$snippets[] = $snippet;
				}
			}
		}

		return $snippets;
	}

	public function onAfterSave()
	{
		parent::onAfterSave();
		ee()->functions->clear_caching('all');
	}
}

// EOF
