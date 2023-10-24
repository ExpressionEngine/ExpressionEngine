<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Jumps;

use CP_Controller;

/**
 * Member Create Controller
 */
class Channels extends Jumps
{
    public function __construct()
    {
        parent::__construct();
        if (!ee('Permission')->can('admin_channels')) {
            $this->sendResponse([]);
        }
    }

    /**
     * Publish Jump Data
     */
    public function index()
    {
        // Should never be here without another segment.
        show_error(lang('unauthorized_access'), 403);
    }

    public function edit()
    {
        $channels = $this->loadChannels(ee()->input->post('searchString'));

        $response = array();

        foreach ($channels as $channel) {
            $id = $channel->getId();
            $title = $channel->channel_title;

            $response['editChannel' . $channel->getId()] = array(
                'icon' => 'fa-pencil-alt',
                'command' => $channel->channel_title,
                'command_title' => $channel->channel_title,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('channels/edit/' . $channel->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }

    public function layouts()
    {
        $channels = $this->loadChannels(ee()->input->post('searchString'));

        $response = array();

        foreach ($channels as $channel) {
            $id = $channel->getId();
            $title = $channel->channel_title;

            $response['viewLayouts' . $channel->getId()] = array(
                'icon' => 'fa-object-group',
                'command' => $channel->channel_title,
                'command_title' => $channel->channel_title,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('channels/layouts/' . $channel->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }

    public function field()
    {
        $fields = $this->loadChannelFields(ee()->input->post('searchString'));

        $response = array();

        foreach ($fields as $field) {
            $response['editChannelField' . $field->field_id] = array(
                'icon' => 'fa-pencil-alt',
                'command' => $field->field_label . ' ' . $field->field_name,
                'command_title' => $field->field_label,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('fields/edit/' . $field->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }

    private function loadChannels($searchString = false)
    {
        $channels = ee('Model')->get('Channel')->filter('site_id', ee()->config->item('site_id'));

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                $channels->filter('channel_title', 'LIKE', '%' . ee()->db->escape_like_str($keyword) . '%');
            }
        }

        return $channels->order('channel_title', 'ASC')->limit(11)->all();
    }

    private function loadChannelFields($searchString = false)
    {
        $fields = ee('Model')->get('ChannelField');

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                $fields->filter('field_label', 'LIKE', '%' . ee()->db->escape_like_str($keyword) . '%');
            }
        }

        return $fields->order('field_label', 'ASC')->limit(11)->all();
    }
}
