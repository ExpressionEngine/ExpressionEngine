<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Fields;

use ExpressionEngine\Controller\Fields\AbstractFields as AbstractFieldsController;
use ExpressionEngine\Model\Channel\ChannelField;

/**
 * Fields Controller
 */
class Fields extends AbstractFieldsController
{
    public function index()
    {
        $group_id = ee('Request')->get('group_id');

        if ($group_id) {
            $base_url = ee('CP/URL')->make('fields', ['group_id' => $group_id]);
        } else {
            $base_url = ee('CP/URL')->make('fields');
        }

        if (ee()->input->post('bulk_action') == 'remove') {
            $this->remove(ee()->input->post('selection'));
            ee()->functions->redirect($base_url);
        }

        $this->generateSidebar($group_id);

        $vars['create_url'] = $group_id
            ? ee('CP/URL')->make('fields/create/' . $group_id)
            : ee('CP/URL')->make('fields/create');
        $vars['base_url'] = $base_url;

        $data = array();

        $field_id = ee()->session->flashdata('field_id');

        // Set up filters
        $group_ids = ee('Model')->get('ChannelFieldGroup')
            ->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
            ->order('group_name')
            ->all()
            ->getDictionary('group_id', 'group_name');

        $filters = ee('CP/Filter');
        $group_filter = $filters->make('group_id', 'group_filter', $group_ids);
        $group_filter->setPlaceholder(lang('all'));
        $group_filter->disableCustomValue();

        $fieldtypes = ee('Model')->make('ChannelField')->getCompatibleFieldtypes();

        $fieldtype_filter = $filters->make('fieldtype', 'type_filter', $fieldtypes);
        $fieldtype_filter->setPlaceholder(lang('all'));
        $fieldtype_filter->disableCustomValue();

        $page = ee('Request')->get('page') ?: 1;
        $per_page = 10;

        $filters->add($group_filter)
            ->add($fieldtype_filter);

        $filter_values = $filters->values();

        $total_fields = 0;

        $group = $group_id && $group_id != 'all'
            ? ee('Model')->get('ChannelFieldGroup', $group_id)->first()
            : null;

        // Are we showing a specific group? If so, we need to apply filtering differently
        // because we are acting on a collection instead of a query builder
        if ($group) {
            $fields = $group->ChannelFields->sortBy('field_label')->asArray();

            if ($search = ee()->input->get_post('filter_by_keyword')) {
                $fields = array_filter($fields, function ($field) use ($search) {
                    return strpos(
                        strtolower($field->field_label) . strtolower($field->field_name),
                        strtolower($search)
                    ) !== false;
                });
            }
            if ($fieldtype = $filter_values['fieldtype']) {
                $fields = array_filter($fields, function ($field) use ($fieldtype) {
                    return $field->field_type == $fieldtype;
                });
            }

            $total_fields = count($fields);
        } else {
            $fields = ee('Model')->get('ChannelField')
                ->filter('site_id', 'IN', [ee()->config->item('site_id'), 0]);

            if ($search = ee()->input->get_post('filter_by_keyword')) {
                $fields->search(['field_label', 'field_name'], $search);
            }

            if ($fieldtype = $filter_values['fieldtype']) {
                $fields->filter('field_type', $fieldtype);
            }

            $total_fields = $fields->count();
        }

        $filters->add('Keyword')
            ->add('Perpage', $total_fields, 'all_fields', true);

        $filter_values = $filters->values();
        $vars['base_url']->addQueryStringVariables($filter_values);
        $per_page = $filter_values['perpage'];

        if ($group) {
            $fields = array_slice($fields, (($page - 1) * $per_page), $per_page);
        } else {
            $fields = $fields->limit($per_page)
                ->offset(($page - 1) * $per_page)
                ->order('field_label')
                ->all();
        }

        // Only show filters if there is data to filter or we are currently filtered
        if ($group_id or ! empty($fields)) {
            $vars['filters'] = $filters->render(ee('CP/URL')->make('fields'));
        }

        foreach ($fields as $field) {
            $edit_url = ee('CP/URL')->make('fields/edit/' . $field->getId());
            $fieldtype = isset($fieldtypes[$field->field_type]) ? '(' . $fieldtypes[$field->field_type] . ')' : '';

            $data[] = [
                'id' => $field->getId(),
                'label' => $field->field_label,
                'faded' => strtolower($fieldtype),
                'href' => $edit_url,
                'extra' => LD . $field->field_name . RD,
                'selected' => ($field_id && $field->getId() == $field_id),
                'toolbar_items' => null,
                'selection' => ee('Permission')->can('delete_channel_fields') ? [
                    'name' => 'selection[]',
                    'value' => $field->getId(),
                    'data' => [
                        'confirm' => lang('field') . ': <b>' . ee('Format')->make('Text', $field->field_label)->convertToEntities() . '</b>'
                    ]
                ] : null
            ];
        }

        if (ee('Permission')->can('delete_channel_fields')) {
            ee()->javascript->set_global('lang.remove_confirm', lang('field') . ': <b>### ' . lang('fields') . '</b>');
            ee()->cp->add_js_script(array(
                'file' => array(
                    'cp/confirm_remove',
                ),
            ));
        }

        $vars['pagination'] = ee('CP/Pagination', $total_fields)
            ->perPage($per_page)
            ->currentPage($page)
            ->render($vars['base_url']);

        $vars['cp_page_title'] = $group
            ? $group->group_name . '&mdash;' . lang('fields')
            : lang('all_fields');
        $vars['fields'] = $data;
        $vars['no_results'] = ['text' => sprintf(lang('no_found'), lang('fields')), 'href' => $vars['create_url']];

        $breadcrumbs = array(
            '#developer' => '<i class="fas fa-database"></i>'
        );
        if (!$group) {
            ee()->view->cp_breadcrumbs = array(
                '' => lang('fields')
            );
        } else {
            ee()->view->cp_breadcrumbs = array(
                ee('CP/URL')->make('fields')->compile() => lang('fields'),
                '' => $group->group_name
            );
        }

        ee()->cp->render('fields/index', $vars);
    }

