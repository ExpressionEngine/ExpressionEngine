<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Controller\Categories;

use ExpressionEngine\Library\CP;
use ExpressionEngine\Controller\Categories\AbstractCategories as AbstractCategoriesController;
use ExpressionEngine\Addons\FilePicker\FilePicker as FilePicker;
use ExpressionEngine\Model\Content\FieldFacade as FieldFacade;

/**
 * Categories Controller
 */
class Categories extends AbstractCategoriesController
{
    private $new_order_reference = array();

    public function index()
    {
        ee()->session->benjaminButtonFlashdata();

        $cat_group = ee('Model')->get('CategoryGroup')
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if ($cat_group) {
            ee()->functions->redirect(ee('CP/URL')->make('categories/group/' . $cat_group->getId()));
        }

        ee()->functions->redirect(ee('CP/URL')->make('categories/group'));
    }

    /**
     * Category listing
     */
    public function group($group_id = null)
    {
        $cat_group = is_numeric($group_id)
            ? ee('Model')->get('CategoryGroup', $group_id)
            : ee('Model')->get('CategoryGroup');
        $cat_group = $cat_group
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        if (! $cat_group) {
            ee()->view->cp_page_title = lang('categories');

            $vars = [
                'no_results' => [
                    'text' => sprintf(lang('no_found'), lang('category_groups'))
                        . ' <a href="' . ee('CP/URL', 'categories/groups/create') . '">' . lang('add_new') . '</a>'
                ],
                'channel_id' => ''
            ];

            return ee()->cp->render('channels/layout/index', $vars);
        }

        $this->generateSidebar();

        ee()->cp->add_js_script('plugin', 'nestable');

        if (ee('Permission')->can('edit_categories')) {
            ee()->cp->add_js_script('file', 'cp/channel/category_reorder');
        }

        // Get the category tree with a single query
        ee()->load->library('datastructures/tree');
        ee()->view->categories = $cat_group->getCategoryTree(ee()->tree);

        ee()->view->base_url = $cat_group->group_name . ' &mdash; ' . lang('categories');
        ee()->view->cp_page_title = $cat_group->group_name . ' &mdash; ' . lang('categories');
        ee()->view->cat_group = $cat_group;

        ee()->javascript->set_global('lang.remove_confirm', lang('categories') . ': <b>### ' . lang('categories') . '</b>');
        ee()->cp->add_js_script('file', 'cp/confirm_remove');

        $reorder_ajax_fail = ee('CP/Alert')->makeBanner('reorder-ajax-fail')
            ->asIssue()
            ->canClose()
            ->withTitle(lang('category_ajax_reorder_fail'))
            ->addToBody(lang('category_ajax_reorder_fail_desc'));

        ee()->javascript->set_global('cat.reorder_url', ee('CP/URL')->make('categories/reorder/' . $group_id)->compile());
        ee()->javascript->set_global('alert.reorder_ajax_fail', $reorder_ajax_fail->render());

        $data = array(
            'can_create_categories' => ee('Permission')->can('create_categories'),
            'can_edit_categories' => ee('Permission')->can('edit_categories'),
            'can_delete_categories' => ee('Permission')->can('delete_categories')
        );

        $can_edit = explode('|', rtrim((string) $cat_group->can_edit_categories, '|'));
        if ($data['can_edit_categories'] === true && ! ee('Permission')->isSuperAdmin() and ! ee('Permission')->hasAnyRole($can_edit)) {
            $data['can_edit_categories'] = false;
        }
        $can_delete = explode('|', rtrim((string) $cat_group->can_delete_categories, '|'));
        if ($data['can_delete_categories'] === true && ! ee('Permission')->isSuperAdmin() and ! ee('Permission')->hasAnyRole($can_delete)) {
            $data['can_delete_categories'] = false;
        }

        ee()->view->cp_breadcrumbs = array(
            '' => lang('categories'),
            //'' => $cat_group->group_name
        );

        ee()->cp->render('channels/cat/list', $data);
    }

