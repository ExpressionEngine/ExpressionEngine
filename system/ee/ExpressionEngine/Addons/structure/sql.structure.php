<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once PATH_ADDONS . 'structure/helper.php';
require_once PATH_ADDONS . 'structure/addon.setup.php';

use ExpressionEngine\Structure\Conduit\StaticCache;
use ExpressionEngine\Structure\Conduit\PersistentCache;

class Sql_structure
{
    public $site_id;
    public $data = array();
    public $cids = array();
    public $lcids = array();
    public $cache;

    public function __construct()
    {
        ee()->load->add_package_path(PATH_ADDONS . 'structure/');
        ee()->load->library('sql_helper');
        ee()->load->library('general_helper');

        $this->site_id = $this->get_site_id();
    }

    /**
     * Get global and MSM specific settings
     * from exp_structure_settings table
     *
     * @return array
     **/
    public function get_settings()
    {
        static $settings = null;

        if (is_array($settings) || !ee()->addons_model->module_installed('structure')) {
            return $settings;
        }

        $site_id = '0,' . $this->site_id;

        $sql = "SELECT var_value, var FROM exp_structure_settings WHERE site_id IN ({$site_id})";
        $result = ee()->db->query($sql);

        $settings = array(
            'show_picker'          => 'y',
            'show_view_page'       => 'y',
            'show_status'          => 'y',
            'show_page_type'       => 'y',
            'show_global_add_page' => 'y',
            'redirect_on_login'    => 'n',
            'redirect_on_publish'  => 'n',
            'add_trailing_slash'   => 'y'
        );

        if ($result->num_rows() > 0) {
            foreach ($result->result_array() as $row) {
                if ($row['var_value'] != '') {
                    $settings[$row['var']] = $row['var_value'];
                }
            }
        }

        return $settings;
    }

    public static function set_channel_ids($entry_id, $channel_id)
    {
        $sql = "UPDATE exp_structure SET channel_id = $channel_id WHERE entry_id = $entry_id ";
        ee()->db->query($sql);
    }

    public function get_categories($group_id)
    {
        $sql = "SELECT * from exp_categories where group_id={$group_id}";

        $result = ee()->db->query($sql);

        $data = $result->result_array();

        // =debug
        header('Content-Type: text/plain; charset=iso-8859-1');
        print_r($cats); //TODO WHY IS THIS HERE? -Matt
        exit;

        return $data;
    }

    /**
     * Get all data for all Structure Channels
     *
     * @return array
     */
    public function get_data($entry_id = 0)
    {
        $data = array();

        $sql = "SELECT node.*, expt.title, expt.status
                FROM (SELECT node.*, (COUNT(parent.entry_id) - 1) AS depth
                    FROM exp_structure AS node
                    INNER JOIN exp_structure AS parent
                        ON node.lft BETWEEN parent.lft AND parent.rgt
                    WHERE parent.lft > 1
                    AND parent.site_id = {$this->site_id}
                    GROUP BY node.entry_id) AS node
                INNER JOIN exp_channel_titles AS expt
                    ON node.entry_id = expt.entry_id
                WHERE node.site_id = {$this->site_id}";

        if ($entry_id != 0) {
            $sql .= " AND expt.entry_id != " . $entry_id;
            $sql .= " AND node.parent_id != " . $entry_id;
        }

        $sql .= " ORDER BY node.lft";

        $cached_data = StaticCache::get('get_data__' . $sql);

        if (!empty($cached_data)) {
            // If the cache is actually empty, we want to return that here.
            if ($cached_data !== 'EMPTY') {
                $data = $cached_data;
            }
        } else {
            $result = ee()->db->query($sql);

            if ($result->num_rows() > 0) {
                foreach ($result->result_array() as $row) {
                    $data[$row['entry_id']] = $row;
                }
            }

            // If there was no data returned, we need something to put in the cache
            // to let Structure know we already ran this query otherwise it'll just run it again.
            if (empty($data)) {
                StaticCache::set('get_data__' . $sql, 'EMPTY');
            } else {
                StaticCache::set('get_data__' . $sql, $data);
            }
        }

        // -------------------------------------------
        // 'structure_get_data_end' hook.
        //
        if (ee()->extensions->active_hook('structure_get_data_end') === true) {
            $data = ee()->extensions->call('structure_get_data_end', $data);
        }
        //
        // -------------------------------------------

        return $data;
    }

    public function get_status_colors()
    {
        $data = array();
        $sql = "SELECT status, highlight FROM exp_statuses";
        $result = ee()->db->query($sql);

        if ($result->num_rows() > 0) {
            foreach ($result->result_array() as $row) {
                $data[$row['status']] = $row['highlight'];
            }
        }

        return $data;
    }

    /**
     * Get selective data on all Structure Channels
     *
     * @return array
     */
    public function get_selective_data($site_id, $current_id, $branch_entry_id, $mode, $show_depth, $max_depth, $status, $include, $exclude, $show_overview, $rename_overview, $show_expired, $show_future, $override_hidden_state = "no", $recursive_overview = "no", $include_site_url = "yes")
    {
        $parent_id = $this->get_parent_id($current_id);

        $settings = $this->get_settings();
        $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y';

        $pages = $this->get_site_pages();

        /*
        Trimming Control Params:

        The tree trimmer loads all the entire tree/branch for all nodes
        within the branch_entry_id (which is set by 'start_from' param).

        The trimmer then removes nodes based on the following:
        start_from/branch_entry_id = The root of this nav
        show_depth =    Depth past 'start_from' node which should always be shown
                        (use -1 to disable will also disable 'expand_depth')
        expand_depth =  If current node is at the edge, or past the edge of show_depth
                        then it will keep (aka expand) this much further
                        (use -1 to disable note: only a depth of one currently works)

        ---
        Then something crazy happens- purify_bloodlines is called on the current node
        removing all cousins, 2nd cousins etc.

        To prevent un-wanted carnage the active sub-branch (the limb which contains the
        current node on it) can be severed from it's parent ($node->parent = NULL;)
        stopping the bloodline.
        ---

        max_depth =     Depth past 'start_from' which you never want to see. Happens
                        last which means it will over-ride all the others.
                        (use -1 to disable)
        */

        switch ($mode) {
            case 'full':
                // show everything
                $branch_entry_id = 0;   // start from root
                $show_depth = -1;       // defaults to show full tree, passed by the param
                $expand_depth = -1;     // don't trim past current
                // $max_depth = -1; (can be specified by tag)
                $current_id = false;

                break;
            case 'main':
                // show top nav but never any children
                $branch_entry_id = 0;   // start from root
                $show_depth = -1;       // show full tree
                $expand_depth = -1;     // don't trim past current
                $max_depth = 1;         // only show top level
                $current_id = false;

                break;
            case 'sub':
                $expand_depth = 1;      // show child of current node
                // am I a listing?
                if ($current_id !== false && $this->is_listing_entry($current_id)) {
                    $current_id = $parent_id;
                }

                break;
        }

        if ($show_depth == 'all') {
            $show_depth = -1;
        }

        $status = strtolower($status);
        $status_exclude = false;
        if (strncmp($status, 'not ', 4) == 0) {
            $status_exclude = true;
            $status = substr($status, 4);
        }
        $statuses = explode('|', $status);

        if (! is_array($include)) {
            $include = array_filter(explode('|', $include), 'ctype_digit');
        }

        if (! is_array($exclude)) {
            $exclude = array_filter(explode('|', $exclude), 'ctype_digit');
        }

        // ---
        // Retreive branch data from DB
        // ---

        // generate flash-data cache name
        $cache_name = 'root=' . $branch_entry_id;
        if (count($exclude)) {
            sort($exclude, SORT_NUMERIC);
            $cache_name .= '-' . implode(',', $exclude);
        }

        // check the flash-data cache
        $results = @ee()->session->cache['structure'][$cache_name];
        $results = '';
        if (! is_array($results)) {
            $where_exclude = '';

            foreach ($exclude as $id) {
                if ($id != '' && array_key_exists($id, $pages['uris'])) {
                    $where_exclude .= " AND structure.lft NOT BETWEEN (SELECT lft FROM exp_structure WHERE entry_id = '$id') AND (SELECT rgt FROM exp_structure WHERE entry_id = '$id')";
                }
            }

            // $where_include = '';
            // foreach ($include as $id)
            // {
            //  if ($id != '' && array_key_exists($id, $pages['uris']))
            //      $where_include .= " AND structure.lft BETWEEN (SELECT lft FROM exp_structure WHERE entry_id = '$id') AND (SELECT rgt FROM exp_structure WHERE entry_id = '$id')";
            // }

            $timestamp = (ee()->TMPL->cache_timestamp != '') ? ee()->TMPL->cache_timestamp : ee()->localize->now;

            if ($show_future == 'no') {
                $where_exclude .= " AND (structure.entry_id = 0 OR titles.entry_date < " . $timestamp . ") ";
            }

            if ($show_expired == 'no') {
                $where_exclude .= " AND (structure.entry_id = 0 OR titles.expiration_date = 0 OR titles.expiration_date > " . $timestamp . ") ";
            }

            $sql = "SELECT structure.*, titles.title, titles.entry_date, titles.expiration_date,  LOWER(titles.status) AS status
                    FROM
                        exp_structure AS structure
                        LEFT JOIN exp_channel_titles AS titles
                            ON (structure.entry_id = titles.entry_id)
                        JOIN (
                            SELECT entry_id, lft, rgt
                            FROM exp_structure
                            WHERE entry_id = '$branch_entry_id' AND site_id IN (0,'$site_id')
                        ) AS root_node
                            ON (structure.lft BETWEEN root_node.lft AND root_node.rgt)
                                OR structure.entry_id = root_node.entry_id
                    WHERE
                        structure.site_id IN (0,'$site_id')
                        AND (titles.entry_id IS NOT NULL OR structure.entry_id = 0)
                        $where_exclude
                    ORDER BY structure.lft";
            // echo $sql;
            $query = ee()->db->query($sql);
            $results = $query->result_array();
            $query->free_result();

            // -------------------------------------------
            // 'structure_get_selective_data_results' hook.
            //
            if (ee()->extensions->active_hook('structure_get_selective_data_results') === true) {
                $results = ee()->extensions->call('structure_get_selective_data_results', $results);
            }
            //
            // -------------------------------------------

            ee()->session->cache['structure'][$cache_name] = $results;
        }

        // ---
        // Return empty array with no nav
        // ---
        if (count($results) == 0) {
            return array();
        }

        // ---
        // Build branch tree and trim
        // ---
        // =debug

        $tree = structure_leaf::build_from_results($results);

        // find the current page in the tree
        $cur_leaf = false;
        if ($current_id !== false) {
            $cur_leaf = $tree->find_ancestor('entry_id', $current_id);
        }
        // note: if cur_leaf = FALSE then the current page is not in this sub nav

        if ($cur_leaf === false) {
            // the current page is not in this branch
            // use root as current
            $cur_leaf = $tree;
        }

        // limit the shown depth (-1 for show all)
        if ($show_depth >= 0) {
            foreach ($tree->children as $child) {
                if ($child->has_ancestor($cur_leaf)) {
                    $cur_depth = $cur_leaf->depth();
                    if ($cur_depth < $show_depth) {
                        // not past show_depth yet (no expansion)
                        $child->prune_children($show_depth - 1);
                        // prevent purify_bloodlines from working
                        $cur_leaf->parent = null;
                    } elseif ($expand_depth >= 0) {
                        // expand past current node
                        // while preserving show_depth of other branches
                        $cur_leaf->prune_children($expand_depth);
                        foreach ($child->children as $grandchild) {
                            if ($grandchild->has_ancestor($cur_leaf)) {
                                // protect non-active branches from
                                // purify_bloodlines
                                $grandchild->parent = null;
                            } else {
                                // but don't forget to prune them to
                                // the correct show_depth
                                $grandchild->prune_children($show_depth - 2);
                            }
                        }
                    } else {
                        $child->prune_children($show_depth - 1);
                        // prevent purify_bloodlines from working
                        $cur_leaf->parent = null;
                    }
                } else {
                    // keep show_depth of non-active branches
                    $child->prune_children($show_depth - 1);
                }
            }
        }

        // gets rid of cousins and 2nd cousins
        // keeps children, parents and uncles
        $cur_leaf->purify_bloodline();

        // limit overall depth shown (-1 for infinite)
        if ($max_depth >= 0) {
            $tree->prune_children($max_depth);
        }

        // limit based on status
        if (count($statuses)) {
            if ($tree->row['entry_id'] != 0) { // don't test structure ROOT (would always fail)
                if ($tree->is_of_value('status', $statuses, $status_exclude)) {
                    return array();
                } // root node removed based on critera
            }

            $tree->selective_prune('status', $statuses, $status_exclude);
        }

        if ($override_hidden_state) {
            if ($override_hidden_state != 'yes') {
                $tree->selective_prune('hidden', array('y'), true);
            }
        }

        // limit to 'include' ids
        if (count($include)) {
            array_unshift($include, $branch_entry_id); // add current root node as valid
            if ($tree->is_of_value('entry_id', $include, false)) {
                return array();
            } // root node removed based on critera

            $tree->selective_prune_alt('entry_id', $include, false);
        }

        // add 'depth' to the rows
        // (happens here, because the tree is already trimmed = less waste)
        $tree->add_row_depth();

        // rebuild results from what is left in the tree
        $results = $tree->get_results();

        if ($show_overview) {
            // add sql to get this entry
            $overview = $this->get_overview($branch_entry_id);
            $rename_overview = ($rename_overview == 'title' && isset($overview['title'])) ? $overview['title'] : $rename_overview; // override if "title"

            $overview['title'] = $rename_overview;
            $overview['overview'] = $rename_overview;

            array_unshift($results, $overview);
        }

        $data = array();
        foreach ($results as $row) {
            if (! isset($row['entry_id'])) {
                continue;
            }

            if (isset($pages['uris'][$row['entry_id']])) {
                // this is to fix a bug in EE3.4.x. It should not replace anything otherwise
                $ee_url = ee()->functions->create_page_url(($include_site_url != 'no' ? $pages['url'] : ''), $pages['uris'][$row['entry_id']], $trailing_slash);
                $url = str_replace("{base_url}/", ee()->config->item('base_url'), $ee_url);

                // Hook to override the url we generate for each structure link (ex: Transcribe's multi-lingual language domains).
                if (ee()->extensions->active_hook('structure_generate_page_url_end') === true) {
                    $url = ee()->extensions->call('structure_generate_page_url_end', $url);
                }

                $data[$row['entry_id']] = $row;
                $data[$row['entry_id']]['uri'] = $url;
                $data[$row['entry_id']]['slug'] = $pages['uris'][$row['entry_id']];
                $data[$row['entry_id']]['classes'] = array();
                $data[$row['entry_id']]['ids'] = array();
                // echo $data[$row['entry_id']]['uri'].' ';
            }
        }

        return $data;
    }

