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

use ExpressionEngine\Core\Request;
use ExpressionEngine\Library\Resource\Request as ResourceRequest;
use ExpressionEngine\Service\Model\FileSyncedModel;

/**
 * Template Model
 *
 * A model representing a template.  Templates contain a mix of EECode and HTML
 * and are parsed to become the front end pages of sites built with
 * ExpressionEngine.
 */
class Template extends FileSyncedModel
{
    protected static $_primary_key = 'template_id';
    protected static $_table_name = 'templates';

    protected static $_hook_id = 'template';

    protected static $_typed_columns = array(
        'cache' => 'boolString',
        'enable_http_auth' => 'boolString',
        'allow_php' => 'boolString',
        'protect_javascript' => 'boolString',
        'refresh' => 'int',
        'hits' => 'int',
    );

    protected static $_relationships = array(
        'Site' => array(
            'type' => 'BelongsTo'
        ),
        'TemplateGroup' => array(
            'type' => 'BelongsTo'
        ),
        'LastAuthor' => array(
            'type' => 'BelongsTo',
            'model' => 'Member',
            'from_key' => 'last_author_id',
            'weak' => true
        ),
        'Roles' => array(
            'type' => 'HasAndBelongsToMany',
            'model' => 'Role',
            'pivot' => array(
                'table' => 'templates_roles',
                'left' => 'template_id',
                'right' => 'role_id'
            )
        ),
        'TemplateRoute' => array(
            'type' => 'HasOne'
        ),
        'DeveloperLogItems' => array(
            'type' => 'hasMany',
            'model' => 'DeveloperLog'
        ),
        'Versions' => array(
            'type' => 'hasMany',
            'model' => 'RevisionTracker',
            'to_key' => 'item_id',
        )
    );

    protected static $_validation_rules = array(
        'site_id' => 'required|isNatural',
        'group_id' => 'required|isNatural',
        'template_name' => 'required|unique[group_id]|alphaDashPeriodEmoji|validateTemplateName',
        'template_type' => 'required',
        'cache' => 'enum[y,n]',
        'refresh' => 'isNatural',
        'enable_http_auth' => 'enum[y,n]',
        'allow_php' => 'enum[y,n]',
        'php_parse_location' => 'enum[i,o]',
        'hits' => 'isNatural',
        'protect_javascript' => 'enum[y,n]',
        'enable_frontedit' => 'enum[y,n]',
    );

    protected static $_events = array(
        'beforeInsert',
        'afterSave',
    );

    protected $template_id;
    protected $site_id;
    protected $group_id;
    protected $template_name;
    protected $template_type;
    protected $template_engine;
    protected $template_data;
    protected $template_notes;
    protected $edit_date;
    protected $last_author_id;
    protected $cache;
    protected $refresh;
    protected $no_auth_bounce;
    protected $enable_http_auth;
    protected $allow_php;
    protected $php_parse_location;
    protected $hits;
    protected $protect_javascript;
    protected $enable_frontedit;

    /**
     * Returns the path to this template i.e. "site/index"
     *
     * @return string The path to this template
     */
    public function getPath()
    {
        $groupName = !is_null($this->getTemplateGroup()) ? $this->getTemplateGroup()->group_name : '';

        return $groupName . '/' . $this->template_name;
    }

    /**
     * Get the full filesystem path to the template file
     *
     * @return String Filesystem path to the template file
     */
    public function getFilePath()
    {
        static $group;

        if (ee()->config->item('save_tmpl_files') != 'y') {
            return null;
        }

        if (! $group || $group->group_id != $this->group_id) {
            $group = $this->getTemplateGroup();
        }

        if (! isset($group)) {
            return null;
        }

        $group->ensureFolderExists();

        $path = $group->getFolderPath();
        $file = $this->template_name;
        $ext = $this->getFileExtension();

        if ($path == '' || $file == '' || $ext == '') {
            return null;
        }

        return $path . '/' . $file . $ext;
    }

