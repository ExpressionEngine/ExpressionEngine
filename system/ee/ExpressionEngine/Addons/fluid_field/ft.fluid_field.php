<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Fluid Field Fieldtype
 */
class Fluid_field_ft extends EE_Fieldtype
{
    public $info = array();

    public $has_array_data = true;

    public $complex_data_structure = true;

    public $size = 'large';

    private $errors;

    /**
     * Fetch the fieldtype's name and version from its addon.setup.php file.
     */
    public function __construct()
    {
        $addon = ee('Addon')->get('fluid_field');
        $this->info = array(
            'name' => $addon->getName(),
            'version' => $addon->getVersion()
        );

        $this->errors = new \ExpressionEngine\Service\Validation\Result();
    }

    public function validate($field_data)
    {
        $this->errors = new \ExpressionEngine\Service\Validation\Result();

        if (empty($field_data)) {
            return true;
        }

        $field_templates = ee('Model')->get('ChannelField')
            ->with('ChannelFieldGroups')
            ->filter('field_id', 'IN', $this->settings['field_channel_fields'])
            ->orFilter('ChannelFieldGroups.group_id', 'IN', $this->settings['field_channel_field_groups'])
            ->order('field_label')
            ->all()
            ->indexByIds();

        foreach ($field_data['fields'] as $key => $data) {
            $field_id = null;
            $new_field = '';
            $field_group_id = null;
            $fluid_field_data_id = null;

            if (strpos($key, 'field_') === 0) {
                $fluid_field_data_id = (int) str_replace('field_', '', $key);
            } else if (strpos($key, 'new_field_') === 0) {
                $new_field = "[$key]";
            }

            foreach ($data as $id => $datum) {
                if (strpos($id, 'field_group_id_') === 0) {
                    $field_group_id = (int) str_replace('field_group_id_', '', $id);
                }

                $group_name = (!is_null($field_group_id)) ? '[field_group_id_' . $field_group_id . ']' : '';

                foreach($datum as $fieldId => $fieldValue) {
                    if (strpos($fieldId, 'field_id_') === 0) {
                        $field_id = str_replace('field_id_', '', $fieldId);
                    }

                    $field_name = implode('', array_filter([
                        $this->name(),
                        '[fields]',
                        $new_field,
                        ($fluid_field_data_id) ? "[field_{$fluid_field_data_id}]" : null,
                        $group_name,
                        "[field_id_{$field_id}]",
                    ]));

                    // Is this AJAX validation? If so, just return the result for the field
                    // we're validating by skipping the others
                    if (ee()->input->is_ajax_request() && strpos(ee()->input->post('ee_fv_field'), $field_name) === false) {
                        continue;
                    }

                    $field = clone $field_templates[$field_id];

                    $f = $field->getField();
                    $ft_instance = $f->getNativeField();

                    if (
                        isset($ft_instance->has_array_data)
                        && $ft_instance->has_array_data
                        && !is_array($datum['field_id_' . $field_id])
                    ) {
                        $datum['field_id_' . $field_id] = array();
                    }

                    $f->setName($field_name);
                    $f = $this->setupFieldInstance($f, $datum, $fluid_field_data_id);

                    $validator = ee('Validation')->make();
                    $validator->defineRule('validateField', function ($key, $value, $parameters, $rule) use ($f) {
                        return $f->validate($value);
                    });

                    $validator->setRules(array(
                        $f->getName() => 'validateField'
                    ));

                    $result = $validator->validate(array($f->getName() => $f->getData()));

                    if ($result->isNotValid()) {
                        foreach ($result->getFailed() as $field_name => $rules) {
                            foreach ($rules as $rule) {
                                $this->errors->addFailed($field_name, $rule);
                            }
                        }
                    }
                }
            }
        }

        if (ee()->input->is_ajax_request()) {
            if ($this->errors->hasErrors($field_name)) {
                $errors = $this->errors->getErrors($field_name);

                return $errors['callback'];
            }

            return true;
        }

        return ($this->errors->isValid()) ? true : 'form_validation_error';
    }

