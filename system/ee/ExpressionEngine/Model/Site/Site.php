<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Model\Site;

use ExpressionEngine\Service\Model\Model;

/**
 * ExpressionEngine Site Table
 *
 * The Site model stores preference sets for each site in this installation
 * of ExpressionEngine.  Each site can have a completely different set of
 * settings and prefereces.
 */
class Site extends Model
{
    protected static $_primary_key = 'site_id';
    protected static $_table_name = 'sites';

    protected static $_hook_id = 'site';

    protected static $_type_classes = array(
        'ChannelPreferences' => 'ExpressionEngine\Model\Site\Column\ChannelPreferences',
        'MemberPreferences' => 'ExpressionEngine\Model\Site\Column\MemberPreferences',
        'SystemPreferences' => 'ExpressionEngine\Model\Site\Column\SystemPreferences',
        'TemplatePreferences' => 'ExpressionEngine\Model\Site\Column\TemplatePreferences',
    );

    protected static $_typed_columns = array(
        'site_bootstrap_checksums' => 'base64Serialized',
        'site_pages' => 'base64Serialized',
    );

    protected static $_relationships = array(
        'GlobalVariables' => array(
            'model' => 'GlobalVariable',
            'type' => 'hasMany'
        ),
        'Stats' => array(
            'type' => 'HasOne'
        ),
        'TemplateGroups' => array(
            'model' => 'TemplateGroup',
            'type' => 'hasMany'
        ),
        'Templates' => array(
            'model' => 'Template',
            'type' => 'hasMany'
        ),
        'SpecialtyTemplates' => array(
            'model' => 'SpecialtyTemplate',
            'type' => 'hasMany'
        ),
        'SearchLogs' => array(
            'model' => 'SearchLog',
            'type' => 'hasMany'
        ),
        'CpLogs' => array(
            'model' => 'CpLog',
            'type' => 'hasMany'
        ),
        'Channels' => array(
            'model' => 'Channel',
            'type' => 'hasMany'
        ),
        'ChannelEntries' => array(
            'model' => 'ChannelEntry',
            'type' => 'hasMany'
        ),
        'Comments' => array(
            'type' => 'hasMany',
            'model' => 'Comment'
        ),
        'Files' => array(
            'model' => 'File',
            'type' => 'hasMany'
        ),
        'UploadDestinations' => array(
            'model' => 'UploadDestination',
            'type' => 'hasMany'
        ),
        'FileDimensions' => array(
            'model' => 'FileDimension',
            'type' => 'hasMany'
        ),
        'Permissions' => array(
            'model' => 'Permission',
            'type' => 'hasMany'
        ),
        'HTMLButtons' => array(
            'model' => 'HTMLButton',
            'type' => 'hasMany'
        ),
        'Snippets' => array(
            'model' => 'Snippet',
            'type' => 'hasMany'
        ),
        'Configs' => array(
            'model' => 'Config',
            'type' => 'hasMany'
        ),
        'RoleSettings' => array(
            'model' => 'RoleSetting',
            'type' => 'hasMany'
        ),
        'CategoryGroups' => array(
            'model' => 'CategoryGroup',
            'type' => 'hasMany'
        ),
        'Categories' => array(
            'model' => 'Category',
            'type' => 'hasMany'
        ),
        'ChannelFieldGroups' => array(
            'model' => 'ChannelFieldGroup',
            'type' => 'hasMany'
        ),
        'ChannelFields' => array(
            'model' => 'ChannelField',
            'type' => 'hasMany'
        ),
        'ChannelLayouts' => array(
            'model' => 'ChannelLayout',
            'type' => 'hasMany'
        ),
    );

    protected static $_validation_rules = array(
        'site_name' => 'required|validateShortName|unique',
        'site_label' => 'required',
        'site_color' => 'hexColor'
    );

    protected static $_events = array(
        'beforeInsert',
        'afterInsert',
        'afterSave'
    );

    // Properties
    protected $site_id;
    protected $site_label;
    protected $site_name;
    protected $site_description;
    protected $site_color;
    protected $site_bootstrap_checksums;
    protected $site_pages;

    public function validateShortName($key, $value, $params, $rule)
    {
        if (preg_match('/[^a-z0-9\-\_]/i', (string) $value)) {
            return 'invalid_short_name';
        }

        return true;
    }

