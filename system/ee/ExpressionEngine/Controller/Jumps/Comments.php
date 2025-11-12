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
class Comments extends Jumps
{
    public function __construct()
    {
        parent::__construct();
        if (! ee('Permission')->hasAny(
            'can_moderate_comments',
            'can_edit_own_comments',
            'can_delete_own_comments',
            'can_edit_all_comments',
            'can_delete_all_comments'
        )) {
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

    public function list()
    {
        $entries = $this->loadEntries(ee()->input->post('searchString'));

        $response = array();

        foreach ($entries as $entry) {
            $response['viewCommentsFor' . $entry->getId()] = array(
                'icon' => 'fa-pencil-alt',
                'command' => $entry->title,
                'command_title' => $entry->title,
                'dynamic' => false,
                'addon' => false,
                'target' => ee('CP/URL')->make('publish/comments/entry/' . $entry->getId())->compile()
            );
        }

        $this->sendResponse($response);
    }


    private function loadEntries($searchString = false)
    {
        $entries = ee('Model')->get('ChannelEntry')->fields('entry_id', 'title')->filter('comment_total', '>', 0);

        if (!empty($searchString)) {
            // Break the search string into individual keywords so we can partially match them.
            $keywords = explode(' ', $searchString);

            foreach ($keywords as $keyword) {
                $entries->filter('title', 'LIKE', '%' . ee()->db->escape_like_str($keyword) . '%');
            }
        }

        return $entries->order('title', 'ASC')->limit(11)->all();
    }

}