    /**
     * AJAX end point for reordering categories on catList page
     */
    public function reorder($group_id)
    {
        if (! ee('Permission')->can('edit_categories')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $cat_group = ee('Model')->get('CategoryGroup')
            ->filter('group_id', $group_id)
            ->first();

        $cat_group->sort_order = 'c';
        $cat_group->save();

        $new_order = ee()->input->post('order');

        if (! AJAX_REQUEST or ! $cat_group or empty($new_order)) {
            show_error(lang('unauthorized_access'), 403);
        }

        // Create a flattened array based on the JSON response
        // from Nestable; we basically want to mirror the data
        // format we have in the database for easy comparison
        $order = 1;
        foreach ($new_order as $category) {
            $this->flattenCategoryTree($category, 0, $order);
            $order++;
        }

        // Compare all categories to what we got back from
        // Nestable to see if any parent IDs or orderings
        // changed; if so, ONLY update those categories
        foreach ($cat_group->getCategories() as $category) {
            $new_category = $this->new_order_reference[$category->cat_id];

            if (
                $category->parent_id != $new_category['parent_id'] or
                $category->cat_order != $new_category['order']
            ) {
                $category->parent_id = $new_category['parent_id'];
                $category->cat_order = $new_category['order'];
                $category->save();
            }
        }

        ee()->output->send_ajax_response(null);
        exit;
    }

    /**
     * Recursive function to flatten the category tree we get back
     * from the Nestable jQuery plugin
     */
    private function flattenCategoryTree($category, $parent_id, $order)
    {
        $this->new_order_reference[$category['id']] = array(
            'parent_id' => $parent_id,
            'order' => $order
        );

        // Has children? Flatten them to same array
        if (isset($category['children'])) {
            $order = 1;
            foreach ($category['children'] as $child) {
                $this->flattenCategoryTree($child, $category['id'], $order);
                $order++;
            }
        }
    }

    /**
     * Category removal handler
     */
    public function remove()
    {
        if (! ee('Permission')->can('delete_categories')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $cat_ids = ee()->input->post('categories');

        if (! empty($cat_ids) && ee()->input->post('bulk_action') == 'remove') {
            // Filter out junk
            $cat_ids = array_filter($cat_ids, 'is_numeric');

            if (! empty($cat_ids)) {
                $cats = ee('Model')->get('Category')
                    ->filter('cat_id', 'IN', $cat_ids);

                // Grab the group ID for the possible AJAX return below
                $cat_group = ee('Model')->get('Category', $cat_ids[0])->first()->CategoryGroup;
                $can_delete = explode('|', rtrim((string) $cat_group->can_delete_categories, '|'));
                if (! ee('Permission')->isSuperAdmin() and ! ee('Permission')->hasAnyRole($can_delete)) {
                    show_error(lang('unauthorized_access'), 403);
                }

                $cats->delete();

                if (! AJAX_REQUEST) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('categories_deleted'))
                        ->addToBody(sprintf(lang('categories_deleted_desc'), count($cat_ids)))
                        ->defer();
                }
            }
        } else {
            show_error(lang('unauthorized_access'), 403);
        }

        ee()->functions->redirect(
            ee('CP/URL')->make('categories/group/' . ee()->input->post('cat_group_id'))
        );
    }

    /**
     * Category removal handler
     */
    public function removeSingle()
    {
        if (! ee('Permission')->can('delete_categories')) {
            show_error(lang('unauthorized_access'), 403);
        }

        $cat_id = ee('Request')->post('content_id');

        if (! empty($cat_id)) {
            ee('Model')->get('Category', $cat_id)->delete();
        }

        return ['success'];
    }

    /**
     * Category create
     *
     * @param   int $group_id   ID of category group category is to be in
     */
    public function create($group_id)
    {
        if (! ee('Permission')->can('create_categories')) {
            show_error(lang('unauthorized_access'), 403);
        }

        return $this->categoryForm($group_id, null);
    }

    /**
     * Category edit
     *
     * @param   int $group_id   ID of category group category is in
     * @param   int $category_id    ID of category to edit
     */
    public function edit($group_id, $category_id)
    {
        if (! ee('Permission')->can('edit_categories')) {
            show_error(lang('unauthorized_access'), 403);
        }

        return $this->categoryForm($group_id, $category_id, true);
    }