    public function create($group_id = null)
    {
        if (! ee('Permission')->can('create_channel_fields')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (ee('Request')->post('group_id')) {
            $group_id = ee('Request')->post('group_id');
        }

        $this->generateSidebar($group_id);

        $errors = null;
        $field = ee('Model')->make('ChannelField');

        if (! empty($_POST)) {
            $field = $this->setWithPost($field);
            $this->validationResult = $field->validate();

            if (ee('Request')->post('field_is_conditional') == 'y') {
                list($conditionSets, $conditions) = $this->prepareFieldConditions();
            }

            if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($this->validationResult)) {
                return $response;
            }

            if ($this->validationResult->isValid()) {
                $field->save();
                if (ee('Request')->post('field_is_conditional') == 'y') {
                    foreach ($conditionSets as $i => $conditionSet) {
                        $conditionSet->ChannelFields->getAssociation()->set($field);
                        $conditionSet->save();
                        foreach ($conditions[$i] as $condition) {
                            $condition->condition_set_id = $conditionSet->getId();
                            $condition->save();
                        }
                    }
                }

                if ($group_id) {
                    $field_group = ee('Model')->get('ChannelFieldGroup', $group_id)->first();
                    if ($field_group) {
                        $field_group->ChannelFields->getAssociation()->add($field);
                        $field_group->save();
                    }
                }

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('create_field_success'))
                    ->addToBody(sprintf(lang('create_field_success_desc'), $field->field_label))
                    ->defer();

                if (AJAX_REQUEST) {
                    return ['saveId' => $field->getId()];
                }

                if (ee('Request')->post('submit') == 'save_and_new') {
                    $return = (empty($group_id)) ? '' : '/' . $group_id;
                    $redirectUrl = ee('CP/URL')->make('fields/create' . $return);
                } elseif (ee()->input->post('submit') == 'save_and_close') {
                    $redirectUrl = ee('CP/URL')->make('fields');
                } else {
                    $redirectUrl = ee('CP/URL')->make('fields/edit/' . $field->getId());
                }

                // If the new field is conditional, we need to sync channel entries
                if (ee('Request')->post('field_is_conditional') == 'y') {
                    ee()->functions->redirect(
                        ee('CP/URL')->make('fields/syncConditions/' . $field->getId())
                        ->setQueryStringVariable('return', base64_encode($redirectUrl))
                    );
                }

                ee()->functions->redirect($redirectUrl);
            } else {
                $errors = $this->validationResult;

                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('create_field_error'))
                    ->addToBody(lang('create_field_error_desc'))
                    ->now();
            }
        }

        $vars = array(
            'errors' => $errors,
            'ajax_validate' => true,
            'base_url' => $group_id
                ? ee('CP/URL')->make('fields/create/' . $group_id)
                : ee('CP/URL')->make('fields/create'),
            'sections' => $this->form($field),
            'buttons' => [
                [
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'save',
                    'text' => 'save',
                    'working' => 'btn_saving'
                ],
                [
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'save_and_new',
                    'text' => 'save_and_new',
                    'working' => 'btn_saving'
                ],
                [
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'save_and_close',
                    'text' => 'save_and_close',
                    'working' => 'btn_saving'
                ]
            ],
            'form_hidden' => array(
                'field_id' => null
            ),
        );

        if (AJAX_REQUEST) {
            unset($vars['buttons'][2]);
        }

        ee()->view->cp_page_title = lang('create_new_field');

        ee()->view->extra_alerts = array('search-reindex'); // for Save & New

        if (AJAX_REQUEST) {
            return ee()->cp->render('_shared/form', $vars);
        }

        ee()->cp->add_js_script('plugin', 'ee_url_title');

        ee()->javascript->set_global([
            'publish.foreignChars' => ee()->config->loadFile('foreign_chars')
        ]);

        ee()->javascript->output('
            $("input[name=field_label]").bind("keyup keydown", function() {
                $(this).ee_url_title("input[name=field_name]", true);
            });
        ');

        $breadcrumbs = array(
            ee('CP/URL')->make('fields')->compile() => lang('fields')
        );
        if (!empty($group_id)) {
            $breadcrumbs[ee('CP/URL')->make('fields', ['group_id' => $group_id])->compile()] = ee('Model')->get('ChannelFieldGroup', $group_id)->first()->group_name;
        }
        $breadcrumbs[''] = lang('create_new_field');
        ee()->view->cp_breadcrumbs = $breadcrumbs;

        ee()->cp->render('settings/form', $vars);
    }

    public function edit($id)
    {
        if (! ee('Permission')->can('edit_channel_fields')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $field = ee('Model')->get('ChannelField', $id)
            ->first();

        if (! $field) {
            show_404();
        }

        $field_groups = $field->ChannelFieldGroups;
        $active_groups = $field_groups->pluck('group_id');
        $this->generateSidebar($active_groups);

        $errors = null;

        if (! empty($_POST)) {
            $field = $this->setWithPost($field);
            $this->validationResult = $field->validate();

            $conditionSets = [];
            if (ee('Request')->post('field_is_conditional') == 'y') {
                list($conditionSets, $conditions) = $this->prepareFieldConditions();
            }

            if ($response = $this->ajaxValidation($this->validationResult)) {
                return $response;
            }

            if ($this->validationResult->isValid()) {
                $field->save();
                // Build an array representing our conditions that we can compare
                $conditionalsBefore = $this->getConditionArray($field->FieldConditionSets);

                // If request is conditional we need to save those conditionals and sets
                if (ee('Request')->post('field_is_conditional') == 'y') {
                    $assignedConditionalSetIds = [];
                    // Loop through all conditional sets, created from POST data
                    foreach ($conditionSets as $i => $conditionSet) {
                        // Associate the condition set with the field
                        $assignedConditionIds = [];
                        $conditionSet->ChannelFields->getAssociation()->set($field);
                        $conditionSet->save();

                        // Loop through conditions and attach them to the condition sets
                        foreach ($conditions[$i] as $condition) {
                            $condition->condition_set_id = $conditionSet->getId();
                            $condition->save();
                            $assignedConditionIds[] = $condition->getId();
                        }

                        // If a condition was removed lets delete it in the DB
                        $conditionSet->FieldConditions->filter('condition_id', 'NOT IN', $assignedConditionIds)->delete();
                        $assignedConditionalSetIds[$i] = $conditionSet->getId();
                    }
                    // If a condition set was removed, lets delete it
                    $field->FieldConditionSets->filter('condition_set_id', 'NOT IN', $assignedConditionalSetIds)->delete();

                    // Remove condition sets that were removed
                    foreach (array_keys($conditionSets) as $i) {
                        if (!isset($assignedConditionalSetIds[$i])) {
                            unset($conditionSets[$i]);
                        }
                    }
                } else {
                    $field->FieldConditionSets->delete();
                }

                // After saving all that, lets get the field again
                $fieldAfterSave = ee('Model')->get('ChannelField', $id)->first();

                // Build an array representing our conditions that we can compare
                $conditionalsAfter = $this->getConditionArray($fieldAfterSave->FieldConditionSets);

                $conditionalEntriesRequireSync = ! $this->conditionsAreSame($conditionalsBefore, $conditionalsAfter);

                if (ee()->input->post('update_formatting') == 'y') {
                    ee()->db->where('field_ft_' . $field->field_id . ' IS NOT NULL', null, false);
                    ee()->db->update(
                        $field->getDataStorageTable(),
                        array('field_ft_' . $field->field_id => $field->field_fmt)
                    );
                }

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('edit_field_success'))
                    ->addToBody(sprintf(lang('edit_field_success_desc'), $field->field_label))
                    ->defer();

                if (ee('Request')->post('submit') == 'save_and_new') {
                    $redirectUrl = ee('CP/URL')->make('fields/create');
                } elseif (ee()->input->post('submit') == 'save_and_close') {
                    $redirectUrl = ee('CP/URL')->make('fields');
                } else {
                    $redirectUrl = ee('CP/URL')->make('fields/edit/' . $field->getId());
                }

                if ($conditionalEntriesRequireSync) {
                    ee()->functions->redirect(
                        ee('CP/URL')->make('fields/syncConditions/' . $field->getId())
                        ->setQueryStringVariable('return', base64_encode($redirectUrl))
                    );
                }

                ee()->functions->redirect($redirectUrl);
            } else {
                $errors = $this->validationResult;

                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('edit_field_error'))
                    ->addToBody(lang('edit_field_error_desc'))
                    ->now();
            }
        }

        $vars = array(
            'errors' => $errors,
            'ajax_validate' => true,
            'base_url' => ee('CP/URL')->make('fields/edit/' . $id),
            'sections' => $this->form($field),
            'buttons' => [
                [
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'save',
                    'text' => 'save',
                    'working' => 'btn_saving'
                ],
                [
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'save_and_new',
                    'text' => 'save_and_new',
                    'working' => 'btn_saving'
                ],
                [
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'save_and_close',
                    'text' => 'save_and_close',
                    'working' => 'btn_saving'
                ]
            ],
            'form_hidden' => array(
                'field_id' => $id,
            ),
        );

        ee()->view->cp_page_title = lang('edit_field');
        ee()->view->extra_alerts = array('search-reindex');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('fields')->compile() => lang('fields'),
            '' => lang('edit_field')
        );

        ee()->cp->render('settings/form', $vars);
    }

    public function syncConditions($field_id = null)
    {
        if (! ee('Permission')->can('edit_channel_fields')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (!is_null($field_id)) {
            $field = ee('Model')->get('ChannelField', $field_id)->first();
        }

        $channelIds = ee('Request')->get('channel_ids');
        if ($channelIds) {
            $channelIds = explode(',', $channelIds);
        }

        if (! $field_id && empty($channelIds)) {
            show_404();
        }

        if (!is_null($field_id)) {
            $field_groups = $field->ChannelFieldGroups;
            $active_groups = $field_groups->pluck('group_id');
            $this->generateSidebar($active_groups);
            $channels = $field->getAllChannels();
        } else {
            $channels = ee('Model')->get('Channel')->filter('channel_id', 'IN', $channelIds)->all();
        }

        $channelEntryCount = 0;
        $groupedChannelEntryCounts = [];

        foreach ($channels as $channel) {
            $count = $channel->Entries->count();
            $channelEntryCount += $count;
            $groupedChannelEntryCounts[] = [
                'channel_id' => $channel->getId(),
                'entry_count' => $count
            ];
        }

        ksort($groupedChannelEntryCounts);

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'field_conditions_sync_existing_entries',
                    'desc' => sprintf(lang('field_conditions_sync_desc'), $channelEntryCount),
                    'fields' => array(
                        'progress' => array(
                            'type' => 'html',
                            'content' => ee()->load->view('_shared/progress_bar', array('percent' => 0), true)
                        ),
                        'message' => array(
                            'type' => 'html',
                            'content' => ee()->load->view('_shared/message', array(
                                'cp_messages' => [
                                    'field-instruct' => '<em>'.lang('field_conditions_sync_in_progress_message').'</em>'
                                ]), true)
                        )
                    )
                )
            )
        );

        if (!is_null($field_id)) {
            $base_url = ee('CP/URL')->make('fields/syncConditions/' . $field_id);
            $defaultReturnUrl = ee('CP/URL')->make('fields/edit/' . $field_id);
        } else {
            $base_url = ee('CP/URL')->make('fields/syncConditions')
                ->setQueryStringVariable('channel_ids', implode(',', $channelIds));
            $defaultReturnUrl = ee('CP/URL')->make('fields');
        }


        $return = ee()->input->get('return') ? base64_decode(ee()->input->get('return')) : $defaultReturnUrl->compile();

        if ($channelEntryCount === 0) {
            ee()->functions->redirect($return);
        }

        ee()->cp->add_js_script('file', 'cp/fields/synchronize');

        // Globals needed for JS script
        ee()->javascript->set_global(array(
            'fieldManager' => array(
                'channel_entry_count' => $channelEntryCount,
                'groupedChannelEntryCounts' => $groupedChannelEntryCounts,

                'sync_baseurl' => $base_url->compile(),
                'sync_returnurl' => $return,
                'sync_endpoint' => ee('CP/URL')->make('fields/evaluateConditions')->compile(),
            )
        ));

        ee()->view->base_url = $base_url;
        ee()->view->cp_page_title = lang('field_conditions_syncing_conditional_logic');
        ee()->view->cp_page_title_alt = lang('field_conditions_syncing_conditional_logic');
        ee()->view->save_btn_text = 'btn_sync_conditional_logic';
        ee()->view->save_btn_text_working = 'btn_sync_conditional_logic_working';

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('fields')->compile() => lang('fields'),
            '' => lang('field_conditions_sync_conditional_logic')
        );

        ee()->cp->render('settings/form', $vars);
    }

    // This builds a simple array we can compare so we know if a condition has changed
    private function getConditionArray($conditionSets)
    {
        $comparable = [];
        foreach ($conditionSets as $conditionSet) {
            $conditions = [];

            foreach ($conditionSet->FieldConditions as $condition) {
                $conditions[] = [
                    'condition_field_id' => (int) $condition->condition_field_id,
                    'evaluation_rule' => $condition->evaluation_rule,
                    'value' => $condition->value,
                ];
            }

            $comparable[] = [$conditionSet->match => $conditions];
        }
        return $comparable;
    }

    public function conditionsAreSame($conditionSetsBefore, $conditionSetsAfter)
    {
        if (!is_array($conditionSetsAfter) || !is_array($conditionSetsBefore)) {
            return $conditionSetsBefore === $conditionSetsAfter;
        }

        foreach (array_keys($conditionSetsAfter) as $key) {
            if (!isset($conditionSetsBefore[$key]) || !$this->conditionsAreSame($conditionSetsBefore[$key], $conditionSetsAfter[$key])) {
                return false;
            }
        }

        foreach (array_keys($conditionSetsBefore) as $key) {
            if (!isset($conditionSetsAfter[$key]) || !$this->conditionsAreSame($conditionSetsBefore[$key], $conditionSetsAfter[$key])) {
                return false;
            }
        }
        return true;
    }

    private function setWithPost(ChannelField $field)
    {
        $field->field_list_items = ($field->field_list_items) ?: '';
        $field->field_order = ($field->field_order) ?: 0;
        $field->site_id = (int) $field->site_id ?: 0;

        $field->set(ee('Security/XSS')->clean($_POST));

        if ($field->field_pre_populate && ee('Request')->post('field_pre_populate_id')) {
            list($channel_id, $field_id) = explode('_', ee('Request')->post('field_pre_populate_id'));

            $field->field_pre_channel_id = $channel_id;
            $field->field_pre_field_id = $field_id;
        }

        return $field;
    }

    private function form(ChannelField $field = null)
    {
        if (! $field) {
            $field = ee('Model')->make('ChannelField');
        }

        $fieldtype_choices = $field->getCompatibleFieldtypes();

        $fieldtypes = ee('Model')->get('Fieldtype')
            ->fields('name')
            ->filter('name', 'IN', array_keys($fieldtype_choices))
            ->order('name')
            ->all();

        $field->field_type = ($field->field_type) ?: 'text';

        $sections = array(
            array(
                array(
                    'title' => 'type',
                    'desc' => '',
                    'fields' => array(
                        'field_type' => array(
                            'type' => 'dropdown',
                            'choices' => $fieldtype_choices,
                            'group_toggle' => $fieldtypes->getDictionary('name', 'name'),
                            'value' => $field->field_type,
                            'no_results' => ['text' => sprintf(lang('no_found'), lang('fieldtypes'))]
                        )
                    )
                ),
                array(
                    'title' => 'name',
                    'fields' => array(
                        'field_label' => array(
                            'type' => 'text',
                            'value' => $field->field_label,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'short_name',
                    'desc' => 'alphadash_desc',
                    'fields' => array(
                        'field_name' => array(
                            'type' => 'text',
                            'value' => $field->field_name,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'instructions',
                    'desc' => 'instructions_desc',
                    'fields' => array(
                        'field_instructions' => array(
                            'type' => 'textarea',
                            'value' => $field->field_instructions,
                        )
                    )
                ),
                array(
                    'title' => 'require_field',
                    'desc' => 'require_field_desc',
                    'fields' => array(
                        'field_required' => array(
                            'type' => 'yes_no',
                            'value' => $field->field_required,
                        )
                    )
                ),
                array(
                    'title' => 'include_in_search',
                    'desc' => 'include_in_search_desc',
                    'fields' => array(
                        'field_search' => array(
                            'type' => 'yes_no',
                            'value' => $field->field_search,
                        )
                    )
                ),
                array(
                    'title' => 'hide_field',
                    'desc' => 'hide_field_desc',
                    'fields' => array(
                        'field_is_hidden' => array(
                            'type' => 'yes_no',
                            'value' => $field->field_is_hidden,
                        )
                    )
                ),
                array(
                    'title' => 'make_conditional',
                    'desc' => 'make_conditional_desc',
                    'fields' => array(
                        'field_is_conditional' => array(
                            'type' => 'yes_no',
                            'value' => $field->field_is_conditional,
                            'group_toggle' => array(
                                'y' => 'rule_groups',
                            )
                        )
                    )
                ),
            ),
        );

        if (IS_PRO && ee('pro:Access')->hasValidLicense()) {
            ee()->lang->load('pro', ee()->session->get_language(), false, true, PATH_ADDONS . 'pro/');
            $sections['pro_settings'][] = array(
                'title' => 'enable_frontedit',
                'desc' => 'enable_frontedit_field_desc',
                'fields' => array(
                    'enable_frontedit' => array(
                        'type' => 'yes_no',
                        'value' => $field->enable_frontedit
                    )
                )
            );
        }

        $field_options = $field->getSettingsForm();
        if (is_array($field_options) && ! empty($field_options)) {
            $sections = array_merge($sections, $field_options);
        }

        $fieldsWithEvaluationRules = [];
        foreach ($fieldtypes as $fieldtype) {
            // If editing an option field, populate the dummy fieldtype with the
            // same settings to make switching between the different types easy
            if (! $field->isNew()) {
                $dummy_field = clone $field;
            } else {
                $dummy_field = ee('Model')->make('ChannelField');
            }
            $dummy_field->field_type = $fieldtype->name;

            if ($fieldtype->name == $field->field_type) {
                continue;
            }

            $field_options = $dummy_field->getSettingsForm();
            if (is_array($field_options) && ! empty($field_options)) {
                $sections = array_merge($sections, $field_options);
            }
        }

        $siteFields = ee('Model')->get('ChannelField')->filter('site_id', 'IN', [0, ee()->config->item('site_id')])->filter('field_id', '!=', (int) $field->getId())->all();
        if ($siteFields) {
            foreach ($siteFields as $siteField) {
                $evaluationRules = $siteField->getSupportedEvaluationRules();
                if (!empty($evaluationRules)) {
                    $fieldsWithEvaluationRules[$siteField->field_id] = [
                        'field_id' => $siteField->field_id,
                        'field_label' => $siteField->field_label,
                        'field_name' => $siteField->field_name,
                        'field_type' => $siteField->field_type,
                        'evaluationRules' => $evaluationRules,
                        'evaluationValues' => $siteField->getPossibleValuesForEvaluation()
                    ];
                }
            }
        }

        ee()->javascript->set_global('fieldsInfo', $fieldsWithEvaluationRules);

        $ruleGroupsField = array(
            'title' => '',
            'desc' => '',
            'group' => 'rule_groups',
            'fields' => array(
                'condition_fields' => array(
                    'type' => 'html',
                    'content' => ee('View')->make('ee:_shared/form/condition/condition-rule-group')->render([
                        'fieldsList' => $fieldsWithEvaluationRules,
                        'fieldConditionSets' => $field->isNew() ? null : $field->FieldConditionSets,
                        'errors' => $this->validationResult
                    ])
                ),
            ),
        );

        array_push($sections[0], $ruleGroupsField);

        $relatedConditionalFields = [];
        if (!is_null($field->UsesFieldConditions)) {
            foreach ($field->UsesFieldConditions as $relatedFieldConditions) {
                if (!is_null($relatedFieldConditions->FieldConditionSet)) {
                    if (!is_null($relatedFieldConditions->FieldConditionSet->ChannelFields)) {
                        foreach ($relatedFieldConditions->FieldConditionSet->ChannelFields as $relatedChannelField) {
                            $relatedConditionalFields[$relatedChannelField->getId()] = [
                                'id' => $relatedChannelField->getId(),
                                'label' => $relatedChannelField->field_label,
                                'extra' => '{' . $relatedChannelField->field_name . '}',
                                'href' => ee('CP/URL')->make('fields/edit/' . $relatedChannelField->getId())->compile(),
                            ];
                        }
                    }
                }
            }
        }

        if (!empty($relatedConditionalFields)) {
            $sections[0][] = array(
                'title' => 'is_conditional',
                'desc' => 'is_conditional_desc',
                'fields' => array(
                    'is_conditional' => array(
                        'type' => 'html',
                        'content' => ee('View')->make('ee:_shared/table-list')->render(['data' => $relatedConditionalFields, 'disable_action' => true])
                    )
                )
            );
        }

        ee()->javascript->output('$(document).ready(function () {
            EE.cp.fieldToggleDisable();
        });');

        ee()->cp->add_js_script('file', array('cp/conditional_logic'));

        return $sections;
    }

    private function remove($field_ids)
    {
        if (! ee('Permission')->can('delete_channel_fields')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($field_ids)) {
            $field_ids = array($field_ids);
        }

        $fields = ee('Model')->get('ChannelField', $field_ids)->all();

        $dependentChannels = [];

        // Lets loop through each field and figure out if it's used in a field condition
        foreach ($fields as $field) {
            // if the field is being used as a condition, lets loop through those conditions
            foreach ($field->UsesFieldConditions as $fieldCondition) {
                // Lets loop through each channel field that uses the current field as a condition
                foreach ($fieldCondition->FieldConditionSet->ChannelFields as $channelField) {
                    // This is a field dependent on the field being deleted as part of it's conditions
                    // $dependentConditionalFields[$channelField->getId()] = $channelField;
                    foreach ($channelField->getAllChannels() as $channel) {
                        $dependentChannels[$channel->getId()] = $channel->getId();
                    }
                }
            }
        }

        $field_names = $fields->pluck('field_label');

        $fields->delete();
        ee('CP/Alert')->makeInline('fields')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(lang('fields_deleted_desc'))
            ->addToBody($field_names)
            ->defer();

        foreach ($field_names as $field_name) {
            ee()->logger->log_action(sprintf(lang('removed_field'), '<b>' . $field_name . '</b>'));
        }

        if (!empty($dependentChannels)) {
            ee()->functions->redirect(
                ee('CP/URL')->make('fields/syncConditions')
                ->setQueryStringVariable('channel_ids', implode(',', $dependentChannels))
            );
        }
    }
}

// EOF
