<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Cli;

trait CliOptionsTrait
{
    /**
     * Check if an option should be skipped
     *
     * @param array $optionParams
     * @return bool
     */
    private function shouldSkipOption($optionParams)
    {
        return in_array($optionParams['type'], ['radio', 'select']) &&
            (
                !isset($optionParams['choices']) || //no choice
                empty($optionParams['choices']) || // choice is empty
                (count($optionParams['choices']) == 1 && array_key_first($optionParams['choices']) == ($optionParams['default'] ?? null)) // there just 1 choice, which is default
            );
    }

    /**
     * Get option value from user input
     *
     * @param string $option
     * @param array $optionParams
     * @return mixed
     */
    private function getOptionValue($option, $optionParams)
    {
        $default = isset($optionParams['default']) ? $optionParams['default'] : (isset($optionParams['value']) ? $optionParams['value'] : '');
        $required = $optionParams['required'] ?? false;
        $askText = $this->buildAskText($option, $optionParams);

        $optionValue = $this->getOptionOrAsk(
            '--' . $option,
            $askText,
            $default,
            $required
        );

        return $this->processOptionValue($optionValue, $optionParams);
    }

    /**
     * Build the ask text for an option
     *
     * @param string $option
     * @param array $optionParams
     * @return string
     */
    private function buildAskText($option, $optionParams)
    {
        $askText = isset($optionParams['desc']) ? lang($optionParams['desc']) : lang($option);

        if (isset($optionParams['choices']) && !empty($optionParams['choices'])) {
            foreach ($optionParams['choices'] as $key => $val) {
                $askText .= "\n - " . $key . " : " . lang($val);
            }
            if ($optionParams['type'] == 'checkbox') {
                $askText .= "\n\n" . lang('separate_choices_commas') . ":";
            } else {
                $askText .= "\n\n: ";
            }
        }

        return $askText;
    }

    /**
     * Process option value based on its type
     *
     * @param mixed $optionValue
     * @param array $optionParams
     * @return mixed
     */
    private function processOptionValue($optionValue, $optionParams)
    {
        // ensure the checkbox options receive an array
        // comma is expected separator, but we'll also allow | for convenience
        if (isset($optionParams['type']) && $optionParams['type'] == 'checkbox' && !is_array($optionValue)) {
            $optionValue = explode('|', str_replace(',', '|', $optionValue));
            $optionValue = array_map('trim', $optionValue);
        } elseif (is_string($optionValue)) {
            $optionValue = trim($optionValue);
        }

        return $optionValue;
    }

    /**
     * Get MSM site ID based on --site_id option or user prompt
     * @param  string $default 'all' or 'first' or specific site ID
     * @return int|null
     */
    protected function getMsmSiteId($default = 'all', $canBeShared = false)
    {
        $site_id = null;

        if (!bool_config_item('multiple_sites_enabled')) {
            return ee()->config->item('site_id');
        }

        $sites = ee('Model')->get('Site')->all()->getDictionary('site_id', 'site_label');
        if ($canBeShared) {
            $sites = ['0' => lang('command_all_sites')] + $sites;
        }

        switch ($default) {
            case 'first':
                $default = array_key_first($sites);
                $defaultAsText = $sites[$default];
                break;
            case 'all':
                $defaultAsText = lang('command_all_sites');
                $default = ($canBeShared) ? 0 : null;
                break;
            default:
                $defaultAsText = $default;
                break;
        }

        if (count($sites) > 1) {
            if ($this->option('--site')) {
                $site_id = $this->option('--site');
                if ($site_id === 'all') {
                    $site_id = null;
                } elseif ($site_id === 'first') {
                    $site_id = array_key_first($sites);
                }
            } else {
                $site_id = $this->getOptionValue('site_id', [
                    'type' => 'select',
                    'desc' => lang('command_select_msm_site') . ' [' . $defaultAsText . ']',
                    'choices' => $sites,
                    'default' => $default,
                    'required' => false
                ]);
                if (empty($site_id)) {
                    $site_id = null;
                }
            }
        } else {
            $site_id = array_key_first($sites);
        }

        if (!array_key_exists($site_id, $sites) && !empty($site_id)) {
            $this->fail(lang('command_sites_site_not_found'));
        }

        return $site_id;
    }

