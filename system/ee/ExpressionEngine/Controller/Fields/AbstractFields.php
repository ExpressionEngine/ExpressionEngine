<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Fields;

use CP_Controller;

/**
 * Abstract Categories
 */
abstract class AbstractFields extends CP_Controller
{
    protected $validationResult;

    protected $hasUngroupedFields;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->has('can_admin_channels')) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! ee('Permission')->hasAny(
            'can_create_channel_fields',
            'can_edit_channel_fields',
            'can_delete_channel_fields'
        )) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('admin');
        ee()->lang->loadfile('admin_content');
        ee()->lang->loadfile('channel');

        $header = [
            'title' => lang('field_manager')
        ];

        if (ee('Permission')->can('create_channel_fields')) {
            $header['action_button'] = [
                'text' => lang('new_field'),
                'href' => ee('CP/URL')->make('fields/create/' . (ee('Request')->get('group_id') ? (int) ee('Request')->get('group_id') : ''))
            ];
        }

        ee()->view->header = $header;

        ee()->javascript->set_global(
            'sets.importUrl',
            ee('CP/URL', 'channels/sets')->compile()
        );

        ee()->cp->add_js_script(array(
            'file' => array('cp/channel/menu'),
        ));
    }

    protected function generateSidebar($active = null)
    {
        // More than one group can be active, so we use an array
        $active_groups = (is_array($active)) ? $active : array($active);

        $sidebar = ee('CP/Sidebar')->makeNew();

        $all_fields = $sidebar->addItem(lang('all_fields'), ee('CP/URL')->make('fields'))->withIcon('pen-field');

        if (!is_null($active)) {
            $all_fields->isInactive();
        }

        $list = $sidebar->addHeader(lang('field_groups_uc'));

        $list->withButton(lang('new'), ee('CP/URL')->make('fields/groups/create'));

        $list = $list->addFolderList('field_groups')
            ->withNoResultsText(sprintf(lang('no_found'), lang('field_groups')));

        if (ee('Permission')->can('delete_channel_fields')) {
            $list->withRemoveUrl(ee('CP/URL')->make('fields/groups/remove', ee()->cp->get_url_state()))
                ->withRemovalKey('content_id');
        }

        $imported_groups = ee()->session->flashdata('imported_field_groups') ?: [];

        $field_groups = ee('Model')->get('ChannelFieldGroup')
            ->filter('site_id', 'IN', [ee()->config->item('site_id'), 0])
            ->order('group_name')
            ->all();

        // if there are fields that are not in any group, show a separate link
        $ungroupedQuery = ee('db')->query('SELECT COUNT(exp_channel_fields.field_id) AS missing FROM exp_channel_fields WHERE NOT EXISTS (SELECT field_id FROM exp_channel_field_groups_fields WHERE exp_channel_fields.field_id=exp_channel_field_groups_fields.field_id)');
        if ($ungroupedQuery->row('missing') > 0) {
            $this->hasUngroupedFields = true;
            $item = $list->addItem(
                lang('ungrouped'),
                ee('CP/URL')->make('fields', ['group_id' => 0])
            );
            if ((string) ee('Request')->get('group_id') === '0') {
                $item->isActive();
            } else {
                $item->isInactive();
            }
        }


        foreach ($field_groups as $group) {
            $group_name = ee('Format')->make('Text', $group->group_name)->convertToEntities();

            $item = $list->addItem(
                $group_name,
                ee('CP/URL')->make('fields', ['group_id' => $group->getId()])
            );

            if (ee('Permission')->can('edit_channel_fields')) {
                $item->withEditUrl(
                    ee('CP/URL')->make('fields/groups/edit/' . $group->getId())
                );
            }

            if (ee('Permission')->can('delete_channel_fields')) {
                $item->withRemoveConfirmation(
                    lang('field_group') . ': <b>' . $group_name . '</b>'
                )->identifiedBy($group->getId());
            }

            if (in_array($group->getId(), $active_groups)) {
                $item->isActive();
            } else {
                $item->isInactive();
            }

            if (in_array($group->getId(), $imported_groups)) {
                $item->isSelected();
            }
        }

        ee()->view->left_nav = $sidebar->render();
        ee()->view->left_nav_collapsed = $sidebar->collapsedState;
    }

    protected function prepareFieldConditions()
    {
        $conditionSets = [];
        $conditions = [];
        $set_index = 0;
        foreach (ee('Request')->post('condition_set') as $condition_set_id => $condition_set_data) {
            $orig_condition_set_id = $condition_set_id;
            if (defined('CLONING_MODE') && CLONING_MODE === true) {
                $condition_set_id = 'new_set_' . $condition_set_id;
            }
            if (!is_numeric($condition_set_id)) {
                $fieldConditionSet = ee('Model')->make('FieldConditionSet');
            } else {
                $fieldConditionSet = ee('Model')->get('FieldConditionSet', $condition_set_id)->first();
                if (empty($fieldConditionSet)) {
                    $fieldConditionSet = ee('Model')->make('FieldConditionSet');
                }
            }
            $fieldConditionSet->match = $condition_set_data['match'] ?: 'all';
            $fieldConditionSet->order = $set_index;
            $fieldConditionSetValidation = $fieldConditionSet->validate();
            if (!$fieldConditionSetValidation->isValid()) {
                $errors = $fieldConditionSetValidation->getFailed();
                foreach ($errors as $piece => $rules) {
                    foreach ($rules as $rule) {
                        $errorName = 'condition_set[' . $condition_set_id . '][' . $piece . ']';
                        $this->validationResult->addFailed($errorName, $rule);
                    }
                }
            }
            $conditionSets[$set_index] = $fieldConditionSet;

            $rule_index = 0;
            $postedConditions = ee('Request')->post('condition');
            if (!empty($postedConditions) && isset($postedConditions[$orig_condition_set_id])) {
                foreach ($postedConditions[$orig_condition_set_id] as $condition_id => $condition_data) {
                    if (defined('CLONING_MODE') && CLONING_MODE === true) {
                        $condition_id = 'new_row_' . $condition_id;
                    }
                    if (!is_numeric($condition_id)) {
                        $fieldCondition = ee('Model')->make('FieldCondition');
                    } else {
                        $fieldCondition = ee('Model')->get('FieldCondition', $condition_id)->first();
                        if (empty($fieldCondition)) {
                            $fieldCondition = ee('Model')->make('FieldCondition');
                        }
                    }
                    $fieldCondition->evaluation_rule = isset($condition_data['evaluation_rule']) ? $condition_data['evaluation_rule'] : '';
                    $fieldCondition->value = isset($condition_data['value']) ? $condition_data['value'] : '';
                    $fieldCondition->condition_field_id = isset($condition_data['condition_field_id']) ? $condition_data['condition_field_id'] : '';
                    $fieldCondition->order = $rule_index;
                    $fieldConditionValidation = $fieldCondition->validate();
                    if (!$fieldConditionValidation->isValid()) {
                        $errors = $fieldConditionValidation->getFailed();
                        foreach ($errors as $piece => $rules) {
                            foreach ($rules as $rule) {
                                $errorName = 'condition[' . $orig_condition_set_id . '][' . $condition_id . '][' . $piece . ']';
                                $this->validationResult->addFailed($errorName, $rule);
                            }
                        }
                    }

                    $conditions[$set_index][$rule_index] = $fieldCondition;
                    $rule_index++;
                }
            }

            $set_index++;
        }

        return array($conditionSets, $conditions);
    }

    /**
     * AJAX endpoint for Relationship field settings author list
     *
     * @return	array
     */
    public function relationshipMemberFilter()
    {
        ee()->load->add_package_path(PATH_ADDONS . 'relationship');

        ee()->load->library('Relationships_ft_cp');
        $util = ee()->relationships_ft_cp;

        $author_list = $util->all_authors(ee('Request')->get('search'));

        ee()->load->remove_package_path(PATH_ADDONS . 'relationship');

        return ee('View/Helpers')->normalizedChoices($author_list, true);
    }
}

// EOF
