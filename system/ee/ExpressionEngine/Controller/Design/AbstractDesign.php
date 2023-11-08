<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Design;

use CP_Controller;
use ZipArchive;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Library\Data\Collection;
use ExpressionEngine\Model\Template\TemplateRoute;
use ExpressionEngine\Service\CP\Filter\Filter;
use ExpressionEngine\Service\Filter\FilterFactory;
use ExpressionEngine\Service\CP\Filter\FilterRunner;
use ExpressionEngine\Service\Model\Query\Builder as QueryBuilder;
use ExpressionEngine\Library\Advisor;

/**
 * Abstract Design Controller
 */
abstract class AbstractDesign extends CP_Controller
{
    public $base_url;
    protected $params;
    protected $perpage = 25;
    protected $page = 1;
    protected $offset = 0;
    protected $assigned_template_groups = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        ee('CP/Alert')->makeDeprecationNotice()->now();

        if (! ee('Permission')->can('access_design')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('design');

        $this->assigned_template_groups = array_keys(ee()->session->userdata('assigned_template_groups'));
    }

    /**
     * Display filters
     *
     * @param int
     * @return void
     */
    protected function renderFilters(FilterFactory $filters)
    {
        ee()->view->filters = $filters->render($this->base_url);
        $this->params = $filters->values();
        $this->perpage = $this->params['perpage'];
        $this->page = ((int) ee()->input->get('page')) ?: 1;
        $this->offset = ($this->page - 1) * $this->perpage;

        $this->base_url->addQueryStringVariables($this->params);
    }

    protected function generateSidebar($active = null)
    {
        $active_group_id = null;
        if (is_numeric($active)) {
            $active_group_id = (int) $active;
        }

        $sidebar = ee('CP/Sidebar')->make();

        // Template Groups
        if (ee('Permission')->isSuperAdmin() || ee('Permission')->can('create_template_groups') || !empty($this->assigned_template_groups)) {
            $template_group_list = $sidebar->addHeader(lang('template_groups'));

            if (ee('Permission')->can('create_template_groups')) {
                $template_group_list = $template_group_list->withButton(lang('new'), ee('CP/URL')->make('design/group/create'));
            }

            $template_group_list = $template_group_list->addFolderList('template-group')
                ->withRemoveUrl(ee('CP/URL')->make('design/group/remove'))
                ->withRemovalKey('group_name')
                ->withNoResultsText(lang('zero_template_groups_found'));

            if (ee('Permission')->can('edit_template_groups')) {
                $template_group_list->canReorder();
            }

            $template_groups = ee('Model')->get('TemplateGroup')
                ->filter('site_id', ee()->config->item('site_id'))
                ->order('group_order', 'asc');

            if (! ee('Permission')->isSuperAdmin()) {
                $template_groups->filter('group_id', 'IN', $this->assigned_template_groups);

                if (empty($this->assigned_template_groups)) {
                    $template_groups->markAsFutile();
                }
            }

            $groupNamesListed = [];

            foreach ($template_groups->all() as $group) {
                if (in_array($group->group_name, $groupNamesListed)) {
                    // duplicates found, show alert but do not add to sidebar
                    if (!isset($duplicateAlert)) {
                        ee()->lang->load('utilities');
                        $templateAdvisor = new Advisor\TemplateAdvisor();
                        $message = sprintf(lang('duplicate_template_groups_found'), $templateAdvisor->getDuplicateTemplateGroupsCount()) . '<br><a href="' . ee('CP/URL')->make('utilities/debug-tools/duplicate-template-groups') . '">' . lang('review_duplicate_template_groups') . '</a>';
                        $duplicateAlert = ee('CP/Alert')
                            ->makeInline()
                            ->addToBody($message)
                            ->asImportant();
                        if (ee('Permission')->isSuperAdmin()) {
                            $duplicateAlert->now();
                        }
                        ee()->logger->developer($message, true, 60 * 60 * 24 * 30);
                    }
                    continue;
                }
                $item = $template_group_list->addItem($group->group_name, ee('CP/URL')->make('design/manager/' . $group->group_name));

                $item->withEditUrl(ee('CP/URL')->make('design/group/edit/' . $group->group_name));

                $item->withRemoveConfirmation(lang('template_group') . ': <b>' . $group->group_name . '</b>')
                    ->identifiedBy($group->group_name);

                if (! ee('Permission')->can('edit_template_groups')) {
                    $item->cannotEdit();
                }

                if (! ee('Permission')->can('delete_template_groups')) {
                    $item->cannotRemove();
                }

                if ($active_group_id == $group->group_id) {
                    $item->isActive();
                }

                if ($group->is_site_default) {
                    $item->asDefaultItem();
                }

                $groupNamesListed[] = $group->group_name;
            }
        }

        // System Templates
        if (ee('Permission')->can('admin_design')) {
            $system_templates = $sidebar->addHeader(lang('system_templates'))
                ->addFolderList('system-templates');

            $item = $system_templates->addItem(lang('messages'), ee('CP/URL')->make('design/system'))
                ->withEditUrl(ee('CP/URL')->make('design/system'))
                ->cannotEdit()
                ->cannotRemove();

            if ($active == 'messages') {
                $item->isActive();
            }

            $item = $system_templates->addItem(lang('email'), ee('CP/URL')->make('design/email'))
                ->withEditUrl(ee('CP/URL')->make('design/email'))
                ->cannotEdit()
                ->cannotRemove();

            if ($active == 'email') {
                $item->isActive();
            }
        }

        if (ee('Config')->getFile()->getBoolean('legacy_member_templates') && ee('Permission')->can('admin_mbr_templates')) {
            $item = $system_templates->addItem(lang('members'), ee('CP/URL')->make('design/members'))
                ->withEditUrl(ee('CP/URL')->make('design/members'))
                ->cannotEdit()
                ->cannotRemove();

            if ($active == 'members') {
                $item->isActive();
            }
        }

        $sidebar->addDivider();

        // Template Partials
        if (ee('Permission')->hasAny('can_create_template_partials', 'can_edit_template_partials', 'can_delete_template_partials')) {
            $header = $sidebar->addItem(lang('template_partials'), ee('CP/URL')->make('design/snippets'))->withIcon('shapes');

            if ($active == 'partials') {
                $header->isActive();
            }
        }

        // Template Variables
        if (ee('Permission')->hasAny('can_create_template_variables', 'can_edit_template_variables', 'can_delete_template_variables')) {
            $header = $sidebar->addItem(lang('template_variables'), ee('CP/URL')->make('design/variables'))->withIcon('cube');

            if ($active == 'variables') {
                $header->isActive();
            }
        }

        // Template Routes
        if (! TemplateRoute::getConfig() && ee('Permission')->can('admin_design')) {
            $header = $sidebar->addItem(lang('template_routes'), ee('CP/URL')->make('design/routes'))->withIcon('truck');

            if ($active == 'routes') {
                $header->isActive();
            }
        }

        ee()->cp->add_js_script(array(
            'file' => array('cp/design/menu'),
        ));
    }

