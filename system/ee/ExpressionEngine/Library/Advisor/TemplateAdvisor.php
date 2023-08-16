<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Advisor;

use ExpressionEngine\Model\Template\Template;

class TemplateAdvisor
{
    // ---TAGS--- //

    /**
     * Count bad tags
     *
     * @return Int
     */
    public function getBadTagCount()
    {
        $badTags = $this->getAllTags();
        foreach ($badTags as $i => $tag) {
            if ($tag['installed']) {
                unset($badTags[$i]);
            }
        }

        return count($badTags);
    }

    /**
     * All tags used in templates
     *
     * @return Array
     */
    public function getAllTags()
    {
        //need to make sure templates are in sync
        ee()->load->library('template');
        ee()->template->sync_from_files();

        $regexp = '/{exp:[a-zA-z0-9:]*/';
        $tags = array();

        $templates = ee('Model')->get('Template')->all()->asArray();
        $partials = ee('Model')->get('Snippet')->all()->asArray();

        $all_templates = array_merge($templates, $partials);

        foreach ($all_templates as $template) {
            if ($template instanceof Template) {
                $template_data = $template->template_data;
                $tmpl_info = [
                    'type' => 'template',
                    'path' => $template->getPath(),
                    'link' => ee('CP/URL', 'cp/design/template/edit/' . $template->getId())->compile()
                ];
            } else {
                $template_data = $template->snippet_contents;
                $tmpl_info = [
                    'type' => 'template_partial',
                    'path' => $template->snippet_name,
                    'link' => ee('CP/URL', 'cp/design/snippets/edit/' . $template->getId())->compile()
                ];
            }

            $template_data = ee()->template->remove_ee_comments($template_data);

            $tags_found = preg_match_all($regexp, $template_data, $keys, PREG_PATTERN_ORDER);

            $tmpl_info['details'][] = $keys;

            foreach ($keys[0] as $key) {
                $tag = $key . '}';

                if (!isset($tags[$tag])) {
                    $tag_arr = explode(':', $key);
                    $addon_name = $tag_arr[1];
                    $addon = ee('Addon')->get($addon_name);
                    $tags[$tag] = array(
                        'addon_name' => !empty($addon) ? $addon->getName() : $addon_name,
                        'installed' => (!empty($addon) && $addon->isInstalled()) ? true : false,
                        'count' => 0,
                        'templates' => array(),
                    );
                }

                $tags[$tag]['count']++;
                $tags[$tag]['templates'][$tmpl_info['path']] = $tmpl_info;
            }
        }

        ksort($tags);

        return $tags;
    }

    public function getDuplicateTemplateGroupsCount()
    {
        $duplicatesCheckQuery = ee()->db
            ->select('group_name')
            ->from('template_groups')
            ->where('site_id', ee()->config->item('site_id'))
            ->group_by('group_name, site_id')
            ->having('COUNT(group_name) > 1')
            ->get();
        return $duplicatesCheckQuery->num_rows();
    }

    public function getDuplicateTemplateGroups()
    {
        $duplicatesCheckQuery = ee()->db
            ->select('group_name')
            ->from('template_groups')
            ->where('site_id', ee()->config->item('site_id'))
            ->group_by('group_name, site_id')
            ->having('COUNT(group_name) > 1')
            ->get();
        if ($duplicatesCheckQuery->num_rows() > 0) {
            $duplicateGroupNames = array_map(function ($row) {
                return $row['group_name'];
            }, $duplicatesCheckQuery->result_array());
            // get the duplicate groups
            $duplicatesQuery = ee()->db
                ->select('group_name, group_id')
                ->from('template_groups')
                ->where('site_id', ee()->config->item('site_id'))
                ->where_in('group_name', $duplicateGroupNames)
                ->order_by('group_name', 'asc')
                ->order_by('group_id', 'asc')
                ->get();
            return $duplicatesQuery->result_array();
        }
        return array();
    }
}

// EOF