    public function count_segments($uri)
    {
        if ($uri != "") {
            $uri = Structure_Helper::remove_double_slashes(trim(html_entity_decode($uri), '/'));
            $segment_array = explode('/', $uri);

            return count($segment_array);
        }

        return null;
    }

    /**
     * Get a single path of data, useful for breadcrumbs etc
     *
     * @return array
     */
    public function get_single_path($entry_id)
    {
        $listing_ids = $this->get_listing_entry_ids();
        if (is_array($listing_ids) && array_key_exists($entry_id, $listing_ids)) {
            $entry_id = $this->get_parent_id($entry_id);
        }

        $sql = "SELECT parent.lft, parent.rgt,expt.title, expt.entry_id
                FROM exp_structure AS node,
                    exp_structure AS parent
                INNER JOIN exp_channel_titles AS expt ON parent.entry_id = expt.entry_id
                WHERE node.lft BETWEEN parent.lft AND parent.rgt
                    AND node.entry_id = '$entry_id'
                    AND expt.site_id = '$this->site_id'
                ORDER BY parent.lft";

        $result = ee()->db->query($sql);

        $data = array();
        $pages = $this->get_site_pages();

        if ($result->num_rows() > 0) {
            foreach ($result->result_array() as $row) {
                if (array_key_exists($row['entry_id'], $pages['uris'])) {
                    // this is to fix a bug in EE3.4.x. It should not replace anything otherwise
                    $ee_url = ee()->functions->create_page_url($pages['url'], $pages['uris'][$row['entry_id']], false);
                    $url = str_replace("{base_url}/", ee()->config->item('base_url'), $ee_url);

                    // Hook to override the url we generate for each structure link (ex: Transcribe's multi-lingual language domains).
                    if (ee()->extensions->active_hook('structure_generate_page_url_end') === true) {
                        $url = ee()->extensions->call('structure_generate_page_url_end', $url);
                    }

                    $data[$row['entry_id']] = $row;
                    $data[$row['entry_id']]['uri'] = $url;
                }
            }
        }

        return $data;
    }

    public function reindex_at_one($array)
    {
        $start_at = 1;
        $new_array = array();
        foreach ($array as $key => $row) {
            $array[$start_at] = $row;
            $start_at++;
        }

        return $array;
    }

    public function add_attributes($pages, $entry_id, $mode, $override_hidden_state = "no")
    {
        $top_array = array();
        $separator = ee()->config->item('word_separator') != "dash" ? '_' : '-';
        $root_id = ee()->TMPL->fetch_param('css_id', 'nav' . $separator . $mode);
        $root_id = $root_id == "none" ? 'nav' : $root_id;

        $path_pages = $this->get_single_path($entry_id);
        $parent_id = $this->get_parent_id($entry_id);
        $listing_ids = $this->get_listing_entry_ids();

        $zero_index_pages = array_values($pages);

        $i = 1;
        foreach ($zero_index_pages as $index => $page) {
            $key = $page['entry_id'];

            // level classes
            if (ee()->TMPL->fetch_param('add_level_classes', false) !== false) {
                $pages[$key]['classes'][] = 'level' . $separator . $page['depth'];
            }

            // here class
            $current_class = ee()->TMPL->fetch_param('current_class', 'here');

            if ($page['entry_id'] == $entry_id && $current_class != "no" && $current_class != "off" && $current_class != "none") {
                $pages[$key]['classes'][] = $current_class;
            }

            // here class to listing parent
            if (is_array($listing_ids) && array_key_exists($entry_id, $listing_ids) && $page['entry_id'] == $parent_id) {
                $pages[$parent_id]['classes'][] = $current_class;
            }

            // parent-here class
            if (array_key_exists($page['entry_id'], $path_pages) && $page['entry_id'] != $entry_id && ! array_key_exists('overview', $page)) {
                if ($mode == "main") {
                    $pages[$key]['classes'][] = $current_class;
                } else {
                    $pages[$key]['classes'][] = 'parent' . $separator . $current_class;
                }
            }

            // has-children class
            $has_children_class = ee()->TMPL->fetch_param('has_children_class', 'no');

            if (
                $has_children_class != 'no' && $page['rgt'] - $page['lft'] > 1
                && ! array_key_exists('overview', $page)
                && (isset($zero_index_pages[$index + 1]['parent_id']) && $zero_index_pages[$index + 1]['parent_id'] == $key)
            ) {
                if ($has_children_class !== "yes") {
                    $pages[$key]['classes'][] = $has_children_class;
                } else {
                    $pages[$key]['classes'][] = 'has' . $separator . 'children';
                }
            }

            // unique ids
            if (ee()->TMPL->fetch_param('add_unique_ids', false) == "yes" || ee()->TMPL->fetch_param('add_unique_ids', false) == "on") {
                $slugs = $this->get_slug($page['slug'], true);

                $pageslug = '';

                foreach ($slugs as $s) {
                    $pageslug .= $separator . $s;
                }

                $pages[$key]['ids'][] = $page['slug'] == '/' ? $root_id . $separator . 'home' : $root_id . $pageslug;
            } elseif (ee()->TMPL->fetch_param('add_unique_ids', false) == "entry_ids" || ee()->TMPL->fetch_param('add_unique_ids', false) == "entry_id") {
                $pages[$key]['ids'][] = $root_id . $separator . $page['entry_id'];
            }

            if (array_key_exists('overview', $page)) {
                $pages[$key]['classes'][] = 'first';
                $pages[$key]['classes'][] = 'overview';
            }

            // first class
            if (
                ($i == 1
                 && (! in_array('first', $pages[$page['entry_id']]['classes']))) #first page
                    || (
                        $page['parent_id'] != 0
                && array_key_exists($page['parent_id'], $pages)
                && ($page['lft'] - 1) == $pages[$page['parent_id']]['lft']
                && (! in_array('overview', $pages[$page['parent_id']]['classes']))
                    )
            ) {
                $pages[$key]['classes'][] = 'first';
            }

            // last class
            if ($page['parent_id'] != 0 && array_key_exists($page['parent_id'], $pages) && ($page['rgt'] + 1) == $pages[$page['parent_id']]['rgt']) {
                // If this is the last but it's set to hidden, we want to go back and set the
                // previous entry and then remove it from the nav.
                if ($pages[$key]['hidden'] == "y" && $override_hidden_state != "yes") {
                    $pages[$key - 1]['classes'][] = 'last';
                    unset($pages[$key]);
                } else {
                    $pages[$key]['classes'][] = 'last';
                }
            }

            // Build array of top level items
            if ($page['depth'] == 1) {
                $top_array[] = $page;
            }

            $i++;
        }

        $first = reset($top_array);

        // Account for the very first top level item and add class="first"
        // if (count($top_array) > 0 && ! in_array('last', $pages[$first['entry_id']]['classes']))
        // $pages[$first['entry_id']]['classes'][] = 'first';

        $last = end($top_array);

        // Account for the very last top level item and add class="last"
        if (count($top_array) > 0 && ! in_array('last', $pages[$last['entry_id']]['classes'])) {
            $pages[$last['entry_id']]['classes'][] = 'last';
        }

        return $pages;
    }

    public function get_slug($uri = false, $all = false)
    {
        if ($uri !== false) {
            $segments = explode('/', trim((string) $uri, '/'));
            if ($all) {
                return $segments;
            } else {
                return end($segments);
            }
        }

        return false;
    }

    /**
     * Get the HTML code for an unordered list of the tree
     * @return string HTML code for an unordered list of the whole tree
     */
    public function generate_nav($selective_data, $current_id, $entry_id, $mode, $show_overview, $rename_overview, $override_hidden_state = "no", $recursive_overview = "no", $level = 1)
    {
        $html = '';
        $separator = ee()->config->item('word_separator') != "dash" ? '_' : '-';

        // Fallback to entry_id if no current_id (e.g. sitemap usage)
        if ($current_id === false) {
            $current_id = $entry_id;
        }

        $pages = $this->add_attributes($selective_data, $current_id, $mode, $override_hidden_state);

        // Now we've got the data, we need to do a cleanup to remove any child entries which don't have the parents
        //$lp=0;
        //
        //foreach($pages as $row)
        //{
        //  //Ignore the Root Node
        //  if (isset($row['parent_id']))
        //  {
        //      if($row['parent_id']!="0")
        //      {
        //          if (!isset($pages[$row['parent_id']]))
        //          {
        //              unset($pages[$lp]);
        //          }
        //      }
        //      $lp++;
        //  }
        //}

        $tree = array_values($pages);
        $tree_count = count($tree);

        if ($tree_count < 1) {
            return null;
        }

        $custom_title_fields = $this->create_custom_titles();

        for ($i = 0; $i < $tree_count; $i++) {
            // if ($i == 0) {
            //  $tree[$i]['classes'][] = 'first';
            // }

            // Build class string if any exist
            $classes = count($tree[$i]['classes']) > 0 ? ' class="' . implode(' ', $tree[$i]['classes']) . '"' : '';

            if ($show_overview) {
                $classes = str_replace("first", "", $classes);
            }

            // Build id string if any exist
            $ids = count($tree[$i]['ids']) > 0 ? ' id="' . implode(' ', $tree[$i]['ids']) . '"' : '';

            // Title field: custom|title
            $title = $custom_title_fields !== false ? $custom_title_fields[$tree[$i]['entry_id']] : $tree[$i]['title'];

            if (ee()->TMPL->fetch_param('encode_titles', 'yes') === "yes") {
                $title = htmlspecialchars($title);
            }

            // Add span hook if desired
            $title_output = ee()->TMPL->fetch_param('add_span', false) == "yes" ? "<span>" . $title . "</span>" : $title;

            // -------------------------------------------
            //  The list item itself
            // -------------------------------------------

            $html .= '<li' . $classes . $ids . '><a href="' . $tree[$i]['uri'] . '">' . $title_output . '</a>';

            // Closing up a level
            if ($tree[$i]['depth'] < @$tree[$i + 1]['depth']) {
                $html .= "\n<ul>\n";

                if ($show_overview) {
                    if ($recursive_overview == "no") {
                        if ($tree[$i]['depth'] == $level) {
                            if ($rename_overview == "title") {
                                $title = $tree[$i]['title'];
                            } else {
                                $title = $rename_overview;
                            }

                            $html .= '<li class="first"><a href="' . $tree[$i]['uri'] . '">' . $title . '</a></li>' . "\n";
                        }
                    } else {
                        if ($rename_overview == "title") {
                            $title = $tree[$i]['title'];
                        } else {
                            $title = $rename_overview;
                        }

                        $html .= '<li class="first"><a href="' . $tree[$i]['uri'] . '">' . $title . '</a></li>' . "\n";
                    }
                }
            } elseif (@$tree[$i]['depth'] == @$tree[$i + 1]['depth']) {
                // Closing up a list item
                $html .= "</li>\n";
            } else {
                // Closing up multiple levels and list items
                $diff = (array_key_exists($i + 1, $tree)) ? $tree[$i]['depth'] - $tree[$i + 1]['depth'] : $tree[$i]['depth'] - 1 ;
                $html .= str_repeat("</li>\n</ul>\n", $diff) . "</li>\n";
            }
        }

        // Add the unordered list element
        if (ee()->TMPL->fetch_param('include_ul', 'yes') == 'yes') {
            // Add css class
            $css_class = ee()->TMPL->fetch_param('css_class', null);
            if ($css_class !== null && $css_class != '') {
                $css_class = " class=\"$css_class\"";
            }

            $css_id = ee()->TMPL->fetch_param('css_id', 'nav' . $separator . $mode);
            $root_id = " id=\"" . $css_id . "\"";

            if ($css_id == 'none' || $css_id == 'no') {
                $root_id = null;
            }

            $html = ee()->TMPL->fetch_param('wrap_start', null) . "<ul$root_id$css_class>\n" . $html . "</ul>" . ee()->TMPL->fetch_param('wrap_end', null);
        }

        return $html;
    }

