<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Template;

use FilesystemIterator;
use ExpressionEngine\Service\Model\FileSyncedModel;
use ExpressionEngine\Library\Filesystem\Filesystem;

/**
 * Snippet Model
 */
class Snippet extends FileSyncedModel
{
    protected static $_primary_key = 'snippet_id';
    protected static $_table_name = 'snippets';

    protected static $_hook_id = 'snippet';

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'BelongsTo'
        )
    );

    protected static $_validation_rules = array(
        'snippet_name' => 'required|max_length[50]|validateSnippetName',
    );

    protected static $_events = array(
        'afterSave',
        'afterDelete'
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
        if ($this->snippet_name == '') {
            return null;
        }

        $basepath = PATH_TMPL;

        if (ee()->config->item('save_tmpl_files') != 'y' || $basepath == '') {
            return null;
        }

        $this->ensureFolderExists();

        $path = $this->getFolderPath();
        $file = $this->snippet_name;
        $ext = '.html';

        if ($path == '' || $file == '' || $ext == '') {
            return null;
        }

        return $path . '/' . $file . $ext;
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
        if ($this->snippet_name == '') {
            return null;
        }

        $basepath = PATH_TMPL;

        if (ee()->config->item('save_tmpl_files') != 'y' || $basepath == '') {
            return null;
        }

        if ($this->site_id == 0) {
            return $basepath . '_global_partials';
        }

        if (!isset(ee()->session) || ! $site = ee()->session->cache('site/id/' . $this->site_id, 'site')) {
            $sites = ee('Model')->get('Site')
                ->fields('site_id', 'site_name')
                ->all(true);
            $site = $sites->filter('site_id', $this->site_id)->first();

            if (isset(ee()->session)) {
                ee()->session->set_cache('site/id/' . $this->site_id, 'site', $site);
            }
        }

        return $basepath . $site->site_name . '/_partials';
    }

    /**
     * Make sure the folder exists
     */
    protected function ensureFolderExists()
    {
        $path = $this->getFolderPath();

        if (isset($path) && ! ee('Filesystem')->isDir($path)) {
            ee('Filesystem')->mkDir($path, false);
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
        $paths = [
            0 => PATH_TMPL . '_global_partials',
            ee()->config->item('site_id') => PATH_TMPL . ee()->config->item('site_short_name') . '/_partials',
        ];

        foreach ($paths as $path) {
            try {
                ee('Filesystem')->getDirectoryContents($path, true, true);
            } catch (\Exception $e) {
                //silently continue
            }
        }

        // load up any Snippets
        $snippets = $this->getModelFacade()->get('Snippet')
            ->filter('site_id', ee()->config->item('site_id'))
            ->orFilter('site_id', 0)
            ->all();

        $names = $snippets->pluck('snippet_name');

        foreach ($paths as $site_id => $path) {
            foreach ($this->getNewSnippetsFromFiles($path, $site_id, $names) as $new) {
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
            ->all(true);

        // always include the global partials
        $paths = [0 => PATH_TMPL . '_global_partials'];

        foreach ($sites as $site) {
            $paths[$site->site_id] = PATH_TMPL . $site->site_name . '/_partials';
        }

        $snippets = $this->getModelFacade()->get('Snippet')->all();

        $site_snippets = [];
        foreach ($snippets as $snippet) {
            $site_snippets[$snippet->site_id][] = $snippet->snippet_name;
        }

        foreach ($paths as $site_id => $path) {
            $existing = (! empty($site_snippets[$site_id])) ? $site_snippets[$site_id] : [];
            foreach ($this->getNewSnippetsFromFiles($path, $site_id, $existing) as $new) {
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
        if (ee()->config->item('save_tmpl_files') != 'y') {
            return [];
        }

        $snippets = [];

        if (! ee('Filesystem')->isDir($path)) {
            return $snippets;
        }

        $files = new FilesystemIterator($path);

        foreach ($files as $item) {
            if ($item->isFile() && $item->getExtension() == 'html') {
                $name = $item->getBasename('.html');

                // limited to 75 characters in db
                if (strlen($name) > 75) {
                    continue;
                }

                if (! in_array($name, $existing)) {
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

    public function onAfterDelete()
    {
        parent::onAfterDelete();
        ee()->functions->clear_caching('all');
    }

    /**
     *	 Check Snippet Name
    */
    public function validateSnippetName($key, $value, array $params = array())
    {
        if (! preg_match("#^[a-zA-Z0-9_\-/]+$#i", (string) $value)) {
            return 'illegal_characters';
        }

        if (in_array($value, ee()->cp->invalid_custom_field_names())) {
            return 'reserved_name';
        }

        $snippets = ee('Model')->get('Snippet');
        if ((int) $this->site_id === 0) {
            $snippets->filter('site_id', 'IN', [ee()->config->item('site_id'), 0]);
        } else {
            $snippets->filter('site_id', ee()->config->item('site_id'));
        }
        $count = $snippets->filter('snippet_name', $value)->count();

        if ((strtolower((string) $this->getBackup($key)) != strtolower((string) $value)) and $count > 0) {
            return 'snippet_name_taken';
        } elseif ($count > 1) {
            return 'snippet_name_taken';
        }

        return true;
    }
}

// EOF
