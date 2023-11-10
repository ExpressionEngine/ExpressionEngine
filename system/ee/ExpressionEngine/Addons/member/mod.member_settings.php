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
 * Member Management Settings
 */
class Member_settings extends Member
{
    public $member;

    /** ----------------------------------------
    /**  Member Profile - Menu
    /** ----------------------------------------*/
    public function profile_menu()
    {
        $menu = $this->_load_element('menu');

        if (ee()->config->item('allow_member_localization') == 'n' and ! ee('Permission')->isSuperAdmin()) {
            $menu = $this->_deny_if('allow_localization', $menu);
        } else {
            $menu = $this->_allow_if('allow_localization', $menu);
        }

        if (ee()->config->item('enable_photos') == 'y') {
            $menu = $this->_allow_if('enable_photos', $menu);
        } else {
            $menu = $this->_deny_if('enable_photos', $menu);
        }

        return $this->_var_swap(
            $menu,
            array(
                'path:profile' => $this->_member_path('edit_profile'),
                'path:email' => $this->_member_path('edit_email'),
                'path:username' => $this->_member_path('edit_userpass'),
                'path:localization' => $this->_member_path('edit_localization'),
                'path:subscriptions' => $this->_member_path('edit_subscriptions'),
                'path:ignore_list' => $this->_member_path('edit_ignore_list'),
                'path:notepad' => $this->_member_path('edit_notepad'),
                'include:messages_menu' => $this->pm_menu()
            )
        );
    }

    /** ----------------------------------------
    /**  Member Profile Main Page
    /** ----------------------------------------*/
    public function profile_main()
    {
        $query = ee()->db->query("SELECT email, join_date, last_visit, last_activity, last_entry_date, last_comment_date, total_forum_topics, total_forum_posts, total_entries, total_comments, last_forum_post_date FROM exp_members WHERE member_id = '" . ee()->session->userdata('member_id') . "'");

        return  $this->_var_swap(
            $this->_load_element('home_page'),
            array(
                'email' => $query->row('email'),
                'join_date' => ee()->localize->human_time($query->row('join_date')),
                'last_visit_date' => ($query->row('last_activity') == 0) ? '--' : ee()->localize->human_time($query->row('last_activity')),
                'recent_entry_date' => ($query->row('last_entry_date') == 0) ? '--' : ee()->localize->human_time($query->row('last_entry_date')),
                'recent_comment_date' => ($query->row('last_comment_date') == 0) ? '--' : ee()->localize->human_time($query->row('last_comment_date')),
                'recent_forum_post_date' => ($query->row('last_forum_post_date') == 0) ? '--' : ee()->localize->human_time($query->row('last_forum_post_date')),
                'total_topics' => $query->row('total_forum_topics'),
                'total_posts' => $query->row('total_forum_posts') + $query->row('total_forum_topics'),
                'total_replies' => $query->row('total_forum_posts'),
                'total_entries' => $query->row('total_entries'),
                'total_comments' => $query->row('total_comments')
            )
        );
    }

