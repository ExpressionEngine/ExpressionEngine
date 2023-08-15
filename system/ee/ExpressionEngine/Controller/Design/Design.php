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

use ZipArchive;
use ExpressionEngine\Library\CP\Table;

use ExpressionEngine\Library\Data\Collection;
use ExpressionEngine\Controller\Design\AbstractDesign as AbstractDesignController;
use ExpressionEngine\Library\Template\Template;

/**
 * Design Controller
 */
class Design extends AbstractDesignController
{
    public function index()
    {
        $this->manager();
    }

    public function export()
    {
        if (! ee('Permission')->isSuperAdmin()) {
            show_error(lang('unauthorized_access'));
        }

        $this->exportTemplates();
    }

    public function manager($group_name = null)
    {
        $assigned_groups = null;

        ee()->load->library('template');
        ee()->template->sync_from_files();

        if (! ee('Permission')->isSuperAdmin()) {
            $assigned_groups = array_keys(ee()->session->userdata['assigned_template_groups']);

            if (empty($assigned_groups)) {
                if (ee('Permission')->can('admin_design')) {
                    ee()->functions->redirect(ee('CP/URL')->make('design/system'));
                } elseif (ee('Permission')->hasAny('can_create_template_partials', 'can_edit_template_partials', 'can_delete_template_partials')) {
                    ee()->functions->redirect(ee('CP/URL')->make('design/snippets'));
                } elseif (ee('Permission')->hasAny('can_create_template_variables', 'can_edit_template_variables', 'can_delete_template_variables')) {
                    ee()->functions->redirect(ee('CP/URL')->make('design/variables'));
                } elseif (ee('Config')->getFile()->getBoolean('legacy_member_templates') && ee('Permission')->can('admin_mbr_templates')) {
                    ee()->functions->redirect(ee('CP/URL')->make('design/members'));
                } else {
                    show_error(lang('unauthorized_access'));
                }
            }
        }

        if (is_null($group_name)) {
            $group = $this->getAssignedTemplateGroup(null, true);

            if (! $group) {
                $group = $this->getAssignedTemplateGroup();
            }

            if (! $group) {
                ee()->functions->redirect(ee('CP/URL')->make('design/system'));
            }
        } else {
            $group = $this->getAssignedTemplateGroup($group_name);

            if (! $group) {
                $group_name = str_replace('_', '.', $group_name);
                $group = $this->getAssignedTemplateGroup($group_name);

                if (! $group) {
                    show_error(sprintf(lang('error_no_template_group'), $group_name));
                }
            }
        }

        if (ee()->input->post('bulk_action') == 'remove') {
            if (ee('Permission')->can('delete_templates_template_group_id_' . $group->getId())) {
                $this->removeTemplates(ee()->input->post('selection'));
                ee()->functions->redirect(ee('CP/URL')->make('design/manager/' . $group_name, ee()->cp->get_url_state()));
            } else {
                show_error(lang('unauthorized_access'), 403);
            }
        } elseif (ee()->input->post('bulk_action') == 'export') {
            $this->export(ee()->input->post('selection'));
        }

        $base_url = ee('CP/URL')->make('design/manager/' . $group->group_name);
        $this->base_url = $base_url;

        $templates = ee('Model')->get('Template')->with('TemplateGroup')->filter('group_id', $group->group_id)->filter('site_id', ee()->config->item('site_id'));

        $vars = $this->buildTableFromTemplateQueryBuilder($templates);

        $vars['show_new_template_button'] = ee('Permission')->can('create_templates_template_group_id_' . $group->getId());
        $vars['show_bulk_delete'] = ee('Permission')->can('delete_templates_template_group_id_' . $group->getId());
        $vars['group_id'] = $group->group_name;

        ee()->javascript->set_global('template_settings_url', ee('CP/URL')->make('design/template/settings/###')->compile());
        ee()->javascript->set_global('templage_groups_reorder_url', ee('CP/URL')->make('design/reorder-groups')->compile());
        ee()->javascript->set_global('lang.remove_confirm', lang('template') . ': <b>### ' . lang('templates') . '</b>');
        ee()->cp->add_js_script(array(
            'plugin' => 'ui.touch.punch',
            'file' => array(
                'cp/confirm_remove',
                'cp/design/manager'
            ),
        ));

        $this->generateSidebar($group->group_id);
        $this->stdHeader();
        ee()->view->cp_page_title = lang('template_manager');
        ee()->view->cp_heading = sprintf(lang('templates_in_group'), $group->group_name);

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('design')->compile() => lang('templates'),
            '' => $group->group_name
        );

        ee()->cp->render('design/index', $vars);
    }

    private function getAssignedTemplateGroup($group_name = null, $site_default = false)
    {
        $assigned_groups = null;

        if (ee()->session->userdata['group_id'] != 1) {
            $assigned_groups = array_keys(ee()->session->userdata['assigned_template_groups']);

            if (empty($assigned_groups)) {
                ee()->functions->redirect(ee('CP/URL')->make('design/system'));
            }
        }

        $group = ee('Model')->get('TemplateGroup')
            ->fields('group_id', 'group_name')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('group_name', 'asc')
            ->order('group_id', 'asc'); //get the older group

        if ($group_name) {
            $group->filter('group_name', $group_name);
        }

        if ($site_default) {
            $group->filter('is_site_default', 'y');
        }

        if ($assigned_groups) {
            $group->filter('group_id', 'IN', $assigned_groups);
        }

        return $group->first();
    }

    /**
     * AJAX end-point for template group reordering
     */
    public function reorderGroups()
    {
        if (! ($group_names = ee()->input->post('groups'))
            or ! AJAX_REQUEST
            or ! ee('Permission')->can('edit_template_groups')) {
            return;
        }

        $groups = ee('Model')->get('TemplateGroup')
            ->filter('site_id', ee()->config->item('site_id'))
            ->order('group_name', 'asc')
            ->all();

        $groups_indexed = $groups->indexBy('group_name');

        $i = 1;
        foreach ($group_names as $name) {
            $groups_indexed[$name]->group_order = $i;
            $i++;
        }

        $groups->save();

        return array('success');
    }
}

// EOF