    // Actual saving takes place in post_save so we have an entry_id
    public function save($data)
    {
        if (is_null($data)) {
            $data = array('fields' => []);
        }

        ee()->session->set_cache(__CLASS__, $this->name(), $data);

        $fluid_field_data = $this->getFieldData()->indexBy('id');

        $compiled_data_for_search = [];

        foreach ($data['fields'] as $key => $value) {
            if ($key == 'new_field_0') {
                continue;
            }
            $create = false;
            $fluid_field_data_id = 0;

            if (strpos(key($value), 'field_group_id_') === 0) {
                $field_group_id = (int) str_replace('field_group_id_', '', key($value));
                $field_group_id = ($field_group_id > 0) ? $field_group_id : null;
                $value = current($value);
            }

            // Existing field - field_id_3[fields][field_3][field_group_0][field_id_2] = value
            if (strpos($key, 'field_') === 0) {
                $fluid_field_id = (int) str_replace('field_', '', $key);
            // New field - field_id_3[fields][new_field_1][field_group_1][field_id_2] = value
            } else if (strpos($key, 'new_field_') === 0) {
                $create = true;
            }

            // Loop through all field_id => field_value pairs
            foreach ($value as $fieldKey => $fieldValue) {
                if (strpos($fieldKey, 'field_id_') === 0) {
                    $field_id = (int) str_replace('field_id_', '', $fieldKey);
                }

                if($create) {
                    $fluid_field = ee('Model')->make('fluid_field:FluidField');
                    $fluid_field->fluid_field_id = $this->field_id;
                    $fluid_field->field_group_id = $field_group_id;
                    $fluid_field->field_id = $field_id;

                    $field = $fluid_field->getField();
                    $fluid_field_data_id = $fieldKey;
                }else{
                    $fluid_field = $fluid_field_data[$fluid_field_id];
                    $fluid_field->field_group_id = $field_group_id;

                    $field = $fluid_field->getField();
                    $fluid_field_data_id = $fluid_field_data[$fluid_field_id]->getId();
                }

                $field->setItem('field_search', true);
                $field->setItem('fluid_field_data_id', $fluid_field_data_id);

                $field->setData($fieldValue);
                $field->validate($fieldValue);
                $compiled_data_for_search[] = $field->save($fieldValue);

            }
        }

        return implode(' ', $compiled_data_for_search);
    }

    public function post_save($data)
    {
        // Prevent saving if save() was never called, happens in Channel Form
        // if the field is missing from the form
        if (($data = ee()->session->cache(__CLASS__, $this->name(), false)) === false) {
            return;
        }

        $fluid_field_data = $this->getFieldData()->indexBy('id');
        $previous_field_group_id = null;
        $previous_group_key = null;

        $i = 1;
        $g = 0;
        // [field_3][field_group_0][field_id_2]
        // [new_field_1][field_group_1][field_id_2]
        foreach ($data['fields'] as $key => $value) {
            if ($key == 'new_field_0') {
                continue;
            }

            if (strpos(key($value), 'field_group_id_') === 0) {
                $field_group_id = (int) str_replace('field_group_id_', '', key($value));
                $field_group_id = ($field_group_id > 0) ? $field_group_id : null;
                $value = current($value);
            }

            $group_key = $key;

            foreach ($value as $fieldKey => $fieldValue) {
                $id = null;
                $field_id = null;

                // Existing field
                if (strpos($key, 'field_') === 0) {
                    $id = str_replace('field_', '', $key);
                    $group_key = 'group_'.$fluid_field_data[$id]->group;
                // New field
                } elseif (strpos($key, 'new_field_') === 0 && strpos($fieldKey, 'field_id_') === 0) {
                    $field_id = str_replace('field_id_', '', $fieldKey);
                }

                // If the field_group is null we do not have a group and are always incrementing
                // If the field_group is not the previous_field_group then we increment the group
                // If the field_group is the previous_field_group but the key has changed
                if (
                    $field_group_id == null
                    || (is_null($field_group_id) && is_null($previous_field_group_id))
                    || ($field_group_id !== $previous_field_group_id)
                    || ($field_group_id === $previous_field_group_id && $group_key !== $previous_group_key)
                ) {
                    $g++;
                }

                $previous_group_key = $group_key;
                $group = ['id' => $field_group_id, 'order' => $g];

                if($field_id) {
                    $this->addField($i, $group, $field_id, [$fieldKey => $fieldValue]);
                }else{
                    $this->updateField($fluid_field_data[$id], $i, $group, [$fieldKey => $fieldValue]);
                    unset($fluid_field_data[$id]);
                }

                $i++;

                $previous_field_group_id = $field_group_id;
            }

        }

        // Remove fields
        foreach ($fluid_field_data as $fluid_field) {
            $this->removeField($fluid_field);
        }
    }