    protected function stdHeader($return = null)
    {
        if (! $return) {
            $return = ee('CP/URL')->getCurrentUrl();

            if (isset($return->qs['return'])) {
                unset($return->qs['return']);
            }

            $return = $return->encode();
        }

        $header = array(
            'title' => lang('template_manager'),
            'search_form_url' => ee('CP/URL')->make('design/template/search', array('return' => $return)),
            'toolbar_items' => array(
                'settings' => array(
                    'href' => ee('CP/URL')->make('settings/template'),
                    'title' => lang('settings')
                ),
            ),
            'search_button_value' => lang('search_templates')
        );

        if (! ee('Permission')->hasAll('can_access_sys_prefs', 'can_admin_design')) {
            unset($header['toolbar_items']['settings']);
        }

        if (ee('Model')->get('Template')->count() > 0 && ee('Permission')->isSuperAdmin()) {
            $header['toolbar_items']['export'] = array(
                'href' => ee('CP/URL', 'design/export'),
                'title' => lang('export_all')
            );
        }

        ee()->view->header = $header;
    }

    protected function loadCodeMirrorAssets($selector = 'template_data')
    {
        ee()->javascript->set_global(
            'editor.lint',
            $this->_get_installed_plugins_and_modules()
        );

        $height = ee()->config->item('codemirror_height');

        if ($height !== false) {
            ee()->javascript->set_global(
                'editor.height',
                $height
            );
        }

        $fontSize = ee()->config->item('codemirror_fontsize');
        if ($fontSize !== false) {
            ee()->cp->add_to_head('<style type="text/css">.CodeMirror-scroll {font-size: ' . $fontSize . '}</style>');
        }

        ee()->cp->add_js_script(
            array(
                'plugin' => 'ee_codemirror',
                'ui' => 'resizable',
                'file' => array(
                    'vendor/codemirror/codemirror',
                    'vendor/codemirror/closebrackets',
                    'vendor/codemirror/comment',
                    'vendor/codemirror/lint',
                    'vendor/codemirror/active-line',
                    'vendor/codemirror/overlay',
                    'vendor/codemirror/xml',
                    'vendor/codemirror/css',
                    'vendor/codemirror/javascript',
                    'vendor/codemirror/htmlmixed',
                    'ee-codemirror-mode',
                    'vendor/codemirror/dialog',
                    'vendor/codemirror/searchcursor',
                    'vendor/codemirror/search',
                )
            )
        );
        ee()->javascript->output("$('textarea[name=\"" . $selector . "\"]').toggleCodeMirror();");
    }