    /**
     * Category creation/edit form
     *
     * @param   int $group_id   ID of category group category is (to be) in
     * @param   int $category_id    ID of category to edit
     */
    private function categoryForm($group_id, $category_id = null)
    {
        if (empty($group_id) or ! is_numeric($group_id)) {
            show_error(lang('unauthorized_access'), 403);
        }

        $cat_group = ee('Model')->get('CategoryGroup')
            ->filter('group_id', $group_id)
            ->first();

        if (! $cat_group) {
            show_error(lang('unauthorized_access'), 403);
        }

        $this->generateSidebar($group_id);

        //  Check discrete privileges when editig (we have no discrete create
        //  permissions)
        $can_edit = explode('|', rtrim((string) $cat_group->can_edit_categories, '|'));

        if (! ee('Permission')->isSuperAdmin() and ! ee('Permission')->hasAnyRole($can_edit)) {
            show_error(lang('unauthorized_access'), 403);
        }

        if (is_null($category_id)) {
            $alert_key = 'created';
            ee()->view->cp_page_title = lang('new_category');
            $create_url = 'categories/create/' . $group_id;

            ee()->view->base_url = ee('CP/URL')->make($create_url);

            $category = ee('Model')->make('Category');
            $category->setCategoryGroup($cat_group);
            $category->site_id = ee()->config->item('site_id');

            // Only auto-complete channel short name for new channels
            ee()->cp->add_js_script('plugin', 'ee_url_title');

            // Create Foreign Character Conversion JS
            $foreign_characters = ee()->config->loadFile('foreign_chars');

            /* -------------------------------------
            /*  'foreign_character_conversion_array' hook.
            /*  - Allows you to use your own foreign character conversion array
            /*  - Added 1.6.0
            *   - Note: in 2.0, you can edit the foreign_chars.php config file as well
            */
            if (ee()->extensions->active_hook('foreign_character_conversion_array') === true) {
                $foreign_characters = ee()->extensions->call('foreign_character_conversion_array');
            }
            /*
            /* -------------------------------------*/

            ee()->javascript->set_global(array(
                'publish.foreignChars' => $foreign_characters,
                'publish.word_separator' => ee()->config->item('word_separator') != "dash" ? '_' : '-'
            ));

            ee()->javascript->output('
				$("input[name=cat_name]").bind("keyup keydown", function() {
					$(this).ee_url_title("input[name=cat_url_title]");
				});
			');
        } else {
            $category = ee('Model')->get('Category')->filter('cat_id', (int) $category_id)->first();

            if (! $category) {
                show_error(lang('unauthorized_access'), 403);
            }

            $alert_key = 'updated';
            ee()->view->cp_page_title = lang('edit_category');
            ee()->view->base_url = ee('CP/URL')->make('categories/edit/' . $group_id . '/' . $category_id);
        }

        ee()->load->library('file_field');

        $vars['sections'] = array(
            array(
                array(
                    'title' => 'name',
                    'desc' => '',
                    'fields' => array(
                        'cat_name' => array(
                            'type' => 'text',
                            'value' => $category->cat_name,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'url_title_lc',
                    'desc' => 'alphadash_desc',
                    'fields' => array(
                        'cat_url_title' => array(
                            'type' => 'text',
                            'value' => $category->cat_url_title,
                            'required' => true
                        )
                    )
                ),
                array(
                    'title' => 'description',
                    'fields' => array(
                        'cat_description' => array(
                            'type' => 'textarea',
                            'value' => $category->cat_description
                        )
                    )
                )
            )
        );

        if (! AJAX_REQUEST) {
            ee()->load->library('file_field');
            $vars['sections'][0][] = array(
                'title' => 'image',
                'fields' => array(
                    'cat_image' => array(
                        'type' => 'html',
                        'content' => ee()->file_field->dragAndDropField(
                            'cat_image',
                            $category->cat_image,
                            'all',
                            'image'
                        )
                    )
                )
            );
        }

        $parent_id_options = [0 => lang('none')] + $cat_group->buildCategoryOptionsTree();

        $disabled_choices = [];
        if (! $category->isNew()) {
            $disabled_choices = array_merge([$category->getId()], $category->getAllChildren()->getIds());
        }

        $vars['sections'][0][] = array(
            'title' => 'parent_category',
            'fields' => array(
                'parent_id' => array(
                    'type' => 'radio',
                    'value' => $category->parent_id === null ? 0 : $category->parent_id,
                    'choices' => $parent_id_options,
                    'disabled_choices' => $disabled_choices,
                    'no_results' => [
                        'text' => sprintf(lang('no_found'), lang('categories'))
                    ]
                )
            )
        );

        foreach ($category->getDisplay()->getFields() as $field) {
            $vars['sections']['custom_fields'][] = array(
                'title' => $field->getLabel(),
                'desc' => '',
                'fields' => array(
                    $field->getName() => array(
                        'type' => 'html',
                        'content' => $field->getForm(),
                        'required' => $field->isRequired(),
                    )
                )
            );
        }

        ee()->view->ajax_validate = true;

        if (! empty($_POST)) {
            if (defined('CLONING_MODE') && CLONING_MODE === true) {
                $word_separator = ee()->config->item('word_separator') != "dash" ? '_' : '-';
                $category->setId(null);
                while (true !== $category->validateUnique('cat_url_title', $_POST['cat_url_title'], ['group_id'])) {
                    $_POST['cat_url_title'] = 'copy' . $word_separator . $_POST['cat_url_title'];
                }
                if ($_POST['cat_name'] == $category->cat_name) {
                    $_POST['cat_name'] = lang('copy_of') . ' ' . $_POST['cat_name'];
                }
                $category->group_id = $group_id;
                $category->parent_id = ee('Security/XSS')->clean($_POST['parent_id']);
                $category->cat_order = null;
                $category->Children = null; // no deep cloning (yet)
                $category->markAsDirty();
            }
            $category->set($_POST);
            $category->parent_id = ee('Security/XSS')->clean($_POST['parent_id']);
            $result = $category->validate();

            if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result)) {
                return $response;
            }

            if ($result->isValid()) {
                $is_new = $category->isNew();
                $category = $category->save();

                if (AJAX_REQUEST) {
                    // Don't select category if editing
                    $save_id = $is_new ? $category->getId() : 0;

                    return ['saveId' => $save_id];
                }

                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('category_' . $alert_key))
                    ->addToBody(sprintf(lang('category_' . $alert_key . '_desc'), $category->cat_name))
                    ->defer();

                if (ee('Request')->post('submit') == 'save_and_new') {
                    ee()->functions->redirect(ee('CP/URL')->make('categories/create/' . $cat_group->group_id));
                } elseif (ee()->input->post('submit') == 'save_and_close') {
                    ee()->functions->redirect(ee('CP/URL')->make('categories/group/' . $cat_group->group_id));
                } else {
                    ee()->functions->redirect(ee('CP/URL')->make('categories/edit/' . $cat_group->group_id . '/' . $category->getId()));
                }
            } else {
                ee()->load->library('form_validation');
                ee()->form_validation->_error_array = $result->renderErrors();
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('category_not_' . $alert_key))
                    ->addToBody(lang('category_not_' . $alert_key . '_desc'))
                    ->now();
            }
        }

