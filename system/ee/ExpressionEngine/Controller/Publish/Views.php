<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Publish;

use CP_Controller;
use ExpressionEngine\Model\EntryManager\View;
use ExpressionEngine\Library\CP\EntryManager;

/**
 * Publish/Edit Controller
 */
class Views extends CP_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (! AJAX_REQUEST) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    public function saveDefault()
    {
        $query = ee('Model')->get('EntryManagerView')
            ->filter('member_id', ee()->session->userdata('member_id'))
            ->filter('channel_id', (int) ee()->input->get('channel_id'));
        $view = $query->first();
        if (empty($view)) {
            $view = ee('Model')->make('EntryManagerView');
        }

        $view->member_id = ee()->session->userdata('member_id');
        $view->channel_id = (int) ee()->input->get('channel_id');
        $view->columns = json_encode(ee()->input->post('columns'));

        if ($view->save()) {
            ee()->output->send_ajax_response('success');
        } else {
            ee()->output->send_ajax_response(array('error' => 'could_not_save_view'));
        }
    }

    public function create()
    {
        $view = ee('Model')->make('EntryManagerView');
        if (!empty(ee()->input->get_post('columns'))) {
            $view->Columns = $this->getColumnModels(ee()->input->get_post('columns'));
        }

        $vars = [
            'cp_page_title' => 'Save Custom View',
            'base_url' => ee('CP/URL')->make('publish/views/create'),
        ];

        return $this->viewForm($view, $vars);
    }

    public function edit($view_id)
    {
        $view = ee('Model')->get('EntryManagerView', $view_id)->first();

        $vars = [
            'cp_page_title' => 'Edit [saved view]',
            'base_url' => ee('CP/URL')->make('publish/views/edit/' . $view_id),
        ];

        return $this->viewForm($view, $vars);
    }

    public function cloneView($view_id)
    {
        $vars = [
            'cp_page_title' => 'Clone [saved view]',
            'base_url' => ee('CP/URL')->make('publish/views/clone-view/' . $view_id),
        ];

        // Probably create a new model here based off the old one and pass that in
        return $this->viewForm(ee('Model')->get('EntryManagerView', $view_id)->first());
    }

    public function remove($view_id)
    {
        ee('Model')->get('EntryManagerView', $view_id)->delete();
    }

    private function viewForm(View $view, $vars = [])
    {
        $channels = ee('Model')->get('Channel')
            ->with('Site')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('channel_title', 'asc')
            ->all();

        $vars['sections'] = [
            [
                [
                    'title' => 'Columns',
                    'fields' => [
                        'columns' => [
                            // TODO: change to relationship field to control order
                            'type' => 'checkbox',
                            'value' => $view->Columns->pluck('identifier'),
                            'choices' => $this->getColumnChoices(),
                        ]
                    ]
                ]
            ],
            'Custom View Options' => [
                [
                    'title' => 'name',
                    'fields' => [
                        'name' => [
                            'type' => 'text',
                            'value' => $view->name,
                            'required' => true
                        ]
                    ]
                ],
                // Implement when Roles branch is merged
                /*[
                    'title' => 'Assign to Member role(s)?',
                    'desc' => 'When assigned all users within a specific role will see this view when using the entry manager.
                    One view per Role.',
                    'fields' => [
                        'assign_to_roles' => [
                            'type' => 'checkbox',
                            'value' => 'all',
                            'choices' => ['all' => 'All Roles'],
                        ]
                    ]
                ],*/
                /*[
                    'title' => 'Assign to Channel(s)?',
                    'desc' => 'When assigned a user will see this view for the chosen channel(s).
                    One view per Channel.',
                    'fields' => [
                        'assign_to_channels' => [
                            'type' => 'checkbox',
                            'value' => ($view->Channels) ?: 'all',
                            'nested' => true,
                            'choices' => [
                                'all' => [
                                    'name' => lang('all_channels'),
                                    'children' => $channels->getDictionary('channel_id', 'channel_title')
                                ]
                            ],
                        ]
                    ]
                ]*/
            ]
        ];

        $vars['buttons'] = [
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save',
                'text' => 'save',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'delete',
                'type' => 'button',
                'value' => 'delete',
                'text' => 'delete',
                'working' => 'btn_deleting',
                'class' => 'button--danger js-modal-link js-modal--destruct',
                'attrs' => 'rel="modal-confirm-delete-view"'
            ]
            /*[
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_new',
                'text' => 'save_and_new',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'clone_and_new',
                'text' => 'clone_and_new',
                'working' => 'btn_saving'
            ]*/
        ];

        if (!isset($vars['cp_page_title']) || empty($vars['cp_page_title'])) {
            $vars['cp_page_title'] = 'Save Custom View';
        }
        if (!isset($vars['base_url']) || empty($vars['base_url'])) {
            $vars['base_url'] = ee('CP/URL')->make('publish/views/create');
        }
        $vars['ajax_validate'] = true;

        if (! empty($_POST)) {
            $view->name = ee('Request')->post('name');
            $view->Columns = $this->getColumnModels(ee('Request')->post('columns'));
            $view->channel_id = (! empty(ee('Request')->post('channel_id')) ? ee('Request')->post('channel_id') : 0);

            $result = $view->validate();

            if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result)) {
                return $response;
            }

            $alert_key = $view->isNew() ? 'created' : 'updated';

            if ($result->isValid()) {
                if (!empty($view->view_id)) {
                    $columns = ee('Model')->get('EntryManagerViewColumn')->filter('view_id', $view->view_id)->delete();
                }

                $view->save();

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('entry_manager_view_' . $alert_key))
                    ->addToBody(sprintf(lang('entry_manager_view_' . $alert_key . '_desc'), $view->name))
                    ->defer();

                return ['redirect' => ee('CP/URL')->make('publish/edit/index/' . $view->getId())->compile()];
            } else {
                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('entry_manager_view_not_' . $alert_key))
                    ->addToBody(lang('entry_manager_view_not_' . $alert_key . '_desc'))
                    ->now();
            }
        }

        return ee()->cp->render('_shared/form', $vars);
    }

    private function getColumnChoices()
    {
        ee()->lang->load('content');

        $column_choices = [];
        foreach (EntryManager\ColumnFactory::getAvailableColumns() as $column) {
            $identifier = $column->getTableColumnIdentifier();

            // This column is mandatory, not optional
            if ($identifier == 'checkbox') {
                continue;
            }

            $column_choices[$identifier] = strip_tags(lang($column->getTableColumnLabel()));
        }

        return $column_choices;
    }

    private function getColumnModels($columns = [])
    {
        $column_models = [];
        $existing = [];
        foreach ($columns as $order => $column) {
            if (!in_array($column, $existing)) {
                $column_models[] = ee('Model')->make('EntryManagerViewColumn', [
                    'identifier' => $column,
                    'order' => $order
                ]);
                $existing[] = $column;
            }
        }

        return $column_models;
    }
}

// EOF