    public function reindex($data)
    {
        $compiled_data_for_search = [];

        $fluid_field_data = $this->getFieldData();
        foreach ($fluid_field_data as $fluid_field) {
            $field = $fluid_field->getField();
            $field_data = $fluid_field->getFieldData();

            if ($field->hasReindex()) {
                $field->setItem('field_search', true);
                $compiled_data_for_search[] = $field->reindex($field_data);
            } else {
                $compiled_data_for_search[] = $field->getData();
            }
        }

        return implode(' ', $compiled_data_for_search);
    }

    private function prepareData($fluid_field, array $values)
    {
        $field_data = $fluid_field->getFieldData();
        $field_data->set($values);
        $field = $fluid_field->getField($field_data);
        $field->setItem('fluid_field_data_id', $fluid_field->getId());
        $field->save();

        $values['field_id_' . $field->getId()] = $field->getData();

        $field->postSave();

        $format = $field->getFormat();

        if (! is_null($format)) {
            $values['field_ft_' . $field->getId()] = $format;
        }

        $timezone = $field->getTimezone();

        if (! is_null($timezone)) {
            $values['field_dt_' . $field->getId()] = $timezone;
        }

        return $values;
    }

    private function updateField($fluid_field, $order, $group, array $values)
    {
        $values = $this->prepareData($fluid_field, $values);

        if (ee()->extensions->active_hook('fluid_field_update_field') === true) {
            $values = ee()->extensions->call(
                'fluid_field_update_field',
                $fluid_field,
                $fluid_field->ChannelField->getTableName(),
                $values
            );
        }

        $fluid_field->field_group_id = array_key_exists('id', $group) ? $group['id'] : null;
        $fluid_field->group = array_key_exists('order', $group) ? $group['order'] : null;
        $fluid_field->order = $order;
        $fluid_field->save();

        $query = ee('db');
        $query->set($values);
        $query->where('id', $fluid_field->field_data_id);
        $query->update($fluid_field->ChannelField->getTableName());
    }

    private function addField($order, $group, $field_id, array $values)
    {
        $fluid_field = ee('Model')->make('fluid_field:FluidField');
        $fluid_field->fluid_field_id = $this->field_id;
        $fluid_field->entry_id = $this->content_id;
        $fluid_field->field_group_id = array_key_exists('id', $group) ? $group['id'] : null;
        $fluid_field->field_id = $field_id;
        $fluid_field->order = $order;
        $fluid_field->group = array_key_exists('order', $group) ? $group['order'] : null;
        $fluid_field->field_data_id = 0;
        $fluid_field->save();

        $values = $this->prepareData($fluid_field, $values);

        $values = array_merge($values, array(
            'entry_id' => 0,
        ));

        if (ee()->extensions->active_hook('fluid_field_add_field') === true) {
            $values = ee()->extensions->call(
                'fluid_field_add_field',
                $fluid_field->ChannelField->getTableName(),
                $values
            );
        }

        $field = ee('Model')->get('ChannelField', $field_id)->first();

        $query = ee('db');
        $query->set($values);
        $query->insert($field->getTableName());
        $id = $query->insert_id();

        $fluid_field->field_data_id = $id;
        $fluid_field->save();
    }

