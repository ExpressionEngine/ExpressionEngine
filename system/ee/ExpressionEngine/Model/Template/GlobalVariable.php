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
 * Global Variable Model
 */
class GlobalVariable extends FileSyncedModel
{
    protected static $_primary_key = 'variable_id';
    protected static $_table_name = 'global_variables';

    protected static $_hook_id = 'global_variable';

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'belongsTo'
        )
    );

    protected static $_events = array(
        'afterSave',
    );

    protected static $_validation_rules = array(
        'variable_name' => 'required|max_length[50]|validateVariableName',
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
        if ($this->variable_name == '') {
            return null;
        }

        $basepath = PATH_TMPL;

        if (ee()->config->item('save_tmpl_files') != 'y' || ee()->config->item('save_tmpl_globals') != 'y' || $basepath == '') {
            return null;
        }

        $this->ensureFolderExists();

        $path = $this->getFolderPath();
        $file = $this->variable_name;
        $ext = '.html';

        if ($path == '' || $file == '' || $ext == '') {
            return null;
        }

        return $path . '/' . $file . $ext;
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
        if ($this->variable_name == '') {
            return null;
        }

        $basepath = PATH_TMPL;

        if (ee()->config->item('save_tmpl_files') != 'y' || ee()->config->item('save_tmpl_globals') != 'y' || $basepath == '') {
            return null;
        }

        if ($this->site_id == 0) {
            return $basepath . '_global_variables';
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

        return $basepath . $site->site_name . '/_variables';
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
     * Load all variabless available on this site, including global variabless and
     * any that are currently only available as files.
     *
     * This method is run from a front-end context, so we are sensitive to having as few and light queries as possible.
     *
     * @return Collection of variabless
     */
    public function loadAll()
    {
        $paths = [
            0 => PATH_TMPL . '_global_variables',
            ee()->config->item('site_id') => PATH_TMPL . ee()->config->item('site_short_name') . '/_variables',
        ];

        foreach ($paths as $path) {
            try {
                ee('Filesystem')->getDirectoryContents($path, true, true);
            } catch (\Exception $e) {
                //silently continue
            }
        }

        // load up any variables
        $variables = $this->getModelFacade()->get('GlobalVariable')
            ->filter('site_id', ee()->config->item('site_id'))
            ->orFilter('site_id', 0)
            ->all();

        $names = $variables->pluck('variable_name');

        foreach ($paths as $site_id => $path) {
            foreach ($this->getNewVariablesFromFiles($path, $site_id, $names) as $new) {
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
            ->all(true);

        // always include the global partials
        $paths = [0 => PATH_TMPL . '_global_variables'];

        foreach ($sites as $site) {
            $paths[$site->site_id] = PATH_TMPL . $site->site_name . '/_variables';
        }

        $variables = $this->getModelFacade()->get('GlobalVariable')->all();

        $site_variables = [];
        foreach ($variables as $variable) {
            $site_variables[$variable->site_id][] = $variable->variable_name;
        }

        foreach ($paths as $site_id => $path) {
            $existing = (! empty($site_variables[$site_id])) ? $site_variables[$site_id] : [];
            foreach ($this->getNewVariablesFromFiles($path, $site_id, $existing) as $new) {
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
        if (ee()->config->item('save_tmpl_files') != 'y' || ee()->config->item('save_tmpl_globals') != 'y') {
            return [];
        }
        
        $variables = [];

        if (! ee('Filesystem')->isDir($path)) {
            return $variables;
        }

        $files = new FilesystemIterator($path);

        foreach ($files as $item) {
            if ($item->isFile() && $item->getExtension() == 'html') {
                $name = $item->getBasename('.html');

                // limited to 50 characters in db
                if (strlen($name) > 50) {
                    continue;
                }

                if (! in_array($name, $existing)) {
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

    public function onAfterDelete()
    {
        parent::onAfterDelete();
        ee()->functions->clear_caching('all');
    }

    /**
     *	 Check GlobalVariable Name
    */
    public function validateVariableName($key, $value, array $params = array())
    {
        if (! preg_match("#^[a-zA-Z0-9_\-/]+$#i", (string) $value)) {
            return 'illegal_characters';
        }

        if (in_array($value, ee()->cp->invalid_custom_field_names())) {
            return 'reserved_name';
        }

        $variables = ee('Model')->get('GlobalVariable');
        if ((int) $this->site_id === 0) {
            $variables->filter('site_id', 'IN', [ee()->config->item('site_id'), 0]);
        } else {
            $variables->filter('site_id', ee()->config->item('site_id'));
        }
        $count = $variables->filter('variable_name', $value)->count();

        if ((strtolower((string) $this->getBackup($key)) != strtolower((string) $value)) and $count > 0) {
            return 'variable_name_taken';
        } elseif ($count > 1) {
            return 'variable_name_taken';
        }

        return true;
    }
}

// EOF