    /**
     * This function lets you override fields from some template tags
     * @param  boolean $include_listings [description]
     * @return [type]                    [description]
     */
    public function create_custom_titles($include_listings = false)
    {
        // Check if the parameter is set. If its not, just return false
        $custom_titles = ee()->TMPL->fetch_param('channel:title', false);
        if ($custom_titles === false) {
            return false;
        }

        $custom_titles = explode('|', $custom_titles);

        // -------------------------------------------
        // 'structure_create_custom_titles' hook.
        //
        if (ee()->extensions->active_hook('structure_create_custom_titles') === true) {
            $page_titles = ee()->extensions->call('structure_create_custom_titles', $custom_titles);

            return $page_titles;
        }
        //
        // -------------------------------------------

        // get the title fields
        $title_fields = $this->_get_custom_title_fields($custom_titles);

        // Return false if there are none!
        if (!$title_fields) {
            return false;
        }

        $sql_fields = $this->_get_sql_fields($custom_titles, $title_fields);

        // At this point we know if we need to continue. If we don't have any sql fields, we can just return false.
        if (! count($sql_fields)) {
            return false;
        }

        // Get the page titles for this
        $page_titles = $this->_get_page_titles($sql_fields, $include_listings);

        return $page_titles;
    }

    /**
     * Separating out some code to split it into single purpose functions
     *
     * @param  [type] $custom_titles [description]
     * @return [type]                [description]
     */
    private function _get_custom_title_fields($custom_titles)
    {
        // We are getting the title fields
        $title_fields = array();

        // If there are no 'custom_titles' in cache, then lets query it and then cache it.
        if (! isset($this->cache['custom_titles'])) {
            $query = ee()->db->query("SELECT channel_id, channel_name FROM exp_channels WHERE site_id = '$this->site_id'");

            foreach ($query->result_array() as $row) {
                $this->cache['custom_titles'][$row['channel_name']] = $row;
            }
        }

        // Loop through the titles passed as parameters, and create the $title_fields array.
        //
        // $pair looks like "channel:custom_field" and is a string.
        foreach ($custom_titles as $pair) {
            // Each pair needs to have a : delimeter. If one of the pairs doesnt have it, return false.
            if (strstr($pair, ':') === false) {
                return false;
            }

            // We are exploding on : and then assigning variables for clarity and readability
            $exploded = explode(':', $pair);
            $channel_name = $exploded[0];
            $custom_name = $exploded[1];

            // Populate the $title_fields array with the data.
            // $title_fields is an array where $key is the channel id, and $value is the new name
            if (isset($this->cache['custom_titles'][$channel_name]) && $this->cache['custom_titles'][$channel_name] !== null) {
                $custom_channel_id = $this->cache['custom_titles'][$channel_name]['channel_id'];
                $title_fields[$custom_channel_id] = $custom_name;
            }
        }

        return $title_fields;
    }

    /**
     * Separating out some code to split it into single purpose functions
     *
     * @param  [type] $custom_titles [description]
     * @return [type]                [description]
     */
    private function _get_sql_fields($custom_titles, $title_fields)
    {
        // Load the Channel API
        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');

        // Get all of the Channel fields
        $c_fields = ee()->api_channel_fields->fetch_custom_channel_fields($custom_titles);

        // Get the fields that exist for all sites (EE4 Only).
        $c_fields_global = array_key_exists(0, $c_fields['custom_channel_fields']) ? $c_fields['custom_channel_fields'][0] : array();

        // Get only the custom channel fields for this site_id
        $c_fields_site = array_key_exists($this->site_id, $c_fields['custom_channel_fields']) ? $c_fields['custom_channel_fields'][$this->site_id] : array();

        $c_fields = array_merge($c_fields_global, $c_fields_site);

        $sql_fields = array();
        foreach ($title_fields as $channel_id => $field) {
            // if we dont have custom fields, or if the field is not in the custom fields, continue.
            // This could leave $sql_fields as an empty array, which is okay.
            if (! is_array($c_fields) or ! array_key_exists($field, $c_fields)) {
                continue;
            }

            // Populate $sql_fields with new the channel data for the current field.
            $sql_fields[$channel_id]['field_id'] = $c_fields[$field];
            $sql_fields[$channel_id]['field_name'] = $field;
        }

        return $sql_fields;
    }

    private function _get_structure_channel_ids($include_listings = false, $as_string = false)
    {
        // Get an array of all the structure managed channels
        $structure_channels = $this->get_structure_channels('page');

        // include the listings if it is set to true
        if ($include_listings) {
            $add_ch_list = $this->get_structure_channels('listing');
            if (is_array($add_ch_list)) { // no listing === FALSE so typecheck!
                $structure_channels += $add_ch_list;
            }
        }

        // get all the channel id's
        $structure_channel_ids = array_keys($structure_channels);

        // We are returning it as a string, for an sql query
        if ($as_string) {
            // get a list of all the channel ids, as a string. This is for sql. Ex: "1,3,2"
            $structure_channel_ids = implode(',', $structure_channel_ids);
        }

        return $structure_channel_ids;
    }

    private function _get_page_titles($sql_fields, $include_listings)
    {
        // we need the channel_id's of the structure entries
        $structure_channel_ids = $this->_get_structure_channel_ids($include_listings);

        // Get the Channel Entries that have are in a Structure Channel
        $selectFields = array_map(function ($field_data) {
            return 'field_id_' . $field_data['field_id'];
        }, $sql_fields);
        $channelEntries = ee('Model')->get('ChannelEntry')->fields('entry_id', 'channel_id', 'site_id', 'title');
        foreach ($selectFields as $field) {
            $channelEntries->fields($field);
        }
        $channelEntries = $channelEntries->filter('channel_id', 'IN', $structure_channel_ids)
            ->filter('site_id', '==', $this->site_id)
            ->all();

        // Loop through the Channel Entries and create the page titles array
        $page_titles = array();
        foreach ($channelEntries as $channelEntry) {
            // Do not get the field, unless we prove that we should.
            $should_get_field = false;

            // This will determine if the custom title array actually has the data we need in it
            $custom_field_exists = (
                array_key_exists($channelEntry->channel_id, $sql_fields)    // the channel_id is in the array
                                && isset($sql_fields[$channelEntry->channel_id]['field_id'])    // The field_id is set
                                && !empty($sql_fields[$channelEntry->channel_id]['field_id'])   // The field_id is not empty
            );

            // if the custom field exists, lets check if the field actually has data in it in the channelEntry instance
            if ($custom_field_exists) {
                $field_id = 'field_id_' . $sql_fields[$channelEntry->channel_id]['field_id'];
            }

            // Set the page titles array accordingly
            if ($custom_field_exists && !empty($channelEntry->$field_id)) {
                $page_titles[$channelEntry->entry_id] = $channelEntry->$field_id;
            } else {
                $page_titles[$channelEntry->entry_id] = $channelEntry->title;
            }
        }

        return $page_titles;
    }

    public function get_overview($entry_id)
    {
        $sql = "SELECT node.*, (1) AS depth,
                    if((node.rgt - node.lft) = 1,1,0) AS isLeaf,
                    ((node.rgt - node.lft - 1) DIV 2) AS numChildren, expt.status, expt.title
                FROM exp_structure AS node
                INNER JOIN exp_structure AS parent
                    ON node.lft BETWEEN parent.lft AND parent.rgt
                INNER JOIN exp_channel_titles AS expt
                    ON node.entry_id = expt.entry_id
                WHERE node.entry_id = '$entry_id'
                AND parent.site_id IN (0,$this->site_id)
                GROUP BY node.lft
                LIMIT 1";

        $cached_data = StaticCache::get('get_overview__' . $sql);

        if (!empty($cached_data)) {
            if ($cached_data === 'EMPTY') {
                return false;
            }

            $result_row = $cached_data;
        } else {
            $result = ee()->db->query($sql);

            if ($result->num_rows == 0) {
                StaticCache::set('get_overview__' . $sql, 'EMPTY');

                return false;
            }

            $result_row = "";
            foreach ($result->result_array() as $row) {
                $result_row = $row;
            }

            StaticCache::set('get_overview__' . $sql, $result_row);
        }

        return $result_row;
    }

    public function get_member_settings()
    {
        $data = array(
            'site_id' => $this->site_id,
            'member_id' => $member_id = ee()->session->userdata('member_id')
        );

        $results = ee()->db->get_where('structure_members', $data, 1);
        if ($results->num_rows > 0) {
            if (! function_exists('json_decode')) {
                ee()->load->library('Services_json');
            }

            $member_settings = $results->row_array();
            $member_settings['nav_state'] = json_decode($member_settings['nav_state']);

            return $member_settings;
        }

        return null;
    }

    public function get_parent_id($entry_id, $default = 'home')
    {
        if (! is_numeric($entry_id)) {
            return false;
        }

        $cacheKey = 'get_parent_id/' . $this->site_id . '/' . $entry_id . '/' . $default;
        $cacheItem = StaticCache::get($cacheKey);

        if ($cacheItem) {
            return $cacheItem;
        }

        $listing_ids = $this->get_listing_entry_ids();
        if (is_array($listing_ids) && in_array($entry_id, $listing_ids)) {
            $sql = "SELECT parent_id FROM exp_structure_listings WHERE entry_id = $entry_id AND site_id = $this->site_id";
        } else {
            $sql = "SELECT parent_id FROM exp_structure WHERE entry_id = $entry_id AND site_id = $this->site_id";
        }

        $result = ee()->sql_helper->row($sql);
        if (empty($result)) {
            return false;
        }

        // Get homepage instead
        if ($result['parent_id'] !== 0) {
            StaticCache::set($cacheKey, $result['parent_id']);
            return $result['parent_id'];
        } elseif ($default == 'home') {
            $sql = "SELECT entry_id FROM exp_structure WHERE lft = 2 AND site_id = $this->site_id";
            $result = ee()->sql_helper->row($sql);

            if (isset($result['entry_id']) && is_numeric($result['entry_id'])) {
                StaticCache::set($cacheKey, $result['entry_id']);
                return $result['entry_id'];
            }
        }

        // This will be parent_id of 0
        return $result['parent_id'];
    }

    public function get_home_page_id()
    {
        $sql = "SELECT entry_id FROM exp_structure WHERE lft = 2 AND site_id = $this->site_id";
        $result = ee()->sql_helper->row($sql);

        return $result['entry_id'];
    }

    public function get_home_node()
    {
        $cacheItem = StaticCache::get('get_home_node');

        if ($cacheItem) {
            return $cacheItem;
        }

        $sql = "SELECT * FROM exp_structure WHERE entry_id = 0";
        $result = ee()->sql_helper->row($sql);

        StaticCache::set('get_home_node', $result);

        return $result;
    }

    public function get_page_title($entry_id)
    {
        if (!is_numeric($entry_id)) {
            return false;
        }

        $cached_page_title = StaticCache::get('structure_page_title_' . $entry_id);

        if(!empty($cached_page_title)) {
            return $cached_page_title;
        }

        ee()->db->where('entry_id', $entry_id);
        ee()->db->limit(1);

        $query = ee()->db->get('exp_channel_titles');

        if ($query->num_rows() == 1) {
            $row = $query->row();

            StaticCache::set('structure_page_title_' . $entry_id, $row->title);

            return $row->title;
        }
        

        return false;
    }

    public function is_listing_entry($entry_id)
    {
        $listing_entries = $this->get_listing_entry_ids();
        // TODO
        return isset($listing_entries[$entry_id]);
    }