    private function removeField($fluid_field)
    {
        $query = ee('db');
        $query->where('id', $fluid_field->field_data_id);
        $query->delete($fluid_field->ChannelField->getTableName());

        if (ee()->extensions->active_hook('fluid_field_remove_field') === true) {
            ee()->extensions->call('fluid_field_remove_field', $fluid_field);
        }

        $fluid_field->delete();
    }

    /**
     * Displays the field for the CP or Frontend, and accounts for grid
     *
     * @param string $data Stored data for the field
     * @return string Field display
     */
    public function display_field($field_data)
    {
        $fields = '';

        $field_templates = ee('Model')->get('ChannelField', $this->settings['field_channel_fields'])
            ->order('field_label')
            ->all();

        $field_groups = ee('Model')->get('ChannelFieldGroup', $this->settings['field_channel_field_groups'])
            ->with('ChannelFields')
            ->order('group_name')
            ->all();

        $filter_options = $field_templates->map(function ($field) {
            $field = $field->getField();
            return \ExpressionEngine\Addons\FluidField\Model\FluidFieldFilter::make([
                'name' => $field->getShortName(),
                'label' => $field->getItem('field_label'),
                'icon' => $field->getIcon()
            ]);
        });

        if (IS_PRO && ee('pro:Access')->hasValidLicense()) {
            foreach($field_groups as $field_group) {
                $filter_options[] = \ExpressionEngine\Addons\FluidField\Model\FluidFieldFilter::make([
                    'name' => $field_group->group_name,
                    'label' =>  $field_group->group_name,
                    'icon' => URL_THEMES . 'asset/img/' . 'default-addon-icon.svg'
                ]);
            }
        }

        $filters = ee('View')->make('fluid_field:filters')->render(array('filters' => $filter_options));

        $field_templates = $field_templates->indexByIds();

        if (! is_array($field_data)) {
            if ($this->content_id) {
                $fluid_field_data = $this->getFieldData();

                // group items
                $fluid_field_data_groups = [];

                $fluid_field_data->each(function($field) use(&$fluid_field_data_groups) {
                    if(!array_key_exists($field->group, $fluid_field_data_groups)) {
                        $fluid_field_data_groups[$field->group] = [];
                    }

                    $fluid_field_data_groups[$field->group][] = $field;
                });

                foreach ($fluid_field_data_groups as $data) {
                    $is_group = !is_null($data[0]->ChannelFieldGroup);
                    $view = ($is_group) ? 'fluid_field:fieldgroup' : 'fluid_field:field';

                    $viewData = [
                        'filters' => $filters,
                        'errors' => $this->errors,
                        'reorderable' => true,
                        'show_field_type' => false,
                        'field_filters' => $filter_options
                    ];

                    if($is_group) {
                        $field_group = $data[0]->ChannelFieldGroup; // might want to eager load this
                        $viewData = array_merge($viewData, [
                            'field_group' => $field_group,
                            'field_group_fields' => array_map(function ($field) use ($field_group) {
                                $f = $field->getField();
                                $f->setName($this->name() . '[fields][field_' . $field->getId() . '][field_group_id_' . $field_group->getId() . '][field_id_' . $f->getId() . ']');
                                return $f;
                            }, $data),
                            'field_name' => $field_group->group_name,
                        ]);
                    }else{
                        $field = $data[0]->getField();

                        $field->setName($this->name() . '[fields][field_' . $data[0]->getId() . '][field_group_id_0][field_id_' . $field->getId() . ']');
                        $viewData = array_merge($viewData, [
                            'field' => $field,
                            'field_name' => $data[0]->ChannelField->field_name,
                        ]);
                    }

                    $fields .= ee('View')->make($view)->render($viewData);
                }
            }
        // This happens when we have a validation issue and data was not saved
        } else {
            $field_group_map = $this->getFieldData()->indexBy('id');
            $field_groups = $field_groups->indexByIds();
            $rows = [];
            $fields = '';

            foreach ($field_data['fields'] as $key => $data) {
                $field_group_id = 0;
                $field_id = null;
                $field_group_id = null;
                $fluid_field_data_id = null;

                if (strpos($key, 'field_') === 0) {
                    $fluid_field_data_id = (int) str_replace('field_', '', $key);
                }

                foreach ($data as $id => $datum) {
                    if (strpos($id, 'field_group_id_') === 0) {
                        $field_group_id = (int) str_replace('field_group_id_', '', $id);
                    }

                    $group_name = (!is_null($field_group_id)) ? '[field_group_id_' . $field_group_id . ']' : '';

                    foreach($datum as $fieldId => $fieldValue) {
                        if (strpos($fieldId, 'field_id_') === 0) {
                            $field_id = str_replace('field_id_', '', $fieldId);
                        }

                        $field_name = implode('', array_filter([
                            $this->name(),
                            '[fields]',
                            ($fluid_field_data_id) ? "[field_{$fluid_field_data_id}]" : "[$key]",
                            $group_name,
                            "[field_id_{$field_id}]",
                        ]));

                        $field = clone $field_templates[$field_id];

                        $f = clone $field->getField();
                        $f->setName($field_name);

                        $f = $this->setupFieldInstance($f, $datum, $fluid_field_data_id);

                        $group_key = $key;

                        if(array_key_exists($fluid_field_data_id, $field_group_map)) {
                            $group = $field_group_map[$fluid_field_data_id]->ChannelFieldGroup;
                            $group_key = $field_group_map[$fluid_field_data_id]->group;
                        }else{
                            $group = ($field_group_id > 0) ? $field_groups[$field_group_id] : null;
                        }

                        if(!array_key_exists($group_key, $rows)) {
                            $rows[$group_key] = [];
                        }
                        $rows[$group_key][] = [
                            'field' => $f,
                            'field_name' => $field->field_name,
                            'field_group' => $group,
                        ];
                    }
                }
            }

            foreach($rows as $data) {
                $is_group = (count($data) > 1 || !is_null($data[0]['field_group']));
                $view = ($is_group) ? 'fluid_field:fieldgroup' : 'fluid_field:field';

                $viewData = [
                    'filters' => $filters,
                    'errors' => $this->errors,
                    'reorderable' => true,
                    'show_field_type' => false,
                    'field_filters' => $filter_options
                ];

                if($is_group) {
                    $field_group = $data[0]['field_group'];
                    $viewData = array_merge($viewData, [
                        'field_group' => $field_group,
                        'field_group_fields' => array_map(function ($field) {
                            return $field['field'];
                        }, $data),
                        'field_name' => $field_group->group_name,
                    ]);
                }else{
                    $viewData = array_merge($viewData, [
                        'field' => $data[0]['field'],
                        'field_name' => $data[0]['field_name']
                    ]);
                }

                $fields .= ee('View')->make($view)->render($viewData);
            }
        }

        $templates = '';

        foreach ($field_templates as $field) {
            $f = $field->getField();
            $f->setItem('fluid_field_data_id', null);
            $f->setName($this->name() . '[fields][new_field_0][field_group_id_0][field_id_' . $field->getId() . ']');

            $templates .= ee('View')->make('fluid_field:field')->render([
                'field' => $f,
                'field_name' => $field->field_name,
                'filters' => $filters,
                'errors' => $this->errors,
                'reorderable' => true,
                'show_field_type' => false,
                'field_filters' => $filter_options
            ]);
        }

        if (IS_PRO && ee('pro:Access')->hasValidLicense()) {
            foreach($field_groups as $field_group) {
                $templates .= ee('View')->make('fluid_field:fieldgroup')->render([
                    'field_group' => $field_group,
                    'field_group_fields' => $field_group->ChannelFields->map(function($field) use($field_group) {
                        $f = $field->getField();
                        $f->setItem('fluid_field_data_id', null);
                        $f->setName($this->name() .'[fields][new_field_0][field_group_id_' . $field_group->getId() . '][field_id_' . $field->getId() . ']');
                        return $f;
                    }),
                    'field_name' => $field_group->group_name,
                    'filters' => $filters,
                    'errors' => $this->errors,
                    'reorderable' => true,
                    'show_field_type' => false,
                    'field_filters' => $filter_options
                ]);
            }
        }

        if (REQ == 'CP') {
            ee()->cp->add_js_script(array(
                'ui' => array(
                    'sortable'
                ),
                'file' => array(
                    'fields/fluid_field/cp',
                    'cp/sort_helper'
                ),
            ));

            return ee('View')->make('fluid_field:publish')->render(array(
                'fields' => $fields,
                'field_templates' => $templates,
                'filters' => $filters,
            ));
        }
    }

