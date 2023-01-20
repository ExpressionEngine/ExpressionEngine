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

use ExpressionEngine\Library\CP;
use ExpressionEngine\Controller\Channels\AbstractChannels as AbstractChannelsController;
use Mexitek\PHPColors\Color;

/**
 * Channel Status Controller
 */
class Status extends AbstractChannelsController
{
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->hasAny(
            'can_create_statuses',
            'can_edit_statuses',
            'can_delete_statuses'
        )) {
            show_error(lang('unauthorized_access'), 403);
        }
    }

    /**
     * AJAX endpoint for reordering statuses
     */
    public function reorder()
    {
        if (! ee('Permission')->can('edit_statuses')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $statuses = ee('Model')->get('Status')->all()->indexBy('status_id');

        foreach (ee('Request')->post('order') as $order => $status) {
            $statuses[$status['id']]->status_order = $order + 1;
            $statuses[$status['id']]->save();
        }

        return ['success'];
    }

    /**
     * Remove status handler
     */
    public function remove()
    {
        if (! ee('Permission')->can('delete_statuses')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $status_id = ee('Request')->post('content_id');

        if (! empty($status_id)) {
            ee('Model')->get('Status', $status_id)->delete();
        }

        return ['success'];
    }

    /**
     * New status form
     */
    public function create()
    {
        if (! ee('Permission')->can('create_statuses')) {
            show_error(lang('unauthorized_access'), 403);
        }

        return $this->statusForm();
    }

    /**
     * Edit status form
     */
    public function edit($status_id)
    {
        if (! ee('Permission')->can('edit_statuses')) {
            show_error(lang('unauthorized_access'), 403);
        }

        return $this->statusForm($status_id);
    }

    /**
     * Status creation/edit form
     *
     * @param	int	$status_id	ID of status to edit
     */
    private function statusForm($status_id = null)
    {
        $vars = [];
        if (is_null($status_id)) {
            $alert_key = 'created';
            $vars['cp_page_title'] = lang('create_status');
            $vars['base_url'] = ee('CP/URL')->make('channels/status/create');
            $status = ee('Model')->make('Status');
            if (empty($_POST)) {
                $status->Roles = ee('Model')->get('Role')->all();
            }
        } else {
            $status = ee('Model')->get('Status', $status_id)->first();

            if (! $status) {
                show_error(lang('unauthorized_access'), 403);
            }

            $alert_key = 'updated';
            $vars['cp_page_title'] = lang('edit_status');
            $vars['base_url'] = ee('CP/URL')->make('channels/status/edit/' . $status_id);
        }

        $roles = ee('Model')->get('Role')
            ->filter('role_id', 'NOT IN', array(1,2,3,4))
            ->order('name')
            ->all()
            ->getDictionary('role_id', 'name');
        //also include roles that are normally restricted, but can create entries on this site
        $can_create_entries = ee('Permission')->rolesThatHave('can_create_entries', null, true);
        if (!empty($can_create_entries)) {
            $extra_roles = ee('Model')->get('Role')
                ->filter('role_id', 'IN', $can_create_entries)
                ->filter('role_id', 'NOT IN', [1])
                ->order('name')
                ->all()
                ->getDictionary('role_id', 'name');
            foreach ($extra_roles as $role_id => $name) {
                $roles[$role_id] = $name;
            }
        }

        // Create the status example
        $status_style = '';
        if (! in_array($status->status, array('open', 'closed')) && $status->highlight != '') {
            $status_style = "style='background-color: var(--ee-bg-blank); border-color: #{$status->highlight}; color: #{$status->highlight};'";
        }

        $status_name = (empty($status->status)) ? lang('status') : $status->status;

        $status_class = str_replace(' ', '_', strtolower((string) $status->status));
        $status_example = '<span class="status-tag st-' . $status_class . '" ' . $status_style . '>' . $status_name . '</span>';

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'name',
                    'desc' => 'status_name_desc',
                    'fields' => array(
                        'status' => array(
                            'type' => 'text',
                            'value' => $status->getRawProperty('status'),
                            'required' => true,
                            'disabled' => in_array($status->getRawProperty('status'), ['open', 'closed'])
                                ? 'disabled' : null
                        )
                    )
                ),
                array(
                    'title' => 'highlight_color',
                    'desc' => 'highlight_color_desc',
                    'example' => $status_example,
                    'fields' => array(
                        'highlight' => array(
                            'type' => 'text',
                            'attrs' => 'class="color-picker"',
                            'value' => $status->highlight ?: '000000',
                            'required' => true
                        )
                    )
                )
            ),
            'permissions' => array(
                ee('CP/Alert')->makeInline('permissions-warn')
                    ->asWarning()
                    ->addToBody(lang('category_permissions_warning'))
                    ->addToBody(
                        sprintf(lang('category_permissions_warning2'), '<span class="icon--caution" title="exercise caution"></span>'),
                        'caution'
                    )
                    ->cannotClose()
                    ->render(),
                array(
                    'title' => 'status_access',
                    'desc' => 'status_access_desc',
                    'caution' => true,
                    'fields' => array(
                        'status_access' => array(
                            'type' => 'checkbox',
                            'choices' => $roles,
                            'value' => $status->Roles->pluck('role_id'),
                            'no_results' => [
                                'text' => sprintf(lang('no_found'), lang('roles'))
                            ]
                        )
                    )
                )
            )
        );

        if (! empty($_POST)) {
            $status = $this->setWithPost($status);
            $result = $status->validate();

            if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result)) {
                return $response;
            }

            if ($result->isValid()) {
                $status->save();

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('status_' . $alert_key))
                    ->addToBody(sprintf(lang('status_' . $alert_key . '_desc'), $status->status))
                    ->defer();

                return ['saveId' => $status->getId()];
            } else {
                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('status_not_' . $alert_key))
                    ->addToBody(lang('status_not_' . $alert_key . '_desc'))
                    ->now();
            }
        }

        $vars['ajax_validate'] = true;
        $vars['buttons'] = [
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
            ]
        ];

        return ee('View')->make('ee:_shared/form')->render($vars);
    }

    private function setWithPost($status)
    {
        if (! in_array($status->getRawProperty('status'), ['open', 'closed'])) {
            $status->status = ee()->input->post('status');
        }

        $status->highlight = ltrim(ee()->input->post('highlight'), '#');

        $access = ee()->input->post('status_access') ?: array();

        $roles = ee('Model')->get('Role', $access)
            ->filter('role_id', 'NOT IN', [1,2,3,4])
            ->all();

        if ($roles->count() > 0) {
            $status->Roles = $roles;
        } else {
            // Remove all member groups from this status
            $status->Roles = null;
        }

        return $status;
    }

    /**
     * Retrieve the foreground color for a given status color
     *
     * @deprecated 6.0.0
     *
     * @param string $color The hex color for the background
     * @return void
     */
    public function getForegroundColor($color = '')
    {
        ee()->logger->deprecated('6.0.0');

        $color = ee()->input->post('highlight');
        $foreground = $this->calculateForegroundFor($color);
        ee()->output->send_ajax_response($foreground);
    }

    /**
     * Retrieve the foreground color for a given status color
     *
     * @param string $color The hex color for the background
     * @return string The hex color best suited for the background color
     */
    protected function calculateForegroundFor($background)
    {
        try {
            $background = new Color($background);
            $foreground = ($background->isLight())
                ? $background->darken(100)
                : $background->lighten(100);
        } catch (\Exception $e) {
            $foreground = 'ffffff';
        }

        return $foreground;
    }
}

// EOF
