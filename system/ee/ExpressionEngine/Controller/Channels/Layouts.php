<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Channels;

use ExpressionEngine\Library\CP\Table;

use ExpressionEngine\Model\Channel\Display\DefaultChannelLayout;
use ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;
use ExpressionEngine\Model\Channel\Channel;
use ExpressionEngine\Library\Data\Collection;

/**
 * Channels\Layouts Controller
 */
class Layouts extends AbstractChannelsController
{
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('edit_channels')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('content');
    }

    public function layouts($channel_id = null)
    {
        if (ee()->input->post('bulk_action') == 'remove') {
            $this->remove(ee()->input->post('selection'));
            ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/' . $channel_id));
        }

        $channel = is_numeric($channel_id)
            ? ee('Model')->get('Channel', $channel_id) : ee('Model')->get('Channel');
        $channel = $channel->filter('site_id', ee()->config->item('site_id'))->first();

        if (! $channel) {
            $vars = [
                'no_results' => [
                    'text' => sprintf(lang('no_found'), lang('channels'))
                        . ' <a href="' . ee('CP/URL', 'channels/create') . '">' . lang('add_new') . '</a> '
                        . lang('or') . ' <a href="#" rel="import-channel">' . lang('import') . '</a>'
                ],
                'channel_id' => ''
            ];

            return ee()->cp->render('channels/layout/index', $vars);
        }

        $vars['channel_id'] = $channel_id;
        $vars['create_url'] = ee('CP/URL')->make('channels/layouts/create/' . $channel->getId());
        $vars['base_url'] = ee('CP/URL', 'channels/layouts/' . $channel->getId());

        $data = array();

        $layout_id = ee()->session->flashdata('layout_id');

        // Set up filters
        $role_ids = ee('Model')->get('Role')
            // Banned & Pending have their own views
            ->filter('role_id', 'NOT IN', array(2, 4))
            ->order('name', 'asc')
            ->all()
            ->getDictionary('role_id', 'name');

        $options = $role_ids;
        $options['all'] = lang('all');

        $filters = ee('CP/Filter');
        $group_filter = $filters->make('role_id', 'role', $options);
        $group_filter->setPlaceholder(lang('all'));
        $group_filter->disableCustomValue();

        $filters->add($group_filter)
            ->add('Keyword');

        $filter_values = $filters->values();

        $page = ee('Request')->get('page') ?: 1;
        $per_page = 10;

        $layouts = $channel->ChannelLayouts->asArray();

        // Perform filtering
        if ($role_id = $filter_values['role_id']) {
            $layouts = array_filter($layouts, function ($layout) use ($role_id) {
                return in_array($role_id, $layout->PrimaryRoles->pluck('role_id'));
            });
        }
        if ($search = $filter_values['filter_by_keyword']) {
            $layouts = array_filter($layouts, function ($layout) use ($search) {
                return strpos(
                    strtolower($layout->layout_name),
                    strtolower($search)
                ) !== false;
            });
        }

        $layouts = array_slice($layouts, (($page - 1) * $per_page), $per_page);

        // Only show filters if there is data to filter or we are currently filtered
        if ($role_id or ! empty($layouts)) {
            $vars['filters'] = $filters->render($vars['base_url']);
        }

        foreach ($layouts as $layout) {
            $edit_url = ee('CP/URL')->make('channels/layouts/edit/' . $layout->layout_id);

            $data[] = [
                'id' => $layout->getId(),
                'label' => $layout->layout_name,
                'href' => $edit_url,
                'extra' => implode(', ', $layout->PrimaryRoles->pluck('name')),
                'selected' => ($layout_id && $layout->layout_id == $layout_id),
                'toolbar_items' => [],
                'selection' => [
                    'name' => 'selection[]',
                    'value' => $layout->layout_id,
                    'data' => [
                        'confirm' => lang('layout') . ': <b>' . ee('Format')->make('Text', $layout->layout_name)->convertToEntities() . '</b>'
                    ]
                ]
            ];
        }

        ee()->javascript->set_global('lang.remove_confirm', lang('layout') . ': <b>### ' . lang('layouts') . '</b>');
        ee()->cp->add_js_script(array(
            'file' => array(
                'cp/confirm_remove',
            ),
        ));

        $vars['pagination'] = ee('CP/Pagination', $channel->ChannelLayouts->count())
            ->perPage($per_page)
            ->currentPage($page)
            ->render($vars['base_url']);

        $vars['cp_page_title'] = sprintf(lang('channel_form_layouts'), $channel->channel_title);
        $vars['channel_title'] = ee('Format')->make('Text', $channel->channel_title)->convertToEntities();
        $vars['layouts'] = $data;
        $vars['no_results'] = ['text' => lang('no_layouts'), 'href' => $vars['create_url']];

        $breadcrumbs = array(
            ee('CP/URL')->make('channels')->compile() => lang('channels')
        );
        $breadcrumbs[''] = lang('form_layouts');
        ee()->view->cp_breadcrumbs = $breadcrumbs;

        ee()->cp->render('channels/layout/index', $vars);
    }

    public function create($channel_id)
    {
        ee()->view->left_nav = null;

        $channel = ee('Model')->get('Channel', $channel_id)
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $channel) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->view->header = [
            'title' => $channel->channel_title
        ];

        $entry = ee('Model')->make('ChannelEntry');
        $entry->Channel = $channel;

        $channel_layout = ee('Model')->make('ChannelLayout');
        $channel_layout->Channel = $channel;
        $channel_layout->site_id = ee()->config->item('site_id');

        if (! ee()->input->post('field_layout')) {
            $default_layout = new DefaultChannelLayout($channel_id, null);
            $field_layout = $default_layout->getLayout();

            foreach ($channel->getAllCustomFields() as $custom_field) {
                $field_layout[0]['fields'][] = array(
                    'field' => $entry->getCustomFieldPrefix() . $custom_field->field_id,
                    'visible' => true,
                    'collapsed' => false,
                    'width' => 100
                );
            }

            $channel_layout->field_layout = $field_layout;
        } else {
            $channel_layout->field_layout = json_decode(ee()->input->post('field_layout'), true);
        }

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules(array(
            array(
                'field' => 'layout_name',
                'label' => 'lang:layout_name',
                'rules' => 'required'
            ),
            array(
                'field' => 'roles',
                'label' => 'lang:layout_roles',
                'rules' => 'required'
            ),
        ));

        $roles = ee('Model')->get('Role', ee()->input->post('roles'))->all();
        $channel_layout->PrimaryRoles = $roles;

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            $channel_layout->layout_name = ee()->input->post('layout_name');

            $channel_layout->save();

            ee()->session->set_flashdata('layout_id', $channel_layout->layout_id);

            ee('CP/Alert')->makeInline('layout-form')
                ->asSuccess()
                ->withTitle(lang('create_layout_success'))
                ->addToBody(sprintf(lang('create_layout_success_desc'), $channel_layout->layout_name))
                ->defer();

            if (ee('Request')->post('submit') == 'save_and_new') {
                ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/create/' . $channel_id));
            } elseif (ee()->input->post('submit') == 'save_and_close') {
                ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/' . $channel_id));
            } else {
                ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/edit/' . $channel_layout->getId()));
            }
        } elseif (ee()->form_validation->errors_exist()) {
               ee('CP/Alert')->makeInline('layout-form')
                    ->asIssue()
                    ->withTitle(lang('create_layout_error'))
                    ->addToBody(lang('create_layout_error_desc'))
                    ->now();

            // Error with cloning mode roles?
            if (defined('CLONING_MODE') && CLONING_MODE === true) {
                if (ee()->form_validation->error('roles') != '') {
                    // Give warning that settings are cloned but
                    // need to be changed before it can be saved
                    // and replace error banner above

                    ee('CP/Alert')->makeInline('layout-form')
                        ->asWarning()
                        ->withTitle(lang('clone_settings_success'))
                        ->addToBody(lang('clone_layout_role_error'))
                        ->now();
                }
            }
        }

        $vars = array(
            'channel' => $channel,
            'form_url' => ee('CP/URL', 'channels/layouts/create/' . $channel_id),
            'layout' => $entry->getDisplay($channel_layout),
            'channel_layout' => $channel_layout,
            'form' => $this->getForm($channel_layout),
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
            ]
        );

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('channels')->compile() => lang('channels'),
            ee('CP/URL')->make('channels/edit/' . $channel_id)->compile() => ee('Format')->make('Text', $channel->channel_title)->convertToEntities(),
            ee('CP/URL')->make('channels/layouts/' . $channel_id)->compile() => lang('form_layouts'),
            '' => lang('create')
        );

        ee()->view->cp_page_title = lang('create_form_layout');

        $this->addJSAlerts();
        ee()->javascript->set_global('publish_layout', $channel_layout->field_layout);
        ee()->cp->add_js_script('ui', array('droppable', 'sortable'));
        ee()->cp->add_js_script('file', 'cp/channel/layout');

        ee()->cp->render('channels/layout/form', $vars);
    }

    public function edit($layout_id)
    {
        ee()->view->left_nav = null;

        $channel_layout = ee('Model')->get('ChannelLayout', $layout_id)
            ->with('Channel')
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $channel_layout) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (defined('CLONING_MODE') && CLONING_MODE === true) {
            if ($_POST['layout_name'] == $channel_layout->layout_name) {
                $_POST['layout_name'] = lang('copy_of') . ' ' . $_POST['layout_name'];
            }
            if (! empty($_POST['roles'])) {
                foreach ($channel_layout->PrimaryRoles->pluck('role_id') as $role_id) {
                    if (($roleIdKey = array_search($role_id, $_POST['roles'])) !== false) {
                        unset($_POST['roles'][$roleIdKey]);
                    }
                }
            }
            if (empty($_POST['roles'])) {
                unset($_POST['roles']);
            }
            return $this->create($channel_layout->channel_id);
        }

        $channel_layout->synchronize();

        $channel = $channel_layout->Channel;
        ee()->view->header = [
            'title' => $channel->channel_title
        ];

        $entry = ee('Model')->make('ChannelEntry');
        $entry->Channel = $channel;

        if (ee()->input->post('field_layout')) {
            $channel_layout->field_layout = json_decode(ee()->input->post('field_layout'), true);
        }

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules(array(
            array(
                'field' => 'layout_name',
                'label' => 'lang:layout_name',
                'rules' => 'required'
            ),
            array(
                'field' => 'roles',
                'label' => 'lang:layout_roles',
                'rules' => 'required'
            ),
        ));

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            $channel_layout->layout_name = ee()->input->post('layout_name');

            $roles = ee('Model')->get('Role', ee()->input->post('roles'))
                ->all();

            $channel_layout->PrimaryRoles = $roles;

            $channel_layout->save();

            ee('CP/Alert')->makeInline('layout-form')
                ->asSuccess()
                ->withTitle(lang('edit_layout_success'))
                ->addToBody(sprintf(lang('edit_layout_success_desc'), ee()->input->post('layout_name')))
                ->defer();

            if (ee('Request')->post('submit') == 'save_and_new') {
                ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/create/' . $channel->getId()));
            } elseif (ee()->input->post('submit') == 'save_and_close') {
                ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/' . $channel->getId()));
            } else {
                ee()->functions->redirect(ee('CP/URL')->make('channels/layouts/edit/' . $channel_layout->getId()));
            }
        } elseif (ee()->form_validation->errors_exist()) {
            ee('CP/Alert')->makeInline('layout-form')
                ->asIssue()
                ->withTitle(lang('edit_layout_error'))
                ->addToBody(lang('edit_layout_error_desc'))
                ->now();
        }

        $vars = array(
            'channel' => $channel,
            'form_url' => ee('CP/URL', 'channels/layouts/edit/' . $layout_id),
            'layout' => $entry->getDisplay($channel_layout),
            'channel_layout' => $channel_layout,
            'form' => $this->getForm($channel_layout),
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
                ],
                [
                    'name' => 'submit',
                    'type' => 'submit',
                    'value' => 'save_as_new_entry',
                    'text' => sprintf(lang('clone_to_new'), lang('layout')),
                    'working' => 'btn_saving'
                ]
            ]
        );

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('channels')->compile() => lang('channels'),
            ee('CP/URL')->make('channels/edit/' . $channel->getId())->compile() => ee('Format')->make('Text', $channel->channel_title)->convertToEntities(),
            ee('CP/URL')->make('channels/layouts/' . $channel->getId())->compile() => lang('form_layouts'),
            '' => lang('edit')
        );

        ee()->view->cp_page_title = lang('edit_form_layout');

        $this->addJSAlerts();
        ee()->javascript->set_global('publish_layout', $this->prepareLayoutForJS($channel_layout));

        ee()->cp->add_js_script('ui', array('droppable', 'sortable'));
        ee()->cp->add_js_script('file', 'cp/channel/layout');

        ee()->cp->render('channels/layout/form', $vars);
    }

    private function addJSAlerts()
    {
        $alert_required = ee('CP/Alert')->makeBanner('tab-has-required-fields')
            ->asIssue()
            ->canClose()
            ->withTitle(lang('error_cannot_hide_tab'))
            ->addToBody(lang('error_tab_has_required_fields'));

        $alert_not_empty = ee('CP/Alert')->makeBanner('tab-has-fields')
            ->asIssue()
            ->canClose()
            ->withTitle(lang('error_cannot_remove_tab'))
            ->addToBody(lang('error_tab_has_fields'));

        ee()->javascript->set_global('alert.required', $alert_required->render());
        ee()->javascript->set_global('alert.not_empty', $alert_not_empty->render());
    }

    private function getForm($layout)
    {
        $disabled_choices = array();
        $roles = $this->getEligibleRoles($layout->Channel)
            ->getDictionary('role_id', 'name');

        $other_layouts = ee('Model')->get('ChannelLayout')
            ->with('PrimaryRoles')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('channel_id', $layout->Channel->channel_id);

        if (! $layout->isNew()) {
            // Exclude this layout
            $other_layouts->filter('layout_id', '!=', $layout->layout_id);
        }

        foreach ($other_layouts->all() as $other_layout) {
            foreach ($other_layout->PrimaryRoles as $role) {
                $roles[$role->role_id] = [
                    'label' => $role->name,
                    'value' => $role->role_id,
                    'instructions' => lang('assigned_to') . ' ' . $other_layout->layout_name
                ];
                $disabled_choices[] = $role->role_id;
            }
        }

        $selected_roles = ($layout->PrimaryRoles) ? $layout->PrimaryRoles->pluck('role_id') : array();

        $section = array(
            array(
                'title' => 'name',
                'fields' => array(
                    'layout_name' => array(
                        'type' => 'text',
                        'required' => true,
                        'value' => $layout->layout_name,
                    )
                )
            ),
            array(
                'title' => 'layout_roles',
                'desc' => 'roles_desc',
                'fields' => array(
                    'roles' => array(
                        'type' => 'checkbox',
                        'required' => true,
                        'choices' => $roles,
                        'disabled_choices' => $disabled_choices,
                        'value' => $selected_roles,
                        'no_results' => [
                            'text' => sprintf(lang('no_found'), lang('roles'))
                        ]
                    )
                )
            ),
        );

        return ee('View')->make('ee:_shared/form/section')
            ->render(array('name' => 'layout_options', 'settings' => $section));
    }

    private function getEligibleRoles(Channel $channel)
    {
        $super_admins = ee('Model')->get('Role', 1)
            ->all();

        $roles = array_merge($super_admins->asArray(), $channel->AssignedRoles->asArray());

        return new Collection($roles);
    }

    private function remove($layout_ids)
    {
        if (! is_array($layout_ids)) {
            $layout_ids = array($layout_ids);
        }

        $channel_layouts = ee('Model')->get('ChannelLayout', $layout_ids)
            ->filter('site_id', ee()->config->item('site_id'))
            ->all();

        $layout_names = array();

        foreach ($channel_layouts as $layout) {
            $layout_names[] = $layout->layout_name;
        }

        $channel_layouts->delete();
        ee('CP/Alert')->makeInline('layouts')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(lang('layouts_deleted_desc'))
            ->addToBody($layout_names)
            ->defer();
    }

    private function prepareLayoutForJS($channel_layout)
    {
        $field_layout = $channel_layout->field_layout;

        if (bool_config_item('enable_comments') && $channel_layout->Channel->comment_system_enabled) {
            $comment_expiration_date = [
                'field' => 'comment_expiration_date',
                'visible' => true,
                'collapsed' => false
            ];

            $allow_comments = [
                'field' => 'allow_comments',
                'visible' => true,
                'collapsed' => false
            ];

            $has_comment_expiration_date = false;
            $has_allow_comments = false;

            foreach ($field_layout as $i => $section) {
                foreach ($section['fields'] as $j => $field_info) {
                    if ($field_info['field'] == 'comment_expiration_date') {
                        $has_comment_expiration_date = true;

                        continue;
                    }

                    if ($field_info['field'] == 'allow_comments') {
                        $has_allow_comments = true;

                        continue;
                    }
                }
            }

            // Order matters...

            if (! $has_allow_comments) {
                $field_layout[0]['fields'][] = $allow_comments;
            }

            if (! $has_comment_expiration_date) {
                $field_layout[0]['fields'][] = $comment_expiration_date;
            }
        }

        return $field_layout;
    }
}

// EOF
