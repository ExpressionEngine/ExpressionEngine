<?php

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
require_once PATH_ADDONS . 'structure/sql.structure.php';
require_once PATH_ADDONS . 'structure/addon.setup.php';
require_once PATH_ADDONS . 'structure/helper.php';
require_once PATH_ADDONS . 'structure/libraries/nestedset/structure_nestedset.php';
require_once PATH_ADDONS . 'structure/libraries/nestedset/structure_nestedset_adapter_ee.php';
require_once PATH_ADDONS . 'structure/libraries/Structure_nav_parser.php';
if (version_compare(APP_VER, '6.0.0', '>=') && defined('PATH_PRO_ADDONS') && is_dir(PATH_PRO_ADDONS)) {
    require_once PATH_PRO_ADDONS . 'channel/mod.channel.php';
} else {
    require_once PATH_MOD . 'channel/mod.channel.php';
}

use ExpressionEngine\Addons\Structure\Libraries\Structure_core_nav_parser;

class Structure extends Channel
{
    public $nset;
    public $channel_type = '';
    public $debug = false;

    public $query_string;
    public $cat_trigger;
    public $site_pages;
    public $sql;

    public function __construct()
    {
        parent::__construct();

        $this->sql = new Sql_structure();
        $adapter = new Structure_Nestedset_Adapter_Ee('exp_structure', 'lft', 'rgt', 'entry_id');
        $this->nset = new Structure_Nestedset($adapter);

        $this->cat_trigger = ee()->config->item('reserved_category_word');
        $this->site_pages = $this->sql->get_site_pages();

        // -------------------------------------------
        //  Prepare Cache
        // -------------------------------------------

        // if (! isset(ee()->session->cache['structure']))
        // {
        //  ee()->session->cache['structure'] = array();
        // }
        // $this->cache =& ee()->session->cache['structure'];
    }

    /**
     * TAG: nav
     *
     * PARAMETERS:
     * entry_id - allow "parent" node override
     * start_from
     * add_level_classes
     * add_unique_ids = "yes|entry_id|no"
     * current_class
     * include_ul
     * exclude
     * status
     * css_id
     * show_depth = "all"
     * mode="sub|full|main|sitemap"
     * max_depth
     *
     *
     **/
    public function nav()
    {
        $site_id = ee()->config->item('site_id');
        $uri = $this->sql->get_uri();
        $site_pages = $this->site_pages;
        $current_id = ee()->TMPL->fetch_param('entry_id', array_search($uri, $this->site_pages['uris']));
        $start_from = ee()->TMPL->fetch_param('start_from', '/');
        $strict_start_from = ee()->TMPL->fetch_param('strict_start_from', false);
        $mode = ee()->TMPL->fetch_param('mode', 'sub');
        $show_depth = ee()->TMPL->fetch_param('show_depth', 1); // depth past current to be shown by the tag
        $max_depth = ee()->TMPL->fetch_param('max_depth', -1); // max depth ever shown by the tag
        $status = ee()->TMPL->fetch_param('status', 'open');
        $include = ee()->TMPL->fetch_param('include', array());
        $recursive_overview = ee()->TMPL->fetch_param('recursive_overview', 'no');
        $exclude = ee()->TMPL->fetch_param('exclude', array());
        $show_overview = ee()->TMPL->fetch_param('show_overview', false);
        $rename_overview = ee()->TMPL->fetch_param('rename_overview', 'Overview');
        $show_expired = ee()->TMPL->fetch_param('show_expired', 'no');
        $show_future = ee()->TMPL->fetch_param('show_future_entries', 'no');
        $override_hidden_state = ee()->TMPL->fetch_param('override_hidden_state', 'no');
        $include_site_url = ee()->TMPL->fetch_param('site_url', 'no');

        $branch_entry_id = 0; // default to 'root'

        if ($start_from != '/') {
            $start_from = Structure_Helper::remove_double_slashes('/' . html_entity_decode($start_from) . '/');

            $settings = $this->sql->get_settings();
            $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y';

            if ($trailing_slash === false) {
                $start_from = rtrim($start_from, '/');
            }

            // find 'start_from' in pages
            $found_key = array_search($start_from, $this->site_pages['uris']);

            if ($found_key !== false) {
                $branch_entry_id = $found_key;
            } elseif ($strict_start_from !== false) {
                return '';
            }
        }

        $start_from = Structure_Helper::remove_double_slashes($start_from);

        // This level defines how deep to go if recursive is set to no. For / it's 1 deep, if anything else
        // it's 0.
        if ($start_from == "/") {
            $level = "1";
        } else {
            $level = "0";
        }

        $selective_data = $this->sql->get_selective_data($site_id, $current_id, $branch_entry_id, $mode, $show_depth, $max_depth, $status, $include, $exclude, $show_overview, $rename_overview, $show_expired, $show_future, $override_hidden_state, $recursive_overview, $include_site_url);

        $html = $this->sql->generate_nav($selective_data, $current_id, $branch_entry_id, $mode, $show_overview, $rename_overview, $override_hidden_state, $recursive_overview, $level);

        return $html;
    }

    public function entries()
    {
        $parent_id = ee()->TMPL->fetch_param('parent_id', false);
        $include_hidden = ee()->TMPL->fetch_param('include_hidden', 'n');
        $dynamic = ee()->TMPL->fetch_param('dynamic', false);

        $cat = '';

        // If dynamic does not equal "no", check for a category to filter by as well.
        if ($dynamic !== 'no') {
            $uricount = ee()->uri->total_segments();

            // Let's iterate through all the segment URIs for the trigger word
            for ($x = 1; $x <= $uricount; $x++) {
                if (ee()->uri->segment($x) == $this->cat_trigger) {
                    $cat = ee()->uri->segment($x + 1);

                    break;
                }
            }
        }

        if (is_numeric($parent_id)) {
            $child_ids = $this->sql->get_child_entries($parent_id, $cat, $include_hidden);
            $fixed_order = $child_ids !== false && is_array($child_ids) && count($child_ids) > 0 ? implode('|', $child_ids) : false;

            if ($fixed_order) {
                ee()->TMPL->tagparams['fixed_order'] = $fixed_order;
            } else {
                ee()->TMPL->tagparams['entry_id'] = '-1'; // No results
            }
        }

        return parent::entries();
    }