    /**
     * Get the list of fields for category
     *
     * @param Collection|null $currentCategory
     * @return object
     */
    private function getFieldsForCategories($currentCategory = null)
    {
        $allFields = [];

        if (is_null($currentCategory)) {
            $groupId = $this->data['group_id'];
        } else {
            $groupId = $currentCategory->group_id;
        }

        $existingCategories = ['' => lang('none')];
        $existingCategories = $existingCategories + ee('Model')->get('Category')->fields('cat_id', 'cat_name')->filter('group_id', $groupId)->all()->getDictionary('cat_id', 'cat_name');

        if (is_null($currentCategory)) {
            $currentCategory = ee('Model')->make('Category', ['group_id' => $this->data['group_id']]);
        } else {
            // when editing category, need to ask for group_id too
            $allFields['group_id'] = [
                'type' => 'select',
                'desc' => 'category_group',
                'choices' => ee('Model')->get('CategoryGroup')->fields('group_id', 'group_name')->filter('site_id', $currentCategory->site_id)->all()->getDictionary('group_id', 'group_name'),
                'default' => $currentCategory->group_id,
                'required' => false
            ];
        }

        $allFields = array_merge($allFields, [
            'cat_name' => [
                'type' => 'text',
                'desc' => 'category_name',
                'default' => $currentCategory->cat_name,
                'required' => true
            ],
            'cat_url_title' => [
                'desc' => 'category_url_title',
                'default' => $currentCategory->cat_url_title,
                'required' => true
            ],
            'cat_description' => [
                'type' => 'textarea',
                'desc' => 'category_description',
                'default' => $currentCategory->cat_description,
                'required' => false
            ],
            'cat_image' => [
                'type' => 'text',
                'desc' => 'category_image',
                'default' => $currentCategory->cat_image,
                'required' => false
            ],
            'parent_id' => [
                'type' => 'select',
                'desc' => 'parent_category',
                'choices' => $existingCategories,
                'default' => $currentCategory->parent_id,
                'required' => false
            ],
        ]);

        $customFields = [];
        foreach ($currentCategory->getDisplay()->getFields() as $field) {
            $fieldTechName = 'field_id_' . $field->getId();
            $allFields[$field->getShortName()] = [
                'type' => $field->getTypeName(),
                'desc' => $field->getLabel(),
                'default' => $currentCategory->$fieldTechName,
                'required' => $field->isRequired()
            ];
            if ($field->isOptionFieldtype()) {
                $allFields[$field->getShortName()]['choices'] = $field->getFieldOptions();
            }
            $customFields[$field->getShortName()] = $fieldTechName;
        }

        $fieldsOption = $this->getOptionOrAsk('--fields', lang('command_sites_edit_which_fields'), implode(', ', array_keys($allFields)));
        $fieldsOption = array_map('trim', explode(',', $fieldsOption));

        $fields = [];
        foreach ($fieldsOption as $field) {
            if (array_key_exists($field, $allFields)) {
                $fields[$field] = $allFields[$field];
            } else {
                $fields[$field] = [];
            }
        }

        $this->setupCommandOptions($fields);

        foreach ($fields as $field => $params) {
            $key = array_key_exists($field, $customFields) ? $customFields[$field] : $field;
            $this->data[$key] = $this->getOptionValue($field, $params);
        }

        return $currentCategory;
    }

