<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Design Model
 */
class Design_model extends CI_Model
{
    public function fetch_templates($group_id = array())
    {
        $this->db->select(array('t.template_id', 't.group_id', 't.template_name', 't.template_type', 't.cache', 't.refresh', 't.no_auth_bounce', 't.enable_http_auth', 'tr.route', 'tr.route_required', 't.allow_php', 't.php_parse_location', 't.hits', 'tg.group_name', 't.protect_javascript'));
        $this->db->from('templates AS t');
        $this->db->join('template_groups AS tg', 'tg.group_id = t.group_id');
        $this->db->join('template_routes AS tr', 'tr.template_id = t.template_id', 'left');
        $this->db->where('t.site_id', $this->config->item('site_id'));
        $this->db->order_by('t.group_id, t.template_name', 'ASC');

        if (! empty($group_id)) {
            $this->db->where_in('t.group_id', $group_id);
        }

        $keywords = trim($this->input->post('template_keywords'));

        // add in search terms if necessary
        if ($keywords !== false and $keywords != '') {
            // note that search helper sanitize_search_terms() is intentionally not used here
            // since users may want to search for tags, javascript etc.  Terms are escaped
            // before used in queries, and are converted to entities for display
            $terms = array();

            if (preg_match_all("/\-*\"(.*?)\"/", $keywords, $matches)) {
                for ($m = 0; $m < sizeof($matches['1']); $m++) {
                    $terms[] = trim(str_replace('"', '', $matches['0'][$m]));
                    $keywords = str_replace($matches['0'][$m], '', $keywords);
                }
            }

            if (trim($keywords) != '') {
                $terms = array_merge($terms, preg_split("/\s+/", trim($keywords)));
            }

            rsort($terms);
            $not_and = (sizeof($terms) > 2) ? ') AND (' : 'AND';
            $criteria = 'AND';

            $mysql_function = (substr($terms['0'], 0, 1) == '-') ? 'NOT LIKE' : 'LIKE';
            $search_term = (substr($terms['0'], 0, 1) == '-') ? substr($terms['0'], 1) : $terms['0'];

            // We have two parentheses in the beginning in case
            // there are any NOT LIKE's being used
            $sql = "\n (t.template_data $mysql_function '%" . $this->db->escape_like_str($search_term) . "%' ";

            for ($i = 1; $i < sizeof($terms); $i++) {
                if (trim($terms[$i]) == '') {
                    continue;
                }
                $mysql_criteria = ($mysql_function == 'NOT LIKE' or substr($terms[$i], 0, 1) == '-') ? $not_and : $criteria;
                $mysql_function = (substr($terms[$i], 0, 1) == '-') ? 'NOT LIKE' : 'LIKE';
                $search_term = (substr($terms[$i], 0, 1) == '-') ? substr($terms[$i], 1) : $terms[$i];

                $sql .= "$mysql_criteria t.template_data $mysql_function '%" . $this->db->escape_like_str($search_term) . "%' ";
            }

            $sql .= ") \n";

            $this->db->where($sql, null, false);
        }

        $vars['search_terms'] = ($keywords == '') ? false : htmlentities(implode(',', $terms));
        $vars['no_results'] = false;

        $this->load->vars($vars);

        return $this->db->get();
    }

    /**
     * 	Fetch Templates in a specified group
     */
    public function export_tmpl_group($tmpl_group = false)
    {
        $this->db->select('template_groups.group_name, templates.template_name,
						templates.template_data, templates.template_type, templates.edit_date');
        $this->db->from('templates');
        $this->db->join('template_groups', 'template_groups.group_id = templates.group_id');

        if ($tmpl_group) {
            $this->db->where('template_groups.group_id', str_replace('template_group_', '', $tmpl_group));
        }

        $this->db->where('templates.site_id', $this->config->item('site_id'));
        $query = $this->db->get();

        if ($query->num_rows() == 0) {
            return false;
        }

        return $query->result_array();
    }

    /**
     *	Template Access Restrictions
     *
     * 	@return array
     */
    public function template_access_restrictions()
    {
        $this->db->select('member_group, template_id');
        $no_access = $this->db->get('template_no_access');

        $denied_groups = array();

        foreach ($no_access->result() as $row) {
            $denied_groups[$row->template_id][$row->member_group] = true;
        }

        return $denied_groups;
    }
}

// EOF
