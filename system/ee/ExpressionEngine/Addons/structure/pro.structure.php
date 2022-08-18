<?php

use ExpressionEngine\Addons\Pro\Service\Prolet;

class Structure_pro extends Prolet\AbstractProlet implements Prolet\ProletInterface
{
    protected $name = 'Structure';

    protected $buttons = null;

    public function index()
    {
        require_once PATH_ADDONS . 'structure/addon.setup.php';
        require_once PATH_ADDONS . 'structure/helper.php';
        require_once PATH_ADDONS . 'structure/sql.structure.php';
        require_once PATH_ADDONS . 'structure/mod.structure.php';

        $sql = new Sql_structure();
        $structure = new Structure();

        if (! function_exists('json_decode')) {
            ee()->load->library('Services_json');
        }

        ee()->cp->add_to_head("<link rel='stylesheet' href='" . $sql->theme_url() . "css/structure.css'>");

        $settings = $sql->get_settings();

        // Load Libraries and Helpers
        ee()->load->library('javascript');
        ee()->load->library('table');
        ee()->load->library('general_helper');
        ee()->load->helper('path');
        ee()->load->helper('form');

        // Check if we have admin permission
        $permissions = array();
        $permissions['admin'] = $sql->user_access('perm_admin_structure', $settings);
        $permissions['view_add_page'] = false;//$sql->user_access('perm_view_add_page', $settings);
        $permissions['view_view_page'] = false;//$sql->user_access('perm_view_view_page', $settings);
        $permissions['view_global_add_page'] = false;//$sql->user_access('perm_view_global_add_page', $settings);
        $permissions['delete'] = false;//$sql->user_access('perm_delete', $settings);
        $permissions['reorder'] = $sql->user_access('perm_reorder', $settings);

        $rules = array();

        // put fields to go faster.
        $builder = ee('Model')->get('Channel')->filter('site_id', '==', ee()->config->item('site_id'))->order('channel_id', 'ASC')->fields('allow_preview', 'channel_id')->all();
        $channel_rules = $builder->pluck('allow_preview');
        $channel_id = $builder->pluck('channel_id');

        for ($i = 0; $i < count($channel_id); $i++) {
            $rules[$channel_id[$i]] = $channel_rules[$i];
        }

        // Enable/disable dragging and reordering
        // if ((isset($permissions['reorder']) && $permissions['reorder']) || $permissions['admin'])
        ee()->cp->load_package_js('jquery.ui.nestedsortable');
        ee()->cp->load_package_js('structure-nested-20170328002');
        ee()->cp->load_package_js('structure-actions');
        ee()->cp->load_package_js('structure-collapse');

        $site_pages = $sql->get_site_pages();
        $data['ee_ver'] = substr(APP_VER, 0, 1);
        $data['tabs'] = array('page-ui' => lang('all_pages'));
        $data['data'] = array('page-ui' => $sql->get_data());
        $data['valid_channels'] = $sql->get_structure_channels('page', '', 'alpha', true);
        $data['listing_cids'] = $structure->get_data_cids(true);
        $data['settings'] = $settings;
        $data['member_settings'] = $sql->get_member_settings();
        $data['cp_asset_data'] = $sql->get_cp_asset_data();
        $data['site_pages'] = count($site_pages) > 0 ? $site_pages : array();
        $data['site_uris'] = is_array($data['site_pages']) && array_key_exists('uris', $data['site_pages']) ? $data['site_pages']['uris'] : array();
        $data['asset_path'] = PATH_ADDONS . 'structure/views/';
        $data['permissions'] = $permissions;
        $data['page_count'] = $sql->get_page_count();
        $data['attributes'] = array('class' => 'structure-form', 'id' => 'delete_form');
        $data['status_colors'] = $sql->get_status_colors();
        $data['assigned_channels'] = is_array(ee()->session->userdata('assigned_channels')) ? ee()->session->userdata('assigned_channels') : array();
        $data['action_url'] = ee('CP/URL')->make('addons/settings/structure/delete');
        $data['theme_url'] = ee()->config->item('theme_folder_url') . 'third_party/structure';
        $data['extra_reorder_options'] = false;
        $data['homepage'] = array_search('/', $site_pages['uris']);
        $data['selected_tab'] = 0;
        $data['channel_rules'] = $rules;

        $data['prolet'] = true;

        // Get the last updated datetime.
        $data['updated_time'] = ee()->db->select('updated')->get_where('structure', array('dead' => 'root'), 1)->row()->updated;

        // -------------------------------------------
        // 'structure_index_view_data' hook.
        // - Used to expand the tree switcher (new tabs and content)
        //
        if (ee()->extensions->active_hook('structure_index_view_data') === true) {
            $data = ee()->extensions->call('structure_index_view_data', $data);
        }
        //
        // -------------------------------------------

        $page_choices = array();

        if (is_array($data['valid_channels'])) {
            $page_choices = array_intersect_key($data['valid_channels'], $data['assigned_channels']);
        }

        $data['page_choices'] = $page_choices;

        if ($page_choices && count($page_choices) == 1) {
            $data['add_page_url'] = ee()->general_helper->cpURL('publish', 'create', array('channel_id' => key($page_choices)));
        } elseif ($data['page_count'] == 0) {
            $data['add_page_url'] = ee('CP/URL')->make('addons/settings/structure/channel_settings');
        } else {
            $data['add_page_url'] = '#';
        }

        $add_body = '';
        $add_urls = array();

        $vc_total = count($page_choices);
        $vci = 0;
        if (is_array($page_choices) && count($page_choices) > 0) {
            foreach ($page_choices as $key => $channel) {
                $vci++;
                $add_url = (string) ee()->general_helper->cpURL('publish', 'create', array('channel_id' => $key, 'template_id' => $channel['template_id']));
                $add_urls[] = $add_url;
                $add_body .= '<li';
                $add_body .= $vci == $vc_total ? ' class="last">' : '>';
                $add_body .= '<a rel="what" href="' . $add_url . '">' . $channel['channel_title'] . '</a></li>';
            }
        }

        if ($add_body) {
            $add_body = '<ul class="plain">' . $add_body . '</ul>';
        }

        $dialogs = array(
            'add' => array(
                'urls' => $add_urls,
                'title' => ee()->lang->line('select_page_type'),
                'body' => $add_body,
                'buttons' => array('cancel' => ee()->lang->line('cancel'))
            ),
            'del' => array(
                'title' => '',
                'body' => ee()->lang->line('structure_delete_confirm'),
                'buttons' => array(
                    'del' => ee()->lang->line('delete_page'),
                    'cancel' => ee()->lang->line('cancel')
                )
            )
        );

        $settings_array = array(
            'dialogs' => $dialogs,
            'site_id' => ee()->config->item('site_id'),
            'xid' => XID_SECURE_HASH,
            'global_add_page' => $settings['show_global_add_page'],
            'show_picker' => $settings['show_picker'],
            'can_reorder' => $permissions['reorder'] ? true : false,
            'admin' => $permissions['admin'] ? true : false
        );

        $settings_json = json_encode($settings_array);

        ee()->cp->add_to_foot('<script type="text/javascript">var structure_updated = "' . $data['updated_time'] . '"; var structure_settings = ' . $settings_json . ';</script>');

        return ee('View')->make('structure:index')->render($data);
    }
}