    /**
     * Get the data to be stored in the file
     */
    protected function serializeFileData()
    {
        return $this->template_data;
    }

    /**
     * Set the model based on the data in the file
     */
    protected function unserializeFileData($str)
    {
        $this->setProperty('template_data', $str);
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
     * Get the file extension for a given template type
     *
     * @param String $template_type Used by onAfterUpdate to divine the old path
     * @return String File extension (including the .)
     */
    public function getFileExtension($template_type = null)
    {
        $type = $template_type ?: $this->template_type;
        $engine = $this->template_engine ?: null;

        ee()->load->library('api');
        ee()->legacy_api->instantiate('template_structure');

        return ee()->api_template_structure->file_extensions($type, $engine);
    }

    /**
     * Get the old template path, so that we can delete it if
     * the path changed.
     */
    protected function getPreviousFilePath($prev)
    {
        $values = $this->getValues();
        $parts = array_merge($values, $prev);

        if ($parts['group_id'] != $this->group_id) {
            // TODO there must be a better way
            $group = $this->getModelFacade()->get('TemplateGroup', $parts['group_id'])->first();
        } else {
            $group = $this->getTemplateGroup();
        }

        $path = $group->getFolderPath();
        $file = $parts['template_name'];

        ee()->load->library('api');
        ee()->legacy_api->instantiate('template_structure');

        $ext = ee()->api_template_structure->file_extensions($parts['template_type'], $parts['template_engine'] ?? null);

        if ($path == '' || $file == '' || $ext == '') {
            return null;
        }

        return $path . '/' . $file . $ext;
    }

    /**
     * Saves a new template revision and rotates revisions based on 'max_tmpl_revisions' config item
     */
    public function saveNewTemplateRevision()
    {
        if (! bool_config_item('save_tmpl_revisions')) {
            return;
        }

        // Create the new version
        $version = ee('Model')->make('RevisionTracker');
        $version->Template = $this;
        $version->item_table = 'exp_templates';
        $version->item_field = 'template_data';
        $version->item_data = $this->template_data;
        $version->item_date = ee()->localize->now;
        if (!empty($this->last_author_id)) {
            $version->item_author_id = $this->last_author_id;
        } else {
            $version->item_author_id = 0;
        }
        $version->save();

        // Now, rotate template revisions based on 'max_tmpl_revisions' config item
        $versions = ee('Model')->get('RevisionTracker')
            ->filter('item_id', $this->getId())
            ->filter('item_field', 'template_data')
            ->order('item_date', 'desc')
            ->limit(ee()->config->item('max_tmpl_revisions'))
            ->all();

        // Reassign versions and delete the leftovers
        $this->Versions = $versions;
        $this->save();
    }

    /**
     * Validates the template name checking for reserved names.
     */
    public function validateTemplateName($key, $value, $params, $rule)
    {
        $reserved_names = array('act', 'css', 'js');

        if (in_array($value, $reserved_names)) {
            return 'reserved_name';
        }

        return true;
    }

    public function onBeforeInsert()
    {
        if (!isset($this->Roles) || is_null($this->Roles)) {
            $this->Roles = $this->getModelFacade()->get('Role')->fields('role_id')->all();
        }
    }

    public function onAfterSave()
    {
        parent::onAfterSave();
        ee()->functions->clear_caching('all');

        if (in_array($this->template_type, ResourceRequest::TYPES)) {
            ee('Resource')->clear_cache($this->getPath());
        }
    }

    /**
     * Manually clean out no_auth_bounce template field after delete.
     */
    public function onAfterDelete()
    {
        parent::onAfterDelete();

        $updatable = $this->getModelFacade()->get('Template')
            ->filter('no_auth_bounce', $this->template_id)
            ->all();

        if ($updatable) {
            $updatable->no_auth_bounce = '';
            $updatable->save();
        }
    }
}

// EOF