    /**
     * Function to get all entry id's for all listings
     *
     * @method get_listing_entry_ids
     * @return Array of listing ids's
     */
    public function get_listing_entry_ids()
    {
        $listing_ids = StaticCache::get('listing_ids');
        $listing_ids_empty = StaticCache::get('listing_ids_empty');

        if (!is_array($listing_ids)) {
            $listing_ids = array();
        }

        if (empty($listing_ids_empty) && empty($listing_ids)) {
            // not in cache lets get it from the DB
            $sql = "SELECT entry_id FROM exp_structure_listings WHERE site_id = $this->site_id LIMIT 99999999";
            $result = ee()->db->query($sql);

            if ($result->num_rows > 0) {
                foreach ($result->result_array() as $row) {
                    // key has to be entry_id for rest of module code to work.
                    $listing_ids[$row['entry_id']] = $row['entry_id'];
                }

                // save to cache so we can use it later
                StaticCache::set('listing_ids', $listing_ids);
                StaticCache::set('listing_ids_empty', 'false');
            } else {
                StaticCache::set('listing_ids_empty', 'true');
            }
        }

        return $listing_ids;
    }

    /**
     * Get all data from the exp_structure_channels table
     * @param $type|unmanaged|page|listing|asset
     * @param $channel_id you can pass a channel_id to retreive it's data
     * @param $order pass it 'alpha' to order by channel title
     * @return array An array of channel_ids and it's associated template_id, type and channel_title
     */
    public function get_structure_channels($type = '', $channel_id = '', $order = '', $selector = false)
    {
        $site_id = ee()->config->item('site_id');

        // Get Structure Channel Data
        $sql = "SELECT ec.channel_id, ec.channel_title, ec.site_id, esc.template_id, esc.type, esc.split_assets, esc.show_in_page_selector
                FROM exp_channels AS ec
                LEFT JOIN exp_structure_channels as esc USING (channel_id)
                WHERE ec.site_id = '$site_id'";
        if ($type != '') {
            $sql .= " AND esc.type = '$type'";
        }
        if ($channel_id != '') {
            $sql .= " AND esc.channel_id = '$channel_id'";
        }
        if ($selector == true) {
            $sql .= " AND esc.show_in_page_selector = 'y'";
        }
        if ($order == 'alpha') {
            $sql .= " ORDER BY ec.channel_title";
        }

        $results = ee()->db->query($sql);

        if ($results->num_rows > 0) {
            // Format the array nicely
            $channel_data = array();
            foreach ($results->result_array() as $key => $value) {
                $channel_data[$value['channel_id']] = $value;
            }

            return $channel_data;
        }

        return false;
    }

    public function get_channel_type($channel_id)
    {
        if (is_numeric($channel_id)) {
            $sql = "SELECT type FROM exp_structure_channels WHERE channel_id = '$channel_id' AND site_id = '$this->site_id' LIMIT 1";
            $query = ee()->db->query($sql);
            if ($query->num_rows > 0) {
                $row = $query->row();

                return $row->type;
            }
        }

        return false;
    }

    public function get_default_template($channel_id)
    {
        if (is_numeric($channel_id)) {
            $sql = "SELECT template_id FROM exp_structure_channels WHERE channel_id = '$channel_id' AND site_id = '$this->site_id' LIMIT 1";
            $query = ee()->db->query($sql);
            if ($query->num_rows > 0) {
                $row = $query->row();

                return $row->template_id;
            }
        }

        return false;
    }

    public function get_child_entries($parent_id, $cat = '', $include_hidden = 'n')
    {
        $cached = StaticCache::get(array($parent_id, $cat, $include_hidden));

        if (!empty($cached)) {
            return $cached;
        }

        $entries = array();
        $catarray = array();

        if ($cat != '') {
            $cat_entries = $this->get_entries_by_category($cat);

            foreach ($cat_entries as $entry) {
                $catarray[] = $entry['entry_id'];
            }

            $catarray = implode(",", $catarray);
        }

        if ($parent_id !== false && is_numeric($parent_id)) {
            $sql = "select entry_id from exp_structure where
                        parent_id = " . $parent_id . " AND
                        entry_id!=0 AND
                        site_id = " . $this->site_id;

            if ($include_hidden == 'n') {
                $sql .= " AND hidden != 'y' ";
            }

            // I've had to remove this from Active Record because CI adds backticks on a
            // where_in clause which is a bit crap when you want to do an inclusive array of entry_id's!

            //ee()->db->select('entry_id')->from('structure')->where(array(
            //  'parent_id' => $parent_id,
            //  'entry_id !=' => 0,
            //  'hidsden !=' => 'y',
            //  'site_id' => $this->site_id
            //));

            if ($catarray) {
                $sql .= "   AND entry_id IN(" . $catarray . ")";
            }

            $sql .= "   order by lft asc";

            $results = ee()->db->query($sql);

            if ($results->num_rows() > 0) {
                foreach ($results->result_array() as $row) {
                    $entries[] = $row['entry_id'];
                }
            }
        }

        StaticCache::set(array($parent_id, $cat, $include_hidden), $entries);

        return $entries;
    }

    public function get_entries_by_category($cat)
    {
        //firstly, lets see whether we have a category id or a word
        if (is_numeric($cat)) {
            //it's a number, so we'll assume a cat_id and not bother with the lookup.
            //I'm going to leave this if conditional in here though as I might want to use it at some point.
        } else {
            //it's not a number so we need to get a cat id;
            $result = ee()->db->select("cat_id")->from("categories")->where("cat_url_title", $cat)->get();

            if ($result->num_rows() > 0) {
                $cat = $result->row()->cat_id;
            }
        }

        // Now, lets get an array of all entries which are in this category_id
        return ee()->db->select("entry_id")->from("category_posts")->where("cat_id", $cat)->get()->result_array();
    }

    public function get_channel_by_entry_id($entry_id)
    {
        ee()->db->select('channel_id')->from('channel_titles')->where('entry_id', $entry_id);
        $result = ee()->db->get();
        if ($result->num_rows() > 0) {
            return $result->row()->channel_id;
        }

        return null;
    }

    public function get_channel_name_by_channel_id($channel_id)
    {
        ee()->db->select('channel_name')->from('channels')->where('channel_id', $channel_id);
        $result = ee()->db->get();
        if ($result->num_rows() > 0) {
            return $result->row()->channel_name;
        }

        return null;
    }

    /**
     * Get data from the exp_sites table
     *
     * @return array with site_id as key
     */
    public function get_site_pages($cache_bust = false, $override_slash = false)
    {
        $settings = $this->get_settings();

        $trailing_slash = $override_slash === false && isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y';

        $blank_pages = array(
            'url' => '',
            'uris' => array(),
            'templates' => array()
        );

        $site_id = ee()->config->item('site_id');

        if ($cache_bust === true) {
            $sql = "SELECT site_pages FROM exp_sites WHERE site_id = $this->site_id";
            $pages_array = ee()->sql_helper->row($sql);
            $all_pages = unserialize(base64_decode($pages_array['site_pages']));
        } else {
            $all_pages = ee()->config->item('site_pages');
        }

        if (is_array($all_pages) && isset($all_pages[$this->site_id]) && is_array($all_pages[$this->site_id])) {
            $site_pages = array_merge($blank_pages, $all_pages[$this->site_id]);
        } else {
            $site_pages = $blank_pages;
        }

        if ($trailing_slash) {
            foreach ($site_pages['uris'] as $key => $uri) {
                $site_pages['uris'][$key] = Structure_Helper::remove_double_slashes($uri . '/');
            }
        } else {
            foreach ($site_pages['uris'] as $key => $uri) {
                if ($site_pages['uris'][$key] !== '/') {
                    $site_pages['uris'][$key] = rtrim($uri, '/');
                }
            }
        }

        return $site_pages;
    }

    public function get_page_count()
    {
        return ee()->db->count_all('structure') - 1;
    }

    public function get_channel_data($channel_id)
    {
        $sql = "SELECT * FROM exp_structure_channels
                WHERE channel_id = $channel_id
                AND site_id = $this->site_id";

        return ee()->sql_helper->row($sql);
    }

    /**
     * Get Templates
     *
     * @return Single dimensional array of templates, ids and names
     **/
    public function get_templates()
    {
        $sql = "SELECT tg.group_name, t.template_id, t.template_name
                FROM   exp_template_groups tg, exp_templates t
                WHERE  tg.group_id = t.group_id
                AND tg.site_id = '$this->site_id'
                ORDER BY tg.group_name, t.template_name";
        $query = ee()->db->query($sql);
        $templates = $query->result_array();

        $settings = $this->get_settings();

        if (isset($settings['hide_hidden_templates']) && $settings['hide_hidden_templates'] == 'y') {
            $hidden_indicator = ee()->config->item('hidden_template_indicator') ? ee()->config->item('hidden_template_indicator') : '.';

            foreach ($templates as $key => $row) {
                if (substr($row['template_name'], 0, 1) == $hidden_indicator) {
                    unset($templates[$key]);
                }
            }
        }

        return $templates;
    }

    public function get_listing_entry($entry_id)
    {
        $sql = "SELECT * FROM exp_structure_listings WHERE entry_id = '$entry_id' AND site_id = '$this->site_id'";
        $result = ee()->sql_helper->row($sql);

        if ($result !== null) {
            return $result;
        }

        return false;
    }

    public function get_listing_parent($channel_id)
    {
        // $sql = "SELECT entry_id FROM exp_structure WHERE listing_cid = '$channel_id' AND site_id = '$this->site_id'";
        $result = ee()->db->select('entry_id')
            ->from('structure')
            ->where('listing_cid', $channel_id)
            ->where('site_id', $this->site_id)
            ->get();

        if ($result->num_rows() > 0) {
            return $result->row()->entry_id;
        }

        return null;
    }
    /**
     * Gets the listing channel id for entry id passed in.
     *
     * @method get_listing_channel
     * @param  int $entry_id Listing parent (who the listing is tied to) channel entry id
     * @return INT, Returns the listing channel id tied to the entry_id passed in OR FALSE
     */
    public function get_listing_channel($entry_id)
    {
        if (!is_numeric($entry_id)) {
            return false;
        }

        // get all listing id's
        $listing_ids = $this->get_listing_entry_ids();

        if (is_array($listing_ids) && array_key_exists($entry_id, $listing_ids)) {
            // set to parent_id if it exists
            $entry_id = $this->get_parent_id($entry_id) ?: $entry_id;
        }

        // get the listing channel id for the entry_id that was passed in
        $sql = "SELECT listing_cid FROM exp_structure
                WHERE entry_id = " . $entry_id .
                " AND site_id = " . $this->site_id;

        
        $cached_listing_cid = StaticCache::get('get_listing_channel_' . $sql);

        if(!empty($cached_listing_cid)) {
            return $cached_listing_cid;
        }

        $result = ee()->sql_helper->row($sql);

        // return the listing channel id.
        if ($result !== null && $result['listing_cid'] != '0') {
            // Make sure the channel this listing references still exists as there
            // is no EE hook for adding/deleting an entire channel (as of this writing).
            $channel_exists = ee()->db->get_where('channels', array('channel_id' => $result['listing_cid']), 1)->result_array();

            // No channel exists, it must have been deleted. Let's update our exp_structure record so we don't have to go through this again.
            if (empty($channel_exists)) {
                ee()->db->where('entry_id', $entry_id);
                ee()->db->where('site_id', $this->site_id);
                ee()->db->update('structure', array('listing_cid' => 0));

                // There is no listing channel so make sure we don't return the incorrect response.
                return false;
            }

            StaticCache::set('get_listing_channel_' . $sql, $result['listing_cid']);

            return $result['listing_cid'];
        }

        return false;
    }

    public function get_listing_channel_by_id($entry_id)
    {
        $result = ee()->db->get_where('structure', array('entry_id' => $entry_id, 'site_id' => $this->site_id));
        if ($result->num_rows() > 0) {
            return $result->row()->listing_cid;
        }

        return false;
    }

    public function get_listing_channel_short_name($channel_id)
    {
        $sql = "SELECT channel_name FROM exp_channels
                WHERE channel_id = $channel_id
                AND site_id = $this->site_id";
        $result = ee()->sql_helper->row($sql);

        if ($result !== null && $result['channel_name'] != '0') {
            return $result['channel_name'];
        }

        return false;
    }

    public function get_cp_asset_data()
    {
        $asset_data = $this->get_structure_channels('asset', '', 'alpha');
        $split_assets = $this->get_split_assets();

        if ($asset_data === false) {
            return false;
        }

        $cp_asset_data = array();
        foreach ($asset_data as $channel_id => $row) {
            if ($row['split_assets'] == 'n') {
                $cp_asset_data[$row['channel_title']] = array(
                    'title' => $row['channel_title'],
                    'channel_id' => $channel_id,
                    'split_assets' => 'n'
                );
            } else {
                foreach ($split_assets[$row['channel_id']] as $split_channel_id => $split_row) {
                    $cp_asset_data[$split_row['title']] = array(
                        'title' => $split_row['title'],
                        'channel_id' => $row['channel_id'],
                        'entry_id' => $split_row['entry_id'],
                        'split_assets' => 'y'
                    );
                }
            }
        }
        ksort($cp_asset_data);

        return $cp_asset_data;
    }