    /**
     * Get the list of fields for entry
     *
     * @param Collection|null $currentEntry
     * @return object
     */
    private function getFieldsForEntries($currentEntry = null)
    {
        $allFields = [];

        if (is_null($currentEntry)) {
            $currentEntry = ee('Model')->make('ChannelEntry', [
                'channel_id' => $this->data['channel_id'],
                'author_id' => ee('Member')->getDefaultCLIAuthor()->getId()
            ]);
            $isNew = true;
        } else {
            $isNew = false;
        }

        $channel = ee('Model')->get('Channel', $currentEntry->channel_id)->with(['CategoryGroups' => 'Categories'])->all()->first();
        $categories = [];
        foreach ($channel->CategoryGroups as $categoryGroup) {
            foreach ($categoryGroup->Categories as $category) {
                $categories[$category->cat_id] = $category->cat_name . ' (' . $categoryGroup->group_name . ')';
            }
        }

        $customFields = [];
        foreach ($currentEntry->getDisplay()->getFields() as $field) {
            if (strpos($field->getShortName(), 'categories[cat_') === 0) {
                // categories to be handled later
                continue;
            }
            if (in_array($field->getShortName(), [ 'entry_date', 'expiration_date', 'comment_expiration_date', 'channel_id', 'author_id', 'allow_comments', 'versioning_enabled', 'revisions' ])) {
                // these fields are not supported in CLI context
                continue;
            }
            // arrays can't handled in CLI unless it's multiselect or checkbox
            if ($field->hasArrayData() && !in_array($field->getShortName(), ['status']) && ! $field->isOptionFieldtype()) {
                continue;
            }
            // some ft's are 'simple' but we can't handle them here
            if (in_array($field->getType(), ['relationship'])) {
                continue;
            }
            $fieldTechName = is_numeric($field->getId()) ? 'field_id_' . $field->getId() : $field->getId();
            $allFields[$field->getShortName()] = [
                'type' => in_array($field->getType(), ['select', 'checkbox', 'text']) ? $field->getType() : ($field->isOptionFieldtype() ? 'checkbox' : 'text'),
                'desc' => $field->getLabel(),
                'default' => $currentEntry->$fieldTechName,
                'required' => $field->isRequired()
            ];
            if ($field->isOptionFieldtype()) {
                $allFields[$field->getShortName()]['choices'] = $field->getFieldOptions();
            }
            $customFields[$field->getShortName()] = $fieldTechName;
        }

        $allFields['categories'] = [
            'type' => 'checkbox',
            'desc' => 'categories',
            'choices' => $categories,
            'default' => $isNew ? '' : implode(',', $currentEntry->Categories->pluck('cat_id')),
            'required' => false
        ];

        if (isset($allFields['status'])) {
            $allFields['status']['choices'] = $channel->Statuses->sortBy('status_order')->getDictionary('status', 'status');
            $allFields['status']['default'] = $currentEntry->status ?? $channel->deft_status;
        }

        $fieldsOption = $this->getOptionOrAsk('--fields', lang('command_sites_edit_which_fields'), implode(', ', array_keys($allFields)));
        $fieldsOption = array_map('trim', explode(',', $fieldsOption));

        $fields = [];
        foreach ($fieldsOption as $field) {
            if (array_key_exists($field, $allFields)) {
                $fields[$field] = $allFields[$field];
            } else {
                $fields[$field] = [];
            }
        }

        $this->setupCommandOptions($fields);

        foreach ($fields as $field => $params) {
            $key = array_key_exists($field, $customFields) ? $customFields[$field] : $field;
            $this->data[$key] = $this->getOptionValue($field, $params);
        }

        return $currentEntry;
    }