    /** ----------------------------------------
    /**  Member Public Profile
    /** ----------------------------------------*/
    public function public_profile()
    {
        /** ----------------------------------------
        /**  Can the user view profiles?
        /** ----------------------------------------*/
        if (! ee('Permission')->can('view_profiles')) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('mbr_not_allowed_to_view_profiles')));
        }

        // Get the member_id from the tag param if we have one, otherwise, default back to the legacy `profile_segment/[member_id]` format.
        $member_id = ee()->TMPL->fetch_param('member_id');

        if (! empty($member_id)) {
            $this->cur_id = (int) $member_id;
        }

        // No member id, no view
        if ($this->cur_id == '') {
            return ee()->output->show_user_error('general', array(ee()->lang->line('profile_not_available')));
        }

        // Default Member Data
        $not_in = array(3, 4);

        if ($this->is_admin == false or ! ee('Permission')->isSuperAdmin()) {
            $not_in[] = 2;
        }

        $member = ee('Model')->get('Member', (int) $this->cur_id)
            ->with(['PrimaryRole' => 'RoleSettings'])
            ->filter('role_id', 'NOT IN', $not_in)
            ->first();

        if (! $member) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('profile_not_available')));
        }

        // Fetch the row
        $row = array_merge(
            $member->getValues(),
            $member->PrimaryRole->getValues(),
            $member->PrimaryRole->RoleSettings->getValues()
        );

        // Use member field names
        $member_fields = ee('Model')->get('MemberField')
            ->all();

        foreach ($member_fields as $member_field) {
            $key = 'm_field_id_' . $member_field->m_field_id;
            $row[$member_field->m_field_name] = array_key_exists($key, $row) ? $row[$key] : '';
        }

        /** ----------------------------------------
        /**  Fetch the template
        /** ----------------------------------------*/

        // Fetch the template tag data
        $tagdata = trim(ee()->TMPL->tagdata);

        // If there is tag data, it's a tag pair, otherwise it's a single tag which means it's a legacy speciality template.
        $content = '';
        if (! empty($tagdata)) {
            $content = ee()->TMPL->tagdata;
        } elseif (ee('Config')->getFile()->getBoolean('legacy_member_templates')) {
            $content = $this->_load_element('public_profile');
        }

        /** ----------------------------------------
        /**  Is there an avatar?
        /** ----------------------------------------*/
        if ($row['avatar_filename'] != '') {
            $avatar_path = $member->getAvatarUrl();
            $avatar_width = $row['avatar_width'] ;
            $avatar_height = $row['avatar_height'] ;

            $content = $this->_allow_if('avatar', $content);
        } else {
            $avatar_path = '';
            $avatar_width = '';
            $avatar_height = '';

            $content = $this->_deny_if('avatar', $content);
        }

        /** ----------------------------------------
        /**  Is there a member photo?
        /** ----------------------------------------*/
        if (ee()->config->item('enable_photos') == 'y' and $row['photo_filename'] != '') {
            $photo_path = ee()->config->slash_item('photo_url') . $row['photo_filename'] ;
            $photo_width = $row['photo_width'] ;
            $photo_height = $row['photo_height'] ;

            $content = $this->_allow_if('photo', $content);
            $content = $this->_deny_if('not_photo', $content);
        } else {
            $photo_path = '';
            $photo_width = '';
            $photo_height = '';

            $content = $this->_deny_if('photo', $content);
            $content = $this->_allow_if('not_photo', $content);
        }

        /** ----------------------------------------
        /**  Forum specific stuff
        /** ----------------------------------------*/
        $rank_class = 'rankMember';
        $rank_title = '';
        $rank_stars = '';
        $stars = '';

        if ($this->in_forum == true) {
            $rank_query = ee()->db->query("SELECT rank_title, rank_min_posts, rank_stars FROM exp_forum_ranks ORDER BY rank_min_posts");
            $mod_query = ee()->db->query("SELECT mod_member_id, mod_group_id FROM exp_forum_moderators");

            $total_posts = ($row['total_forum_topics'] + $row['total_forum_posts']);

            /** ----------------------------------------
            /**  Assign the rank stars
            /** ----------------------------------------*/
            if (preg_match("/{if\s+rank_stars\}(.+?){\/if\}/i", $content, $matches)) {
                $rank_stars = $matches['1'];
                $content = str_replace($matches['0'], '{rank_stars}', $content);
            }

            if ($rank_stars != '' and $rank_query->num_rows() > 0) {
                $num_stars = null;
                $rank_title = '';

                $i = 1;
                foreach ($rank_query->result_array() as $rank) {
                    if ($num_stars == null) {
                        $num_stars = $rank['rank_stars'];
                        $rank_title = $rank['rank_title'];
                    }

                    if ($rank['rank_min_posts'] >= $total_posts) {
                        $stars = str_repeat($rank_stars, $num_stars);

                        break;
                    } else {
                        $num_stars = $rank['rank_stars'];
                        $rank_title = $rank['rank_title'];
                    }

                    if ($i++ == $rank_query->num_rows) {
                        $stars = str_repeat($rank_stars, $num_stars);

                        break;
                    }
                }
            }

            /** ----------------------------------------
            /**  Assign the member rank
            /** ----------------------------------------*/

            // Is the user an admin?

            $admin_query = ee()->db->query('SELECT admin_group_id, admin_member_id FROM exp_forum_administrators');

            $is_admin = false;
            if ($admin_query->num_rows() > 0) {
                foreach ($admin_query->result_array() as $admin) {
                    if ($admin['admin_member_id'] != 0) {
                        if ($admin['admin_member_id'] == $this->cur_id) {
                            $is_admin = true;

                            break;
                        }
                    } elseif ($admin['admin_group_id'] != 0) {
                        if ($admin['admin_group_id'] == $row['group_id']) {
                            $is_admin = true;

                            break;
                        }
                    }
                }
            }

            if ($row['group_id'] == 1 or $is_admin == true) {
                $rankclass = 'rankAdmin';
                $rank_class = 'rankAdmin';
                $rank_title = ee()->lang->line('administrator');
            } else {
                if ($mod_query->num_rows() > 0) {
                    foreach ($mod_query->result_array() as $mod) {
                        if ($mod['mod_member_id'] == $this->cur_id or $mod['mod_group_id'] == $row['group_id']) {
                            $rank_class = 'rankModerator';
                            $rank_title = ee()->lang->line('moderator');

                            break;
                        }
                    }
                }
            }
        }

        /** ----------------------------------------
        /**  Parse variables
        /** ----------------------------------------*/
        if ($this->in_forum == true) {
            $search_path = $this->forum_path . 'member_search/' . $this->cur_id . '/';
        } else {
            $search_path = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER . 'ACT=' . ee()->functions->fetch_action_id('Search', 'do_search') . '&amp;mbr=' . urlencode($row['member_id']);
        }

        $ignore_form = array('hidden_fields' => array('toggle[]' => '', 'name' => '', 'daction' => ''),
            'action' => $this->_member_path('update_ignore_list'),
            'id' => 'target'
        );

        if (! in_array($row['member_id'], ee()->session->userdata['ignore_list'])) {
            $ignore_button = "<div><a href='" . $this->_member_path('edit_ignore_list') . "' " .
                                "onclick='dynamic_action(\"add\");list_addition(\"" . $row['screen_name'] . "\");return false;'>" .
                                "{lang:ignore_member}</a></div></form>";
        } else {
            $ignore_button = "<div><a href='" . $this->_member_path('edit_ignore_list') . "' " .
                                "onclick='dynamic_action(\"delete\");list_addition(\"" . $row['member_id'] . "\", \"toggle[]\");return false;'>" .
                                "{lang:unignore_member}</a></div></form>";
        }

        $timezone = $row['timezone'];

        if ($timezone == '') {
            $timezone = (ee()->config->item('default_site_timezone')
                 && ee()->config->item('default_site_timezone') != '') ?
                 ee()->config->item('default_site_timezone') : 'UTC';
        }

        $content = $this->_var_swap(
            $content,
            array(
                'email_console' => "onclick=\"window.open('" . $this->_member_path('email_console/' . $this->cur_id) . "', '_blank', 'width=650,height=600,scrollbars=yes,resizable=yes,status=yes,screenx=5,screeny=5');\"",
                'send_private_message' => $this->_member_path('messages/pm/' . $this->cur_id),
                'search_path' => $search_path,
                'path:avatar_url' => $avatar_path,
                'avatar_width' => $avatar_width,
                'avatar_height' => $avatar_height,
                'path:photo_url' => $photo_path,
                'photo_width' => $photo_width,
                'photo_height' => $photo_height,
                'rank_class' => $rank_class,
                'rank_stars' => $stars,
                'rank_title' => $rank_title,
                'ignore_link' => $this->list_js() .
                                            ee()->functions->form_declaration($ignore_form) .
                                            $ignore_button
            )
        );

        $vars = ee('Variables/Parser')->extractVariables($content);
        $this->var_single = $vars['var_single'];
        $this->var_pair = $vars['var_pair'];

        $this->var_cond = ee()->functions->assign_conditional_variables($content, '/');

        /** ----------------------------------------
        /**  Parse conditional pairs
        /** ----------------------------------------*/
        foreach ($this->var_cond as $val) {
            /** ----------------------------------------
            /**  Conditional statements
            /** ----------------------------------------*/
            $cond = ee()->functions->prep_conditional($val['0']);

            $lcond = substr($cond, 0, strpos($cond, ' '));
            $rcond = substr($cond, strpos($cond, ' '));

            if (array_key_exists($val['3'], $row)) {
                $lcond = str_replace($val['3'], "\$row['" . $val['3'] . "']", $lcond);
                $cond = $lcond . ' ' . $rcond;
                $cond = str_replace("\|", "|", $cond);

                eval("\$result = " . $cond . ";");

                if ($result) {
                    $content = preg_replace("/" . LD . $val['0'] . RD . "(.*?)" . LD . '\/if' . RD . "/s", "\\1", $content);
                } else {
                    $content = preg_replace("/" . LD . $val['0'] . RD . "(.*?)" . LD . '\/if' . RD . "/s", "", $content);
                }
            }

            /** ----------------------------------------
            /**  {if accept_email}
            /** ----------------------------------------*/
            if (preg_match("/^if\s+accept_email.*/i", $val['0'])) {
                if ($row['accept_user_email'] == 'n') {
                    $content = preg_replace("/" . LD . $val['0'] . RD . "(.+?)" . LD . '\/if' . RD . "/s", "", $content);
                } else {
                    $content = preg_replace("/" . LD . $val['0'] . RD . "(.+?)" . LD . '\/if' . RD . "/s", "\\1", $content);
                }
            }

            /** ----------------------------------------
            /**  {if can_private_message}
            /** ----------------------------------------*/
            if (stristr($val['0'], 'can_private_message')) {
                if (! $member->can('send_private_messages') or $row['accept_messages'] == 'n') {
                    $content = preg_replace("/" . LD . $val['0'] . RD . "(.+?)" . LD . '\/if' . RD . "/s", "", $content);
                } else {
                    $content = preg_replace("/" . LD . $val['0'] . RD . "(.+?)" . LD . '\/if' . RD . "/s", "\\1", $content);
                }
            }

            /** -------------------------------------
            /**  {if ignore}
            /** -------------------------------------*/
            if (stristr($val['0'], 'ignore')) {
                if ($row['member_id'] == ee()->session->userdata['member_id']) {
                    $content = $this->_deny_if('ignore', $content);
                } else {
                    $content = $this->_allow_if('ignore', $content);
                }
            }
        }
        // END CONDITIONAL PAIRS
        ee()->load->library('typography');
        ee()->typography->initialize();

        /** ----------------------------------------
        /**  Parse "single" variables
        /** ----------------------------------------*/
        foreach ($this->var_single as $key => $val) {
            /** ----------------------------------------
            /**  Format URLs
            /** ----------------------------------------*/
            // need exception
            /*


            if ($key == 'url')
            {
                if (strncmp($row['url'], 'http', 4) != 0 && strpos($row['url'], '://') === FALSE)
                {
                    $row['url'] = "http://".$row['url'] ;
                }
            }

            */

            /** ----------------------------------------
            /**  "last_visit"
            /** ----------------------------------------*/
            if (strncmp($key, 'last_visit', 10) == 0) {
                $content = $this->_var_swap_single($key, ($row['last_activity'] > 0) ? ee()->localize->format_date($val, $row['last_activity']) : '', $content);
            }

            /** ----------------------------------------
            /**  "join_date"
            /** ----------------------------------------*/
            if (strncmp($key, 'join_date', 9) == 0) {
                $content = $this->_var_swap_single($key, ($row['join_date'] > 0) ? ee()->localize->format_date($val, $row['join_date']) : '', $content);
            }

            /** ----------------------------------------
            /**  "last_entry_date"
            /** ----------------------------------------*/
            if (strncmp($key, 'last_entry_date', 15) == 0) {
                $content = $this->_var_swap_single($key, ($row['last_entry_date'] > 0) ? ee()->localize->format_date($val, $row['last_entry_date']) : '', $content);
            }

            /** ----------------------------------------
            /**  "last_forum_post_date"
            /** ----------------------------------------*/
            if (strncmp($key, 'last_forum_post_date', 20) == 0) {
                $content = $this->_var_swap_single($key, ($row['last_forum_post_date'] > 0) ? ee()->localize->format_date($val, $row['last_forum_post_date']) : '', $content);
            }

            /** ----------------------------------------
            /**  parse "recent_comment"
            /** ----------------------------------------*/
            if (strncmp($key, 'last_comment_date', 17) == 0) {
                $content = $this->_var_swap_single($key, ($row['last_comment_date'] > 0) ? ee()->localize->format_date($val, $row['last_comment_date']) : '', $content);
            }

            /** ----------------------
            /**  {name}
            /** ----------------------*/
            $name = (! $row['screen_name']) ? $row['username'] : $row['screen_name'] ;

            $name = $this->_convert_special_chars($name);

            if ($key == "name") {
                $content = $this->_var_swap_single($val, $name, $content);
            }

            /** ----------------------
            /**  {member_group}
            /** ----------------------*/
            if ($key == "member_group") {
                $content = $this->_var_swap_single($val, $member->PrimaryRole->name, $content);
            }

            if ($key == "member_role") {
                $content = $this->_var_swap_single($val, $member->PrimaryRole->name, $content);
            }

            /** ----------------------
            /**  {email}
            /** ----------------------*/
            if ($key == "email") {
                $content = $this->_var_swap_single($val, ee()->typography->encode_email($row['email']), $content);
            }

            /** ----------------------
            /**  {timezone}
            /** ----------------------*/
            if ($key == "timezone") {
                $content = $this->_var_swap_single($val, lang($timezone), $content);
            }

            /** ----------------------
            /**  {local_time}
            /** ----------------------*/
            if (strncmp($key, 'local_time', 10) == 0) {
                $content = $this->_var_swap_single(
                    $key,
                    ee()->localize->format_date($val, null, $timezone),
                    $content
                );
            }

            // Special consideration for {total_forum_replies}, and
            // {total_forum_posts} whose meanings do not match the
            // database field names
            if ($key == 'total_forum_replies') {
                $content = $this->_var_swap_single($key, $row['total_forum_posts'], $content);
            }

            if ($key == 'total_forum_posts') {
                $total_posts = $row['total_forum_topics'] + $row['total_forum_posts'];
                $content = $this->_var_swap_single($key, $total_posts, $content);
            }

            /** ----------------------------------------
            /**  parse basic fields (username, screen_name, etc.)
            /** ----------------------------------------*/

            // array_key_exists instead of isset since some columns may be NULL
            if (array_key_exists($val, $row)) {
                $content = $this->_var_swap_single($val, strip_tags($row[$val]), $content);
            }
        }

        /** -------------------------------------
        /**  Do we have custom fields to show?
        /** ------------------------------------*/
        // Grab the data for the particular member

        if ($member_fields) {
            if (! ee('Permission')->isSuperAdmin()) {
                $member_fields = $member_fields->filter(function ($field) {
                    return $field->m_field_public == 'y';
                });
            }

            $fnames = array();

            $member_field_ids = array();

            foreach ($member_fields as $member_field) {
                $fnames[$member_field->m_field_name] = array($member_field->m_field_id, $member_field->m_field_fmt, $member_field->m_field_type);
                $member_field_ids[] = $member_field->m_field_id;
                $this->member_fields[$member_field->getId()] = $member_field;
            }

            ee()->load->library('typography');
            ee()->typography->initialize();

            ee()->load->library('api');
            ee()->legacy_api->instantiate('channel_fields');

            /** ----------------------------------------
            /**  Parse conditionals for custom fields
            /** ----------------------------------------*/
            foreach ($this->var_cond as $val) {
                // Prep the conditional
                $cond = ee()->functions->prep_conditional($val['0']);

                $lcond = substr($cond, 0, strpos($cond, ' '));
                $rcond = substr($cond, strpos($cond, ' '));

                if (array_key_exists($val['3'], $fnames)) {
                    $m_field_id_name = 'm_field_id_' . $fnames[$val['3']]['0'];

                    $lcond = str_replace($val['3'], "\$row['" . $m_field_id_name . "']", $lcond);

                    $cond = $lcond . ' ' . $rcond;

                    $cond = str_replace("\|", "|", $cond);

                    eval("\$rez = " . $cond . ";");

                    if ($rez) {
                        $content = preg_replace("/" . LD . $val['0'] . RD . "(.*?)" . LD . '\/if' . RD . "/s", "\\1", $content);
                    } else {
                        $content = preg_replace("/" . LD . $val['0'] . RD . "(.*?)" . LD . '\/if' . RD . "/s", "", $content);
                    }
                }
            }
            // END CONDITIONALS

            /** ----------------------------------------
            /**  Parse single variables
            /** ----------------------------------------*/
            foreach ($this->var_single as $key => $val) {

                // Custom member fields
                $field = ee('Variables/Parser')->parseVariableProperties($key);
                $fval = $field['field_name'];

                // parse custom member fields
                if (isset($fnames[$fval])) {
                    if (array_key_exists('m_field_id_' . $fnames[$fval]['0'], $row)) {
                        ee()->TMPL->tagdata = $this->parseField(
                            $fnames[$fval][0],
                            $field,
                            $row['m_field_id_' . $fnames[$fval]['0']],
                            ee()->TMPL->tagdata,
                            $this->cur_id,
                            array(),
                            $key
                        );
                    } else {
                        ee()->TMPL->tagdata = ee()->TMPL->swap_var_single(
                            $key,
                            '',
                            ee()->TMPL->tagdata
                        );
                    }
                }
            }

            /** ----------------------------------------
            /**  Parse auto-generated "custom_fields"
            /** ----------------------------------------*/
            $field_chunk = $this->_load_element('public_custom_profile_fields');

            // Is there a chunk to parse?
            if (! $member_fields) {
                $content = str_replace("/{custom_profile_fields}/s", '', $content);
            } else {
                $str = '';
                $var_conds = ee()->functions->assign_conditional_variables($field_chunk);
                $member_field = '';

                foreach ($member_fields as $member_field) {
                    $temp = $field_chunk;
                    $field_row = $member_field->getValues();

                    // enables conditionals on these variables
                    $field_row['field_label'] = $field_row['m_field_label'];
                    $field_row['field_description'] = $field_row['m_field_description'];

                    // Custom member fields
                    $field_name = $member_field->m_field_name;

                    // We fake the template data and make it simply be the tag
                    $temp_string = LD . $field_row['m_field_name'] . RD;

                    if (array_key_exists('m_field_id_' . $field_row['m_field_id'], $row)) {
                        // Hard code date field modifier because this doesn't use real variables
                        $params = ($member_field->m_field_type == 'date') ? "%Y %m %d" : '';
                        $field = array(
                            'field_name' => $member_field->m_field_name,
                            'params' => array('format' => $params, 'modifier' => '')
                        );

                        $field_data = $this->parseField(
                            $member_field->m_field_id,
                            $field,
                            $row['m_field_id_' . $field_row['m_field_id']],
                            $temp_string,
                            $this->cur_id
                        );
                    } else {
                        $field_data = '';
                    }

                    $field_row['field_data'] = $field_data;

                    $temp = str_replace('{field_name}', $member_field->m_field_label, $temp);
                    $temp = str_replace('{field_description}', $member_field->m_field_description, $temp);
                    $temp = str_replace('{field_data}', $field_data, $temp);

                    foreach ($var_conds as $val) {
                        // Prep the conditional

                        $cond = ee()->functions->prep_conditional($val['0']);

                        $lcond = substr($cond, 0, strpos($cond, ' '));
                        $rcond = substr($cond, strpos($cond, ' '));

                        if (array_key_exists($val['3'], $field_row)) {
                            $lcond = str_replace($val['3'], "\$field_row['" . $val['3'] . "']", $lcond);
                            $cond = $lcond . ' ' . $rcond;
                            $cond = str_replace("\|", "|", $cond);

                            eval("\$result = " . $cond . ";");

                            if ($result) {
                                $temp = preg_replace("/" . LD . $val['0'] . RD . "(.*?)" . LD . '\/if' . RD . "/s", "\\1", $temp);
                            } else {
                                $temp = preg_replace("/" . LD . $val['0'] . RD . "(.*?)" . LD . '\/if' . RD . "/s", "", $temp);
                            }
                        }
                    }

                    $str .= $temp;
                }

                $content = str_replace("{custom_profile_fields}", $str, $content);
            }
        }
        // END  if ($quey->num_rows() > 0)

        /** ----------------------------------------
        /**  Clean up left over variables
        /** ----------------------------------------*/
        $content = str_replace(LD . 'custom_profile_fields' . RD, '', $content);

        return $content;
    }

    /** ----------------------------------------
    /**  Member Profile Edit Page
    /** ----------------------------------------*/
    public function edit_profile()
    {
        // Load the form helper
        ee()->load->helper('form');

        // UGH- we need these 3 to get the data js or it throws a Legacy\Facade error
        ee()->router->set_class('cp');
        ee()->load->library('cp');
        ee()->load->library('javascript');

        /** ----------------------------------------
        /**  Build the custom profile fields
        /** ----------------------------------------*/

        // Fetch the template tag data
        $tagdata = trim(ee()->TMPL->tagdata);

        // If there is tag data, it's a tag pair, otherwise it's a single tag which means it's a legacy speciality template.
        $template = '';
        if (! empty($tagdata)) {
            $template = ee()->TMPL->tagdata;
        } elseif (ee('Config')->getFile()->getBoolean('legacy_member_templates')) {
            $template = $this->_load_element('edit_profile_form');
        }

        // Find out if we have sub-tag data for our `custom_profile_fields` tag. If not, use the legacy speciality template.
        if (strpos($template, '{/custom_profile_fields}') !== false) {
            $custom_profile_fields_tag_length = strlen(LD . 'custom_profile_fields' . RD);

            // Find the starting and ending position of our subtag and calculate the difference so we can grab it.
            $custom_profile_fields_start = strpos($template, LD . 'custom_profile_fields' . RD) + $custom_profile_fields_tag_length;
            $custom_profile_fields_end = strpos($template, LD . '/custom_profile_fields' . RD);
            $custom_profile_fields_diff = $custom_profile_fields_end - $custom_profile_fields_start;

            $profile_fields_template = substr($template, $custom_profile_fields_start, $custom_profile_fields_diff);
        } else {
            $profile_fields_template = $this->_load_element('custom_profile_fields');
        }

        /** ----------------------------------------
        /**  Fetch the field definitions
        /** ----------------------------------------*/
        $r = '';

        $sql = "SELECT *  FROM exp_member_fields ";

        if (! ee('Permission')->isSuperAdmin()) {
            $sql .= " WHERE m_field_public = 'y' ";
        }

        $sql .= " ORDER BY m_field_order";

        $query = ee()->db->query($sql);

        // $result_row = $result->row_array()

        $member = ee()->session->getMember();

        if (empty($member)) {
            if (! empty($tagdata)) {
                return ee()->TMPL->no_results();
            } else {
                return ee()->output->show_user_error('general', array(ee()->lang->line('must_be_logged_in')));
            }
        }

        $result_row = $member->getValues();

        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');
        // we need to reset this, because might have already been populated by channel:entries tag
        ee()->api_channel_fields->custom_fields = array();

        if (strpos($template, '{/custom_profile_fields}') !== false) {
            if ($query->num_rows() > 0) {
                foreach ($member->getDisplay()->getFields() as $field) {
                    if (! ee('Permission')->isSuperAdmin() && $field->get('field_public') != 'y') {
                        continue;
                    }

                    $temp = $profile_fields_template;

                    /** ----------------------------------------
                    /**  Assign the data to the field
                    /** ----------------------------------------*/
                    $temp = str_replace('{field_id}', $field->getId(), $temp);

                    $required = $field->isRequired() ? "<span class='alert'>*</span>&nbsp;" : '';

                    $fieldForm = $field->getForm();

                    $temp = str_replace(
                        [
                            '{lang:profile_field}',
                            '{lang:profile_field_description}',
                            '{form:custom_profile_field}',
                            '{field_name}',
                            '{field_label}',
                            '{field_id}',
                            '{field_instructions}',
                            '{display_field}',
                            '{field_data}',
                            '{text_direction}',
                            '{maxlength}',
                            '{field_required}',
                            '{field_type}',
                        ],
                        [
                            $required . $field->getLabel(),
                            $field->get('field_description'),
                            $fieldForm,
                            $field->getName(),
                            $field->getLabel(),
                            $field->getId(),
                            $field->get('field_description'),
                            $fieldForm,
                            $result_row[$field->getName()],
                            $field->get('field_text_direction'),
                            $field->get('field_maxl'),
                            $field->isRequired(),
                            $field->getType()
                        ],
                        $temp
                    );

                    /** ----------------------------------------
                    /**  Render textarea fields
                    /** ----------------------------------------*/
                    if ($field->getTypeName() == 'textarea') {
                        $temp = str_replace('<td ', "<td valign='top' ", $temp);
                    }

                    $r .= $temp;
                }
            }
        }

        /** ----------------------------------------
        /**  Build the output data
        /** ----------------------------------------*/

        //if we used templates tags, parse fields
        if (! empty($tagdata)) {
            $template = $this->custom_profile_data(false);

            //and add some more variables
            $template = ee()->TMPL->swap_var_single(
                'timezone_menu',
                ee()->localize->timezone_menu(ee()->session->userdata('timezone'), 'timezone'),
                $template
            );
            $template = ee()->TMPL->swap_var_single(
                'language_menu',
                $this->get_language_listing(ee()->session->get_language()),
                $template
            );
        }

        if (! empty($custom_profile_fields_diff)) {
            $custom_profile_fields_start = strpos($template, LD . 'custom_profile_fields' . RD);
            $custom_profile_fields_end = strpos($template, LD . '/custom_profile_fields' . RD) + $custom_profile_fields_tag_length + 1;
            $custom_profile_fields_diff = $custom_profile_fields_end - $custom_profile_fields_start;

            $template = substr_replace($template, $r, $custom_profile_fields_start, $custom_profile_fields_diff);
        } else {
            $template = str_replace(LD . "custom_profile_fields" . RD, $r, $template);
        }

        if (strpos($template, LD . 'field:') !== false) {
            foreach ($member->getDisplay()->getFields() as $field) {
                if (ee('Permission')->isSuperAdmin() || $field->get('field_public') == 'y') {
                    $template = str_replace(LD . 'field:' . $field->get('field_name') . RD, $field->getForm(), $template);
                }
            }
        }

        //if we run EE template parser, do some things differently
        if (! empty($tagdata)) {
            ee()->load->add_package_path(PATH_ADDONS . 'channel');
            ee()->load->library('channel_form/channel_form_lib');
            ee()->channel_form_lib->datepicker = get_bool_from_string(ee()->TMPL->fetch_param('datepicker', 'y'));
            ee()->channel_form_lib->compile_js();

            $data = [];
            if (ee()->TMPL->fetch_param('form_name', '') != "") {
                $data['name'] = ee()->TMPL->fetch_param('form_name');
            }

            $data['id'] = !empty(ee()->TMPL->form_id) ? ee()->TMPL->form_id : 'cform';
            $data['class'] = (get_bool_from_string(ee()->TMPL->fetch_param('include_assets', 'n') || strpos($template, LD . 'form_assets' . RD) !== false) ? 'ee-cform ' : '');
            $data['class'] .= ee()->TMPL->form_class;

            $data['hidden_fields'] = array(
                'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ? ee()->functions->create_url(ee()->TMPL->fetch_param('return')) : ee()->functions->fetch_current_uri(),
                'ACT' => ee()->functions->fetch_action_id('Member', 'update_profile')
            );

            // check the template for file fields
            if (strpos($template, '_hidden_file') !== false) {
                $data['enctype'] = 'multi';
            }

            $return = ee()->functions->form_declaration($data) . $template . '</form>';
            //make head appear by default
            if (strpos($return, LD . 'form_assets' . RD) !== false) {
                $return = ee()->TMPL->swap_var_single('form_assets', ee()->channel_form_lib->head, $return);
            } elseif (get_bool_from_string(ee()->TMPL->fetch_param('include_assets'), 'n')) {
                // Head should only be there if the param is there
                $return .= ee()->channel_form_lib->head;
            }
            ee()->load->remove_package_path(PATH_ADDONS . 'channel');

            return $return;
        }

        return  $this->_var_swap(
            $template,
            array(
                'form_declaration' => ee()->functions->form_declaration(
                    array(
                        'action' => $this->_member_path('update_profile'),
                        'hidden_fields' => array(
                            'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ? ee()->TMPL->fetch_param('return') : '-1'
                        )
                    )
                ),
                'path:update_profile' => $this->_member_path('update_profile')
            )
        );
    }

    /**
     * Encode EE tags after form prepping or they'll be rendered in the text area.
     */
    protected function _form_prep_encoded($value)
    {
        $value = form_prep($value);

        return ee()->functions->encode_ee_tags($value, true);
    }

    /** ----------------------------------------
    /**  Profile Update
    /** ----------------------------------------*/
    public function update_profile()
    {

        /** -------------------------------------
        /**  Safety....
        /** -------------------------------------*/
        if (count($_POST) == 0) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('invalid_action')));
        }

        /** ----------------------------------------
        /**  Blocked/Allowed List Check
        /** ----------------------------------------*/
        if (ee()->blockedlist->blocked == 'y' && ee()->blockedlist->allowed == 'n') {
            return ee()->output->show_user_error('general', array(ee()->lang->line('not_authorized')));
        }

        ee()->db->select('m_field_id, m_field_label, m_field_type, m_field_name');
        if (! ee('Permission')->isSuperAdmin()) {
            ee()->db->where('m_field_public = "y"');
        }

        $query = ee()->db->get('member_fields');

        $errors = array();

        $member = ee('Model')->get('Member', ee()->session->userdata('member_id'))->first();

        if ($query->num_rows() > 0) {
            ee()->load->library(array('api', 'file_field'));
            foreach ($query->result_array() as $row) {
                $fname = 'm_field_id_' . $row['m_field_id'];
                $post = ee()->input->post($fname);

                // file field is a special case
                if ($row['m_field_type'] == 'file') {
                    if (ee()->input->post($fname . '_hidden_file') === '') {
                        $member->$fname = '';
                    }
                    // trick validation into calling the file fieldtype
                    if (isset($_FILES[$fname]['name'])) {
                        $img = ee()->file_field->validate($_FILES[$fname]['name'], $fname);

                        if (isset($img['value'])) {
                            $member->$fname = $img['value'];
                        } else {
                            $member->$fname = '';
                        }
                    }
                } elseif ($row['m_field_type'] == 'checkbox') {
                    // Handle arrays of checkboxes as a special case;
                    foreach ($row['choices'] as $property => $label) {
                        $member->$fname = in_array($property, $post) ? 'y' : 'n';
                    }
                } else {
                    if ($post !== false) {
                        $member->$fname = ee('Security/XSS')->clean($post);
                    }
                }

                // Set custom field format override if available, too
                $ft_name = 'm_field_ft_' . $row['m_field_id'];
                if (ee()->input->post($ft_name)) {
                    $member->{$ft_name} = ee('Security/XSS')->clean(ee('Request')->post($ft_name));
                }
                $dt_name = 'm_field_dt_' . $row['m_field_id'];
                if (ee()->input->post($dt_name)) {
                    $member->{$dt_name} = ee('Security/XSS')->clean(ee('Request')->post($dt_name));
                }
            }
        }

        unset($_POST['HTTP_REFERER']);

        //if this request initiated from regular EE template, we'll process some additional stuff here
        if (REQ === 'ACTION') {
            //email update
            if (ee()->input->post('email') != '' && ee()->input->post('email') != $member->email) {
                $validator = ee('Validation')->make();
                $validator->setRule('current_password', 'authenticated');

                $member->email = ee()->input->post('email');
            }

            //preferences
            $checkboxes = ['accept_admin_email', 'accept_user_email', 'notify_by_default', 'notify_of_pm', 'accept_messages', 'display_signatures', 'parse_smileys', 'site_default'];
            foreach ($checkboxes as $checkbox) {
                if (in_array(ee()->input->post($checkbox), ['y', 'n'])) {
                    $member->{$checkbox} = ee()->input->post($checkbox);
                }
            }

            //localization settings
            if (ee()->config->item('allow_member_localization') == 'y') {
                if (!empty($_POST['language'])) {
                    $language_pack_names = array_keys(ee()->lang->language_pack_names());
                    if (! in_array(ee()->input->post('language'), $language_pack_names)) {
                        return ee()->output->show_user_error('general', array(lang('invalid_action')));
                    }
                    $member->language = ee()->security->sanitize_filename($_POST['language']);
                }
                if (!empty($_POST['timezone'])) {
                    foreach (array('timezone', 'date_format', 'time_format', 'week_start', 'include_seconds') as $key) {
                        if (ee()->input->post('site_default') == 'y') {
                            $member->{$key} = null;
                        } else {
                            $member->{$key} = ee()->input->post($key);
                        }
                    }
                }
            }

            //notepad
            if (ee()->input->post('notepad') != '') {
                $member->notepad = ee()->input->post('notepad');
            }

            // username & password
            $need_validation = false;
            if (ee()->config->item('allow_username_change') == 'y' && ee()->input->post('username') != '') {
                $member->username = ee()->input->post('username');
                $need_validation = true;
            }

            if (ee()->input->post('screen_name') != '') {
                $need_validation = true;
                $member->screen_name = ee()->input->post('screen_name');
            }

            // set password, and confirmation if needed
            if (ee()->input->post('password')) {
                $need_validation = true;
                $member->password = ee()->input->post('password');
            }

            if (!isset($validator) && $need_validation) {
                $validator = ee('Validation')->make();
                $validator->setRule('current_password', 'authenticated');
            }

            if (ee()->input->post('password')) {
                $validator->setRule('password_confirm', 'matches[password]');
            }
        }

        $result = $member->validate();

        if (ee()->input->post('password')) {
            $password_confirm = $validator->validate($_POST);

            // Add password confirmation failure to main result object
            if ($password_confirm->failed()) {
                $rules = $password_confirm->getFailed();
                foreach ($rules as $field => $rule) {
                    $result->addFailed($field, $rule[0]);
                }
            }
        }

        if ($result->failed()) {
            return ee()->output->show_user_error('general', $result->renderErrors());
        }

        // if the password was set, need to hash it before saving and kill all other sessions
        if (ee()->input->post('password')) {
            $member->hashAndUpdatePassword($member->password);
        }

        $member->save();

        $return = ee()->input->get_post('RET');

        if (! empty($return)) {
            if (is_numeric($return)) {
                $return_link = ee()->functions->form_backtrack($return);
            } else {
                $return_link = $return;
            }

            // Make sure it's an actual URL.
            if (substr($return_link, 0, 4) !== 'http' && substr($return_link, 0, 1) !== '/') {
                $return_link = '/' . $return_link;
            }

            ee()->functions->redirect($return_link);
            exit;
        }

        /** -------------------------------------
        /**  Success message
        /** -------------------------------------*/

        return $this->_var_swap(
            $this->_load_element('success'),
            array(
                'lang:heading' => ee()->lang->line('profile_updated'),
                'lang:message' => ee()->lang->line('mbr_profile_has_been_updated')
            )
        );
    }

    /** ----------------------------------------
    /**  Forum Preferences
    /** ----------------------------------------*/
    public function edit_preferences()
    {
        $query = ee()->db->query("SELECT display_signatures, smart_notifications, accept_messages, parse_smileys FROM exp_members WHERE member_id = '" . ee()->session->userdata('member_id') . "'");

        $element = $this->_load_element('edit_preferences');

        // -------------------------------------------
        // 'member_edit_preferences' hook.
        //  - Allows adding of preferences to user side preferences form
        //
        if (ee()->extensions->active_hook('member_edit_preferences') === true) {
            $element = ee()->extensions->call('member_edit_preferences', $element);
        }
        //
        // -------------------------------------------

        return $this->_var_swap(
            $element,
            array(
                'form_declaration' => ee()->functions->form_declaration(
                    array('action' => $this->_member_path('update_preferences'))
                ),
                'path:update_edit_preferences' => $this->_member_path('update_preferences'),
                'state:accept_messages' => ($query->row('accept_messages') == 'y') ? " checked='checked'" : '',
                'state:display_signatures' => ($query->row('display_signatures') == 'y') ? " checked='checked'" : '',
                'state:parse_smileys' => ($query->row('parse_smileys') == 'y') ? " checked='checked'" : ''
            )
        );
    }

    /** ----------------------------------------
    /**  Update  Preferences
    /** ----------------------------------------*/
    public function update_preferences()
    {
        // This form is all checkboxes, so check a hidden form field for existence to ensure
        // this is a valid POST request, or visiting this page will blank their prefs
        if (! isset($_POST['site_id'])) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('invalid_action')));
        }

        /** -------------------------------------
        /**  Assign the query data
        /** -------------------------------------*/
        $data = array(
            'accept_messages' => (isset($_POST['accept_messages'])) ? 'y' : 'n',
            'display_signatures' => (isset($_POST['display_signatures'])) ? 'y' : 'n',
            'parse_smileys' => (isset($_POST['parse_smileys'])) ? 'y' : 'n'
        );

        ee()->db->update('members', $data, array('member_id' => ee()->session->userdata('member_id')));

        // -------------------------------------------
        // 'member_update_preferences' hook.
        //  - Allows updating of added preferences via user side preferences form
        //
        ee()->extensions->call('member_update_preferences', $data);
        if (ee()->extensions->end_script === true) {
            return;
        }
        //
        // -------------------------------------------

        /** -------------------------------------
        /**  Success message
        /** -------------------------------------*/

        return $this->_var_swap(
            $this->_load_element('success'),
            array(
                'lang:heading' => ee()->lang->line('mbr_preferences_updated'),
                'lang:message' => ee()->lang->line('mbr_prefereces_have_been_updated')
            )
        );
    }

    /** ----------------------------------------
    /**  Email Settings
    /** ----------------------------------------*/
    public function edit_email()
    {
        $query = ee()->db->query("SELECT email, accept_admin_email, accept_user_email, notify_by_default, notify_of_pm, smart_notifications FROM exp_members WHERE member_id = '" . ee()->session->userdata('member_id') . "'");

        // Fetch the template tag data
        $tagdata = trim(ee()->TMPL->tagdata);

        // If there is tag data, it's a tag pair, otherwise it's a single tag which means it's a legacy speciality template.
        $template = '';
        if (! empty($tagdata)) {
            $template = ee()->TMPL->tagdata;
        } elseif (ee('Config')->getFile()->getBoolean('legacy_member_templates')) {
            $template = $this->_load_element('email_prefs_form');
        }

        return $this->_var_swap(
            $template,
            array(
                'form_declaration' => ee()->functions->form_declaration(
                    array(
                        'action' => $this->_member_path('update_email'),
                        'hidden_fields' => array(
                            'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ? ee()->TMPL->fetch_param('return') : '-1'
                        )
                    )
                ),
                'path:update_email_settings' => $this->_member_path('update_email'),
                'email' => $query->row('email'),
                'state:accept_admin_email' => ($query->row('accept_admin_email') == 'y') ? " checked='checked'" : '',
                'state:accept_user_email' => ($query->row('accept_user_email') == 'y') ? " checked='checked'" : '',
                'state:notify_by_default' => ($query->row('notify_by_default') == 'y') ? " checked='checked'" : '',
                'state:notify_of_pm' => ($query->row('notify_of_pm') == 'y') ? " checked='checked'" : '',
                'state:smart_notifications' => ($query->row('smart_notifications') == 'y') ? " checked='checked'" : ''
            )
        );
    }

    /** ----------------------------------------
    /**  Email Update
    /** ----------------------------------------*/
    public function update_email()
    {
        // Safety.
        if (! isset($_POST['email'])) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('invalid_action')));
        }

        /** ----------------------------------------
        /**  Blocked/Allowed List Check
        /** ----------------------------------------*/
        if (ee()->blockedlist->blocked == 'y' && ee()->blockedlist->allowed == 'n') {
            return ee()->output->show_user_error('general', array(ee()->lang->line('not_authorized')));
        }

        $member = ee('Model')->get('Member', ee()->session->userdata('member_id'))->first();

        if (! $member) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('invalid_action')));
        }

        // this action requires password confirmation
        ee()->load->library('auth');

        $sess = ee()->auth->authenticate_username($member->username, ee()->input->post('password'));

        if (! $sess) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('invalid_password')));
        }

        $member->set([
            'email' => ee()->input->post('email'),
            'accept_admin_email' => (ee()->input->post('accept_admin_email')) ? 'y' : 'n',
            'accept_user_email' => (ee()->input->post('accept_user_email')) ? 'y' : 'n',
            'notify_by_default' => (ee()->input->post('notify_by_default')) ? 'y' : 'n',
            'notify_of_pm' => (ee()->input->post('notify_of_pm')) ? 'y' : 'n',
            'smart_notifications' => (ee()->input->post('smart_notifications')) ? 'y' : 'n',
        ]);

        $result = $member->validate();

        if (! $result->isValid()) {
            return ee()->output->show_user_error('submission', $result->getErrors('email'));
        }

        $member->save();

        $return = ee()->input->get_post('RET');

        if (! empty($return)) {
            if (is_numeric($return)) {
                $return_link = ee()->functions->form_backtrack($return);
            } else {
                $return_link = $return;
            }

            // Make sure it's an actual URL.
            if (substr($return_link, 0, 4) !== 'http' && substr($return_link, 0, 1) !== '/') {
                $return_link = '/' . $return_link;
            }

            ee()->functions->redirect($return_link);
            exit;
        }

        // success
        return $this->_var_swap(
            $this->_load_element('success'),
            array(
                'lang:heading' => ee()->lang->line('mbr_email_updated'),
                'lang:message' => ee()->lang->line('mbr_email_has_been_updated')
            )
        );
    }

    /** ----------------------------------------
    /**  Username/Password Preferences
    /** ----------------------------------------*/
    public function edit_userpass()
    {
        $query = ee()->db->query("SELECT username, screen_name FROM exp_members WHERE member_id = '" . ee()->session->userdata('member_id') . "'");

        // Fetch the template tag data
        $tagdata = trim(ee()->TMPL->tagdata);

        // If there is tag data, it's a tag pair, otherwise it's a single tag which means it's a legacy speciality template.
        $template = '';
        if (! empty($tagdata)) {
            $template = ee()->TMPL->tagdata;
        } elseif (ee('Config')->getFile()->getBoolean('legacy_member_templates')) {
            $template = $this->_load_element('username_password_form');
        }

        return $this->_var_swap(
            $template,
            array(
                'form_declaration' => ee()->functions->form_declaration(
                    array(
                        'action' => $this->_member_path('update_userpass'),
                        'hidden_fields' => array(
                            'RET' => (ee()->TMPL->fetch_param('return') && ee()->TMPL->fetch_param('return') != "") ? ee()->TMPL->fetch_param('return') : '-1'
                        )
                    )
                ),
                'row:username_form' =>
                    (ee('Permission')->isSuperAdmin() or ee()->config->item('allow_username_change') == 'y') ?
                        $this->_load_element('username_row') :
                        $this->_load_element('username_change_disallowed'),
                'path:update_username_password' => $this->_member_path('update_userpass'),
                'username' => $query->row('username'),
                'screen_name' => $this->_convert_special_chars($query->row('screen_name'))
            )
        );
    }

    /**
     * Username/Password Update
     */
    public function update_userpass()
    {
        $member = ee('Model')->get('Member', ee()->session->userdata('member_id'))->first();

        if (! $member) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('invalid_action')));
        }

        if (ee()->config->item('allow_username_change') == 'y') {
            $member->username = ee()->input->post('username');
        }

        // If the screen name field is empty, we'll assign it from the username field.
        if (ee()->input->post('screen_name') == '') {
            $member->screen_name = ee()->input->post('username');
        } else {
            $member->screen_name = ee()->input->post('screen_name');
        }

        // require authentication to change user/pass
        $validator = ee('Validation')->make();
        $validator->setRule('current_password', 'authenticated');

        // set password, and confirmation if needed
        if (ee()->input->post('password')) {
            $member->password = ee()->input->post('password');
            $validator->setRule('password_confirm', 'matches[password]');
        }

        $result = $member->validate();
        $password_confirm = $validator->validate($_POST);

        // Add password confirmation failure to main result object
        if ($password_confirm->failed()) {
            $rules = $password_confirm->getFailed();
            foreach ($rules as $field => $rule) {
                $result->addFailed($field, $rule[0]);
            }
        }

        if (! $result->isValid()) {
            $errors = [];
            foreach ($result->getAllErrors() as $error) {
                $errors = array_merge($errors, array_values($error));
            }

            return ee()->output->show_user_error('submission', $errors);
        }

        // if the password was set, need to hash it before saving and kill all other sessions
        if (ee()->input->post('password')) {
            $member->hashAndUpdatePassword($member->password);
        }

        $member->save();

        $return = ee()->input->get_post('RET');

        if (! empty($return)) {
            if (is_numeric($return)) {
                $return_link = ee()->functions->form_backtrack($return);
            } else {
                $return_link = $return;
            }

            // Make sure it's an actual URL.
            if (substr($return_link, 0, 4) !== 'http' && substr($return_link, 0, 1) !== '/') {
                $return_link = '/' . $return_link;
            }

            ee()->functions->redirect($return_link);
            exit;
        }

        /** -------------------------------------
        /**  Success message
        /** -------------------------------------*/

        return $this->_var_swap(
            $this->_load_element('success'),
            [
                'lang:heading' => ee()->lang->line('username_and_password'),
                'lang:message' => ee()->lang->line('mbr_settings_updated'),
            ]
        );
    }

    /** ----------------------------------------
    /**  Localization Edit Form
    /** ----------------------------------------*/
    public function edit_localization()
    {
        // Are localizations enabled?

        if (ee()->config->item('allow_member_localization') == 'n' and ! ee('Permission')->isSuperAdmin()) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('localization_disallowed')));
        }

        // Fetch the admin config values in order to populate the form with
        // the same options
        ee()->load->model('admin_model');
        ee()->load->helper('form');
        // Have to get tz from database since the config will have replaced null with the site default
        $member = ee('Model')->get('Member', ee()->session->userdata('member_id'))->fields('timezone')->first();

        $defaults = array(
            'site_default' => empty($member->timezone) ? 'y' : 'n',
            'date_format' => ee()->session->userdata('date_format'),
            'time_format' => ee()->session->userdata('time_format'),
            'include_seconds' => ee()->session->userdata('include_seconds')
        );

        $config_fields = ee()->config->prep_view_vars('localization_cfg', $defaults);

        return $this->_var_swap(
            $this->_load_element('localization_form'),
            array(
                'form_declaration' => ee()->functions->form_declaration(
                    array('action' => $this->_member_path('update_localization'))
                ),
                'path:update_localization' => $this->_member_path('update_localization'),
                'form:site_default' => form_preference('site_default', $config_fields['fields']['site_default']),
                'form:localization' => ee()->localize->timezone_menu((ee()->session->userdata('timezone') == '') ? 'UTC' : ee()->session->userdata('timezone'), 'timezone'),
                'form:date_format' => form_preference('date_format', $config_fields['fields']['date_format']),
                'form:time_format' => form_preference('time_format', $config_fields['fields']['time_format']),
                'form:include_seconds' => form_preference('include_seconds', $config_fields['fields']['include_seconds']),
                'form:language' => $this->get_language_listing(ee()->session->get_language())
            )
        );
    }

    /** ----------------------------------------
    /**  Update Localization Prefs
    /** ----------------------------------------*/
    public function update_localization()
    {
        // Are localizations enabled?

        if (ee()->config->item('allow_member_localization') == 'n' and ! ee('Permission')->isSuperAdmin()) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('localization_disallowed')));
        }

        if (! isset($_POST['timezone'])) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('invalid_action')));
        }

        $data['language'] = ee()->security->sanitize_filename($_POST['language']);

        foreach (array('timezone', 'date_format', 'time_format', 'week_start', 'include_seconds') as $key) {
            if (ee()->input->post('site_default') == 'y') {
                $data[$key] = null;
            } else {
                $data[$key] = ee()->input->post($key);
            }
        }

        $language_pack_names = array_keys(ee()->lang->language_pack_names());
        if (! in_array($data['language'], $language_pack_names)) {
            return ee()->output->show_user_error('general', array(lang('invalid_action')));
        }

        $member = ee('Model')->get('Member', ee()->session->userdata('member_id'))->first();
        $member->set($data);
        $result = $member->validate();

        if ($result->failed()) {
            return ee()->output->show_user_error('general', $result->renderErrors());
        }

        $member->save();

        /** -------------------------------------
        /**  Success message
        /** -------------------------------------*/

        return $this->_var_swap(
            $this->_load_element('success'),
            array(
                'lang:heading' => lang('localization_settings'),
                'lang:message' => lang('mbr_localization_settings_updated')
            )
        );
    }

    /** -------------------------------------
    /**  Edit Ignore List
    /** -------------------------------------*/
    public function edit_ignore_list($msg = '')
    {
        $query = ee()->db->query("SELECT ignore_list FROM exp_members WHERE member_id = '" . ee()->session->userdata['member_id'] . "'");

        if ($query->num_rows() == 0) {
            return ee()->output->show_user_error('general', array(ee()->lang->line('not_authorized')));
        } else {
            $ignored = ($query->row('ignore_list') == '') ? array() : explode('|', $query->row('ignore_list'));
        }

        $query = ee()->db->query("SELECT screen_name, member_id FROM exp_members WHERE member_id IN ('" . implode("', '", $ignored) . "') ORDER BY screen_name");
        $out = '';

        if ($query->num_rows() == 0) {
            // not ignoring anyone right now
        } else {
            $template = $this->_load_element('edit_ignore_list_rows');
            $i = 0;

            foreach ($query->result_array() as $row) {
                $temp = $this->_var_swap(
                    $template,
                    array(
                        'path:profile_link' => $this->_member_path($row['member_id']),
                        'name' => $row['screen_name'],
                        'member_id' => $row['member_id'],
                        'class' => ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo'
                    )
                );
                $out .= $temp;
            }
        }

        $form_details = array('hidden_fields' => array('name' => '', 'daction' => '', 'toggle[]' => ''),
            'action' => $this->_member_path('update_ignore_list'),
            'id' => 'target'
        );

        $images_folder = URL_THEMES . 'asset/img/';

        $finalized = $this->_var_swap(
            $this->_load_element('edit_ignore_list_form'),
            array(
                'form:form_declaration' => ee()->functions->form_declaration($form_details),
                'include:edit_ignore_list_rows' => $out,
                'include:member_search' => $this->member_search_js() .
                                                    '<a href="#" title="{lang:member_search}" onclick="member_search(); return false;">' .
                                                    '<img src="' . $images_folder . 'search_glass.gif" style="border: 0px" width="12" height="12" alt="' . ee()->lang->line('search_glass') . '" />' .
                                                    '</a>',
                'include:toggle_js' => $this->toggle_js(),
                'form:add_button' => $this->list_js() .
                                                    "<button type='submit' id='add' name='add' value='add' " .
                                                    "class='buttons' title='{lang:add_member}' " .
                                                    "onclick='dynamic_action(\"add\");list_addition();return false;'>" .
                                                    "{lang:add_member}</button>" . NBS . NBS,
                'form:delete_button' => "<button type='submit' id='delete' name='delete' value='delete' " .
                                                    "class='buttons' title='{lang:delete_selected_members}' " .
                                                    "onclick='dynamic_action(\"delete\");'>" .
                                                    "{lang:delete_member}</button> ",
                'path:update_ignore_list' => $this->_member_path('update_ignore_list'),
                'lang:message' => ee()->lang->line('ignore_list_updated')
            )
        );
        if ($msg == '') {
            $finalized = $this->_deny_if('success_message', $finalized);
        } else {
            $finalized = $this->_allow_if('success_message', $finalized);
        }

        return $finalized;
    }

    /** -------------------------------------
    /**  Update Ignore List
    /** -------------------------------------*/
    public function update_ignore_list()
    {
        if (! ($action = ee()->input->post('daction'))) {
            return $this->edit_ignore_list();
        }

        $ignored = array_flip(ee()->session->userdata['ignore_list']);

        if ($action == 'delete') {
            if (! ($member_ids = ee()->input->post('toggle'))) {
                return ee()->output->show_user_error('general', array(ee()->lang->line('not_authorized')));
            }

            foreach ($member_ids as $member_id) {
                unset($ignored[$member_id]);
            }
        } else {
            if (! ($screen_name = ee()->input->post('name'))) {
                return ee()->output->show_user_error('general', array(ee()->lang->line('not_authorized')));
            }

            $query = ee()->db->query("SELECT member_id FROM exp_members WHERE screen_name = '" . ee()->db->escape_str($screen_name) . "'");

            if ($query->num_rows() == 0) {
                return $this->_trigger_error('invalid_screen_name', 'invalid_screen_name_message');
            }

            if ($query->row('member_id') == ee()->session->userdata['member_id']) {
                return $this->_trigger_error('invalid_screen_name', 'can_not_ignore_self');
            }

            if (! isset($ignored[$query->row('member_id')])) {
                $ignored[$query->row('member_id')] = $query->row('member_id') ;
            }
        }

        $ignored_list = implode('|', array_keys($ignored));

        ee()->db->query(ee()->db->update_string('exp_members', array('ignore_list' => $ignored_list), "member_id = '" . ee()->session->userdata['member_id'] . "'"));

        return $this->edit_ignore_list('ignore_list_updated');
    }

    /** -------------------------------------
    /**  Member Mini Search (Ignore List)
    /** -------------------------------------*/
    public function member_mini_search($msg = '')
    {
        $form_details = array('hidden_fields' => array(),
            'action' => $this->_member_path('do_member_mini_search'),
        );

        $group_opts = '';

        $roles = ee('Model')->get('Role')
            ->fields('role_id', 'name')
            ->order('name')
            ->all();

        foreach ($roles as $role) {
            $group_opts .= "<option value='{$role->getId()}'>{$role->name}</option>";
        }

        $template = $this->_var_swap(
            $this->_load_element('search_members'),
            array(
                'form:form_declaration:do_member_search' => ee()->functions->form_declaration($form_details),
                'include:message' => $msg,
                'include:member_group_options' => $group_opts
            )
        );

        if ($msg == '') {
            $template = $this->_deny_if('message', $template);
        } else {
            $template = $this->_allow_if('message', $template);
        }

        return $template;
    }

    /** -------------------------------------
    /**  Do Member Mini Search (Ignore List)
    /** -------------------------------------*/
    public function do_member_mini_search()
    {
        $redirect_url = $this->_member_path('member_mini_search');

        /** -------------------------------------
        /**  Parse the $_POST data
        /** -------------------------------------*/
        if (
            ee('Request')->post('screen_name') == '' &&
            ee('Request')->post('email') == ''
        ) {
            ee()->functions->redirect($redirect_url);
            exit;
        }

        $search_query = array();

        foreach ($_POST as $key => $val) {
            // Don't search for site_id because it isn't on the members table
            if ($key == 'site_id') {
                continue;
            }

            if ($key == 'group_id') {
                if ($val != 'any') {
                    $search_query[] = " role_id ='" . ee()->db->escape_str((int) $_POST['group_id']) . "'";
                }
            } elseif (in_array($key, ['screen_name', 'email'])) {
                if ($val != '') {
                    $search_query[] = ee()->db->escape_str($key) . " LIKE '%" . ee()->db->escape_like_str(ee('Security/XSS')->clean($val)) . "%'";
                }
            }
        }

        if (count($search_query) < 1) {
            ee()->functions->redirect($redirect_url);
            exit;
        }

        $Q = implode(" AND ", $search_query);

        $sql = "SELECT DISTINCT exp_members.member_id, exp_members.screen_name FROM exp_members, exp_roles
                WHERE exp_members.role_id = exp_roles.role_id
                AND " . $Q;

        $query = ee()->db->query($sql);

        if ($query->num_rows() == 0) {
            return $this->member_mini_search(ee()->lang->line('no_search_results'));
        }

        $r = '';

        foreach ($query->result_array() as $row) {
            $item = '<a href="#" onclick="opener.dynamic_action(\'add\');opener.list_addition(\'' . $row['screen_name'] . '\', \'name\');return false;">' . $row['screen_name'] . '</a>';
            $r .= $this->_var_swap(
                $this->_load_element('member_results_row'),
                array(
                    'item' => $item
                )
            );
        }

        return $this->_var_swap(
            $this->_load_element('member_results'),
            array(
                'include:search_results' => $r,
                'path:new_search_url' => $redirect_url,
                'which_field' => 'name'     // not used in this instance; probably will log a minor js error
            )
        );
    }

    /** -------------------------------------
    /**  Toggle JS - used in Ignore List mgmt.
    /** -------------------------------------*/
    public function toggle_js()
    {
        $str = <<<EOT

    <script type="text/javascript">
    //<![CDATA[

    function toggle(thebutton)
    {
        if (thebutton.checked)
        {
            val = true;
        }
        else
        {
            val = false;
        }

        if (document.target)
        {
            var theForm = document.target;
        }
        else if (document.getElementById('target'))
        {
            var theForm = document.getElementById('target');
        }
        else
        {
            return false;
        }

        var len = theForm.elements.length;

        for (var i = 0; i < len; i++)
        {
            var button = theForm.elements[i];

            var name_array = button.name.split("[");

            if (name_array[0] == "toggle")
            {
                button.checked = val;
            }
        }

        theForm.toggleflag.checked = val;
    }
    //]]>
    </script>

EOT;

        return trim($str);
    }

    /** -------------------------------------
    /**  Add member to Ignore List js
    /** -------------------------------------*/
    public function list_js()
    {
        return <<<EWOK

    <script type="text/javascript">
    //<![CDATA[

    function list_addition(member, el)
    {
        var member_text = '{lang:member_usernames}';

        var Name = (member == null) ? prompt(member_text, '') : member;
        var el = (el == null) ? 'name' : el;

         if ( ! Name || Name == null)
         {
            return;
         }

        var frm = document.getElementById('target');
        var x;

        for (i = 0; i < frm.length; i++)
        {
            if (frm.elements[i].name == el)
            {
                frm.elements[i].value = Name;
            }
        }

         document.getElementById('target').submit();
    }

    function dynamic_action(which)
    {
        if (document.getElementById('target').daction)
        {
            document.getElementById('target').daction.value = which;
        }
    }
    //]]>
    </script>
EWOK;
    }

    /** -------------------------------------
    /**  Member Search JS for Ignore List
    /** -------------------------------------*/
    public function member_search_js()
    {
        $url = $this->_member_path('member_mini_search');

        $str = <<<UNGA

<script type="text/javascript">
//<![CDATA[
function member_search()
{
    var popWin = window.open('{$url}', '_blank', 'width=450,height=480,scrollbars=yes,status=yes,screenx=0,screeny=0,resizable=yes');
}

//]]>
</script>

UNGA;

        return $str;
    }

    /** ----------------------------------------
    /**  Notepad Edit Form
    /** ----------------------------------------*/
    public function edit_notepad()
    {
        $query = ee()->db->query("SELECT notepad, notepad_size FROM exp_members WHERE member_id = '" . ee()->session->userdata('member_id') . "'");

        return $this->_var_swap(
            $this->_load_element('notepad_form'),
            array(
                'form_declaration' => ee()->functions->form_declaration(
                    array('action' => $this->_member_path('update_notepad'))
                ),
                'path:update_notepad' => $this->_member_path('update_notepad'),
                'notepad_data' => $this->_form_prep_encoded($query->row('notepad')),
                'notepad_size' => $query->row('notepad_size')
            )
        );
    }

    /** ----------------------------------------
    /**  Update Notepad
    /** ----------------------------------------*/
    public function update_notepad()
    {
        if (! isset($_POST['notepad'])) {
            return ee()->functions->redirect($this->_member_path('edit_notepad'));
        }

        $notepad_size = (! is_numeric($_POST['notepad_size'])) ? 18 : $_POST['notepad_size'];

        ee()->db->query("UPDATE exp_members SET notepad = '" . ee()->db->escape_str(ee('Security/XSS')->clean($_POST['notepad'])) . "', notepad_size = '" . $notepad_size . "' WHERE member_id ='" . ee()->session->userdata('member_id') . "'");

        /** -------------------------------------
        /**  Success message
        /** -------------------------------------*/

        return $this->_var_swap(
            $this->_load_element('success'),
            array(
                'lang:heading' => ee()->lang->line('mbr_notepad'),
                'lang:message' => ee()->lang->line('mbr_notepad_updated')
            )
        );
    }

    /** ----------------------------------
    /**  Username/password update
    /** ----------------------------------*/
    public function unpw_update()
    {
        if ($this->cur_id == '' or strpos($this->cur_id, '_') === false) {
            return;
        }

        $x = explode('_', $this->cur_id);

        if (count($x) != 3) {
            return;
        }

        foreach ($x as $val) {
            if (! is_numeric($val)) {
                return;
            }
        }

        $mid = $x['0'];
        $ulen = $x['1'];
        $plen = $x['2'];

        $tmpl = $this->_load_element('update_un_pw_form');

        $uml = ee()->config->item('un_min_len');
        $pml = ee()->config->item('pw_min_len');

        if ($ulen < $uml) {
            $tmpl = $this->_allow_if('invalid_username', $tmpl);
        }

        if ($plen < $pml) {
            $tmpl = $this->_allow_if('invalid_password', $tmpl);
        }

        $tmpl = $this->_deny_if('invalid_username', $tmpl);
        $tmpl = $this->_deny_if('invalid_password', $tmpl);

        $data['hidden_fields']['ACT'] = ee()->functions->fetch_action_id('Member', 'update_un_pw');
        $data['hidden_fields']['FROM'] = ($this->in_forum == true) ? 'forum' : '';

        if (ee()->uri->segment(5)) {
            $data['action'] = ee()->functions->fetch_current_uri();
        }

        $this->_set_page_title(lang('member_login'));

        return $this->_var_swap(
            $tmpl,
            array(
                'form_declaration' => ee()->functions->form_declaration($data),
                'lang:username_length' => sprintf(lang('un_len'), ee()->config->item('un_min_len')),
                'lang:password_length' => sprintf(lang('pw_len'), ee()->config->item('pw_min_len'))
            )
        );
    }

    /** ----------------------------------
    /**  Update the username/password
    /** ----------------------------------*/
    public function update_un_pw()
    {
        ee()->load->library('auth');

        // Run through basic verifications: authenticate, username and
        // password both exist, not banned, IP checking is okay
        if (! ($verify_result = ee()->auth->verify())) {
            // In the event it's a string, send it to show_user_error
            return ee()->output->show_user_error('submission', implode(', ', ee()->auth->errors));
        }

        list($username, $password, $incoming) = $verify_result;
        $member_id = $incoming->member('member_id');

        /** -------------------------------------
        /**  Instantiate validation class
        /** -------------------------------------*/
        $new_un = (string) ee()->input->post('new_username');
        $new_pw = (string) ee()->input->post('new_password');
        $new_pwc = (string) ee()->input->post('new_password_confirm');

        $un_pw_data = array(
            'val_type' => 'new',
            'fetch_lang' => true,
            'require_cpw' => false,
            'enable_log' => false,
            'username' => $new_un,
            'password' => $new_pw,
            'password_confirm' => $new_pwc,
            'cur_password' => $password,
        );

        $validationRules = [];
        $un_exists = ($new_un !== '') ? true : false;
        $pw_exists = ($new_pw !== '' and $new_pwc !== '') ? true : false;

        if ($un_exists) {
            $validationRules['username'] = 'uniqueUsername|validUsername|notBanned';
        }

        if ($pw_exists) {
            $validationRules['password'] = 'validPassword|passwordMatchesSecurityPolicy|matches[password_confirm]';
        }

        $validationResult = ee('Validation')->make($validationRules)->validate($un_pw_data);

        /** -------------------------------------
        /**  Display errors if there are any
        /** -------------------------------------*/
        if ($validationResult->isNotValid()) {
            $errors = [];
            foreach ($validationResult->getAllErrors() as $error) {
                $errors = array_merge($errors, array_values($error));
            }

            return ee()->output->show_user_error('submission', $errors);
        }

        if ($un_exists) {
            ee()->auth->update_username($member_id, $new_un);
        }

        if ($pw_exists) {
            ee()->auth->update_password($member_id, $new_pw);
        }

        // Clear the tracker cookie since we're not sure where the redirect should go
        ee()->input->delete_cookie('tracker');

        $return = ee()->functions->form_backtrack();

        if (ee()->config->item('website_session_type') != 'c') {
            if (ee()->config->item('force_query_string') == 'y' && substr($return, 0, -3) == "php") {
                $return .= '?';
            }

            if (ee()->session->userdata['session_id'] != '') {
                $return .= "/S=" . ee()->session->userdata['session_id'] . "/";
            }
        }

        if (ee()->uri->segment(5)) {
            $link = ee()->functions->create_url(ee()->uri->segment(5));
            $line = lang('return_to_forum');
        } else {
            $link = $this->_member_path('login');
            $line = lang('return_to_login');
        }

        // We're done.
        $data = array(
            'title' => lang('settings_update'),
            'heading' => lang('thank_you'),
            'content' => lang('unpw_updated'),
            'link' => array($link, $line)
        );

        ee()->output->show_message($data);
    }
}
// END CLASS

// EOF