        $vars['buttons'] = [
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save',
                'text' => 'save',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_new',
                'text' => 'save_and_new',
                'working' => 'btn_saving'
            ],
            [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_and_close',
                'text' => 'save_and_close',
                'working' => 'btn_saving'
            ]
        ];

        if (! $category->isNew()) {
            $vars['buttons'][] = [
                'name' => 'submit',
                'type' => 'submit',
                'value' => 'save_as_new_entry',
                'text' => sprintf(lang('clone_to_new'), lang('category')),
                'working' => 'btn_saving'
            ];
        }

        if (AJAX_REQUEST) {
            return ee()->cp->render('_shared/form', $vars);
        }

        ee()->view->cp_breadcrumbs = array(
            ee('CP/URL')->make('categories')->compile() => lang('categories'),
            ee('CP/URL')->make('categories/group/' . $cat_group->group_id)->compile() => $cat_group->group_name,
            '' => is_null($category_id) ? lang('create') : lang('edit')
        );

        ee()->cp->render('settings/form', $vars);
    }

    /**
     * AJAX return body for adding a new category via the publish form; when a
     * new category is added, we have to refresh the category list
     *
     * @param   int $group_id   Category group ID
     * @param   int $channel_id   Channel ID
     */
    public function categoryGroupPublishField($group_id, $channel_id = null)
    {
        $group = ee('Model')->get('CategoryGroup', $group_id)->first();

        $entry = ee('Model')->make('ChannelEntry');
        $entry->Categories = null;

        // Initialize a new category group field so we can return its publish form
        $category_group_field = $group->getFieldMetadata();
        $category_group_field['categorized_object'] = $entry;

        $field_id = 'categories[cat_group_id_' . $group_id . ']';
        $field = new FieldFacade($field_id, $category_group_field);
        $field->setName($field_id);

        $group->populateCategories($field);

        $selected = ee('Request')->post('categories');

        // Reset the categories they already have selected
        if (!empty($selected)) {
            $selected_cats = ee('Model')->get('Category')
                ->fields('cat_id')
                ->filter('cat_id', 'IN', $selected['cat_group_id_' . $group_id])
                ->all();
            $field->setData(implode('|', $selected_cats->pluck('cat_id')));
        }
        $field->setItem('editing', true);

        if (!empty($channel_id)) {
            $categoryGroupSettings = ee('Model')
                ->get('CategoryGroupSettings')
                ->filter('channel_id', $channel_id)
                ->filter('group_id', $group_id)
                ->first();
            if (!is_null($categoryGroupSettings)) {
                if ($categoryGroupSettings->cat_required) {
                    $field->setItem('field_required', true);
                }
                if (!$categoryGroupSettings->cat_allow_multiple) {
                    $field->setItem('field_type', 'radio');
                }
            }
        }

        return $field->getForm();
    }
}

// EOF