    /**
     * Get the list of fields for member
     *
     * @param Collection|null $currentMember
     * @return object
     */
    private function getFieldsForMembers($currentMember = null)
    {
        $allFields = [];
        ee()->load->library('session');

        if (is_null($currentMember)) {
            $currentMember = ee('Model')->make('Member');
            $currentMember->language = ee()->config->item('deft_lang') ?: 'english';
            $isNew = true;
        } else {
            $isNew = false;
        }

        $allFields = [
            'username' => [
                'type' => 'text',
                'desc' => 'command_members_username',
                'default' => $currentMember->username,
                'required' => true
            ],
            'email' => [
                'type' => 'text',
                'desc' => 'command_members_email',
                'default' => $currentMember->email,
                'required' => true
            ],
            'password' => [
                'type' => 'text',
                'desc' => 'command_members_password',
                'default' => '',
                'required' => $isNew
            ],
            'password_confirm' => [
                'type' => 'text',
                'desc' => 'command_members_password_confirm',
                'default' => '',
                'required' => $isNew
            ],
            'screen_name' => [
                'type' => 'text',
                'desc' => 'command_members_screen_name',
                'default' => $currentMember->screen_name,
                'required' => true
            ],
            'role_id' => [
                'type' => 'select',
                'desc' => 'command_members_role',
                'choices' => ee('Model')->get('Role')->all()->getDictionary('role_id', 'name'),
                'default' => $currentMember->role_id,
                'required' => true
            ],
        ];

        $customFields = [];
        foreach ($currentMember->getDisplay()->getFields() as $field) {
            $fieldTechName = 'm_field_id_' . $field->getId();
            $allFields[$field->getShortName()] = [
                'type' => in_array($field->getType(), ['select', 'checkbox', 'text']) ? $field->getType() : ($field->isOptionFieldtype() ? 'checkbox' : 'text'),
                'desc' => $field->getLabel(),
                'default' => $currentMember->$fieldTechName,
                'required' => $field->isRequired()
            ];
            if ($field->isOptionFieldtype()) {
                $allFields[$field->getShortName()]['choices'] = $field->getFieldOptions();
            }
            $customFields[$field->getShortName()] = $fieldTechName;
        }

        $fieldsOption = $this->getOptionOrAsk('--fields', lang('command_members_edit_which_fields'), implode(', ', array_keys($allFields)));
        $fieldsOption = array_map('trim', explode(',', $fieldsOption));

        $fields = [];
        foreach ($fieldsOption as $field) {
            if (array_key_exists($field, $allFields)) {
                $fields[$field] = $allFields[$field];
            } else {
                $fields[$field] = [];
            }
        }

        $this->setupCommandOptions($fields);

        foreach ($fields as $field => $params) {
            $key = array_key_exists($field, $customFields) ? $customFields[$field] : $field;
            $this->data[$key] = $this->getOptionValue($field, $params);
        }

        if (empty($this->data['screen_name'])) {
            $this->data['screen_name'] = $this->data['username'];
        }

        if (isset($this->data['password']) && empty($this->data['password']) && !$isNew) {
            unset($this->data['password']);
            unset($this->data['password_confirm']);
        }

        return $currentMember;
    }