    public function display_settings($data)
    {
        $custom_field_options = ee('Model')->get('ChannelField')
            ->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
            ->filter('field_type', '!=', 'fluid_field')
            ->order('field_label')
            ->all()
            ->filter(function ($field) {
                return $field->getField()->acceptsContentType('fluid_field');
            })
            ->map(function ($field) {
                return [
                    'label' => $field->field_label,
                    'value' => $field->getId(),
                    'instructions' => LD . $field->field_name . RD
                ];
            });

        $settings = array(
            array(
                'title' => 'custom_fields',
                'fields' => array(
                    'field_channel_fields' => array(
                        'type' => 'checkbox',
                        'choices' => $custom_field_options,
                        'value' => isset($data['field_channel_fields']) ? $data['field_channel_fields'] : array(),
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('fields')),
                            'link_text' => 'add_new',
                            'link_href' => ee('CP/URL')->make('fields/create')
                        ]
                    )
                )
            ),
        );

        if (IS_PRO && ee('pro:Access')->hasValidLicense()) {

            $custom_field_group_options = ee('Model')->get('ChannelFieldGroup')
                ->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
                ->order('group_name')
                ->with('ChannelFields')
                ->all()
                ->map(function ($group) {
                    $hasFluid = $group->ChannelFields->filter(function($field) {
                        return $field->field_type === 'fluid_field';
                    })->count() > 0;

                    return [
                        'label' => ($hasFluid) ? "{$group->group_name} (nested fluid fields will be hidden)" : $group->group_name,
                        'value' => $group->getId(),
                        'instructions' => LD . $group->group_name . RD
                    ];
                });

            $settings[] = array(
                'title' => 'custom_field_groups',
                'fields' => array(
                    'field_channel_field_groups' => array(
                        'type' => 'checkbox',
                        'force_react' => true,
                        'choices' => $custom_field_group_options,
                        'value' => isset($data['field_channel_field_groups']) ? $data['field_channel_field_groups'] : array(),
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('field_groups')),
                            'link_text' => 'add_new',
                            'link_href' => ee('CP/URL')->make('fields/groups/create')
                        ]
                    )
                )
            );
        }

        if (! $this->isNew()) {
            ee()->javascript->set_global(array(
                'fields.fluid_field.fields' => $data['field_channel_fields'],
                'fields.fluid_field.groups' => isset($data['field_channel_field_groups']) ? $data['field_channel_field_groups'] : []
            ));

            ee()->cp->add_js_script(array(
                'file' => 'fields/fluid_field/settings',
            ));

            $modal = ee('View')->make('fluid_field:modal')->render();
            ee('CP/Modal')->addModal('remove-field', $modal);
        }

        return array('field_options_fluid_field' => array(
            'label' => 'field_options',
            'group' => 'fluid_field',
            'settings' => $settings
        ));
    }

    public function save_settings($data)
    {
        $defaults = array(
            'field_channel_fields' => array(),
            'field_channel_field_groups' => array(),
        );

        $all = array_merge($defaults, $data);

        $fields = ee('Model')->get('ChannelField', $all['field_channel_fields'])
            ->filter('legacy_field_data', 'y')
            ->all();

        foreach ($fields as $field) {
            $field->createTable();
        }

        // Need to handle table creation for legacy fields belonging to selected field groups
        $field_groups = ee('Model')->get('ChannelFieldGroup', $all['field_channel_field_groups'])
            ->with('ChannelFields')
            ->filter('ChannelFields.legacy_field_data', 'y')
            ->filter('ChannelFields.field_id', 'NOT IN', $all['field_channel_fields'])
            ->all();

        $field_groups->each(function($group) {
            $group->ChannelFields->each(function($field) {
                $field->createTable();
            });
        });

        $reindexNeeded = false;

        if (isset($this->settings['field_channel_fields'])) {
            $this->settings['field_channel_fields'] = array_filter($this->settings['field_channel_fields'], function ($value) {
                return is_numeric($value);
            });

            $removed_fields = (array_diff($this->settings['field_channel_fields'], $all['field_channel_fields']));

            if (! empty($removed_fields)) {
                ee('Model')->get('fluid_field:FluidField')
                    ->filter('fluid_field_id', $this->field_id)
                    ->filter('field_id', 'IN', $removed_fields)
                    ->all()
                    ->delete();

                $reindexNeeded = true;

                $fields = ee('Model')->get('ChannelField', $removed_fields)
                    ->fields('field_label')
                    ->all()
                    ->pluck('field_label');

                if (! empty($fields)) {
                    ee()->logger->log_action(sprintf(lang('removed_fields_from_fluid_field'), $this->settings['field_label'], '<b>' . implode('</b>, <b>', $fields) . '</b>'));
                }
            }
        }

        if(isset($this->settings['field_channel_field_groups'])) {
            $this->settings['field_channel_field_groups'] = array_filter($this->settings['field_channel_fields'], function ($value) {
                return is_numeric($value);
            });

            $removed_groups = (array_diff($this->settings['field_channel_field_groups'], $all['field_channel_field_groups']));

            ee('Model')->get('fluid_field:FluidField')
                    ->filter('fluid_field_id', $this->field_id)
                    ->filter('field_group_id', 'IN', $removed_groups)
                    ->all()
                    ->delete();

            $reindexNeeded = true;
        }

        if ($reindexNeeded) {
            ee('CP/Alert')->makeInline('search-reindex')
                    ->asImportant()
                    ->withTitle(lang('search_reindex_tip'))
                    ->addToBody(sprintf(lang('search_reindex_tip_desc'), ee('CP/URL')->make('utilities/reindex')->compile()))
                    ->defer();

            ee()->config->update_site_prefs(['search_reindex_needed' => ee()->localize->now], 0);
        }

        return array_intersect_key($all, $defaults);
    }

    public function settings_modify_column($data)
    {
        if (isset($data['ee_action']) && $data['ee_action'] == 'delete') {
            $fluid_field_data = ee('Model')->get('fluid_field:FluidField')
                ->filter('fluid_field_id', $data['field_id'])
                ->all()
                ->delete();
        }

        $columns['field_id_' . $data['field_id']] = [
            'type' => 'mediumtext',
            'null' => true
        ];

        return $columns;
    }

    /**
     * Called when entries are deleted
     *
     * @param   array   Entry IDs to delete data for
     */
    public function delete($entry_ids)
    {
        $fluid_field_data = ee('Model')->get('fluid_field:FluidField')
            ->filter('fluid_field_id', $this->field_id)
            ->filter('entry_id', 'IN', $entry_ids)
            ->all()
            ->delete();
    }

    /**
     * Accept all but grid and fluid_field content types.
     *
     * @param string  The name of the content type
     * @return bool   Accepts all content types
     */
    public function accepts_content_type($name)
    {
        $incompatible = array('grid', 'fluid_field');

        return (! in_array($name, $incompatible));
    }

    /**
     * Update the fieldtype
     *
     * @param string $version The version being updated to
     * @return boolean true if successful, FALSE otherwise
     */
    public function update($version)
    {
        return true;
    }

    /**
     * Gets the fluid field's data for a given field and entry
     *
     * @param int $fluid_field_id The id for the field
     * @param int $entry_id The id for the entry
     * @return obj A Collection of FluidField objects
     */
    private function getFieldData($fluid_field_id = '', $entry_id = '')
    {
        $fluid_field_id = ($fluid_field_id) ?: $this->field_id;
        $entry_id = ($entry_id) ?: $this->content_id;

        $cache_key = "FluidField/{$fluid_field_id}/{$entry_id}";

        if (($fluid_field_data = ee()->session->cache("FluidField", $cache_key, false)) === false) {
            $fluid_field_data = ee('Model')->get('fluid_field:FluidField')
                ->with('ChannelField')
                ->filter('fluid_field_id', $fluid_field_id)
                ->filter('entry_id', $entry_id)
                ->order('group')
                ->order('order')
                ->all();

            ee()->session->set_cache("FluidField", $cache_key, $fluid_field_data);
        }

        return $fluid_field_data;
    }

    /**
     * Sets the data, format, and timzeone for a field
     *
     * @param FieldFacade $field The field
     * @param array $data An associative array containing the data to set
     * @return FieldFacade The field.
     */
    private function setupFieldInstance($field, array $data, $fluid_field_data_id = null)
    {
        $field_id = $field->getId();

        $field->setContentId($this->content_id);

        $field->setData($data['field_id_' . $field_id]);

        if (isset($data['field_ft_' . $field_id])) {
            $field->setFormat($data['field_ft_' . $field_id]);
        }

        if (isset($data['field_dt_' . $field_id])) {
            $field->setTimezone($data['field_dt_' . $field_id]);
        }

        $field->setItem('fluid_field_data_id', $fluid_field_data_id);

        return $field;
    }

    /**
     * Replace Fluid Field template tags
     */
    public function replace_tag($data, $params = '', $tagdata = '')
    {
        ee()->load->library('fluid_field_parser');

        // not in a channel scope? pre-process may not have been run.
        if ($this->content_type() != 'channel') {
            ee()->load->library('api');
            ee()->legacy_api->instantiate('channel_fields');
            ee()->grid_parser->fluid_field_field_names[$this->id()] = $this->name();
        }

        return ee()->fluid_field_parser->parse($this->row, $this->id(), $params, $tagdata, $this->content_type());
    }

    /**
     * :length modifier
     */
    public function replace_length($data, $params = '', $tagdata = '')
    {
        return $this->replace_total_fields($data, $params, $tagdata);
    }

    /**
     * :total_fields modifier
     */
    public function replace_total_fields($data, $params = '', $tagdata = '')
    {
        ee()->load->library('fluid_field_parser');

        $fluid_field_data = $this->getFieldData();

        if (ee('LivePreview')->hasEntryData()) {
            $data = ee('LivePreview')->getEntryData();

            if ($data['entry_id'] == $this->content_id()) {
                $fluid_field_data = ee()->fluid_field_parser->overrideWithPreviewData(
                    $fluid_field_data,
                    [$this->id()]
                );

                if (! isset($data["field_id_{$this->id()}"])
                    || ! isset($data["field_id_{$this->id()}"]['fields'])) {
                    return 0;
                }
            }
        }

        if (isset($params['type'])) {
            $fluid_field_data = $fluid_field_data->filter(function ($datum) use ($params) {
                return ($params['type'] == $datum->ChannelField->field_type);
            });
        }

        if (isset($params['name'])) {
            $fluid_field_data = $fluid_field_data->filter(function ($datum) use ($params) {
                return ($params['name'] == $datum->ChannelField->field_name);
            });
        }

        return ($fluid_field_data) ? count($fluid_field_data) : 0;
    }
}

// EOF