    public function onBeforeInsert()
    {
        $current_number_of_sites = $this->getModelFacade()->get('Site')->count();

        $can_add = ee('License')->getEELicense()
            ->canAddSites($current_number_of_sites);

        if (! $can_add) {
            throw new \Exception("Site limit reached.");
        }
    }

    public function onAfterInsert()
    {
        $this->setDefaultPreferences('system');
        $this->setDefaultPreferences('channel');
        $this->setDefaultPreferences('template');
        $this->setDefaultPreferences('member');

        $this->createNewStats();
        $this->createHTMLButtons();
        $this->createSpecialtyTemplates();
        $this->copyPermissions();
        $this->copyRoleSettings();
    }

    public function onAfterSave()
    {
        ee()->cache->delete('/site_pages/', \Cache::GLOBAL_SCOPE);
        ee('CP/JumpMenu')->clearAllCaches();
    }

    /**
     * Given a type loops through config's divination method and sets the
     * default property values as indicated.
     *
     * @param string $type The type of preference ('system', 'channel', 'template', or 'member')
     * @return void
     */
    protected function setDefaultPreferences($type)
    {
        foreach (ee()->config->divination($type) as $key) {
            $this->getModelFacade()->make('Config', [
                'site_id' => $this->site_id,
                'key' => $key,
                'value' => ee()->config->item($key)
            ])->save();
        }
    }

    /**
     * Creates a new Stats object for this site. If this is not site id 1 then
     * it will copy the member stats, since those are not site specific.
     *
     * @return void
     */
    protected function createNewStats()
    {
        $data = array(
            'site_id' => $this->site_id
        );

        if ($this->site_id != 1) {
            $stats = $this->getModelFacade()->get('Stats')
                ->fields('total_members', 'recent_member_id', 'recent_member')
                ->filter('site_id', 1)
                ->first();

            $data['total_members'] = $stats->total_members;
            $data['recent_member_id'] = $stats->recent_member_id;
            $data['recent_member'] = $stats->recent_member;
        }

        $this->getModelFacade()->make('Stats', $data)->save();
    }

    /**
     * Creates HTML buttons for this site by cloning site 1's default HTML
     * buttons.
     *
     * @return void
     */
    protected function createHTMLButtons()
    {
        $buttons = $this->getModelFacade()->get('HTMLButton')
            ->filter('site_id', 1)
            ->filter('member_id', 0)
            ->all();

        foreach ($buttons as $button) {
            $data = $button->getValues();
            unset($data['id']);
            $data['site_id'] = $this->site_id;

            $this->getModelFacade()->make('HTMLButton', $data)->save();
        }
    }

    /**
     * Creates specialty templates for this site by cloning site 1's specialty
     * templates.
     *
     * @return void
     */
    protected function createSpecialtyTemplates()
    {
        $templates = $this->getModelFacade()->get('SpecialtyTemplate')
            ->filter('site_id', 1)
            ->all();

        foreach ($templates as $template) {
            $data = $template->getValues();
            unset($data['template_id']);
            $data['site_id'] = $this->site_id;

            $this->getModelFacade()->make('SpecialtyTemplate', $data)->save();
        }
    }

    /**
     * Creates permissions for this site by cloning site 1's permissions
     *
     * @return void
     */
    protected function copyPermissions()
    {
        $permissions = $this->getModelFacade()->get('Permission')
            ->filter('site_id', 1)
            ->all();

        foreach ($permissions as $permission) {
            $data = $permission->getValues();
            $data['site_id'] = $this->site_id;

            $this->getModelFacade()->make('Permission', $data)->save();
        }
    }

    /**
     * Creates RoleSettings for this site by cloning site 1's RoleSettings
     *
     * @return void
     */
    protected function copyRoleSettings()
    {
        $role_settings = $this->getModelFacade()->get('RoleSetting')
            ->filter('site_id', 1)
            ->all();

        foreach ($role_settings as $role_setting) {
            $data = $role_setting->getValues();
            $data['site_id'] = $this->site_id;
            $data['role_id'] = $role_setting->role_id;

            $this->getModelFacade()->make('RoleSetting', $data)->save();
        }
    }
}

// EOF