    /**
     * Get the list of fields for upload directory
     *
     * @param Collection|null $currentUploadDirectory
     * @return object
     */
    private function getFieldsForUploadDirectories($currentUploadDirectory = null)
    {
        $isNew = false;
        if (is_null($currentUploadDirectory)) {
            $currentUploadDirectory = ee('Model')->make('UploadDestination', [
                'adapter' => 'local',
                'site_id' => $this->data['site_id']
            ]);
            $isNew = true;
        }
        $this->data['adapter'] = $currentUploadDirectory->adapter;

        $fields = [];

        $this->data['name'] = $this->getOptionOrAsk('--name', lang('command_upload_directories_name'), $currentUploadDirectory->name, true);

        if (!bool_config_item('file_manager_compatibility_mode')) {
            $adapters = ee('Filesystem/Adapter')->all();
            if (count($adapters) > 1) {
                $adapterChoices = [];
                foreach (array_keys($adapters) as $key) {
                    $adapterChoices[$key] = lang('adapter_' . $key);
                }
                $this->data['adapter'] = $this->getOptionValue('adapter', [
                    'type' => 'select',
                    'desc' => 'command_upload_directories_adapter',
                    'choices' => $adapterChoices,
                    'default' => $currentUploadDirectory->adapter,
                    'required' => true,
                ]);
            }

            $this->data['allow_subfolders'] = $this->getOptionValue('allow_subfolders', [
                'type' => 'select',
                'choices' => array(
                    'y' => lang('yes'),
                    'n' => lang('no')
                ),
                'default' => !$isNew ? $currentUploadDirectory->getRawProperty('allow_subfolders') : 'n'
            ]);

            if ($this->data['allow_subfolders'] == 'y') {
                $fields['subfolders_on_top'] = [
                    'type' => 'select',
                    'desc' => 'keep_subfolders_top',
                    'choices' => [
                        'y' => lang('yes'),
                        'n' => lang('no')
                    ],
                    'default' => !$isNew ? $currentUploadDirectory->getRawProperty('subfolders_on_top') : 'n'
                ];
            }
        }

        $adapterSettings = array_merge([
            'url' => $currentUploadDirectory->getConfigOverriddenProperty('url'),
            'server_path' => $currentUploadDirectory->getConfigOverriddenProperty('server_path'),
        ], $currentUploadDirectory->getProperty('adapter_settings') ?? []);
        $adapterFields = ee('Filesystem/Adapter')->createSettingsFields($this->data['adapter'], $adapterSettings);
        if (!empty($adapterFields)) {
            foreach ($adapterFields as $adapterField) {
                $fields += $adapterField['fields'];
            }
        }

        $fileTypes = array_filter(array_keys(ee()->config->loadFile('mimes')), 'is_string');
        $allowed_types = ['all' => lang('type_all')];
        foreach ($fileTypes as $type) {
            $allowed_types[$type] = lang('type_' . $type);
        }

        $fields['allowed_types'] = [
            'type' => 'checkbox',
            'desc' => 'upload_allowed_types',
            'choices' => $allowed_types,
            'default' => !$isNew ? implode(', ', $currentUploadDirectory->allowed_types) : 'all',
            'required' => true,
        ];

        $fields['default_modal_view'] = [
            'type' => 'select',
            'choices' => array(
                'list' => lang('default_modal_view_list'),
                'thumb' => lang('default_modal_view_thumbnails')
            ),
            'default' => !$isNew ? $currentUploadDirectory->default_modal_view : 'list'
        ];

        $fields['max_size'] = [
            'desc' => 'upload_file_size',
            'type' => 'text',
            'default' => $currentUploadDirectory->max_size
        ];

        $fields['max_width'] = [
            'desc' => 'upload_image_width',
            'type' => 'text',
            'default' => $currentUploadDirectory->max_width
        ];

        $fields['max_height'] = [
            'desc' => 'upload_image_height',
            'type' => 'text',
            'default' => $currentUploadDirectory->max_height
        ];

        $roles = ee('Model')->get('Role')
            ->filter('role_id', 'NOT IN', array(1,2,3,4))
            ->order('name')
            ->all()
            ->getDictionary('role_id', 'name');

        $fields['upload_roles'] = [
            'type' => 'checkbox',
            'choices' => $roles,
            'default' => !$isNew ? implode(', ', $currentUploadDirectory->Roles->pluck('role_id')) : ''
        ];

        $cat_group_options = ee('Model')
            ->get('CategoryGroup')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('exclude_group', '!=', '2')
            ->all()
            ->getDictionary('group_id', 'group_name');

        $fields['cat_group'] = [
            'type' => 'checkbox',
            'desc' => 'upload_category_groups',
            'choices' => $cat_group_options,
            'default' => !$isNew ? implode(', ', $currentUploadDirectory->CategoryGroups->pluck('group_id')) : ''
        ];

        $this->setupCommandOptions($fields);

        foreach ($fields as $field => $params) {
            $this->data[$field] = $this->getOptionValue($field, $params);
        }

        if (isset($this->data['upload_roles'])) {
            $currentUploadDirectory->Roles = ee('Model')->get('Role', $this->data['upload_roles'])->all();
            unset($this->data['upload_roles']);
        }

        if (isset($this->data['cat_group'])) {
            $currentUploadDirectory->CategoryGroups = ee('Model')->get('CategoryGroup', $this->data['cat_group'])->all();
            
        }

        return $currentUploadDirectory;
    }


    /**
     * Validate model in CLI context
     *
     * @param [type] $model
     * @return void
     */
    private function validateModel($model, $failMessage = 'error')
    {
        $validation = $model->validate();
        if ($validation->failed()) {
            foreach ($validation->getAllErrors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->error($field . ' : ' . $message);
                }
            }
            $this->fail($failMessage);
        }
    }
}