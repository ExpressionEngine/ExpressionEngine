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

use CP_Controller;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Service\Model\Query\Builder;
use ExpressionEngine\Service\CP\Filter\Filter;
use ExpressionEngine\Service\Filter\FilterFactory;

/**
 * Abstract Channels
 */
abstract class AbstractChannels extends CP_Controller
{
    protected $perpage = 25;
    protected $page = 1;
    protected $offset = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee('CP/Alert')->makeDeprecationNotice()->now();

        if (! ee('Permission')->has('can_admin_channels')) {
            show_error(lang('unauthorized_access'), 403);
        }

        // Allow AJAX requests for category editing
        if (AJAX_REQUEST && in_array(ee()->router->method, array('createCat', 'editCat'))) {
            if (! ee('Permission')->hasAny(
                'can_create_categories',
                'can_edit_categories'
            )) {
                show_error(lang('unauthorized_access'), 403);
            }
        } else {
            if (! ee('Permission')->can('admin_channels')) {
                show_error(lang('unauthorized_access'), 403);
            } elseif (! ee('Permission')->hasAny(
                'can_create_channels',
                'can_edit_channels',
                'can_delete_channels',
                'can_create_channel_fields',
                'can_edit_channel_fields',
                'can_delete_channel_fields',
                'can_create_statuses',
                'can_delete_statuses',
                'can_edit_statuses'
            )) {
                show_error(lang('unauthorized_access'), 403);
            }
        }

        ee()->lang->loadfile('content');
        ee()->lang->loadfile('admin_content');
        ee()->lang->loadfile('channel');
        ee()->load->library('form_validation');

        // This header is section-wide
        $header = array(
            'title' => lang('channel_manager'),
        );

        if (ee('Permission')->hasAll('can_access_sys_prefs', 'can_admin_channels')) {
            $header['toolbar_items'] = [
                'settings' => [
                    'href' => ee('CP/URL')->make('settings/content-design'),
                    'title' => lang('settings')
                ]
            ];
        }

        if (ee('Permission')->has('can_create_channels')) {
            $header['action_buttons'] = [
                [
                    'text' => lang('import'),
                    'href' => '#',
                    'rel' => 'import-channel'
                ],
                [
                    'text' => lang('new_channel'),
                    'href' => ee('CP/URL', 'channels/create')
                ]
            ];
        }

        ee()->view->header = $header;

        ee()->javascript->set_global(
            'sets.importUrl',
            ee('CP/URL', 'channels/sets')->compile()
        );

        ee()->javascript->set_global(array(
            'lang.edit_element' => lang('edit_element'),
            'lang.remove_btn' => lang('remove_btn'),
        ));

        ee()->cp->add_js_script(array(
            'file' => array('cp/channel/menu'),
        ));

        ee()->cp->add_js_script('file', array('cp/conditional_logic'));

    }

    /**
     * Display filters
     *
     * @param filter object
     * @return void
     */
    protected function renderFilters(FilterFactory $filters)
    {
        ee()->view->filters = $filters->render($this->base_url);
        $this->params = $filters->values();
        $this->perpage = $this->params['perpage'];
        $this->page = ((int) ee()->input->get('page')) ?: 1;
        $this->offset = ($this->page - 1) * $this->perpage;

        $this->base_url->addQueryStringVariables($this->params);
    }
}
// END CLASS

// EOF