    /**
     *  Returns installed module information for CodeMirror linting
     */
    private function _get_installed_plugins_and_modules()
    {
        $addons = array_keys(ee('Addon')->all());

        $modules = ee('Model')->get('Module')->all()->pluck('module_name');
        $plugins = ee('Model')->get('Plugin')->all()->pluck('plugin_package');

        $modules = array_map('strtolower', $modules);
        $plugins = array_map('strtolower', $plugins);
        $installed = array_merge($modules, $plugins);

        return array(
            'available' => $installed,
            'not_installed' => array_values(array_diff($addons, $installed))
        );
    }

    /**
     * Export templates
     *
     * @return void
     */
    protected function exportTemplates()
    {
        $templates = ee('Model')->get('Template')->all();

        if (! $templates) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('error_export'))
                ->addToBody(lang('error_export_no_templates'))
                ->now();

            return;
        }

        // Create the Zip Archive
        $zipfilename = tempnam(sys_get_temp_dir(), '');
        $zip = new ZipArchive();
        if ($zip->open($zipfilename, ZipArchive::OVERWRITE) !== true) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('error_export'))
                ->addToBody(lang('error_cannot_create_zip'))
                ->now();

            return;
        }

        $sites = ee('Model')->get('Site')
            ->fields('site_id', 'site_name')
            ->all()
            ->getDictionary('site_id', 'site_name');

        // Loop through templates and add them to the zip
        $templates->each(function ($template) use ($zip, $sites) {
            $filename = // site_short_name/template_group.group/template.ext
                    $sites[$template->site_id] . '/' .
                    $template->TemplateGroup->group_name . '.group/' .
                    $template->template_name . $template->getFileExtension();
            $zip->addFromString($filename, $template->template_data);
        });

        // and now partials
        $partials = ee('Model')->make('Snippet')->loadAllInstallWide();
        $partials->each(function ($partial) use ($zip, $sites) {
            $folder = ($partial->site_id) ? $sites[$partial->site_id] . '/_partials/' : '_global_partials/';
            $filename = $folder . $partial->snippet_name . '.html';
            $zip->addFromString($filename, $partial->snippet_contents);
        });

        // and now venerable variables
        $variables = ee('Model')->make('GlobalVariable')->loadAllInstallWide();
        $variables->each(function ($variable) use ($zip, $sites) {
            $folder = ($variable->site_id) ? $sites[$variable->site_id] . '/_variables/' : '_global_variables/';
            $filename = $folder . $variable->variable_name . '.html';
            $zip->addFromString($filename, $variable->variable_data);
        });

        $zip->close();

        $data = file_get_contents($zipfilename);
        unlink($zipfilename);

        ee()->load->helper('download');
        force_download('ExpressionEngine-templates.zip', $data);
    }

    protected function buildTableFromTemplateQueryBuilder(QueryBuilder $templates, $include_group_name = false)
    {
        $table = ee('CP/Table', array('autosort' => false));
        $total = $templates->count();
        $vars = array();

        $columns = array(
            'template' => array(
                'encode' => false
            ),
            'type' => array(
                'encode' => false
            ),
        );

        if (bool_config_item('enable_hit_tracking')) {
            $columns[] = 'hits';
        }

        $columns['manage'] = array(
            'type' => Table::COL_TOOLBAR
        );
        $columns[] = array(
            'type' => Table::COL_CHECKBOX
        );

        $table->setColumns($columns);
        $table->setNoResultsText('no_templates_found');

        $data = array();

        $template_id = ee()->session->flashdata('template_id');

        $hidden_indicator = (ee()->config->item('hidden_template_indicator') != '') ? ee()->config->item('hidden_template_indicator') : '_';
        $hidden_indicator_length = strlen($hidden_indicator);

        $filters = ee('CP/Filter')
            ->add('Perpage', $total, 'show_all_templates');

        // Before pagination so perpage is set correctly
        $this->renderFilters($filters);

        $sort_col = $table->sort_col;

        $sort_map = [
            'template' => 'template_name',
            'type' => 'template_type',
            'hits' => 'hits', // if they have enabled hit tracking
        ];

        if (! array_key_exists($sort_col, $sort_map)) {
            throw new \Exception("Invalid sort column: " . htmlentities($sort_col));
        }

        $template_data = $templates->order($sort_map[$sort_col], $table->sort_dir)
            ->limit($this->perpage)
            ->offset($this->offset)
            ->all();

        foreach ($template_data as $template) {
            $group = $template->getTemplateGroup();
            $template_name = htmlentities($template->template_name, ENT_QUOTES, 'UTF-8');
            $edit_url = ee('CP/URL')->make('design/template/edit/' . $template->template_id);
            $edit_url = ee('CP/URL', 'design/template/edit/' . $template->template_id);

            if ($include_group_name) {
                $template_name = $group->group_name . '/' . $template_name;
            }

            if (ee('Permission')->can('edit_templates_template_group_id_' . $group->getId())) {
                $template_name = '<a href="' . $edit_url->compile() . '">' . $template_name . '</a>';
            }

            if (ee('Config')->getFile()->getBoolean('allow_php') && $template->allow_php == 'y') {
                $template_name = '<i class="fab fa-php fa-lg icon-left" title="' . lang('enable_php') . '"></i>' . $template_name;
            }

            if ($template->enable_http_auth == 'y') {
                $template_name = '<i class="fal fa-key fa-sm icon-left" title="' . lang('http_auth_protected') . '"></i>' . $template_name;
            }

            if (strncmp($template->template_name, $hidden_indicator, $hidden_indicator_length) == 0) {
                $template_name = '<i class="fal fa-sm fa-eye-slash icon-left"></i>' . $template_name;
            }

            if ($template->template_name == 'index') {
                $template_name = '<i class="fal fa-home fa-sm icon-left"></i>' . $template_name;
            }

            $view_url = ee()->functions->fetch_site_index();
            $view_url = rtrim($view_url, '/') . '/';

            if ($template->template_type == 'css') {
                $view_url .= QUERY_MARKER . 'css=' . $group->group_name . '/' . $template->template_name;
            } else {
                $view_url .= $group->group_name . (($template->template_name == 'index') ? '' : '/' . $template->template_name);
            }

            $type_col = $template->template_type;
            if (in_array($template->template_type, array('webpage', 'feed', 'css', 'js', 'static', 'xml'))) {
                $type_col = lang($template->template_type . '_type_col');
            }

            $toolbar = array(
                'view' => array(
                    'href' => ee()->cp->masked_url($view_url),
                    'title' => lang('view'),
                    'rel' => 'external'
                ),
                'edit' => array(
                    'href' => $edit_url,
                    'title' => lang('edit')
                ),
                'settings' => array(
                    'href' => $edit_url . '#tab=t-2',
                    'class' => 'm-link',
                    'title' => lang('settings'),
                    'data-template-id' => $template->template_id,
                )
            );

            if (! ee('Permission')->can('edit_templates_template_group_id_' . $group->getId())) {
                unset($toolbar['edit']);
            }

            if (! ee('Permission')->can('manage_settings_template_group_id_' . $group->getId())) {
                unset($toolbar['settings']);
            }

            $column = array(
                $template_name,
                '<span class="st-info">' . $type_col . '</span>'
            );

            if (bool_config_item('enable_hit_tracking')) {
                $column[] = $template->hits;
            }

            $column[] = array('toolbar_items' => $toolbar);
            $column[] = array(
                'name' => 'selection[]',
                'value' => $template->template_id,
                'disabled' => (bool_config_item('save_tmpl_files') && $template->template_name == 'index') ? true : false,
                'data' => array(
                    'confirm' => lang('template') . ': <b>' . htmlentities($template->template_name, ENT_QUOTES, 'UTF-8') . '</b>'
                )
            );

            $attrs = array();

            if ($template_id && $template->template_id == $template_id) {
                $attrs = array('class' => 'selected');
            }

            $data[] = array(
                'attrs' => $attrs,
                'columns' => $column
            );
        }

        $table->setData($data);

        $vars['table'] = $table->viewData($this->base_url);
        $vars['form_url'] = $this->base_url;
        $vars['total'] = $total;

        if (! empty($vars['table']['data'])) {
            // Paginate!
            $vars['pagination'] = ee('CP/Pagination', $total)
                ->perPage($this->perpage)
                ->currentPage($this->page)
                ->render($this->base_url);
        }

        return $vars;
    }

    protected function removeTemplates($template_ids)
    {
        $group_ids = [];
        $authorized = false;

        foreach ($this->assigned_template_groups as $group_id) {
            if (ee('Permission')->can('delete_templates_template_group_id_' . $group_id)) {
                $authorized = true;
                $group_ids[] = $group_id;
            }
        }

        if (! $authorized) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (! is_array($template_ids)) {
            $template_ids = array($template_ids);
        }

        $template_names = array();
        $templates = ee('Model')->get('Template', $template_ids)
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('group_id', 'IN', $group_ids);

        if (empty($group_ids)) {
            $templates->markAsFutile();
        }

        $templates = $templates->all();

        foreach ($templates as $template) {
            $template_names[] = $template->getTemplateGroup()->group_name . '/' . $template->template_name;
        }

        $templates->delete();

        ee('CP/Alert')->makeInline('shared-form')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(lang('templates_deleted_desc'))
            ->addToBody($template_names)
            ->defer();
    }
}

// EOF
