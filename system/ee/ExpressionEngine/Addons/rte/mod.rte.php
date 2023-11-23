<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use ExpressionEngine\Addons\Rte\RteHelper;

/**
 * Rte Module
 */
class Rte
{
    public function pages_autocomplete()
    {
        $search = ee()->input->get('search');
        $modified = ee()->input->get('t');
        if ($modified == 0) {
            $modified = ee()->localize->now;
        }

        ee()->output->set_status_header(200);
        @header("Cache-Control: max-age=172800, must-revalidate");
        @header('Vary: Accept-Encoding');
        @header('Last-Modified: ' . ee()->localize->format_date('%D, %d %M %Y %H:%i:%s', $modified, false) . ' GMT');
        @header('Expires: ' . ee()->localize->format_date('%D, %d %M %Y %H:%i:%s', ee()->localize->now + 172800, false) . ' GMT');

        if (ee('Request')->get('structured') == 'y' && ee('Addon')->get('structure')->isInstalled()) {
            $cache_key = 'rte_' . ee()->config->item('site_id') . '_structured';
            $pages = ee()->cache->get('/site_pages/' . md5($cache_key), \Cache::GLOBAL_SCOPE);
            if ($pages === false) {
                $pages = [];
                require_once PATH_ADDONS . 'structure/sql.structure.php';
                $sql = new Sql_structure();
                $structure_data = $sql->get_data();

                $exclude_status_list[] = "closed";
                $closed_parents = array();

                foreach ($structure_data as $key => $entry_data) {
                    if (in_array(strtolower($entry_data['status']), $exclude_status_list) || (isset($entry_data['parent_id']) && in_array($entry_data['parent_id'], $closed_parents))) {
                        $closed_parents[] = $entry_data['entry_id'];
                        unset($structure_data[$key]);
                    }
                }

                $structure_data = array_values($structure_data);

                $options = array();

                foreach ($structure_data as $page) {
                    if (isset($page['depth'])) {
                        $options[$page['entry_id']] = str_repeat('--', $page['depth']) . $page['title'];
                    } else {
                        $options[$page['entry_id']] = $page['title'];
                    }
                    $pages[] = (object) [
                        'id' => '@' . $page['entry_id'],
                        'text' => ee('Format')->make('Text', $options[$page['entry_id']])->convertToEntities()->compile(),
                        'href' => '{page_' . $page['entry_id'] . '}',
                        'entry_id' => $page['entry_id'],
                    ];
                }
                ee()->cache->save('/site_pages/' . md5($cache_key), $pages, 0, \Cache::GLOBAL_SCOPE);
            }
        } else {
            $pages = RteHelper::getSitePages($search);
        }

        ee()->output->send_ajax_response($pages);
    }
}