    /**
     * Module Is Installed
     *
     * @return bool TRUE if installed
     * @return bool FALSE if not installed
     */
    public function module_is_installed()
    {
        $module_id_query = ee()->cache->get('/Structure/module_id_query');

        if (! $module_id_query) {
            $module_id_query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Structure'")->result();

            ee()->cache->save('/Structure/module_id_query', $module_id_query);
        }

        if (count($module_id_query)) {
            return true;
        }

        return false;
    }

    /**
     * Extension Is Installed
     *
     * @return bool TRUE if installed
     * @return bool FALSE if not installed
     */
    public function extension_is_installed()
    {
        $results = ee()->db->query("SELECT * FROM exp_extensions WHERE class = 'Structure_ext' AND enabled='y'");

        if ($results->num_rows > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get Module ID
     *
     * @return numeral Module's ID
     */
    public function get_module_id()
    {
        $module_id_query = ee()->cache->get('/Structure/module_id_query');
        if (! $module_id_query) {
            $module_id_query = ee()->db->query("SELECT module_id FROM exp_modules WHERE module_name = 'Structure'")->result();

            ee()->cache->save('/Structure/module_id_query', $module_id_query);
        }

        if (count($module_id_query)) {
            return $module_id_query[0]->module_id;
        }

        return false;
    }

    public function is_duplicate_listing_uri($entry_id, $uri, $parent_id)
    {
        $query = ee()->db->get_where('structure_listings', array('uri' => $uri, 'parent_id' => $parent_id, 'entry_id !=' => $entry_id));

        if ($query->num_rows > 0) {
            // Let's see how many dupes there really are. -1, -2, -3 is nicer than -1, -1-1, -1-1-1
            $sql = "SELECT * FROM exp_structure_listings WHERE parent_id={$parent_id} AND uri REGEXP '^{$uri}.[0-9]'";
            $result = ee()->db->query($sql);

            return $result->num_rows + 1; // we're implying 1, and the regex will not factor the "root" or original url
        }

        return false;
    }

    public function is_duplicate_page_uri($entry_id, $uri)
    {
        $site_pages_array = $this->get_site_pages(true);
        $pages = $site_pages_array['uris'];

        unset($pages[$entry_id]);

        $word_separator = ee()->config->item('word_separator');
        $separator = $word_separator != 'dash' ? '_' : '-';

        $settings = $this->get_settings();

        $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : '';

        if (in_array($uri . $trailing_slash, $pages)) {
            $uri = rtrim($uri, '/') . $separator . '1/';

            if (in_array($uri, $pages)) {
                $uri = rtrim($uri, '-1/') . $separator . '2/';
            }

            return $uri;
        }

        return false;
    }

    public function is_valid_template($template_id)
    {
        if (! is_numeric($template_id)) {
            return false;
        }

        $result = ee()->db->get_where('templates', array('template_id' => $template_id));

        return ($result->num_rows() > 0);
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

    /*
    * @param parent_uri
    * @param page_uri/slug
    */
    public function create_page_uri($parent_uri, $page_uri = '')
    {
        $parent_uri = preg_replace("#[^a-zA-Z0-9_\-\.]+#i", '', $parent_uri);
        $page_uri = preg_replace("#[^a-zA-Z0-9_\-\.]+#i", '', $page_uri);

        // prepend the parent uri
        $uri = $parent_uri . '/' . $page_uri . '/';

        // ensure beginning and ending slash
        $uri = '/' . trim($uri, '/') . '/';

        // if double slash, reduce to one
        return str_replace('//', '/', $uri);
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
        $uri = '/' . trim($uri, '/') . '/';
        // if double slash, reduce to one
        return str_replace('//', '/', $uri);
    }

    public function set_site_pages($site_id, $site_pages)
    {
        if (empty($site_id)) {
            $site_id = ee()->config->item('site_id');
        }

        foreach ($site_pages['uris'] as &$uri) {
            if ($uri != "/") {
                $uri = rtrim($uri, '/');
            }
        }

        $pages[$site_id] = $site_pages;

        unset($site_pages);

        $site_id = ee()->db->escape_str($site_id);
        $new_site_pages = serialize($pages);
        $new_site_pages = base64_encode($new_site_pages);

        $update_string = ee()->db->update_string('exp_sites', array('site_pages' => $new_site_pages), "site_id='" . $site_id . "'");

        // var_dump($update_string);
        ee()->db->query($update_string);
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
            $site_pages = $this->get_site_pages(true);
        }

        // Update the entry for our listing item in site_pages
        $site_pages['uris'][$data['entry_id']] = $this->create_full_uri($data['parent_uri'], $data['uri']);
        $site_pages['templates'][$data['entry_id']] = $data['template_id'];
        $site_id = ee()->config->item('site_id');

        $this->set_site_pages($site_id, $site_pages);

        // Our listing table doesn't need this anymore, so remove it.
        unset($data['listing_cid']);
        unset($data['parent_uri']);
        unset($data['hidden']);

        $data['uri'] = trim($data['uri'], '/'); // Just keeping our data clean.

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
        $this->update_root_node();
    }

    /**
     * Get Member Groups
     *
     * @return Array of member groups with access to Structure
     */
    public function get_member_groups()
    {
        foreach (['can_create_entries', 'can_edit_other_entries', 'can_edit_self_entries'] as $permission) {
            $$permission = ee('Model')->get('Permission')
                ->fields('role_id')
                ->filter('permission', 'LIKE', $permission . '%')
                ->filter('site_id', ee()->config->item('site_id'))
                ->all()
                ->pluck('role_id');
        }

        if (empty($can_create_entries) || empty($can_edit_other_entries) || empty($can_edit_self_entries)) {
            return false;
        }

        $allowedRoles = ee('Model')->get('Role')
            ->filter('role_id', 'IN', $can_create_entries)
            ->filter('role_id', 'IN', $can_edit_other_entries)
            ->filter('role_id', 'IN', $can_edit_self_entries)
            ->filter('role_id', 'NOT IN', [1])
            ->order('role_id', 'asc')
            ->all()
            ->toArray();

        $moduleRoles = ee('Model')->get('Module')->filter('module_name', 'Structure')->first()->AssignedRoles->pluck('role_id');

        $roles = [];
        $i = 0;
        if (!empty($allowedRoles)) {
            foreach ($allowedRoles as $role) {
                if (in_array($role['role_id'], $moduleRoles)) {
                    $roles[$i] = $role;
                    $roles[$i]['id'] = $role['role_id'];
                    $roles[$i]['title'] = $role['name'];
                    $i++;
                }
            }
        }

        return $roles;
    }

    /**
     * Get Entry Title
     *
     * @param string $entry_id
     * @return string Entry Title or NULL
     */
    public function get_entry_title($entry_id)
    {
        if (! is_numeric($entry_id)) {
            return null;
        }

        $sql = "SELECT title FROM exp_channel_titles WHERE entry_id = $entry_id";
        $result = ee()->db->query($sql);

        if ($result->num_rows > 0) {
            return $result->row('title');
        } else {
            return null;
        }
    }

    /**
     * Get entries by channel
     *
     * @param int $channel_id
     * @return $entry_ids array of entry_ids or FALSE
     **/
    public function get_entries_by_channel($channel_id)
    {
        if (! is_numeric($channel_id)) {
            return false;
        }

        $sql = "SELECT entry_id FROM exp_channel_titles WHERE channel_id = $channel_id AND site_id = $this->site_id";
        $result = ee()->db->query($sql);

        if ($result->num_rows = 0) {
            return false;
        }

        $entry_ids = array();
        foreach ($result->result_array() as $row) {
            $entry_ids[] = $row['entry_id'];
        }

        return $entry_ids;
    }

    public function get_entry_titles_by_channel($channel_id)
    {
        if (! is_numeric($channel_id)) {
            return false;
        }

        $sql = "SELECT entry_id, title FROM exp_channel_titles WHERE channel_id = $channel_id AND site_id = $this->site_id ORDER BY title";
        $result = ee()->db->query($sql);

        if ($result->num_rows = 0) {
            return false;
        }

        $entry_ids = array();
        foreach ($result->result_array() as $row) {
            $entry_ids[] = array('entry_id' => $row['entry_id'], 'title' => $row['title']);
        }

        return $entry_ids;
    }

    public function get_split_assets()
    {
        $sql = "SELECT channel_id FROM exp_structure_channels WHERE type = 'asset' AND split_assets = 'y'";
        $result = ee()->db->query($sql);

        if ($result->num_rows > 0) {
            $data = array();
            foreach ($result->result_array() as $channel_id => $row) {
                $data[$row['channel_id']] = $this->get_entry_titles_by_channel($row['channel_id']);
            }

            return $data;
        }

        return null;
    }

    public function get_listing_channel_data($channel_id = false)
    {
        $data = ee()->db->select('*')
            ->from('channel_titles')
            ->where('channel_id', $channel_id)->get();

        return $data->result_array();
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
        if (!$result->num_rows) {
            return false;
        }

        $pid = $result->row('entry_id');

        return $pid;
    }

    /**
     * Gets all listing entry data form the structure_listings table with an array key of the entry id
     *
     * @method get_channel_listing_entries
     * @param  int $channel_id Channel ID of the listing channel
     * @return Array of structure listing data for the channel passed in OR FALSE
     */
    public function get_channel_listing_entries($channel_id)
    {
        if (! is_numeric($channel_id)) {
            return false;
        }

        // get all listing data from the structure listing table
        $sql = "SELECT * FROM exp_structure_listings WHERE channel_id = $channel_id AND site_id = $this->site_id limit 99999999";
        $result = ee()->db->query($sql);

        if ($result->num_rows > 0) {
            $listings = array();

            // go over each item and add it to an array where the key is the entry id
            foreach ($result->result_array() as $entry) {
                $listings[$entry['entry_id']] = $entry;
            }

            return $listings;
        }

        return false;
    }

    public function get_hidden_state($entry_id)
    {
        ee()->db->select('hidden')->from('structure')->where(array('entry_id' => $entry_id, 'site_id' => $this->site_id));
        $result = ee()->db->get();

        if ($result->num_rows > 0) {
            $row = $result->row();

            return $row->hidden;
        }

        return 'n';
    }

    public function update_integrity_data()
    {
        $site_pages = $this->get_site_pages(true);
        $site_pages_entry_ids = $site_pages['uris'];
        $site_pages_template_ids = $site_pages['templates'];

        // Get the structure channels to use to lookup the default `template_id`.
        $template_defaults = $this->get_structure_channels();

        // Loop through the existing structure entries and grab the URI and template_id from the site_pages array.
        // Get just the url_title portion of the URI and store it inside Structure for data integrity purposes.
        // EE will still use the uri's in site_pages for routing.
        ee()->db->select('structure.entry_id, structure.channel_id, channel_titles.url_title, structure.parent_id');
        ee()->db->from('structure');
        ee()->db->where('structure.site_id', $this->site_id);
        ee()->db->where('channel_titles.site_id', $this->site_id);
        ee()->db->join('channel_titles', 'channel_titles.entry_id = structure.entry_id');

        $structure_index = ee()->db->get()->result_array();

        foreach ($structure_index as $structure_entry) {
            $structure_url_title = false;
            $entry_id = $structure_entry['entry_id'];

            // Check if there's an existing site_pages uri record for this entry.
            // if(!empty($site_pages_entry_ids[$entry_id])) {
            //  $uri = rtrim($site_pages_entry_ids[$entry_id], '/');
            //  $uri = substr($uri, strrpos($uri, '/'));
            //  $structure_url_title = trim($uri, '/');
            // } else {
            // There is no entry in site_pages so try to get the url_title from the channel entry itself.
            if (!empty($structure_entry['url_title'])) {
                $structure_url_title = $structure_entry['url_title'];
            }
            // }

            // Lookup the Template ID for this entry.
            $template_id = 0;

            // Check if there's an existing site_pages template_id record for this entry.
            if (!empty($site_pages_template_ids[$entry_id])) {
                $template_id = $site_pages_template_ids[$entry_id];
            } elseif (!empty($structure_entry['channel_id']) && !empty($template_defaults[$structure_entry['channel_id']]['template_id'])) {
                // There is no template_id stored in `site_pages` so lookup the default template selected for this channel.
                $template_id = $template_defaults[$structure_entry['channel_id']]['template_id'];
            }

            if (!empty($template_id)) {
                ee()->db->where('entry_id', $entry_id);
                ee()->db->update('structure', array('template_id' => $template_id));
            }

            if (!empty($structure_url_title)) {
                ee()->db->where('entry_id', $entry_id);
                ee()->db->update('structure', array('structure_url_title' => $structure_url_title));
            }
        }
    }

    public function cleanup_check()
    {
        $vals = array();

        // Remove extraneous entries in exp_structure
        $site_pages = $this->get_site_pages(true);
        $site_pages_entry_ids = array_keys($site_pages['uris']);
        $site_pages_uris = array_values($site_pages['uris']);

        // Copy the URIs to another array so we can see if there are things in site_pages that are NOT in structure.
        $site_pages_remaining = $site_pages['uris'];

        // Find out if the site_pages array has duplicate entries.
        $vals['site_pages_duplicates'] = array();

        foreach (array_count_values($site_pages_entry_ids) as $entry_id => $count) {
            if ($count > 1) {
                $vals['site_pages_duplicates'][] = $entry_id;
            }
        }

        // Find out if the site_pages array has duplicate uris (different from duplicate entries).
        $site_pages_uri_duplicates = array();
        $vals['site_pages_uri_duplicates'] = array();

        $uri_counts = array();
        foreach ($site_pages['uris'] as $entry_id => $uri) {
            // Search the site_pages array to see if there are any duplicates using the array_keys with optional search parameter.
            $keys = array_keys($site_pages['uris'], $uri);

            if (count($keys) > 1) {
                $site_pages_uri_duplicates[$entry_id] = $uri;
            }
        }

        if (count($site_pages_uri_duplicates) > 0) {
            asort($site_pages_uri_duplicates);

            // We have to loop through these items again adding the ee_url to link to the edit entry page.
            foreach ($site_pages_uri_duplicates as $entry_id => $uri) {
                $vals['site_pages_uri_duplicates'][$entry_id]['structure_url_title'] = $uri;
                $vals['site_pages_uri_duplicates'][$entry_id]['ee_url'] = ee()->general_helper->cpURL('publish', 'edit', array('entry_id' => $entry_id));
            }
        }

        $vals['total_site_pages_duplicates'] = count($vals['site_pages_duplicates']);

        $vals['orphaned_entries'] = 0;
        $vals['duplicate_rights'] = 0;
        $vals['duplicate_lefts'] = 0;

        if (!is_array($site_pages_entry_ids) || empty($site_pages_entry_ids)) {
            $site_pages_entry_ids = array();
        }

        $structure_entries = ee()->db->get_where('structure', array('site_id' => $this->site_id))->result_array();

        $vals['orphaned_entries'] = array();

        $totalSitePagesEntries = count($site_pages_entry_ids);
        $totalStructureEntries = 0;
        $eeOrphans = 0;
        $sitePagesOrphans = 0;
        $sitePagesListingOrphans = 0;
        $structureOrphans = 0;
        $structureListingOrphans = 0;
        $checkedListingChannels = array();

        $vals['validation_action_enabled'] = false;

        $vals['mismatch_url_entries'] = array();
        $vals['template_id_errors'] = array();

        // Loop through Structure's index and check if the entries exist in EE (channel data) and EE's site_pages array.
        foreach ($structure_entries as $structure_entry) {
            $eeExists = false;
            $spExists = false;

            $totalStructureEntries++;

            $structure_entry_ids[] = $structure_entry['entry_id'];

            $vals['orphaned_entries'][$structure_entry['entry_id']]['ee'] = 0;
            $vals['orphaned_entries'][$structure_entry['entry_id']]['site_pages'] = 0;
            $vals['orphaned_entries'][$structure_entry['entry_id']]['title'] = 'Missing';
            $vals['orphaned_entries'][$structure_entry['entry_id']]['url_title'] = '--';
            $vals['orphaned_entries'][$structure_entry['entry_id']]['is_listing'] = 0;
            $vals['orphaned_entries'][$structure_entry['entry_id']]['structure'] = 0;
            $vals['orphaned_entries'][$structure_entry['entry_id']]['ee_url'] = '';

            // Check if this entry actually exists in the database.
            $ee_data_query = ee()->db->get_where('channel_titles', array('entry_id' => $structure_entry['entry_id'], 'site_id' => $this->site_id), 1);
            $ee_exists = $ee_data_query->num_rows();

            // If it exists in the DB, update our data in case it doesn't exist in site_pages.
            if (!empty($ee_exists) && $ee_exists == 1) {
                $eeExists = true;
                $ee_data = $ee_data_query->row_array();
                $vals['orphaned_entries'][$structure_entry['entry_id']]['ee'] = 1;
                $vals['orphaned_entries'][$structure_entry['entry_id']]['title'] = (!empty($ee_data['title']) ? $ee_data['title'] : 'Missing');
                $vals['orphaned_entries'][$structure_entry['entry_id']]['url_title'] = (!empty($ee_data['url_title']) ? $ee_data['url_title'] : '--');
                $vals['orphaned_entries'][$structure_entry['entry_id']]['listing_cid'] = $structure_entry['listing_cid'];
                $vals['orphaned_entries'][$structure_entry['entry_id']]['ee_url'] = ee()->general_helper->cpURL('publish', 'edit', array('entry_id' => $structure_entry['entry_id']));
            } else {
                $eeOrphans++;
            }

            // Find out if this is in EE's site_pages array.
            if (in_array($structure_entry['entry_id'], $site_pages_entry_ids)) {
                $spExists = true;

                // Flag that this entry exists (even though it's in orphans, we'll remove it later if it exists in Structure too).
                $vals['orphaned_entries'][$structure_entry['entry_id']]['site_pages'] = 1;

                $site_pages_url = $site_pages['uris'][$structure_entry['entry_id']];
                $check_url = $site_pages_url;

                if (!empty($site_pages_url)) {
                    $check_url = trim($check_url, '/');

                    if (strpos($check_url, '/')) {
                        $check_url = substr($check_url, strrpos($check_url, '/') + 1);
                    }

                    if (empty($check_url) && $site_pages_url == '/') {
                        $check_url = '/';
                    }
                }

                // Check to see if the Structure URL matches the site_pages URL.
                if ($structure_entry['structure_url_title'] != $check_url) {
                    $vals['mismatch_url_entries'][$structure_entry['entry_id']]['site_pages_url'] = $check_url;
                    $vals['mismatch_url_entries'][$structure_entry['entry_id']]['structure_url'] = $structure_entry['structure_url_title'];
                    $vals['mismatch_url_entries'][$structure_entry['entry_id']]['ee_url'] = ee()->general_helper->cpURL('publish', 'edit', array('entry_id' => $structure_entry['entry_id']));
                }

                // Check to make sure the entry's associated template_id is valid.
                if (!$this->is_valid_template($structure_entry['template_id'])) {
                    $vals['template_id_errors'][$structure_entry['entry_id']]['template_id'] = $structure_entry['template_id'];
                    $vals['template_id_errors'][$structure_entry['entry_id']]['ee_url'] = ee()->general_helper->cpURL('publish', 'edit', array('entry_id' => $structure_entry['entry_id']));
                }

                // Because this entry exists in Structure, remove it from our copy of the site_pages so
                // eventually we'll have a list of just site_pages entries that Structure does NOT have.
                unset($site_pages_remaining[$structure_entry['entry_id']]);
            } else {
                $sitePagesOrphans++;
                $vals['orphaned_entries'][$structure_entry['entry_id']]['site_pages'] = 0;

                $vals['validation_action_enabled'] = true;
            }

            // If this entry is a "Listing Channel", loop through the entries in the associated channel
            // to see if they're also in the `site_pages` array.
            if (!empty($structure_entry['listing_cid']) && !in_array($structure_entry['listing_cid'], $checkedListingChannels)) {
                $checkedListingChannels[] = $structure_entry['listing_cid'];

                $listing_entries = ee()->db->select('entry_id, title, url_title')->get_where('channel_titles', array('channel_id' => $structure_entry['listing_cid']))->result_array();

                foreach ($listing_entries as $listing_entry) {
                    $listingSpExists = false;
                    $listingStructureExists = false;

                    // Find out if this listing entry is in EE's site_pages array.
                    if (in_array($listing_entry['entry_id'], $site_pages_entry_ids)) {
                        $listingSpExists = true;

                        // Because this listng entry exists in Structure, remove it from our copy of the site_pages so
                        // eventually we'll have a list of just site_pages listing entries that Structure does NOT have.
                        unset($site_pages_remaining[$listing_entry['entry_id']]);
                    } else {
                        $sitePagesListingOrphans++;
                    }

                    // Just because Structure has the "Listing Channel" set, it doesn't mean this individual listing entry exists in Structure's `exp_structure_listings` table.
                    $listing_data_query = ee()->db->get_where('structure_listings', array('entry_id' => $listing_entry['entry_id'], 'site_id' => $this->site_id), 1);
                    $listing_exists = $listing_data_query->num_rows();

                    // If it exists in the DB, flag it so we know an actual entry exists for this orphaned Structure index.
                    if (!empty($listing_exists) && $listing_exists == 1) {
                        $listingStructureExists = true;
                        $totalStructureEntries++;

                        if ($listingSpExists) {
                            $listing_data = $listing_data_query->row_array();

                            $site_pages_url = $site_pages['uris'][$listing_entry['entry_id']];
                            $check_url = $site_pages_url;

                            if (!empty($site_pages_url)) {
                                $check_url = trim($check_url, '/');

                                if (strpos($check_url, '/')) {
                                    $check_url = substr($check_url, strrpos($check_url, '/') + 1);
                                }

                                if (empty($check_url) && $site_pages_url == '/') {
                                    $check_url = '/';
                                }
                            }

                            // Check to see if the Structure URL matches the site_pages URL.
                            if ($listing_data['uri'] != $check_url) {
                                $vals['mismatch_url_entries'][$listing_entry['entry_id']]['site_pages_url'] = $check_url;
                                $vals['mismatch_url_entries'][$listing_entry['entry_id']]['structure_url'] = $listing_data['uri'];
                                $vals['mismatch_url_entries'][$listing_entry['entry_id']]['ee_url'] = ee()->general_helper->cpURL('publish', 'edit', array('entry_id' => $listing_entry['entry_id']));
                            }

                            // Check to make sure the entry's associated template_id is valid.
                            if (!$this->is_valid_template($listing_data['template_id'])) {
                                $vals['template_id_errors'][$listing_entry['entry_id']]['template_id'] = $listing_data['template_id'];
                                $vals['template_id_errors'][$listing_entry['entry_id']]['ee_url'] = ee()->general_helper->cpURL('publish', 'edit', array('entry_id' => $listing_entry['entry_id']));
                            }
                        }
                    } else {
                        $structureListingOrphans++;
                    }

                    // If the listing entry is missing in either site_pages or Structure, log it as an orphaned listing entry.
                    if (!$listingSpExists || !$listingStructureExists) {
                        $vals['orphaned_entries'][$listing_entry['entry_id']]['ee'] = 1; // We know it exists in EE as we pulled it from `channel_titles`.
                        $vals['orphaned_entries'][$listing_entry['entry_id']]['site_pages'] = ($listingSpExists ? 1 : 0);
                        $vals['orphaned_entries'][$listing_entry['entry_id']]['title'] = (!empty($listing_entry['title']) ? $listing_entry['title'] : 'Missing');
                        $vals['orphaned_entries'][$listing_entry['entry_id']]['url_title'] = (!empty($listing_entry['url_title']) ? $listing_entry['url_title'] : '--');
                        $vals['orphaned_entries'][$listing_entry['entry_id']]['is_listing'] = 1;
                        $vals['orphaned_entries'][$listing_entry['entry_id']]['structure'] = ($listingStructureExists ? 1 : 0);
                        $vals['orphaned_entries'][$listing_entry['entry_id']]['ee_url'] = ee()->general_helper->cpURL('publish', 'edit', array('entry_id' => $listing_entry['entry_id']));
                    }
                }
            }

            // We're looping through Structure's index, so anything here is clearly in the Structure index.
            $vals['orphaned_entries'][$structure_entry['entry_id']]['structure'] = 1;

            // If the entry exists in all 3 locations, remove it from our array as we only want the faulty entries.
            if ($eeExists && $spExists) {
                unset($vals['orphaned_entries'][$structure_entry['entry_id']]);
            }
        }

        // Find out if there are any entries in Site Pages that Structure has no knowledge of (as either page entries or listing entries).
        $structureOrphans = count($site_pages_remaining);

        if ($structureOrphans > 0) {
            // Loop through the entries site_pages has that Structure does not.
            foreach ($site_pages_remaining as $entry_id => $url_title) {
                $eeExists = false;
                $isListing = 0;

                // Check if this entry actually exists in the database.
                $ee_data_query = ee()->db->get_where('channel_titles', array('entry_id' => $entry_id, 'site_id' => $this->site_id), 1);
                $ee_exists = $ee_data_query->num_rows();
                $ee_channel_id = 0;

                // If it exists in the DB, flag it so we know an actual entry exists for this orphaned Structure index.
                if (!empty($ee_exists) && $ee_exists == 1) {
                    $eeExists = true;
                    $ee_data = $ee_data_query->row_array();
                    $ee_channel_id = $ee_data['channel_id'];
                }

                $listing_data_query = ee()->db->get_where('structure_listings', array('entry_id' => $entry_id, 'site_id' => $this->site_id), 1);
                $listing_exists = $listing_data_query->num_rows();

                // If it exists in the DB, flag it so we know an actual entry exists for this orphaned Structure index.
                if (!empty($listing_exists) && $listing_exists == 1) {
                    $isListing = true;
                } elseif (!empty($ee_channel_id)) {
                    $structureListingOrphans++;

                    // Find out if the channel this entry is in is managed by structure.
                    $listing_channel_data_query = ee()->db->get_where('structure_channels', array('channel_id' => $ee_channel_id, 'site_id' => $this->site_id), 1);
                    $listing_channel_exists = $listing_channel_data_query->num_rows();

                    if (!empty($listing_exists) && $listing_exists == 1) {
                        $isListing = true;
                    }
                }

                $vals['orphaned_entries'][$entry_id]['ee'] = ($eeExists ? 1 : 0);
                $vals['orphaned_entries'][$entry_id]['site_pages'] = 1;
                $vals['orphaned_entries'][$entry_id]['title'] = (!empty($ee_data['title']) ? $ee_data['title'] : 'Missing');
                $vals['orphaned_entries'][$entry_id]['url_title'] = (!empty($url_title) ? $url_title : '--');
                $vals['orphaned_entries'][$entry_id]['is_listing'] = ($isListing ? 1 : 0); // If it's not in Structure, we can't know this.
                $vals['orphaned_entries'][$entry_id]['structure'] = 0;
                $vals['orphaned_entries'][$entry_id]['ee_url'] = ee()->general_helper->cpURL('publish', 'edit', array('entry_id' => $entry_id));

                $vals['validation_action_enabled'] = true;
            }
        }

        $vals['total_site_pages_entries'] = $totalSitePagesEntries;
        $vals['total_structure_entries'] = $totalStructureEntries;
        $vals['ee_orphans'] = $eeOrphans;
        $vals['site_pages_orphans'] = $sitePagesOrphans;
        $vals['site_pages_listing_orphans'] = $sitePagesListingOrphans;
        $vals['structure_orphans'] = $structureOrphans;
        $vals['structure_listing_orphans'] = $structureListingOrphans;

        // Duplicate Right Values
        $sql = "SELECT rgt,
                    COUNT(rgt) AS duplicates
                FROM exp_structure
                WHERE site_id = $this->site_id
                GROUP BY rgt
                    HAVING ( COUNT(rgt) > 1 )";

        $query = ee()->db->query($sql);
        $vals['duplicate_rights'] = $query->num_rows();

        // Duplicate Left Values
        $sql = "SELECT lft,
                    COUNT(rgt) AS duplicates
                FROM exp_structure
                WHERE site_id = $this->site_id
                GROUP BY lft
                    HAVING ( COUNT(lft) > 1 )";

        $query = ee()->db->query($sql);
        $vals['duplicate_lefts'] = $query->num_rows();

        // Make this part of our validation routine - compares site_pages URIs against a newly generated list to find mismatches/missing.

        // $site_pages = $this->get_site_pages();
        // $generated_site_pages = $this->generate_site_pages_array();

        // $ee_data_query = ee()->db->get_where('channel_titles', array('site_id'=>$this->site_id));
        // $num_entries = $ee_data_query->num_rows();

        // $ee_data = $ee_data_query->result_array();

        // foreach($ee_data as $entry) {
        //  $entry_channels[$entry['entry_id']] = $entry['channel_id'];
        // }

        // $sitePagesCount = count($site_pages['uris']);
        // $generatedCount = count($generated_site_pages['uris']);

        // echo '<style>html, body, td { font-size:12px; font-family:Courier; }</style>', "\n";
        // echo 'Entry Count: ', $num_entries, '<br />';
        // echo 'Existing Count: ', $sitePagesCount, '<br />';
        // echo 'Generated Count: ', $generatedCount, '<br /><br />';

        // $missingChannels = array();

        // foreach($generated_site_pages['uris'] as $entry_id => $uri) {
        //  echo $entry_id, ':<br />', "\n";
        //  echo "G: '", $uri, "'<br />", "\n";

        //  if(!empty($site_pages['uris'][$entry_id])) {
        //      echo "S: '", $site_pages['uris'][$entry_id], "'";

        //      if($uri != $site_pages['uris'][$entry_id]) echo ' MISMATCH';

        //  } else {
        //      echo 'S: -- MISSING';
        //      $missingChannels[$entry_channels[$entry_id]] = 1;
        //  }

        //  echo ' C: ', $entry_channels[$entry_id];

        //  echo '<br /><hr>', "\n\n";
        // }

        // echo '<h2>Missing Channels</h2><pre>';
        // var_dump($missingChannels);
        // exit;

        return $vals;
    }

    /**
     * Clean up invalid Structure data
     **/
    public function cleanup($mode = 'site_pages')
    {
        // add structure nav history before cleaning up
        // add_structure_nav_revision($this->site_id, 'Pre cleanup');

        // Check the `mode` to see if we're keeping entries from Structure or the site_pages array.
        switch ($mode) {
            // If mode is `site_pages` then we want to remove the Structure entries.
            case 'site_pages':
                // Remove extraneous entries in exp_structure
                $site_pages = $this->get_site_pages(true);

                // If there are NO entries in the site_pages array, delete everything in Structure and Structure Listings.
                if (empty($site_pages['uris'])) {
                    // Delete Structure entries
                    ee()->db->query("DELETE FROM exp_structure WHERE site_id = $this->site_id");

                    // Delete Listing entries
                    ee()->db->query("DELETE FROM exp_structure_listings WHERE site_id = $this->site_id");
                } else {
                    $keys = array_keys($site_pages['uris']);
                    $entry_ids = implode(",", $keys);

                    // Delete Structure entries
                    ee()->db->query("DELETE FROM exp_structure WHERE site_id = $this->site_id AND entry_id NOT IN ($entry_ids)");

                    // Delete Listing entries
                    ee()->db->query("DELETE FROM exp_structure_listings WHERE site_id = $this->site_id AND entry_id NOT IN ($entry_ids)");

                    // @TODO WIP
                    // // Set the starting left value (as 1 is for the root node)
                    // $lft = 2;

                    // // Loop through the site_pages array and re-insert any entries into Structure that are missing.
                    // foreach ($site_pages['uris'] as $entry_id => $uri) {
                    //     $ee_data_query = ee()->db->get_where('channel_titles', array('entry_id' => $entry_id, 'site_id' => $this->site_id), 1);
                    //     $ee_exists = $ee_data_query->num_rows();
                    //     $ee_channel_id = 0;

                    //     // If it exists in the DB, flag it so we know an actual entry exists for this orphaned Structure index.
                    //     if (!empty($ee_exists) && $ee_exists == 1) {
                    //         $ee_data = $ee_data_query->row_array();
                    //         $ee_channel_id = $ee_data['channel_id'];
                    //     }

                    //     $rowData = array(
                    //         'site_id' => $this->site_id,
                    //         'entry_id' => $entry_id,
                    //         'parent_id' => '0',
                    //         'channel_id' => $ee_channel_id,
                    //         'listing_cid' => '0',
                    //         'lft' => $lft,
                    //         'rgt' => $lft+1,
                    //         'dead' => '',
                    //         'structure_url_title' => $uri,
                    //         'template_id' => (!empty($site_pages['templates'][$entry_id]) ? $site_pages['templates'][$entry_id] : 0),
                    //         'updated' => date('Y-m-d H:i:s')
                    //     );

                    //     ee()->db->insert('structure', $rowData);

                    //     $lft++;

                    //     $data = array('site_id' => '0', 'entry_id' => '0', 'parent_id' => '0', 'channel_id' => '0', 'listing_cid' => '0', 'lft' => '1', 'rgt' => '2', 'dead' => 'root', 'updated' => date('Y-m-d H:i:s'));
                    //     $sql = ee()->db->insert_string('structure', $data);
                    // }
                }

                // Adjust the root node's right value
                $sql = "SELECT MAX(rgt) AS max_right FROM exp_structure WHERE site_id != 0";
                $query = ee()->db->query($sql);
                $max_right = $query->row('max_right') + 1;

                $sql = "UPDATE exp_structure SET rgt = $max_right WHERE site_id = 0";
                ee()->db->query($sql);

                break;

                // If mode is `structure` then we want to remove the `site_pages` entries.
            case 'structure':
                $generated_site_pages = $this->generate_site_pages_array();

                // echo '<table width="100%"><tr><td valign="top"><pre>';
                // $site_pages = $this->get_site_pages(true);
                // var_dump($site_pages);
                // echo '</pre></td><td valign="top"><pre>';
                // var_dump($generated_site_pages);
                // echo '</pre></td></tr></table>';
                // exit;

                $this->set_site_pages($this->site_id, $generated_site_pages);

                break;
        }

        // exit;

        // add structure nav history after cleaning up
        add_structure_nav_revision($this->site_id, 'Post cleanup');

        return true;
    }

    public function generate_site_pages_array()
    {
        $debug = false;
        if (!empty($_GET['debug'])) {
            $debug = true;
        }

        ee()->db->select('structure.entry_id, structure.structure_url_title, structure.parent_id, structure.channel_id, structure.listing_cid, structure.template_id');
        ee()->db->from('structure');
        ee()->db->where('structure.site_id', $this->site_id);
        ee()->db->where('channel_titles.site_id', $this->site_id);
        ee()->db->join('channel_titles', 'channel_titles.entry_id = structure.entry_id');
        $structure_entries = ee()->db->get()->result_array();

        $site_pages = $this->get_site_pages(true);
        $site_pages['uris'] = array();

        foreach ($structure_entries as $structure_entry) {
            $entry_id = $structure_entry['entry_id'];

            // This comes from the "integrity_data" column which is originally from the `site_pages`.
            // We have to use it here because if we just pulled the `url_title` from
            $structure_url_title = $structure_entry['structure_url_title'];

            if ($debug) {
                echo '<strong>', $entry_id, ' ', $structure_url_title, '</strong><br />';
            }

            if (!empty($structure_entry['parent_id']) && $structure_entry['parent_id'] != $entry_id) {
                if ($debug) {
                    echo 'P: ', $structure_entry['parent_id'], '<br />';
                }

                // Check if we already have a record for the parent_id
                if (!empty($site_pages['uris'][$structure_entry['parent_id']])) {
                    $structure_url_title = $site_pages['uris'][$structure_entry['parent_id']] . $structure_url_title;
                    if ($debug) {
                        echo 'Existing Parent: ', $site_pages['uris'][$structure_entry['parent_id']], '<br />';
                    }
                } else {
                    $parent_structure_url_title = $this->retrieve_structure_url_title($structure_entry['parent_id']);
                    $structure_url_title = $parent_structure_url_title . '/' . $structure_url_title;
                    if ($debug) {
                        echo 'Gen Parent: ', $parent_structure_url_title, '<br />';
                    }
                }
            }

            $structure_url_title = strtolower($structure_url_title);
            if ($debug) {
                echo 'Final Title: ', $structure_url_title, '<br />';
            }

            if (empty($structure_url_title)) {
                $structure_url_title = '/';
            } else {
                if (substr($structure_url_title, 0, 1) != '/') {
                    $structure_url_title = '/' . $structure_url_title;
                }

                $structure_url_title .= '/';
            }

            $site_pages['uris'][$entry_id] = $structure_url_title;

            // Save the template_id for this entry if it doesn't have one already.
            if (empty($site_pages['templates'][$entry_id])) {
                $site_pages['templates'][$entry_id] = $structure_entry['template_id'];
            }

            // Check if this entry has an attached listing channel and if so, build out all the entry URLs for that channel.
            if (!empty($structure_entry['listing_cid'])) {
                $listing_entries = ee()->db->get_where('channel_titles', array('channel_id' => $structure_entry['listing_cid']))->result_array();

                foreach ($listing_entries as $listing_entry) {
                    $listing_entry_id = $listing_entry['entry_id'];
                    $listing_structure_url_title = $listing_entry['url_title'];

                    // If the entry that controls this listing entry has a `url_title` other than `/`, prepend it to this listing's `url_title`.
                    if (!empty($site_pages['uris'][$entry_id]) && $site_pages['uris'][$entry_id] != '/') {
                        $listing_structure_url_title = $site_pages['uris'][$entry_id] . $listing_structure_url_title;
                    }

                    $listing_structure_url_title = strtolower($listing_structure_url_title);

                    $site_pages['uris'][$listing_entry_id] = (empty($listing_structure_url_title) ? '/' : $listing_structure_url_title . '/');

                    // Save the template_id from the listing parent for this listing entry if it doesn't have one already.
                    if (empty($site_pages['templates'][$listing_entry_id])) {
                        $site_pages['templates'][$listing_entry_id] = $structure_entry['template_id'];
                    }
                }
            }
            if ($debug) {
                echo '<br />';
            }
        }
        if ($debug) {
            die('END generate_site_pages_array');
        }

        return $site_pages;
    }

    public function retrieve_structure_url_title($entry_id)
    {
        ee()->db->select('structure.structure_url_title, structure.parent_id');
        ee()->db->from('structure');
        ee()->db->where('structure.entry_id', $entry_id);
        ee()->db->where('structure.site_id', $this->site_id);
        ee()->db->where('channel_titles.site_id', $this->site_id);
        ee()->db->join('channel_titles', 'channel_titles.entry_id = structure.entry_id');
        ee()->db->limit(1);
        $entry_data = ee()->db->get()->row_array();

        if (!empty($entry_data)) {
            $structure_url_title = $entry_data['structure_url_title'];
            if (!empty($entry_data['parent_id'])) {
                $parent_structure_url_title = $this->retrieve_structure_url_title($entry_data['parent_id']);
                $structure_url_title = $parent_structure_url_title . '/' . $structure_url_title;
            }

            return $structure_url_title;
        }
    }

    // DEPRECATED
    public function restore_site_pages_from_structure()
    {
        $site_pages = $this->get_site_pages(true);
        $uris = $site_pages['uris'];
        $templates = $site_pages['templates'];
        $template_defaults = $this->get_structure_channels();

        foreach ($uris as $key => $row) {
            if (! array_key_exists($key, $templates)) {
                $sql = "SELECT channel_id FROM exp_channel_titles WHERE site_id = '$this->site_id' AND entry_id = '$key'";
                $query = ee()->db->query($sql);
                $channel_id = $query->row()->channel_id;

                $templates[$key] = $template_defaults[$channel_id]['template_id'];
            }
        }

        $new_site_pages_array = array();
        $new_site_pages_array[$this->site_id]['uris'] = $uris;
        $new_site_pages_array[$this->site_id]['templates'] = $templates;
        $new_site_pages_array[$this->site_id]['url'] = ee()->functions->fetch_site_index(1, 0);
        $new_site_pages_array_insert = base64_encode(serialize($new_site_pages_array));

        $data = array('site_pages' => $new_site_pages_array_insert);
        ee()->db->where('site_id', $this->site_id);
        ee()->db->update('sites', $data);
    }

    // DEPRECATED
    public function restore_site_pages_templates()
    {
        $site_pages = $this->get_site_pages(true);
        $uris = $site_pages['uris'];
        $templates = $site_pages['templates'];
        $template_defaults = $this->get_structure_channels();

        foreach ($uris as $key => $row) {
            if (! array_key_exists($key, $templates)) {
                $sql = "SELECT channel_id FROM exp_channel_titles WHERE site_id = '$this->site_id' AND entry_id = '$key'";
                $query = ee()->db->query($sql);
                $channel_id = $query->row()->channel_id;

                $templates[$key] = $template_defaults[$channel_id]['template_id'];
            }
        }

        $new_site_pages_array = array();
        $new_site_pages_array[$this->site_id]['uris'] = $uris;
        $new_site_pages_array[$this->site_id]['templates'] = $templates;
        $new_site_pages_array[$this->site_id]['url'] = ee()->functions->fetch_site_index(1, 0);
        $new_site_pages_array_insert = base64_encode(serialize($new_site_pages_array));

        $data = array('site_pages' => $new_site_pages_array_insert);
        ee()->db->where('site_id', $this->site_id);
        ee()->db->update('sites', $data);
    }

    public function update_root_node()
    {
        // please note.. thsi function doesn't need nav history in it since every
        // function that calls it has it.. or the ones that call the ones that
        // have this have it.

        $sql = "SELECT MAX(rgt) AS max_right FROM exp_structure where site_id != 0";
        $query = ee()->db->query($sql);
        $max_right = $query->row('max_right') + 1;

        $sql = "UPDATE exp_structure SET rgt = $max_right WHERE site_id = 0";
        ee()->db->query($sql);
    }

    public function get_parent_uri_depth($uri)
    {
        if ($uri === null) {
            return 0;
        }

        $uri = trim($uri, '/');
        $parent_uri_array = explode('/', $uri);

        return count($parent_uri_array);
    }

    public function get_uri()
    {
        $settings = $this->get_settings();
        $trailing_slash = isset($settings['add_trailing_slash']) && $settings['add_trailing_slash'] === 'y' ? '/' : null;

        $uri = preg_replace("/(\/P\d*)/", '', Structure_Helper::remove_double_slashes('/' . ee()->uri->uri_string() . $trailing_slash));

        if ($uri == '') {
            $uri = '/'; # e.g. pagination segment off homepage
        }

        return $uri;
    }

    public function get_site_id()
    {
        $site_id = is_numeric(ee()->config->item('site_id')) ? ee()->config->item('site_id') : 1;

        return $site_id;
    }

    public function theme_url()
    {
        if (! isset($this->cache['theme_url'])) {
            $theme_folder_url = defined('URL_THEMES') ? URL_THEMES : ee()->config->slash_item('theme_folder_url') . 'third_party/';
            $this->cache['theme_url'] = $theme_folder_url . 'structure/';
        }

        return $this->cache['theme_url'];
    }

    public function user_access($perm, $settings = array())
    {
        $site_id = ee()->config->item('site_id');
        $group_id = ee()->session->userdata['group_id'];

        // super admins always have access
        if ($group_id == 1) {
            if ($perm == 'perm_delete' || $perm == 'perm_reorder') {
                return 'all';
            }

            return true;
        }

        $admin_perm = 'perm_admin_structure_' . $group_id;
        $this_perm = $perm . '_' . $group_id;

        if ($settings !== array()) {
            if (isset($settings[$this_perm])) {
                return $settings[$this_perm] == 'y' ? true : $settings[$this_perm];
            }

            return false;
        }

        // settings were not passed we have to go to the DB for the check
        $result = ee()->db->select('var')
            ->from('structure_settings')
            ->where('var', $admin_perm)
            ->or_where('var', $this_perm);

        if ($result->num_rows() > 0) {
            if ($perm == 'perm_delete' || $perm == 'perm_reorder') {
                return 'all';
            }

            return true;
        }

        return false;
    }
}

class structure_leaf
{
    public $row;
    public $parent;
    public $children = array();

    /**
     * Leaf constructor
     * @param object $row
     * @param leaf $parent
     */
    public function __construct($row, $parent = null)
    {
        $this->row = $row;
        $this->parent = $parent;
    }

    /**
     * Yep, you know how to make children right?
     * @param leaf $leaf
     */
    public function add_child($leaf)
    {
        $leaf->parent = $this;
        if (! in_array($leaf, $this->children)) {
            $this->children[] = $leaf;
        }
    }

    /**
     * On the fly depth caculation
     */
    public function depth()
    {
        if (is_null($this->parent)) {
            return 0;
        } else {
            return $this->parent->depth() + 1;
        }
    }

    /**
     * Add the 'depth' key to the row entry
     * for this and all of it's children
     */
    public function add_row_depth($depth = 0)
    {
        $this->row['depth'] = $depth;
        foreach ($this->children as $child) {
            $child->add_row_depth($depth + 1);
        }
    }

    /**
     * Prune all leaves past the specified depth
     * @param integer $depth 0 = kill children, 1 = kill grandchildren etc...
     */
    public function prune_children($depth = 0)
    {
        if ($depth <= 0) {
            $this->children = array();
        } else {
            foreach ($this->children as $child) {
                $child->prune_children($depth - 1);
            }
        }
    }

    /**
     * Prune tree based on provided params
     * @param string key Key of the row data
     * @param array $values List of values
     * @param boolean $exclude Exclude = FALSE and non matching items are deleted, Exclude = TRUE and matching items are deleted
     */
    public function selective_prune($key, $values, $exclude = false)
    {
        foreach ($this->children as $id => $child) {
            if ($is_val = $child->is_of_value($key, $values, $exclude)) {
                unset($this->children[$id]);

                continue;
            }
            if ($exclude || !$is_val) {
                $child->selective_prune($key, $values, $exclude);
            }
        }
    }

    /**
     * Prune tree based on provided params
     * @param string key Key of the row data
     * @param array $values List of values
     * @param boolean $exclude Exclude = FALSE and non matching items are deleted, Exclude = TRUE and matching items are deleted
     */
    public function selective_prune_alt($key, $values, $exclude = false)
    {
        foreach ($this->children as $id => $child) {
            if ($is_val = $child->is_of_value($key, $values, $exclude)) {
                unset($this->children[$id]);

                continue;
            }
        }
    }

    /**
     * Does this leaf match the provided values
     * @param string key Key of the row data
     * @param array $values List of values
     * @param boolean $exclude Exclude = FALSE non-matching items are deleted, Exclude = TRUE and matching items are deleted
     */
    public function is_of_value($key, $values, $exclude = false)
    {
        $is_of_value = in_array($this->row[$key], $values);
        // echo $this->row['title'].' of '.$key.' '.$this->row[$key].'<br>';
        return ($is_of_value && $exclude) || (! $is_of_value && ! $exclude);
    }

    /**
     * Find the leaf with the specified row data
     * @param string $key Key of the row data
     * @param seting $data Data to match to row[$key]
     */
    public function find_ancestor($key, $data)
    {
        if (array_key_exists($key, $this->row) && $this->row[$key] == $data) {
            return $this;
        }

        foreach ($this->children as $child) {
            $found = $child->find_ancestor($key, $data);
            if ($found !== false) {
                return $found;
            }
        }

        return false;
    }

    /**
     * Determins if the provided leaf is in this this branch
     * @param leaf $leaf Possible child leaf
     */
    public function has_ancestor($leaf, $compare_on = 'entry_id')
    {
        if (! array_key_exists($compare_on, $leaf->row)) {
            return false;
        }

        if (array_key_exists($compare_on, $this->row) && $leaf->row[$compare_on] == $this->row[$compare_on]) {
            return true;
        }

        foreach ($this->children as $child) {
            $found = $child->has_ancestor($leaf);
            if ($found) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cut this branch off at the specified depth
     * Note: will change the calculated depth for all items which remain on this branch
     * @param integer $height 0 = kill parent, 1 = kill grandparent etc..
     */
    public function prune_ancestors($height = 0)
    {
        if ($height <= 0) {
            $this->parent = null;
        } elseif (! is_null($this->parent)) {
            $this->parent->prune_ancestors($height - 1);
        }
    }

    /**
     * Cut off all leaves that are not children or parents of this item
     * This includes siblings, cousins etc
     */
    public function purify_bloodline()
    {
        $this->prune_nephews();

        if (! is_null($this->parent)) {
            $this->parent->purify_bloodline();
        }
    }

    /**
     * Remove all niece/nephew leaves
     */
    public function prune_nephews()
    {
        if (! is_null($this->parent)) {
            foreach ($this->parent->children as $brother) {
                if ($brother !== $this) {
                    $brother->prune_children();
                }
            }
        }
    }

    /**
     * Get all the rows as a results array
     */
    public function get_results()
    {
        $results = array();
        foreach ($this->children as $child) {
            $results[] = $child->row;
            $results = array_merge($results, $child->get_results($results));
        }

        return $results;
    }

    /**
     * Text printout of the tree
     * @param string $inset
     */
    public function print_branch($inset = '')
    {
        echo '(' . $this->row['entry_id'] . ') ' . $inset . $this->row['title'] . "\n";
        $inset .= ' ';
        foreach ($this->children as $child) {
            $child->print_branch($inset);
        }
    }

    /**
     * HTML nexted ul of the tree
     */
    public function list_branch($inset = '')
    {
        if ($inset == '') {
            echo '<ul>';
        }
        echo '(' . $this->row['entry_id'] . ') ' . $this->row['title'];
        if (count($this->children)) {
            echo "<ul>\n";
            $inset .= ' ';
            foreach ($this->children as $child) {
                echo $inset . '<li>';
                $child->list_branch($inset);
                echo "</li>\n";
            }
            echo substr($inset, 1) . "</ul>\n";
        }
        if ($inset == '') {
            echo "\n</ul>\n";
        }
    }

    /**
     * Converts the nested set results into an tree
     * @param array $results
     */
    public static function build_from_results($results)
    {
        // !assumes first row is root
        $row = array_shift($results);
        $tree = new structure_leaf($row);

        $leaf = $tree;
        $lft = $leaf->row['lft'];
        $rgt = $leaf->row['rgt'];

        $parent_ids = array($row['entry_id']);

        foreach ($results as $row) {
            $new = new structure_leaf($row);
            if (!in_array($row['parent_id'], $parent_ids)) {
                continue;
            }
            $parent_ids[] = $row['entry_id'];

            if ($row['lft'] < $rgt) {
                $leaf->add_child($new);
            } else {
                while (! is_null($leaf->parent) && $row['lft'] > $leaf->row['rgt']) {
                    $leaf = $leaf->parent;
                }

                $leaf->add_child($new);
            }

            if ($row['rgt'] - $row['lft'] > 1) {
                // only change leaf if new leaf can hold sub items
                $leaf = $new;
                $lft = $leaf->row['lft'];
                $rgt = $leaf->row['rgt'];
            }
        }

        return $tree;
    }
}
