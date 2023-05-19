<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Utilities;

/**
 * Search and Replace Controller
 */
class Sandr extends Utilities
{
    /**
     * Search and Replace utility
     *
     * @access	public
     * @return	void
     */
    public function index()
    {
        if (! ee('Permission')->can('access_data')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules(array(
            array(
                'field' => 'search_term',
                'label' => 'lang:sandr_search_text',
                'rules' => 'required'
            ),
            array(
                'field' => 'replace_term',
                'label' => 'lang:sandr_replace_text'
            ),
            array(
                'field' => 'replace_where',
                'label' => 'lang:sandr_in',
                'rules' => 'required'
            ),
            array(
                'field' => 'password_auth',
                'label' => 'lang:current_password',
                'rules' => 'required|auth_password'
            )
        ));

        if (AJAX_REQUEST) {
            ee()->form_validation->run_ajax();
            exit;
        } elseif (ee()->form_validation->run() !== false) {
            $replaced = $this->_do_search_and_replace(
                ee()->input->post('search_term'),
                ee()->input->post('replace_term'),
                ee()->input->post('replace_where')
            );

            ee()->view->set_message('success', lang('cp_message_success'), sprintf(lang('rows_replaced'), (int) $replaced), true);
            ee()->functions->redirect(ee('CP/URL')->make('utilities/sandr'));
        } elseif (ee()->form_validation->errors_exist()) {
            ee()->view->set_message('issue', lang('sandr_error'), lang('sandr_error_desc'));
        }

        ee()->load->model('tools_model');
        ee()->view->replace_options = ee()->tools_model->get_search_replace_options();

        ee()->view->cp_page_title = lang('sandr');

        ee()->view->cp_breadcrumbs = array(
            '' => lang('search_and_replace')
        );

        ee()->cp->render('utilities/sandr');
    }

    /**
     * Do Search and Replace
     *
     * Used by search_and_replace() to execute replacement
     *
     * @access	private
     * @param	string
     * @param	string
     * @param	string
     * @return	bool
     */
    public function _do_search_and_replace($search, $replace, $where)
    {
        // escape search and replace for use in queries
        $search_escaped = ee()->db->escape_str($search);
        $replace_escaped = ee()->db->escape_str($replace);
        $where = ee()->db->escape_str($where);

        $show_reindex_tip = false;

        if ($where == 'title') {
            $sql = "UPDATE `exp_channel_titles` SET `{$where}` = REPLACE(`{$where}`, '{$search_escaped}', '{$replace_escaped}')";
        } elseif ($where == 'preferences' or strncmp($where, 'site_preferences_', 17) == 0) {
            $rows = 0;

            if ($where == 'preferences') {
                $site_id = ee()->config->item('site_id');
            } else {
                $site_id = substr($where, strlen('site_preferences_'));
            }

            /** -------------------------------------------
            /**  Site Preferences in Certain Tables/Fields
            /** -------------------------------------------*/
            $preferences = array(
                'exp_channels' => array(
                    'channel_title',
                    'channel_url',
                    'comment_url',
                    'channel_description',
                    'comment_notify_emails',
                    'channel_notify_emails',
                    'search_results_url',
                    'rss_url'
                ),
                'exp_upload_prefs' => array(
                    'server_path',
                    'properties',
                    'file_properties',
                    'url'
                ),
                'exp_global_variables' => array('variable_data'),
                'exp_categories' => array('cat_image'),
                'exp_forums' => array(
                    'forum_name',
                    'forum_notify_emails',
                    'forum_notify_emails_topics'),
                'exp_forum_boards' => array(
                    'board_label',
                    'board_forum_url',
                    'board_upload_path',
                    'board_notify_emails',
                    'board_notify_emails_topics'
                )
            );

            foreach ($preferences as $table => $fields) {
                if (! ee()->db->table_exists($table) or $table == 'exp_forums') {
                    continue;
                }

                $site_field = ($table == 'exp_forum_boards') ? 'board_site_id' : 'site_id';

                foreach ($fields as $field) {
                    ee()->db->query("UPDATE `{$table}`
								SET `{$field}` = REPLACE(`{$field}`, '{$search_escaped}', '{$replace_escaped}')
								WHERE `{$site_field}` = '" . ee()->db->escape_str($site_id) . "'");

                    $rows += ee()->db->affected_rows();
                }
            }

            if (ee()->db->table_exists('exp_forum_boards')) {
                ee()->db->select('board_id');
                ee()->db->where('board_site_id', $site_id);
                $query = ee()->db->get('forum_boards');

                if ($query->num_rows() > 0) {
                    foreach ($query->result_array() as $row) {
                        foreach ($preferences['exp_forums'] as $field) {
                            ee()->db->query("UPDATE `exp_forums`
										SET `{$field}` = REPLACE(`{$field}`, '{$search_escaped}', '{$replace_escaped}')
										WHERE `board_id` = '" . ee()->db->escape_str($row['board_id']) . "'");

                            $rows += ee()->db->affected_rows();
                        }
                    }
                }
            }

            /** -------------------------------------------
            /**  Site Preferences in Database
            /** -------------------------------------------*/
            ee()->config->update_site_prefs(array(), $site_id, $search, $replace);

            $rows += 5;
        } elseif (strncmp($where, 'template_', 9) == 0) {
            // all templates or a specific group?
            switch ($where) {
                case 'template_partials':
                    $templates = ee('Model')->get('Snippet')
                        ->search('snippet_contents', $search)
                        ->all();
                    break;

                case 'template_variables':
                    $templates = ee('Model')->get('GlobalVariable')
                        ->search('variable_data', $search)
                        ->all();
                    break;

                case 'template_system':
                    $templates = ee('Model')->get('SpecialtyTemplate')
                        ->search('template_data', $search)
                        ->all();
                    break;

                case 'template_data':
                    $templates = ee('Model')->get('Template')
                        ->search('template_data', $search)
                        ->all();
                    break;

                default:
                    $templates = ee('Model')->get('Template')
                        ->filter('group_id', substr($where, 9))
                        ->search('template_data', $search)
                        ->all();
                    break;
            }

            foreach ($templates as $template) {
                switch ($where) {
                    case 'template_partials':
                        $template->snippet_contents = str_ireplace($search, $replace, $template->snippet_contents);
                        break;
                    case 'template_variables':
                        $template->variable_data = str_ireplace($search, $replace, $template->variable_data);
                        break;
                    default:
                        $template->template_data = str_ireplace($search, $replace, $template->template_data);
                        break;
                }
                $template->edit_date = ee()->localize->now;
            }

            $templates->save();

            return $templates->count();
        } elseif (strncmp($where, 'field_id_', 9) == 0) {
            $field_id = str_replace('field_id_', '', $where);
            $field = ee('Model')->get('ChannelField', $field_id)->first();
            $sql = "UPDATE `exp_{$field->getDataStorageTable()}` SET `{$where}` = REPLACE(`{$where}`, '{$search_escaped}', '{$replace_escaped}')";

            if ($field->field_type == 'grid' || $field->field_type == 'file_grid') {
                ee()->load->model('grid_model');
                $affected_grid_rows = ee()->grid_model->search_and_replace(
                    'channel',
                    $field->getId(),
                    $search,
                    $replace_escaped
                );
            }
        } else {
            // no valid $where
            return false;
        }

        if (isset($sql)) {
            ee()->db->query($sql);
            $rows = ee()->db->affected_rows();
        }

        if (isset($affected_grid_rows)) {
            $rows += $affected_grid_rows;
        }

        if ($rows > 0 && $show_reindex_tip) {
            ee('CP/Alert')->makeInline('search-reindex')
                ->asImportant()
                ->withTitle(lang('search_reindex_tip'))
                ->addToBody(sprintf(lang('search_reindex_tip_desc'), ee('CP/URL')->make('utilities/reindex')->compile()))
                ->defer();

            ee()->config->update_site_prefs(['search_reindex_needed' => ee()->localize->now], 0);
        }

        return $rows;
    }
}
// END CLASS

// EOF
