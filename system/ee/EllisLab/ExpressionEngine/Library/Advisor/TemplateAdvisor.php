<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Library\Advisor;

use EllisLab\ExpressionEngine\Model\Template\Template;

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
}

// EOF