    /** -------------------------------------
    /**  Tag: sitemap
    /**
    /**  Returns a full  tree of all site
    /**  pages in <ul>, <xml> or text format.
    /** -------------------------------------*/
    public function sitemap()
    {
        $html = "";

        $css_id = ee()->TMPL->fetch_param('css_id');
        $css_id = $css_id ? strtolower($css_id) : "sitemap";

        if ($css_id == "none") {
            $css_id = '';
        }

        $css_class = ee()->TMPL->fetch_param('css_class');
        $css_class = $css_class ? strtolower($css_class) : '';

        // DEPRECIATED SUPPORT for exclude_status and include_status
        $include_status = strtolower(ee()->TMPL->fetch_param('include_status'));
        $exclude_status = strtolower(ee()->TMPL->fetch_param('exclude_status'));

        // New, native EE status mode
        $status = ee()->TMPL->fetch_param('status', 'open');
        $status = $status == '' ? array() : explode('|', $status);
        $status = array_map('strtolower', $status); // match MySQL's case-insensitivity
        $status_state = 'positive';

        // Check for "not "
        if (! empty($status) && substr($status[0], 0, 4) == 'not ') {
            $status_state = 'negative';
            $status[0] = trim(substr($status[0], 3));
            $status[] = 'closed';
        }

        $include_status_list = explode('|', $include_status);
        $exclude_status_list = explode('|', $exclude_status);

        // Remove the default "open" status if explicitely set
        if (in_array('open', $exclude_status_list)) {
            $status = array_filter($status, create_function('$v', 'return $v != "open";'));
        }

        if ($status_state == 'positive') {
            $status = array_merge($status, $include_status_list);
        } elseif ($status_state == 'negative') {
            $status = array_merge($status, $exclude_status_list);
        }

        // Retrieve entry_ids to exclude
        $exclude = explode("|", ee()->TMPL->fetch_param('exclude'));

        // Sitemap mode -- Completely alternate output
        $mode = strtolower(ee()->TMPL->fetch_param('mode'));
        if ($mode == "") {
            $mode = "html";
        }

        // Get site pages data
        $site_pages = $this->site_pages;

        // Get all pages
        $pages = $this->sql->get_data();

        // Remove anything to be excluded from the results array
        $closed_parents = array();

        foreach ($pages as $key => $entry_data) {
            if (
                $status_state == 'negative' && in_array(strtolower($entry_data['status']), $status)
                || ($status_state == 'positive' && ! in_array(strtolower($entry_data['status']), $status))
                || in_array($entry_data['parent_id'], $closed_parents)
                || in_array($entry_data['entry_id'], $exclude)
            ) {
                $closed_parents[] = $entry_data['entry_id'];
                unset($pages[$key]);
            }
        }

        // Make sure array indices are incremental (0..X)
        $pages = array_values($pages);
        $home = ee()->functions->fetch_site_index(0, 0);

        /** --------------------------------
        /**  XML Sitemap Output
        /** --------------------------------*/
        if ($mode == "xml") {
            $html .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset' . "\n\t" . 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n\t" . 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n\t" . 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n" . '<!-- Created with Structure for ExpressionEngine (https://eeharbor.com/structure) -->' . "\n";
            foreach ($pages as $page) {
                if (!empty($site_pages['uris'][$page['entry_id']])) {
                    $page_uri = $site_pages['uris'][$page['entry_id']];
                    $item_uri = Structure_Helper::remove_double_slashes($home . $page_uri);

                    $xml_item = '<url>' . "\n\t" . '<loc>' . $item_uri . '</loc>' . "\n\t" . '<priority>1.00</priority>' . "\n" . '</url>' . "\n";

                    $html .= $xml_item;
                }
            }

            $html .= '</urlset>';
        } elseif ($mode == "text") {
            foreach ($pages as $page) {
                $page_uri = $site_pages['uris'][$page['entry_id']];
                $item_uri = Structure_Helper::remove_double_slashes($home . $page_uri);
                $xml_item = $item_uri . "\n";
                $html .= $xml_item;
            }
        } else {
            /** --------------------------------
            /**  HTML Sitemap Output
            /** --------------------------------*/
            $html .= "<ul id=\"$css_id\"" . ($css_class != '' ? " class=\"$css_class\"" : '') . ">\n";
            $ul_open = false;
            $last_page_depth = 0;

            foreach ($pages as $page) {
                if (!empty($site_pages['uris'][$page['entry_id']])) {
                    $page_uri = $site_pages['uris'][$page['entry_id']];
                    $item_uri = Structure_Helper::remove_double_slashes($home . $page_uri);

                    // Start a sub nav
                    if ($page['depth'] > $last_page_depth) {
                        $html = substr($html, 0, -6);
                        $html .= "\n<ul>\n";
                        $ul_open = true;
                    }

                    // Close a sub nav
                    if ($page['depth'] < $last_page_depth) {
                        // Finds the previous entry
                        preg_match("~\<li( class=\".+?\"){0,1}>(\<.+?\</.+?>)~", $list_item, $matches);
                        // Creates the new class entry
                        if ($matches[1]) {
                            $last_item_class = substr($matches[1], 0, -1) . " last\"";
                        } else {
                            $last_item_class = " class=\"last\"";
                        }
                        // Stores the inner of the <li>
                        $last_item_inner = $matches[2];

                        // Replace the string
                        $html = str_replace($list_item, "<li$last_item_class>$last_item_inner</li>\n", $html);

                        // Calculate how many levels back I need to go
                        $back_to = $last_page_depth - $page['depth'];
                        $html .= str_repeat("</ul>\n</li>\n", $back_to);
                        $ul_open = false;
                    }

                    // Is this the last in the list?
                    $classes = '';
                    $page_title = "";

                    if ($page == end($pages)) {
                        $classes = ' class="page-' . $page['entry_id'] . ' last"';
                    } else {
                        $classes = ' class="page-' . $page['entry_id'] . '"';
                    }

                    if (ee()->config->item('auto_convert_high_ascii') != 'n') {
                        $page_title = $page['title'];
                    } else {
                        $page_title = htmlspecialchars($page['title']);
                    }

                    $list_item = "<li$classes><a href='$item_uri'>" . $page_title . "</a></li>\n";

                    $html .= $list_item;

                    $last_page_depth = $page['depth'];
                }

                // Make sure all the ULs are closed
                if ($last_page_depth > 1) {
                    $html .= "</ul>\n";
                    $html .= str_repeat("</li>\n</ul>\n", $last_page_depth);
                } else {
                    $html .= $ul_open ? "</ul>\n</li>\n</ul>" : "</ul>\n";
                }
            }
        }

        return $html;
    }

    /** -------------------------------------
    /**  Tag: siblings
    /** -------------------------------------*/
    public function siblings()
    {
        $tagdata = ee()->TMPL->tagdata;

        // Fetch core data
        $uri = $this->sql->get_uri();
        $site_pages = $this->sql->get_site_pages();

        // Get entry_id. Parameter override available.
        $entry_id = ee()->TMPL->fetch_param('entry_id', array_search($uri, $site_pages['uris']));
        $parent_id = $this->sql->get_parent_id($entry_id);

        if ($parent_id == $this->sql->get_home_page_id()) {
            $parent_id = 0;
        }

        $site_id = ee()->config->item('site_id');
        $status = ee()->TMPL->fetch_param('status', 'open');
        $include = ee()->TMPL->fetch_param('include', array());
        $exclude = ee()->TMPL->fetch_param('exclude', array());
        $show_expired = ee()->TMPL->fetch_param('show_expired', 'no');
        $show_future = ee()->TMPL->fetch_param('show_future_entries', 'no');

        $custom_title_fields = $this->sql->create_custom_titles(true);

        $pages = $this->sql->get_selective_data($site_id, $entry_id, $parent_id, 'sub', 1, -1, $status, $include, $exclude, false, false, $show_expired, $show_future); // Get parent and all children

        if (! is_array($pages) || count($pages) == 0) {
            return null;
        }

        $next = array();
        $prev = array();
        // echo $entry_id;
        // Filter out pages not on same level
        foreach ($pages as $key => $page) {
            if ($entry_id && array_key_exists($entry_id, $pages) && $page['depth'] != $pages[$entry_id]['depth']) {
                unset($pages[$key]);
            }
        }

        $pages = array_values($pages); // Zero index for easy array navigation

        foreach ($pages as $key => $page) {
            if ($page['entry_id'] == $entry_id) {
                if (array_key_exists($key - 1, $pages) && $page['depth'] == $pages[$key - 1]['depth']) {
                    $prev[] = array(
                        'title' => $custom_title_fields !== false ? $custom_title_fields[$pages[$key - 1]['entry_id']] : $pages[$key - 1]['title'],
                        'url' => $pages[$key - 1]['uri'],
                        'entry_id' => $pages[$key - 1]['entry_id'],
                        'parent_id' => $pages[$key - 1]['parent_id'],
                        'channel_id' => $pages[$key - 1]['channel_id'],
                        'status' => $pages[$key - 1]['status']
                    );
                }
                if (array_key_exists($key + 1, $pages) && $page['depth'] == $pages[$key + 1]['depth']) {
                    $next[] = array(
                        'title' => $custom_title_fields !== false ? $custom_title_fields[$pages[$key + 1]['entry_id']] : $pages[$key + 1]['title'],
                        'url' => $pages[$key + 1]['uri'],
                        'entry_id' => $pages[$key + 1]['entry_id'],
                        'parent_id' => $pages[$key + 1]['parent_id'],
                        'channel_id' => $pages[$key + 1]['channel_id'],
                        'status' => $pages[$key + 1]['status']
                    );
                }
            }
        }
        $variable_row = array('prev' => $prev, 'next' => $next);
        $vars[] = $variable_row;

        return ee()->TMPL->parse_variables($tagdata, $vars);
    }

    /** -------------------------------------
    /**  Tag: traverse
    /** -------------------------------------*/
    public function traverse()
    {
        $uri = $this->sql->get_uri();

        $site_pages = $this->sql->get_site_pages();

        $site_id = ee()->config->item('site_id');

        $tagdata = ee()->TMPL->tagdata;

        $current_id = ee()->TMPL->fetch_param('entry_id', array_search($uri, $site_pages['uris']));
        $status = ee()->TMPL->fetch_param('status', 'open');
        $include = ee()->TMPL->fetch_param('include', array());
        $exclude = ee()->TMPL->fetch_param('exclude', array());
        $show_expired = ee()->TMPL->fetch_param('show_expired', 'no');
        $show_future = ee()->TMPL->fetch_param('show_future_entries', 'no');

        $pages = $this->sql->get_selective_data($site_id, $current_id, 0, 'full', 1, -1, $status, $include, $exclude, false, false, $show_expired, $show_future); // Get parent and all children

        $next = array();
        $prev = array();

        $pages = array_values($pages); // Zero index for easy array navigation

        // print_r($pages);

        foreach ($pages as $key => $page) {
            if ($page['entry_id'] == $current_id) {
                if (array_key_exists($key - 1, $pages)) {
                    $prev[] = array(
                        'title' => $pages[$key - 1]['title'],
                        'url' => $pages[$key - 1]['uri'],
                        'entry_id' => $pages[$key - 1]['entry_id'],
                        'parent_id' => $pages[$key - 1]['parent_id'],
                        'channel_id' => $pages[$key - 1]['channel_id'],
                        'status' => $pages[$key - 1]['status']
                    );
                }
                if (array_key_exists($key + 1, $pages)) {
                    $next[] = array(
                        'title' => $pages[$key + 1]['title'],
                        'url' => $pages[$key + 1]['uri'],
                        'entry_id' => $pages[$key + 1]['entry_id'],
                        'parent_id' => $pages[$key + 1]['parent_id'],
                        'channel_id' => $pages[$key + 1]['channel_id'],
                        'status' => $pages[$key + 1]['status']
                    );
                }
            }
        }
        $variable_row = array('prev' => $prev, 'next' => $next);
        $vars[] = $variable_row;

        return ee()->TMPL->parse_variables($tagdata, $vars);
    }

    /** -------------------------------------
    /**  Create a Breadcrumb Trail
    /** -------------------------------------*/
    public function breadcrumb()
    {
        $site_pages = $this->sql->get_site_pages();

        if (! $site_pages) {
            return false;
        }

        // Get parameters
        $separator = ee()->TMPL->fetch_param('separator', '&raquo;');

        $inc_separator = ee()->TMPL->fetch_param('inc_separator');
        $separator = $inc_separator === 'no' ? '' : $separator;

        $inc_home = ee()->TMPL->fetch_param('inc_home');
        $inc_home = $inc_home === 'no' ? false : true;

        $inc_here = ee()->TMPL->fetch_param('inc_here');
        $inc_here = $inc_here === 'no' ? false : true;

        $here_as_title = ee()->TMPL->fetch_param('here_as_title');
        $here_as_title = $here_as_title === 'yes' ? true : false;

        $wrap_each = ee()->TMPL->fetch_param('wrap_each', '');
        $wrap_each_class = ee()->TMPL->fetch_param('wrap_each_class');
        $wrap_here = ee()->TMPL->fetch_param('wrap_here', '');
        $wrap_separator = ee()->TMPL->fetch_param('wrap_separator', '');
        $separator = $wrap_separator ? "<{$wrap_separator}>{$separator}</{$wrap_separator}>" : $separator;

        $add_last_class = ee()->TMPL->fetch_param('add_last_class') != 'no';

        $channel_id = ee()->TMPL->fetch_param('channel', false);

        // Are we passed a URI to work from? If not use current URI
        $uri = ee()->TMPL->fetch_param('uri', $this->sql->get_uri());
        $uri = html_entity_decode($uri);

        // get current entry id
        if ($channel_id !== false && is_numeric($channel_id)) {
            // Filter by channel_id. Allows duplicate URIs to exist and still be
            // relatively useful, eg. multi-language
            $channel_entries = $this->sql->get_entries_by_channel($channel_id);
            $entry_ids = array_keys($site_pages['uris'], $uri);
            $entry_id = current(array_intersect($channel_entries, $entry_ids));

            if ($entry_id === false) {
                $entry_id = array_search($uri, $site_pages['uris']);
            }
        } else {
            $entry_id = array_search($uri, $site_pages['uris']);
        }

        $entry_id = ee()->TMPL->fetch_param('entry_id', $entry_id);

        // get node of the current entry
        $node = $entry_id ? $this->nset->getNode($entry_id) : false;

        // node does not have any structure data we return nothing to prevent errors
        if ($node === false && ! $entry_id) {
            return false;
        }

        // if we have an entry id but no node, we have listing entry
        if ($entry_id && ! $node) {
            // get entry's parent id
            $pid = $this->get_pid_for_listing_entry($entry_id);

            // get node of parent entry
            $node = $this->nset->getNode($pid);
        }

        $right = $node['right'];
        $inc_current = isset($pid) ? '=' : '';
        $site_id = ee()->config->item('site_id');
        $sql = "SELECT node.*, expt.title
                FROM exp_structure AS node
                INNER JOIN exp_channel_titles AS expt
                    ON node.entry_id = expt.entry_id
                WHERE node.lft > 1
                    AND node.lft < $right
                    AND node.rgt >$inc_current $right
                    AND expt.site_id = $site_id
                    -- AND node.lft != 2
                ORDER BY node.lft";

        $result = ee()->db->query($sql);

        $home_entry = array_search('/', $site_pages['uris']) ? array_search('/', $site_pages['uris']) : 0; #default to zero

        $site_index = trim(ee()->functions->fetch_site_index(0, 0), '/');
        $home_link = ee()->TMPL->fetch_param('home_link', $site_index);

        $custom_title_fields = $this->sql->create_custom_titles(true);

        // echo "<pre>";
        // var_dump($custom_title_fields);
        // exit;

        // print_r($custom_title_fields);

        $crumbs = array();

        $home_title = ee()->TMPL->fetch_param('rename_home', 'Home');

        if ($inc_home) {
            $crumbs[] = '<a href="' . $home_link . '">' . $home_title . '</a>';
        }

        foreach ($result->result_array() as $entry) {
            if ($entry['entry_id'] == $home_entry) { #remove homepage
                continue;
            }

            $title = $custom_title_fields !== false ? $custom_title_fields[$entry['entry_id']] : $entry['title'];
            $crumbs[] = '<a href="' . Structure_Helper::remove_double_slashes($home_link . $site_pages['uris'][$entry['entry_id']]) . '">' . $title . '</a>';
        }

        // If inc_here param is yes/true then show the here name
        if ($inc_here) {
            // If here_as_title is yes/true then show here as page title
            if ($here_as_title) {
                $title = $custom_title_fields !== false ? array_key_exists($entry_id, $custom_title_fields) ? $custom_title_fields[$entry_id] : $this->sql->get_entry_title($entry_id) : $this->sql->get_entry_title($entry_id);

                if (ee()->TMPL->fetch_param('encode_titles', 'yes') === "yes") {
                    $title = htmlspecialchars($title);
                }

                $crumbs[] = !empty($wrap_here) ? "<{$wrap_here}>$title</{$wrap_here}>" : $title;
            } else {
                $crumbs[] = !empty($wrap_here) ? "<{$wrap_here}>Here</{$wrap_here}>" : "Here";
            }
        }

        $last_class = 'class="last';
        $last_class .= ($wrap_each_class) ? ' ' . $wrap_each_class . '"' : '"';
        $wrap_class = ($wrap_each_class) ? ' ' . 'class="' . $wrap_each_class . '"' : '';
        $count = count($crumbs);
        for ($i = 0; $i < $count; $i++) {
            if (! empty($separator) && $i != ($count - 1)) {
                $crumbs[$i] = "{$crumbs[$i]} {$separator} ";
            }

            if (! empty($wrap_each)) {
                if ($add_last_class === true && $i == $count - 1) {
                    $crumbs[$i] = "<{$wrap_each}  {$last_class}>{$crumbs[$i]}</{$wrap_each}>";
                } else {
                    $crumbs[$i] = "<{$wrap_each}{$wrap_class}>{$crumbs[$i]}</{$wrap_each}>";
                }
            } else {
                if ($add_last_class === true && $i == $count - 1) {
                    $crumbs[$i] = '<span class="last">' . $crumbs[$i] . '</span>';
                }
            }
        }

        return implode('', $crumbs);
    }

    /** -------------------------------------
    /**  Tag: titletrail
    /** -------------------------------------*/
    public function titletrail()
    {
        $site_pages = $this->sql->get_site_pages();

        if (! $site_pages) {
            return false;
        }

        $site_id = ee()->config->item('site_id');
        $uri = $this->sql->get_uri();

        // get current entry id
        $entry_id = ee()->TMPL->fetch_param('entry_id', array_search($uri, $site_pages['uris']));

        // get node of the current entry
        $node = $entry_id ? $this->nset->getNode($entry_id) : false;

        // node does not have any structure data we return site_name to prevent errors
        if ($node === false && !$entry_id) {
            return stripslashes(ee()->config->item('site_name'));
        }

        // if we have an entry id but no node, we have listing entry
        if ($entry_id && ! $node) {
            // get entry's parent id
            $pid = $this->get_pid_for_listing_entry($entry_id);

            // get node of parent entry because we will be showing nav sub from its view point
            $node = $this->nset->getNode($pid);
        }

        $separator = ee()->TMPL->fetch_param('separator', '|');
        $separator = ' ' . $separator . ' '; // Add space around it.

        $reverse = ee()->TMPL->fetch_param('reverse', false);
        $site_name = ee()->TMPL->fetch_param('site_name', false);

        $right = $node['right'];
        $inc_current = isset($pid) ? '=' : '';

        $sql = "SELECT node.*, expt.title
                FROM exp_structure AS node
                INNER JOIN exp_channel_titles AS expt
                    ON node.entry_id = expt.entry_id
                WHERE node.lft > 1
                    AND node.lft < $right
                    AND node.rgt >$inc_current $right
                    AND expt.site_id = $site_id
                    AND node.lft != 2
                ORDER BY node.lft DESC";

        $query = ee()->db->query($sql);
        $results = $query->result_array();
        $query->free_result();

        // Create an array of the page titles and site name
        // If reverse param is true then flip it prior to output

        // prepend current entry to results
        // so that custom titles are applied
        array_unshift($results, array(
            'entry_id' => $entry_id,
            'title' => $this->sql->get_entry_title($entry_id)
        ));

        $custom_titles = $this->sql->create_custom_titles();
        $encode_titles = ee()->TMPL->fetch_param('encode_titles', 'yes') === "yes";

        $title_array = array();
        foreach ($results as $entry) {
            $title = $custom_titles && isset($custom_titles[$entry['entry_id']]) ? $custom_titles[$entry['entry_id']] : $entry['title'];

            if ($encode_titles) {
                $title = htmlspecialchars($title);
            }

            $title_array[] = $title;
        }

        if ($site_name === 'yes') {
            $title_array[] = stripslashes(ee()->config->item('site_name'));
        }

        if ($reverse == 'yes') {
            $title_array = array_reverse($title_array);
        }

        return implode($separator, $title_array);
    }

    /** -------------------------------------
    /**  Tag: top_level_title
    /**  Outputs the first segment's title
    /** -------------------------------------*/
    public function top_level_title()
    {
        $site_pages = $this->sql->get_site_pages();

        if (! $site_pages) {
            return false;
        }

        $seg1 = '/' . ee()->uri->segment(1) . '/';  // get segment 1 value

        $top_id = array_search($seg1, $site_pages['uris']);

        if ($top_id) {
            ee()->db->where('entry_id', $top_id);
            ee()->db->limit(1);

            $query = ee()->db->get('exp_channel_titles');

            if ($query->num_rows() == 1) {
                $row = $query->row();

                return $row->title;
            }
        }

        return '';
    }

    /** -------------------------------------
    /**  Tag: parent_title
    /** -------------------------------------*/
    public function parent_title($entry_id = null)
    {
        $html = "";

        // get site pages data
        $site_pages = $this->sql->get_site_pages();

        if (!$site_pages) {
            return false;
        }

        // get current uri path
        $uri = $this->sql->get_uri();
        // get current entry id
        $entry_id = $entry_id ? $entry_id : array_search($uri, $site_pages['uris']);
        // get node of the current entry
        $node = $entry_id ? $this->nset->getNode($entry_id) : false;

        // node does not have any structure data we return site_name to prevent errors
        if ($node === false && ! $entry_id) {
            return stripslashes(ee()->config->item('site_name'));
        }

        // if we have an entry id but no node, we have listing entry
        if ($entry_id && ! $node) {
            // get entry's parent id
            $pid = $this->get_pid_for_listing_entry($entry_id);

            // get node of parent entry
            // because we will be showing nav sub from its view point
            $node = $this->nset->getNode($pid);
        }

        $right = $node['right'];
        $inc_current = isset($pid) ? '=' : '';

        $site_id = ee()->config->item('site_id');

        $sql = "SELECT node.*, expt.title
                FROM exp_structure AS node
                INNER JOIN exp_channel_titles AS expt
                    ON node.entry_id = expt.entry_id
                WHERE node.lft > 1
                    AND node.lft < $right
                    AND node.rgt >$inc_current $right
                    AND expt.site_id = $site_id
                ORDER BY node.lft DESC";
        $result = ee()->db->query($sql);

        $result_array = $result->result_array();

        return @$result_array[0]['title'];
    }

    /** -------------------------------------
    /**  Tag: page_slug
    /** -------------------------------------*/
    public function page_slug($entry_id = null)
    {
        $slug = "";
        $uri = "";

        // get site pages data
        $site_pages = $this->sql->get_site_pages();

        if (!$site_pages) {
            return false;
        }

        $uri = $this->sql->get_uri();

        if ($entry_id == null) {
            // get current entry id
            $current_page_entry_id = array_search($uri, $site_pages['uris']);

            // Fetch params
            $entry_id = ee()->TMPL->fetch_param('entry_id');
            $entry_id = $entry_id ? $entry_id : $current_page_entry_id;
        }

        // get page uri slug without parents
        @$uri = $site_pages['uris'][$entry_id];

        // if there are no / then we have a root slug already, else get the end
        $slug .= trim($uri, '/');

        if (strpos($slug, '/')) {
            $slug = substr(strrchr($slug, '/'), 1);
        }

        return $slug;
    }

    /** -------------------------------------
    /**  Tag: page_id
    /** -------------------------------------*/
    public function page_id($entry_uri = null)
    {
        // get site pages data
        $site_pages = $this->sql->get_site_pages();

        if (! $site_pages) {
            return false;
        }

        // Fetch params
        $entry_uri = ee()->TMPL->fetch_param('entry_uri');

        if ($entry_uri == null || $entry_uri == '') {
            $entry_uri = '/' . ee()->uri->uri_string() . '/';
        }

        // get current entry id
        $current_page_entry_id = array_search($entry_uri, $site_pages['uris']);

        return $current_page_entry_id;
    }

    // Child IDs function
    // Returns a string of IDs for a given parent

    public function child_ids()
    {
        $site_pages = $this->sql->get_site_pages();

        if (! $site_pages) {
            return false;
        }

        // Fetch our parent ID, or if none default to the current page
        $parent = ee()->TMPL->fetch_param('entry_id');
        $start_from = ee()->TMPL->fetch_param('start_from') ? ee()->TMPL->fetch_param('start_from') : false;

        // Only do an automatic lookup if we're not requiring a parent ID, and no entry_id was defined.
        if (! $parent and ! $start_from) {
            // Find the parent in the site pages array using URL
            $current_uri = implode('/', ee()->uri->segment_array());
            $parent = array_search("/$current_uri/", $site_pages['uris']);
        } elseif ($start_from) {
            $start_from = trim($start_from, '/');
            $parent = array_search("/$start_from/", $site_pages['uris']);
        }

        // If nothing was found, return empty, otherwise the query below will return all child pages.
        if (! $parent) {
            return;
        }

        // Grab the delimiter, or default to a pipe
        $delimiter = ee()->TMPL->fetch_param('delimiter');
        $delimiter = $delimiter ? $delimiter : '|';
        $site_id = ee()->config->item('site_id');
        $results = ee()->db->query("SELECT entry_id FROM exp_structure WHERE parent_id = '{$parent}' AND entry_id != '0' AND site_id = $site_id ORDER BY lft ASC");

        $entries = array();
        if ($results->num_rows() > 0) {
            foreach ($results->result_array() as $row) {
                $entries[] = $row['entry_id'];
            }
        }

        $values = implode($delimiter, $entries);

        if ($values == '') {
            $values = "0";
        }

        return $values;
    }

    // Show a current page's listing channel_id or listing channel short name
    public function child_listing()
    {
        $site_pages = $this->sql->get_site_pages();

        if (!$site_pages) {
            return false;
        }

        $data = $this->sql->get_data();

        $value = "";
        $show = ee()->TMPL->fetch_param('show'); // defaults to "listing_cid"
        $entry_id = ee()->TMPL->fetch_param('entry_id'); // defaults to "listing_cid"
        $current_id = array_search('/' . ee()->uri->uri_string() . '/', $site_pages['uris']);
        $entry_id = $entry_id ? $entry_id : $current_id;

        if ($entry_id == 0 || $entry_id == "") {
            return false;
        }

        $listing_cid = isset($data[$entry_id]['listing_cid']) ? $data[$entry_id]['listing_cid'] : 0;

        if ($listing_cid != 0) {
            // Use zee switch so possible future additions are easier to add.
            switch ($show) {
                case "channel_name":
                    $result = ee()->db->query("SELECT * FROM exp_channels WHERE channel_id = {$listing_cid}");
                    $value = $result->row('channel_name');

                    break;

                case "channel_title":
                    $result = ee()->db->query("SELECT * FROM exp_channels WHERE channel_id = {$listing_cid}");
                    $value = $result->row('channel_title');

                    break;

                default:
                    $value = isset($data[$entry_id]['listing_cid']) ? $data[$entry_id]['listing_cid'] : "";
            }
        }

        return $value;
    }

    /** -------------------------------------
    /**  Tag: first_child_redirect
    /** -------------------------------------*/
    public function first_child_redirect()
    {
        $first_child_id = false;
        $site_id = ee()->config->item('site_id');
        $site_pages = $this->sql->get_site_pages();

        $uri = $this->sql->get_uri();
        $entry_id = array_search($uri, $site_pages['uris']);

        if (is_numeric($entry_id)) {
            // get the first child of the current entry
            $sql = "SELECT node.entry_id
                    FROM exp_structure AS node
                    INNER JOIN exp_structure AS parent ON node.lft
                    BETWEEN parent.lft AND parent.rgt
                    INNER JOIN exp_channel_titles ON node.entry_id = exp_channel_titles.entry_id
                    WHERE parent.lft >1
                        AND node.site_id = " . ee()->db->escape_str($site_id) . "
                        AND node.parent_id = " . ee()->db->escape_str($entry_id) . "
                        AND exp_channel_titles.status != 'closed'
                    GROUP BY node.entry_id
                    ORDER BY node.lft
                    LIMIT 0,1";

            $query = ee()->db->query($sql);

            if ($query->num_rows > 0) {
                $first_child_id = $query->row('entry_id');
            }

            $first_child_uri = $first_child_id ? $site_pages['uris'][$first_child_id] : false;

            // do the redirect
            if ($first_child_uri) {
                // build out the redirect URL here
                $redirect_url = Structure_Helper::remove_double_slashes($site_pages['url'] . $first_child_uri);
                // remove any {base_url} injected in the EE core
                $redirect_url = str_replace("{base_url}/", ee()->config->item('base_url'), $redirect_url);

                // redirect the user
                header('HTTP/1.1 301 Moved Permanently');
                header("Location:" . $redirect_url);
                exit();
            }

            return false;
        }
    }

    public function entry_linking()
    {
        $site_pages = $this->sql->get_site_pages();

        if (! $site_pages) {
            return false;
        }

        $html = $pid = "";
        $html = (! ee()->TMPL->tagdata) ? '' : ee()->TMPL->tagdata;

        if (strtolower(ee()->TMPL->fetch_param('type')) == "next") {
            $type = 'ASC';
        } elseif (strtolower(ee()->TMPL->fetch_param('type')) == "previous") {
            $type = 'DESC';
        } else {
            return "";
        }

        $uri = ee()->TMPL->fetch_param('uri', $this->sql->get_uri());

        $entry_id = array_search($uri, $site_pages['uris']);
        $node = $entry_id ? $this->nset->getNode($entry_id) : false;

        // node does not have any structure data we return nothing to prevent errors
        if ($node === false && ! $entry_id) {
            return '';
        }

        // if we have an entry id but no node, we have listing entry
        if ($entry_id && ! $node) {
            $pid = $this->get_pid_for_listing_entry($entry_id);

            // get node of parent entry
            $node = $this->nset->getNode($pid);
        }

        $channel_id = $node['listing_cid'];

        $sql = "SELECT entry_id, title
                FROM exp_channel_titles
                WHERE channel_id = $channel_id
                    AND status = 'open'
                ORDER BY entry_date $type";

        $result = ee()->db->query($sql);

        $count = 0;
        $r_id = 0;
        $row = array();

        if ($result->num_rows > 0) {
            foreach ($result->result_array() as $row) {
                if ($row['entry_id'] == $entry_id) {
                    $r_id = $count + 1;
                }
                $count++;
            }
        }

        $array_vals = $result->result_array();

        @$eid = $array_vals[$r_id]['entry_id'];
        // Might need to pull left and right data to make this work
        if (! empty($array_vals) && isset($array_vals[$r_id]) && isset($site_pages['uris'][$eid]) && $array_vals[$r_id]['title'] != '') {
            $row['linking_title'] = $array_vals[$r_id]['title'];
            $row['linking_page_url'] = $site_pages['uris'][$eid];
        } else {
            return '';
        }

        foreach (ee()->TMPL->var_single as $key => $val) {
            if (isset($row[$val])) {
                $html = ee()->TMPL->swap_var_single($val, $row[$val], $html);
            }
        }

        return $html;
    }

    public function saef_select($type = '')
    {
        $type = ee()->TMPL->fetch_param('type');

        // Return nothing if no type is set
        if ($type == '' || $type == null) {
            return false;
        }

        ee()->load->helper('form');

        $entry_id = is_numeric(ee()->TMPL->fetch_param('entry_id')) ? ee()->TMPL->fetch_param('entry_id') : false;
        $name = 'structure_' . $type . '_id';

        $data = array();

        if ($type == 'template') {
            $templates = $this->sql->get_templates();
            $site_pages = $this->sql->get_site_pages();

            $selected = isset($site_pages['templates'][$entry_id]) ? $site_pages['templates'][$entry_id] : 0;

            $data[0] = 'Choose Template';

            foreach ($templates as $template) {
                $data[$template['template_id']] = $template['group_name'] . '/' . $template['template_name'];
            }
        } elseif ($type == 'parent') {
            $pages = $this->sql->get_data();

            $data[0] = 'Choose Parent';
            $selected = $this->sql->get_parent_id($entry_id);

            foreach ($pages as $entry_id => $entry) {
                $data[$entry_id] = str_repeat("--", $entry['depth']) . ' ' . $entry['title'];
            }
        }

        return form_dropdown($name, $data, $selected);
    }

    public function order_entries()
    {
        // Grab the delimiter, or default to a pipe
        $delimiter = ee()->TMPL->fetch_param('delimiter');
        $delimiter = $delimiter ? $delimiter : '|';

        // Start building out Start From and Limit Depth Features here

        // get all pages
        $pages = $this->sql->get_data();
        $entries = "";

        // Check if any data before preceeding
        if (isset($pages)) {
            foreach ($pages as $key => $entry_data) {
                // Add entries in order
                $entries .= $entry_data['entry_id'] . $delimiter;
            }
        }

        $entries = substr_replace($entries, "", -1);

        return $entries;
    }

    /**
     * Paginate has been removed
     * @return [type] [description]
     */
    public function paginate()
    {
        ee()->load->library('logger');
        ee()->logger->developer('The structure paginate tag was deprecated in 2011 and has been removed for compatibility with ExpressionEngine 4. Please use the native ExpressionEngine pagination.');

        return false;
    }

    // --------------------------------------------------------------------

    public function set_data($data, $cache_bust = false)
    {
        $site_id = ee()->config->item('site_id');
        $site_pages = $this->sql->get_site_pages($cache_bust, true);

        extract($data); // channel_id, entry_id, uri, template_id, listing_cid, parent_id, hidden

        $structure_channels = $this->get_structure_channels();
        $channel_type = $structure_channels[$data['channel_id']]['type'];

        if ($channel_type == 'page') {
            // get existing node if any out of the database
            $node = $this->nset->getNode($entry_id);
            $parentNode = $this->nset->getNode($parent_id);

            if ($node === false) {
                // all fields except left and right which is handled by the nestedset library
                $extra = array(
                    'site_id'               => $site_id,
                    'entry_id'              => $entry_id,
                    'parent_id'             => $parent_id,
                    'channel_id'            => $channel_id,
                    'listing_cid'           => $listing_cid,
                    'hidden'                => $hidden,
                    'dead'                  => ''
                );

                // create new node
                $this->nset->newLastChild($parentNode['right'], $extra);

                // fetch newly created node to keep working with
                $node = $this->nset->getNode($entry_id);
            }

            // set uri entries
            $node['uri'] = (!empty($site_pages['uris'][$entry_id]) ? $site_pages['uris'][$entry_id] : $uri);
            $parentNode['uri'] = (!empty($parent_id) && !empty($site_pages['uris'][$parent_id]) ? $site_pages['uris'][$parent_id] : '');

            // existing node
            $changed = $this->has_changed($node, $data);

            if ($changed) {
                // Retrieve previous listing channel id
                $prev_lcid_result = ee()->db->query("SELECT listing_cid FROM exp_structure WHERE entry_id = " . $entry_id);

                $prev_lcid = $prev_lcid_result->row('listing_cid');

                $listing = "";
                $lcid = $listing_cid ? $listing_cid : 0;

                $structure_url_title = $data['structure_uri'];
                $structure_template_id = $data['template_id'];

                // Update Structure
                ee()->db->query("UPDATE exp_structure SET parent_id='" . intval($parent_id) . "', listing_cid='" . intval($lcid) . "', hidden='" . $hidden . "', structure_url_title='" . $structure_url_title . "', template_id='" . intval($structure_template_id) . "' WHERE entry_id='" . intval($entry_id) . "'");

                // Listing Channel option in tab was changed TO "Unmanaged"
                if ($prev_lcid != 0 && $lcid == 0) {
                    // Retrieve all entries for channel
                    $listing_entries = ee()->db->query("SELECT * FROM exp_channel_titles WHERE channel_id = " . $prev_lcid);

                    // Go through list of entries to be removed from Structure
                    foreach ($listing_entries->result_array() as $listing_entry) {
                        $listing_id = $listing_entry['entry_id'];

                        // Remove from site_pages
                        if (isset($site_pages['uris'][$listing_id])) {
                            unset($site_pages['uris'][$listing_id]);
                            unset($site_pages['templates'][$listing_id]);
                        }
                    }

                    // Remove from our table too
                    ee()->db->delete('structure_listings', array('channel_id' => $prev_lcid));
                } elseif ($lcid != 0) {
                    $listing_channel = $lcid;

                    // Retrieve all entries for channel
                    $listing_entries = $this->sql->get_channel_listing_entries($listing_channel);

                    $channel_entries = ee()->db->query("SELECT entry_id, url_title FROM exp_channel_titles WHERE channel_id = $listing_channel AND site_id = $site_id");

                    // $structure_channels = $this->get_structure_channels();
                    $default_template = $structure_channels[$listing_channel]['template_id'];

                    $listing_data = array();
                    // $site_pages['uris'][$node['id']] = $node['uri'];
                    // $site_pages['templates'][$data['entry_id']] = $data['template_id'];

                    foreach ($channel_entries->result_array() as $c_entry) {
                        $node_parent_id = $node['id'];
                        $node_parent_uri = $node['uri'];

                        if (ee()->extensions->active_hook('structure_listing_parent') === true) {
                            $node_parent_id = ee()->extensions->call('structure_listing_parent', $node_parent_id, $listing_channel, $c_entry['entry_id']);
                            $node_parent_uri = $site_pages['uris'][$node_parent_id];
                        }

                        $temp_listing = array(
                            'site_id' => $site_id,
                            'channel_id' => $listing_channel,
                            'parent_id' => $node_parent_id,
                            'entry_id' => $c_entry['entry_id'],
                            'template_id' => !empty($listing_entries[$c_entry['entry_id']]['template_id']) ? $listing_entries[$c_entry['entry_id']]['template_id'] : $default_template,
                            'parent_uri' => $node_parent_uri,
                            'uri' => !empty($listing_entries[$c_entry['entry_id']]['uri']) ? $listing_entries[$c_entry['entry_id']]['uri'] : $c_entry['url_title']
                        );

                        // Hook to allow other add-ons to modify the listing data.
                        if (ee()->extensions->active_hook('structure_before_save_listing') === true) {
                            $hook_listing = ee()->extensions->call('structure_before_save_listing', $temp_listing);

                            // Quick gut-check in case the extension went awry and didn't return the data in the format we need.
                            if (!empty($hook_listing) && is_array($hook_listing)) {
                                $temp_listing = $hook_listing;
                            }
                        }

                        $listing_data[] = $temp_listing;

                        $site_pages['uris'][$c_entry['entry_id']] = $this->create_full_uri($temp_listing['parent_uri'], !empty($listing_entries[$c_entry['entry_id']]['uri']) ? $listing_entries[$c_entry['entry_id']]['uri'] : $c_entry['url_title']);
                        $site_pages['templates'][$c_entry['entry_id']] = !empty($listing_entries[$c_entry['entry_id']]['template_id']) ? $listing_entries[$c_entry['entry_id']]['template_id'] : $default_template;
                    }

                    // Update structure_listings table, and site_pages array with proper data
                    $this->set_listings($listing_data);

                    // Fetch newly updated site_pages array
                    // $site_pages = $this->sql->get_site_pages();
                }

                // Check if `$changed` is not exactly boolean TRUE; it can be other values like `self`, `parent`, `hidden`, or `template`.
                // Is only === TRUE if the user changed the entry channel or listing channel.
                if ($changed !== true) {
                    $prevUri = $node['uri'];

                    // Modify only if previous URI is root slash, allows to only affect the single page and it's entries & children
                    if ($prevUri == "/") {
                        $site_pages['uris'][$entry_id] = $uri;

                        // find out if there are children by retrieving the tree
                        // if has children then modify those and their children if they exist

                        $tree = $this->nset->getTree($entry_id);

                        if (count($tree) > 1) {
                            foreach ($tree as $child) {
                                $child_id = $child['entry_id'];

                                // replaces only first occurrence of $prevUri, makes sure only initial slash is replaced
                                $site_pages['uris'][$child_id] = Structure_Helper::remove_double_slashes(preg_replace("#" . $prevUri . "#", $uri . '/', $site_pages['uris'][$child_id], 1));
                            }
                        }

                        // if has entries then modify those as well
                        if ($listing_cid != 0) {
                            // TODO: UPDATE?
                            $listings = ee()->db->query("SELECT entry_id FROM exp_channel_titles WHERE channel_id = $listing_cid");

                            foreach ($listings->result_array() as $listing) {
                                $listing_id = $listing['entry_id'];

                                // replaces only first occurrence of $prevUri, makes sure only initial slash is replaced
                                $site_pages['uris'][$listing_id] = Structure_Helper::remove_double_slashes(preg_replace("#" . $prevUri . "#", $uri . '/', $site_pages['uris'][$listing_id], 1));
                            }
                        }
                    } else {
                        if (isset($site_pages['uris'])) {
                            // @todo - refactor
                            if (array_key_exists($entry_id, $site_pages['uris']) && $site_pages['uris'][$entry_id] != "/") {
                                $local_tree = $this->nset->getTree($entry_id);

                                $adjusted_tree = array();
                                $tree_string = '';
                                foreach ($local_tree as $row) {
                                    $adjusted_tree[$row['entry_id']] = '';
                                    $tree_string .= $row['entry_id'] . ',';
                                }
                                $tree_string = rtrim($tree_string, ",");

                                $sql = "SELECT entry_id FROM exp_structure_listings WHERE parent_id IN ($tree_string)";
                                $result = ee()->db->query($sql);

                                $listings = array();
                                foreach ($result->result_array() as $row) {
                                    $adjusted_tree[$row['entry_id']] = '';
                                }

                                foreach ($site_pages['uris'] as $key => &$path) {
                                    // if path is not root slash then modify as usual
                                    if (array_key_exists($key, $adjusted_tree)) {
                                        $site_pages['uris'][$key] = Structure_Helper::remove_double_slashes(str_replace($prevUri, $uri . '/', $site_pages['uris'][$key]));
                                    }
                                }
                            }
                        }
                    }

                    if ($changed === 'parent') {
                        $this->nset->moveToLastChild($node, $parentNode);
                    }

                    if ($changed === 'hidden') {
                        ee()->db->query("UPDATE exp_structure SET hidden = '$hidden' WHERE entry_id = $entry_id AND site_id = $site_id");
                    }
                }
            } else {
                // create urls for all entries when assigning a new listing channel
                if ($node['listing_cid'] != 0 && is_numeric($node['listing_cid'])) {
                    $listing_channel = $node['listing_cid'];

                    // Retrieve all entries for channel
                    $listing_entries = $this->sql->get_channel_listing_entries($listing_channel);

                    $channel_entries = ee()->db->query("SELECT entry_id, url_title FROM exp_channel_titles WHERE channel_id = $listing_channel AND site_id = $site_id");

                    // $structure_channels = $this->get_structure_channels();
                    $default_template = $structure_channels[$listing_channel]['template_id'];

                    $listing_data = array();

                    foreach ($channel_entries->result_array() as $c_entry) {
                        $node_parent_id = $node['id'];
                        $node_parent_uri = $node['uri'];

                        if (ee()->extensions->active_hook('structure_listing_parent') === true) {
                            $node_parent_id = ee()->extensions->call('structure_listing_parent', $node_parent_id, $listing_channel, $c_entry['entry_id']);
                            $node_parent_uri = $site_pages['uris'][$node_parent_id];
                        }

                        $temp_listing = array(
                            'site_id' => $site_id,
                            'channel_id' => $listing_channel,
                            'parent_id' => $node_parent_id,
                            'entry_id' => $c_entry['entry_id'],
                            'template_id' => !empty($listing_entries[$c_entry['entry_id']]['template_id']) ? $listing_entries[$c_entry['entry_id']]['template_id'] : $default_template,
                            'parent_uri' => $node_parent_uri,
                            'uri' => !empty($listing_entries[$c_entry['entry_id']]['uri']) ? $listing_entries[$c_entry['entry_id']]['uri'] : $c_entry['url_title']
                        );

                        // Hook to allow other add-ons to modify the listing data.
                        if (ee()->extensions->active_hook('structure_before_save_listing') === true) {
                            $hook_listing = ee()->extensions->call('structure_before_save_listing', $temp_listing);

                            // Quick gut-check in case the extension went awry and didn't return the data in the format we need.
                            if (!empty($hook_listing) && is_array($hook_listing)) {
                                $temp_listing = $hook_listing;
                            }
                        }

                        $listing_data[] = $temp_listing;

                        $site_pages['uris'][$c_entry['entry_id']] = $this->create_full_uri($temp_listing['parent_uri'], !empty($listing_entries[$c_entry['entry_id']]['uri']) ? $listing_entries[$c_entry['entry_id']]['uri'] : $c_entry['url_title']);
                        $site_pages['templates'][$c_entry['entry_id']] = !empty($listing_entries[$c_entry['entry_id']]['template_id']) ? $listing_entries[$c_entry['entry_id']]['template_id'] : $default_template;
                    }

                    // Update structure_listings table, and site_pages array with proper data
                    $this->set_listings($listing_data);
                }
            }
        }

        // set site_pages to be compatible with EE core
        $site_pages['uris'][$entry_id] = $uri;
        $site_pages['templates'][$entry_id] = $template_id;

        $settings = $this->sql->get_settings();

        $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y';

        if ($trailing_slash !== false) {
            foreach ($site_pages['uris'] as $e_id => $uri) {
                $site_pages['uris'][$e_id] = $uri . '/';
            }
        }

        $this->set_site_pages($site_id, $site_pages);
        $this->sql->update_root_node();
    }

    public function set_site_pages($site_id, $site_pages)
    {
        if (empty($site_id)) {
            $site_id = ee()->config->item('site_id');
        }

        $pages[$site_id] = $site_pages;

        unset($site_pages);

        ee()->db->query(ee()->db->update_string(
            'exp_sites',
            array('site_pages' => base64_encode(serialize($pages))),
            "site_id = '" . ee()->db->escape_str($site_id) . "'"
        ));
    }

    /*
    @ param
        $data = array(
            'site_id' => $site_id,
            'entry_id' => $entry_id,
            'parent_id' => $pid,
            'channel_id' => $channel_id,
            'template_id' => $template_id,
            'uri' => $slug
        );
    */
    public function set_listing_data($data, $site_pages = false)
    {
        $entry_id = $data['entry_id'];
        if ($site_pages === false) {
            $site_pages = $this->sql->get_site_pages();
        }

        // Update the entry for our listing item in site_pages
        $site_pages['uris'][$data['entry_id']] = $this->create_full_uri($data['parent_uri'], $data['uri']);
        $site_pages['templates'][$data['entry_id']] = $data['template_id'];
        $site_id = ee()->config->item('site_id');
        $data['site_id'] = $site_id;

        $this->set_site_pages($site_id, $site_pages);

        // Our listing table doesn't need this anymore, so remove it.
        unset($data['listing_cid']);
        unset($data['parent_uri']);

        // Hook to allow other add-ons to modify the listing data.
        if (ee()->extensions->active_hook('structure_before_save_listing') === true) {
            $listing = ee()->extensions->call('structure_before_save_listing', $listing);
        }

        // See if row exists first
        $query = ee()->db->get_where('structure_listings', array('entry_id' => $data['entry_id']));

        // We have an entry, so we're modifying existing data
        if ($query->num_rows() == 1) {
            unset($data['entry_id']);
            $sql = ee()->db->update_string('structure_listings', $data, "entry_id = $entry_id");
        } else { // This is a new entry
            $sql = ee()->db->insert_string('structure_listings', $data);
        }

        // Update our listing table
        ee()->db->query($sql);
        $this->sql->update_root_node();
    }

    /* Used in reorder to update all listing urls */
    public function set_listings($data)
    {
        $site_id = ee()->config->item('site_id');

        // Update the entry for our listing item in site_pages
        foreach ($data as $listing) {
            $entry_id = $listing['entry_id'];

            if (is_array($listing)) {
                // Our listing table doesn't need this anymore, so remove it.
                if (array_key_exists('parent_id', $listing)) {
                    unset($listing['parent_uri']);
                }

                // Hook to allow other add-ons to modify the listing data.
                if (ee()->extensions->active_hook('structure_before_save_listing') === true) {
                    $hook_listing = ee()->extensions->call('structure_before_save_listing', $listing);

                    // Quick gut-check in case the extension went awry and didn't return the data in the format we need.
                    if (!empty($hook_listing) && is_array($hook_listing)) {
                        $listing = $hook_listing;
                        unset($listing['parent_uri']);
                    }
                }

                // See if row exists first
                $query = ee()->db->get_where('structure_listings', array('entry_id' => $entry_id));

                // We have an entry, so we're modifying existing data
                if ($query->num_rows() == 1) {
                    // unset($data['entry_id']);
                    $sql = ee()->db->update_string('structure_listings', $listing, "entry_id = $entry_id");
                } else { // This is a new entry
                    $sql = ee()->db->insert_string('structure_listings', $listing);
                }

                // Update our listing table
                ee()->db->query($sql);
            }
        }
    }

    /**
     * Get Listing Data
     *
     * @param int $entry_id
     * @return FALSE or result array
     */
    public function get_listing_data($entry_id)
    {
        $query = ee()->db->get_where('structure_listings', array('entry_id' => $entry_id));
        if ($query->num_rows > 0) {
            return $query->row();
        }

        return false;
    }

    /*
    * @param parent_uri
    * @param listing_uri/slug
    */
    public function create_full_uri($parent_uri, $listing_uri)
    {
        $uri = $this->create_uri($listing_uri);
        // prepend the parent uri
        $uri = $parent_uri . '/' . $uri;
        // ensure beginning and ending slash
        $uri = '/' . trim($uri, '/');
        // if double slash, reduce to one
        return str_replace('//', '/', $uri);
    }

    /*
    * @param parent_uri
    * @param page_uri/slug
    */
    public function create_page_uri($parent_uri, $page_uri = '')
    {
        // prepend the parent uri
        $uri = $parent_uri . '/' . $page_uri;

        // ensure beginning and ending slash
        $uri = '/' . trim($uri, '/');

        // if double slash, reduce to one
        return str_replace('//', '/', $uri);
    }

    /*
    * @param submitted_uri
    * @param default_uri
    */
    public function create_uri($uri, $url_title = '')
    {
        // if structure_uri is not entered use url_title
        $uri = $uri ? $uri : $url_title;
        // Clean it up TODO replace with EE create URL TITLE?
        $uri = preg_replace("#[^a-zA-Z0-9_\-\.]+#i", '', $uri);
        // Make sure there are no "_" underscores at the beginning or end
        return trim($uri, "_");
    }

    // --------------------------------------------------------------------

    /**
     * Converts the jQuery NestedSortables Serialized array
     * To a format which is similar to Structure->get_data()
     * @param $sortable Array of array('id' => #, ['children' => subsortable])
     * @param $data Working array similar to Structure->get_data()
     * @param $lft Working left pointer
     * @param $crumb Working bread-crumb to parents
     * @return array data array similar to Structure->get_data()
     */
    public function nestedsortable_to_nestedset($sortable, &$data = array(), &$lft = 2, $crumb = array())
    {
        $depth = count($crumb);
        foreach ($sortable as $key => $subitem) {
            $crumb[$depth] = $subitem['id'];
            $data[$subitem['id']] = array(
                'lft' => $lft,
                'rgt' => null,
                'crumb' => $crumb
            );

            $lft++;
            if (array_key_exists('children', $subitem)) {
                $this->nestedsortable_to_nestedset($subitem['children'], $data, $lft, $crumb);
            }
            $data[$subitem['id']]['rgt'] = $lft;
            $lft++;

            unset($crumb[$depth]);
        }

        return $data;
    }

    // --------------------------------------------------------------------

    public function get_site_pages_query()
    {
        ee()->db->select('site_pages');
        ee()->db->where('site_id', ee()->config->item('site_id'));
        $query = ee()->db->get('sites');

        ee()->load->helper('string');

        $site_pages = unserialize(base64_decode($query->row('site_pages')));

        return $site_pages[ee()->config->item('site_id')];

        // $site_id =ee()->config->item('site_id');
        // $query_pages = ee()->db->query("SELECT site_pages FROM exp_sites WHERE site_id = $site_id");
        // $with_site_id = unserialize($query_pages->row('site_pages'));
        // $site_pages = $with_site_id[$site_id];
        // return $site_pages;
    }

    public function get_channel_type($channel_id = 0)
    {
        if ($this->channel_type === '') {
            // $channel_id = $channel_type ? $channel_type : ee()->input->get_post('channel_id');
            $channel_id = ee()->input->get_post('channel_id');
            $listing_cids = $this->get_data_cids(true);

            if (in_array($channel_id, $listing_cids)) {
                $this->channel_type = 'listing';
            } else {
                $this->channel_type = 'static';
            }
        }

        return $this->channel_type;
    }

    /**
     * Get all data from the exp_structure_channels table
     * @param $type|unmanaged|page|listing|asset
     * @param $channel_id you can pass a channel_id to retreive it's data
     * @param $order pass it 'alpha' to order by channel title
     * @return array An array of channel_ids and it's associated template_id, type and channel_title
     */
    public function get_structure_channels($type = '', $channel_id = '', $order = '', $allowed = false)
    {
        $site_id = ee()->config->item('site_id');
        $assigned_channels = ee()->functions->fetch_assigned_channels();

        $allowed_channels = count($assigned_channels) > 0 ? implode(',', $assigned_channels) : false;

        if ($allowed_channels === false) {
            return null;
        }

        // Get Structure Channel Data
        $sql = "SELECT ec.channel_id, ec.channel_title, esc.template_id, esc.type, ec.site_id
                FROM exp_channels AS ec
                LEFT JOIN exp_structure_channels AS esc ON ec.channel_id = esc.channel_id
                WHERE ec.site_id = '$site_id'";
        if ($allowed === true) {
            $sql .= " AND esc.channel_id IN ($allowed_channels)";
        }
        if ($type != '') {
            $sql .= " AND esc.type = '$type'";
        }
        if ($channel_id != '') {
            $sql .= " AND esc.channel_id = '$channel_id'";
        }
        if ($order == 'alpha') {
            $sql .= " ORDER BY ec.channel_title";
        }

        $results = ee()->db->query($sql);

        // Format the array nicely
        $channel_data = array();
        foreach ($results->result_array() as $key => $value) {
            $channel_data[$value['channel_id']] = $value;
            unset($channel_data[$value['channel_id']]['channel_id']);
        }

        return $channel_data;
    }

    /**
     * Get all channel_ids of the desired Structure type
     * @param $type|unmanaged|page|listing|asset
     * @return array An array of channel_ids in the specified type
     */
    public function get_channels_by_type($type)
    {
        $results = ee()->db->get_where('exp_structure_channels', array('type' => $type));
        $return = $results->result_array();

        return $return;
    }

    public function has_changed($node, $data)
    {
        $changed = false;

        if ($data['entry_id']) {
            if ($node['channel_id'] && $node['listing_cid'] != $data['listing_cid']) {
                $changed = true;
            }

            // check if path of entry has changed
            if ($node['uri'] != $data['uri']) {
                $changed = 'self';
            }

            // check if parent has changed
            // this overrides all other changed settings as it will do all update functions

            if ($node['parent_id'] != $data['parent_id']) {
                $changed = 'parent';
            }

            if ($node['hidden'] != $data['hidden']) {
                $changed = 'hidden';
            }

            if ($node['template_id'] != $data['template_id']) {
                $changed = 'template';
            }
        }

        return $changed;
    }

    public function delete_data_by_channel($channel_id)
    {
        // add structure nav history before deleting data by channel
        // add_structure_nav_revision($site_id, 'Pre deleting data by channel');

        // Retrieve current site_id
        $site_id = ee()->config->item('site_id');

        // Retrieve entry IDs for current channel
        $query = "SELECT entry_id
                  FROM exp_channel_titles
                  WHERE channel_id = " . $channel_id;
        $entries = ee()->db->query($query);

        // Retrieve site_pages data & unserialize it into an array
        $site_pages = $this->get_site_pages_query();

        // Go through list of entries to be removed from Structure
        foreach ($entries->result_array() as $entry) {
            $entry_id = $entry['entry_id'];

            // Remove from site_pages
            if (isset($site_pages['uris'][$entry_id])) {
                unset($site_pages['uris'][$entry_id]);
                unset($site_pages['templates'][$entry_id]);
            }

            // Remove from structure db table
            $node = $this->nset->getNode($entry_id);
            if ($node) {
                $this->nset->deleteNode($node);
            }
        }

        // store new site_pages array to database
        $this->set_site_pages($site_id, $site_pages);

        // If channel is a listing channel associated with a page, unset it
        $query_lcid = "UPDATE exp_structure SET listing_cid = 0 WHERE listing_cid = $channel_id";
        $lcid = ee()->db->query($query_lcid);

        // Remove listings
        $sql = "DELETE FROM exp_structure_listings
                WHERE site_id = $site_id
                AND channel_id = $channel_id";

        $query = ee()->db->query($sql);

        // add structure nav history before deleting data by channel
        add_structure_nav_revision($site_id, 'Post deleting data by channel');

        return true;
    }

    // Delete Structure data
    public function delete_data($ids)
    {
        if (is_numeric($ids) && $ids != '' && $ids != '0') {
            $ids = array($ids);
        } elseif (! is_array($ids)) {
            return false;
        }

        // search for entries and get the site_id
        $site_id = ee()->config->item('site_id');

        $site_pages = $this->sql->get_site_pages();

        // Check all passed IDs for children/entries, gather IDs for all
        // then remove Structure entries for anything with URI matching an entry

        // search all ids then add IDs to a temp array if it's not already in the array
        $ids_to_remove = array();
        $l_ids = array();

        foreach ($ids as $eid) {
            $node = $this->nset->getNode($eid);

            // Check to see if we have a Structure node or just an entry
            // if a node then get it's tree and affect the children
            // otherwise just remove the entry

            if ($node) {
                // find out if there are children by retrieving the tree
                // if has children then modify those and their children if they exist
                $listing_cid = $node['listing_cid'];
                $tree = "";
                $tree = $this->nset->getTree($eid);

                if (count($tree) > 1) {
                    foreach ($tree as $child) {
                        $child_id = $child['entry_id'];

                        if (! in_array($child_id, $ids_to_remove)) {
                            array_push($ids_to_remove, $child_id);
                        }
                    }
                }

                // if has entries then modify those as well
                if ($listing_cid != 0) {
                    $sql_listings = "SELECT entry_id FROM exp_channel_titles WHERE channel_id = $listing_cid";
                    $listings = ee()->db->query($sql_listings);

                    foreach ($listings->result_array() as $listing) {
                        $listing_id = $listing['entry_id'];
                        array_push($l_ids, $listing_id);

                        if (! in_array($listing_id, $ids_to_remove)) {
                            array_push($ids_to_remove, $listing_id);
                        }
                    }
                }
                //
            }

            if (! in_array($eid, $ids_to_remove)) {
                array_push($ids_to_remove, $eid);
            }
        }

        // Go through list of items to be removed from Structure
        foreach ($ids_to_remove as $entry_id) {
            // if ($entry)
            if (isset($site_pages['uris'][$entry_id])) {
                unset($site_pages['uris'][$entry_id]);
                unset($site_pages['templates'][$entry_id]);

                if (! in_array($entry_id, $l_ids)) {
                    ee('Model')->get('ChannelEntry', $entry_id)->fields('status')->first()->setProperty('status', 'closed')->save();
                }
            }
            // Delete from exp_structure
            $node = $this->nset->getNode($entry_id);
            if ($node) {
                $this->nset->deleteNode($node);
            }
        }

        $entry_ids = implode(",", $ids_to_remove);

        // Remove listings
        $sql = "DELETE FROM exp_structure_listings
                WHERE site_id = $site_id
                AND entry_id IN ($entry_ids)";

        $query = ee()->db->query($sql);

        // Store new site_pages array to database
        $this->set_site_pages($site_id, $site_pages);

        return true;
    }

    public function get_pid_for_listing_entry($entry_id)
    {
        // get entry's channel id
        $sql = "SELECT channel_id
                FROM exp_channel_titles
                WHERE entry_id = $entry_id
                LIMIT 1";
        $result = ee()->db->query($sql);

        $lcid = $result->row('channel_id');

        if (is_array($lcid)) {
            return false;
        }

        // get entry's parent id
        $sql = "SELECT entry_id
                FROM exp_structure
                WHERE listing_cid = $lcid
                LIMIT 1";
        $result = ee()->db->query($sql);
        $pid = $result->row('entry_id');

        // cache pid for later use // Uh, why? This causes issues.
        // ee()->session->cache['structure']['lising_entry_pid'] = $pid;

        // return ee()->session->cache['structure']['lising_entry_pid'];
        return $pid;
    }

    public function user_access($perm, $settings = array())
    {
        $site_id = ee()->config->item('site_id');
        $group_id = ee()->session->userdata['group_id'];

        // super admins always have access
        if ($group_id == 1) {
            return true;
        }

        $admin_perm = 'perm_admin_structure_' . $group_id;
        $this_perm = $perm . '_' . $group_id;

        if ($settings !== array()) {
            if ((isset($settings[$admin_perm]) or isset($settings[$this_perm]))) {
                return true;
            }

            return false;
        }

        // settings were not passed we have to go to the DB for the check
        $result = ee()->db->select('var')
            ->from('structure_settings')
            ->where('var', $admin_perm)
            ->or_where('var', $this_perm);

        if ($result->num_rows() > 0) {
            return true;
        }

        return false;
    }

    // Mark an item with a status
    public function set_status($id, $status)
    {
        // Mark as closed entry in exp_channel_titles
        $sql = "UPDATE exp_channel_titles SET status = '$status' WHERE status <> '$status' AND entry_id = $id";
        ee()->db->query($sql);
    }

    public function get_data_cids($listings = false)
    {
        $cid_field = $listings ? 'listing_cid' : 'channel_id';
        $sql = "SELECT entry_id, $cid_field
                FROM exp_structure";
        $result = ee()->db->query($sql);

        $cids = array();
        foreach ($result->result_array() as $row) {
            if ($row[$cid_field] != 0) {
                $cids[$row['entry_id']] = $row[$cid_field];
            }
        }

        return $cids;
    }

    public function debug($data, $die = false)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';

        if ($die) {
            die;
        }
    }

    // Temporary Blueprints Support

    public function get_data()
    {
        return $this->sql->get_data();
    }

    public function get_site_pages()
    {
        return $this->sql->get_site_pages();
    }

    public function remove_last_segment($uri)
    {
        $segments = (explode('/', trim($uri, '/')));
        unset($segments[count($segments) - 1]);

        return '/' . implode('/', $segments) . '/';
    }

    /**
     * Structure Nav from Rob Sanchez
     * Included with permission.
     * @author Rob Sanchez (rsanchez)
     * @link   https://github.com/rsanchez
     */
    public function nav_basic($add_entry_vars = false)
    {
        $nav = new Structure_core_nav_parser();
        $variables = $nav->get_variables($add_entry_vars);
        unset($nav);

        if (! $variables) {
            return ee()->TMPL->no_results();
        }

        $tagdata = ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $variables);

        return $tagdata;
    }

    public function nav_advanced()
    {
        return $this->nav_basic(true);
    }
}
/* END Class */

/* End of file mod.structure.php */
/* Location: ./system/expressionengine/modules/structure/mod.structure.php */
