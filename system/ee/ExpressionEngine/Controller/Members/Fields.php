<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Members;

use CP_Controller;
use ExpressionEngine\Library\CP;
use ExpressionEngine\Library\CP\Table;
use ExpressionEngine\Service\Filter\FilterFactory;
use ExpressionEngine\Controller\Members;

/**
 * Member Fields Controller
 */
class Fields extends Members\Members
{
    protected $base_url;
    protected $perpage;
    protected $page = 1;
    protected $offset = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! ee('Permission')->can('admin_roles')) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('members');
        ee()->lang->loadfile('channel');
        $this->base_url = ee('CP/URL')->make('members/fields');
        $this->generateSidebar('fields');
    }

    /**
     * Field List Index
     */
    public function index()
    {
        $sort_col = ee()->input->get('sort_col');
        switch ($sort_col) {
            case 'id':
                $sort_col = 'm_field_id';

                break;

            case 'label':
                $sort_col = 'm_field_label';

                break;

            case 'snort_name':
                $sort_col = 'm_field_name';

                break;

            case 'type':
                $sort_col = 'm_field_type';

                break;

            default:
                $sort_col = 'm_field_order';
        }

        $sort_dir = ee()->input->get('sort_dir') ?: 'asc';

        $table = ee('CP/Table', array(
            'sort_col' => $sort_col,
            'sort_dir' => $sort_dir,
            'limit' => $this->perpage,
            'reorder' => true,
            'save' => ee('CP/URL')->make("members/fields/order")
        ));

        $table->setColumns(
            array(
                'id' => array(
                    'encode' => false
                ),
                'label',
                'short_name' => array(
                    'encode' => false
                ),
                'type',
                'manage' => array(
                    'type' => Table::COL_TOOLBAR
                ),
                array(
                    'type' => Table::COL_CHECKBOX
                )
            )
        );

        $table->setNoResultsText(
            sprintf(lang('no_found'), lang('custom_member_fields')),
            'add_new',
            ee('CP/URL')->make('members/fields/create')
        );

        $data = array();
        $fieldData = array();

        $total = ee('Model')->get('MemberField')->count();

        $filter = ee('CP/Filter')
            ->add('Keyword')
            ->add('Perpage', $total, 'show_all_member_fields');

        $this->renderFilters($filter);

        $fields = ee('Model')->get('MemberField')
            ->order($sort_col, $sort_dir)
            ->limit($this->perpage)
            ->offset($this->offset);

        if (isset($this->params['filter_by_keyword'])) {
            $fields->search(['m_field_name', 'm_field_label'], $this->params['filter_by_keyword']);
        }

        $fields = $fields->all();

        $type_map = array(
            'text' => lang('text_input'),
            'textarea' => lang('textarea'),
            'select' => lang('select_dropdown'),
            'date' => lang('date'),
            'url' => lang('url')
        );

        foreach ($fields as $field) {
            $edit_url = ee('CP/URL')->make('members/fields/edit/' . $field->m_field_id);
            $toolbar = array('toolbar_items' => array(
                'edit' => array(
                    'href' => $edit_url,
                    'title' => strtolower(lang('edit'))
                )
            ));

            $columns = array(
                'id' => $field->getId() . form_hidden('order[]', $field->getId()),
                'm_field_label' => array(
                    'content' => $field->m_field_label,
                    'href' => $edit_url
                ),
                'm_field_name' => "<var>{{$field->m_field_name}}</var>",
                'm_field_type' => $type_map[$field->m_field_type],
                $toolbar,
                array(
                    'name' => 'selection[]',
                    'value' => $field->m_field_id,
                    'data' => array(
                        'confirm' => lang('field') . ': <b>' . htmlentities($field->m_field_name, ENT_QUOTES, 'UTF-8') . '</b>'
                    )
                )
            );

            $attrs = array();

            if (ee()->session->flashdata('field_id') == $field->getId()) {
                $attrs = array('class' => 'selected');
            }

            $fieldData[] = array(
                'attrs' => $attrs,
                'columns' => $columns
            );
        }

        $table->setData($fieldData);
        $data['table'] = $table->viewData($this->base_url);
        $data['form_url'] = ee('CP/URL')->make('members/fields/delete');
        $data['new'] = ee('CP/URL')->make('members/fields/create');
        $base_url = $data['table']['base_url'];

        if (! empty($data['table']['data'])) {
            $data['pagination'] = ee('CP/Pagination', $total)
                ->perPage($this->perpage)
                ->currentPage($this->page)
                ->render($this->base_url);
        }

        ee()->javascript->set_global('lang.remove_confirm', lang('custom_member_fields') . ': <b>### ' . lang('custom_member_fields') . '</b>');
        ee()->cp->add_js_script('file', 'cp/confirm_remove');
        ee()->cp->add_js_script('file', 'cp/members/member_field_reorder');
        ee()->cp->add_js_script('plugin', 'ee_table_reorder');

        $reorder_ajax_fail = ee('CP/Alert')->makeBanner('reorder-ajax-fail')
            ->asIssue()
            ->canClose()
            ->withTitle(lang('member_field_ajax_reorder_fail'))
            ->addToBody(lang('member_field_ajax_reorder_fail_desc'));

        ee()->javascript->set_global('member_fields.reorder_url', ee('CP/URL')->make('members/fields/order/')->compile());
        ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

        ee()->view->base_url = $this->base_url;
        ee()->view->ajax_validate = true;
        ee()->view->cp_page_title = lang('custom_profile_fields');

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            '' => lang('custom_member_fields')
        );

        ee()->cp->render('members/custom_profile_fields', $data);
    }

    public function create()
    {
        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            ee('CP/URL')->make('members/fields')->compile() => lang('custom_member_fields'),
            '' => lang('create_member_field')
        );

        $this->form();
    }

    public function edit($id)
    {
        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('members')->compile() => lang('members'),
            ee('CP/URL')->make('members/fields')->compile() => lang('custom_member_fields'),
            '' => lang('edit_member_field')
        );

        $this->form($id);
    }

    public function delete()
    {
        $field_ids = ee()->input->post('selection');

        if (! is_array($field_ids)) {
            $field_ids = array($selected);
        }

        $fields = ee('Model')->get('MemberField', $field_ids)->all();
        $field_names = $fields->pluck('field_label');
        $field_names = array_map(function ($field_name) {
            return htmlentities($field_name, ENT_QUOTES, 'UTF-8');
        }, $field_names);

        $fields->delete();

        ee('CP/Alert')->makeInline('fields')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(lang('member_fields_deleted_desc'))
            ->addToBody($field_names)
            ->defer();

        ee()->functions->redirect($this->base_url);
    }

    public function order()
    {
        // Parse out the serialized inputs sent by the JavaScript
        $new_order = array();
        parse_str(ee()->input->post('order'), $new_order);

        if (! AJAX_REQUEST or empty($new_order['order'])) {
            show_error(lang('unauthorized_access'), 403);
        }

        $fields = ee('Model')->get('MemberField')->order('m_field_order', 'asc')->all()->indexBy('m_field_id');

        $order = 1;
        foreach ($new_order['order'] as $field_id) {
            if (isset($fields[$field_id]) && $fields[$field_id]->m_field_order != $order) {
                $fields[$field_id]->m_field_order = $order;
                $fields[$field_id]->save();
            }

            $order++;
        }

        ee()->output->send_ajax_response(null);
        exit;
    }

    private function form($field_id = null)
    {
        $fieldtype_choices = [
            'date' => lang('date'),
            'text' => lang('text_input'),
            'textarea' => lang('textarea'),
            'select' => lang('select_dropdown'),
            'url' => lang('url'),
        ];

        if ($field_id) {
            $field = ee('Model')->get('MemberField', array($field_id))->first();

            $fieldtype_choices = array_intersect_key($fieldtype_choices, $field->getCompatibleFieldtypes());

            ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('field'));
            ee()->view->cp_page_title = lang('edit_member_field');
            ee()->view->base_url = ee('CP/URL')->make('members/fields/edit/' . $field_id);
        } else {
            // Only auto-complete field short name for new fields
            ee()->cp->add_js_script('plugin', 'ee_url_title');
            ee()->javascript->output('
				$("input[name=m_field_label]").bind("keyup keydown", function() {
					$(this).ee_url_title("input[name=m_field_name]");
				});
			');

            $field = ee('Model')->make('MemberField');
            $field->field_type = 'text';

            ee()->view->save_btn_text = sprintf(lang('btn_save'), lang('field'));
            ee()->view->cp_page_title = lang('create_member_field');
            ee()->view->base_url = ee('CP/URL')->make('members/fields/create');
        }

        if (! $field) {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->lang->loadfile('admin_content');

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'type',
                    'desc' => '',
                    'fields' => array(
                        'm_field_type' => array(
                            'type' => 'dropdown',
                            'choices' => $fieldtype_choices,
                            'group_toggle' => array(
                                'date' => 'date',
                                'text' => 'text',
                                'textarea' => 'textarea',
                                'select' => 'select',
                                'url' => 'url'
                            ),
                            'value' => $field->field_type
                        )
                    )
                ),
                array(
                    'title' => 'name',
                    'fields' => array(
                        'm_field_label' => array(
                            'type' => 'text',
                            'value' => $field->field_label,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'short_name',
                    'desc' => 'alphadash_desc',
                    'fields' => array(
                        'm_field_name' => array(
                            'type' => 'text',
                            'value' => $field->field_name,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'field_description',
                    'desc' => 'field_description_info',
                    'fields' => array(
                        'm_field_description' => array(
                            'type' => 'textarea',
                            'value' => $field->field_description
                        )
                    )
                ),
                array(
                    'title' => 'require_field',
                    'desc' => 'cat_require_field_desc',
                    'fields' => array(
                        'm_field_required' => array(
                            'type' => 'yes_no',
                            'value' => $field->field_required
                        )
                    )
                ),
                array(
                    'title' => 'exclude_from_anonymization',
                    'desc' => 'exclude_from_anonymization_desc',
                    'fields' => array(
                        'm_field_exclude_from_anon' => array(
                            'type' => 'yes_no',
                            'value' => $field->field_exclude_from_anon
                        )
                    )
                )
            ),
            'visibility' => array(
                array(
                    'title' => 'is_field_reg',
                    'desc' => 'is_field_reg_cont',
                    'fields' => array(
                        'm_field_reg' => array(
                            'type' => 'yes_no',
                            'value' => $field->field_reg
                        )
                    )
                ),
                array(
                    'title' => 'is_field_public',
                    'desc' => 'is_field_public_cont',
                    'fields' => array(
                        'm_field_public' => array(
                            'type' => 'yes_no',
                            'value' => $field->field_public
                        )
                    )
                )
            )
        );

        $vars['sections'] = array_merge($vars['sections'], $field->getSettingsForm());

        // These are currently the only fieldtypes we allow; get their settings forms
        foreach (array_keys($fieldtype_choices) as $fieldtype) {
            if ($field->field_type != $fieldtype) {
                $dummy_field = ee('Model')->make('MemberField');
                $dummy_field->field_type = $fieldtype;
                $vars['sections'] = array_merge($vars['sections'], $dummy_field->getSettingsForm());
            }
        }

        if (! empty($_POST)) {
            $field->set($_POST);

            // m_ prefix dance
            foreach ($_POST as $key => $value) {
                if ($field->hasProperty($key) or $field->hasProperty('m_' . $key)) {
                    $field->$key = $value;
                }
            }

            $result = $field->validate();

            if (AJAX_REQUEST) {
                $field = ee()->input->post('ee_fv_field');

                if ($result->hasErrors($field)) {
                    ee()->output->send_ajax_response(array('error' => $result->renderError($field)));
                } else {
                    ee()->output->send_ajax_response(['success']);
                }
                exit;
            }

            if ($result->isValid()) {
                $field->save();
                ee()->session->set_flashdata('field_id', $field->field_id);

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('member_field_saved'))
                    ->addToBody(lang('member_field_saved_desc'))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL')->make('/members/fields'));
            } else {
                ee()->load->library('form_validation');
                ee()->form_validation->_error_array = $result->renderErrors();
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('member_field_not_saved'))
                    ->addToBody(lang('member_field_not_saved_desc'))
                    ->now();
            }
        }

        ee()->view->ajax_validate = true;
        ee()->view->save_btn_text_working = 'btn_saving';

        ee()->javascript->output('$(document).ready(function () {
			EE.cp.fieldToggleDisable(null, "m_field_type");
		});');

        ee()->cp->render('settings/form', $vars);
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
}
// END CLASS

// EOF
