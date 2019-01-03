<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Settings;

use CP_Controller;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Model\Menu\MenuSet;

/**
 * Menu Manager Controller
 */
class MenuManager extends Settings {

	/**
	 * General Settings
	 */
	public function index()
	{
		$menu_sets = ee('Model')->get('MenuSet');
		$total_rows = $menu_sets->count();

		$table = $this->buildSetTable($menu_sets);

		$vars['table'] = $table->viewData(ee('CP/URL')->make('settings/menu-manager'));

		$vars['pagination'] = ee('CP/Pagination', $total_rows)
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->view->cp_page_title = lang('menu_sets');

		ee()->javascript->set_global('lang.remove_confirm', lang('menu_sets') . ': <b>### ' . lang('menu_sets') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		ee()->cp->render('settings/menu-manager/index', $vars);
	}

	/**
	 * Given a built query of menu sets, create a table listing an return
	 * the table instance.
	 *
	 * @param QueryBuilder $sets
	 * @return CP/Table
	 */
	protected function buildSetTable(Builder $sets)
	{
		$table = ee('CP/Table');

		$columns = [
			'set_name',
			'set_assigned' => [
				'sort' => FALSE
			],
			'manage' => [
				'type'	=> Table::COL_TOOLBAR
			],
			[
				'type'	=> Table::COL_CHECKBOX
			]
		];

		$table->setColumns($columns);

		if ($table->sort_col == 'set_name')
		{
			$sets->order('name', $table->sort_dir);
		}

		$sets = $sets->limit($table->config['limit'])
			->offset(($table->config['page'] - 1) * $table->config['limit'])
			->all();

		$data = array();

		foreach ($sets as $set)
		{
			$edit_url = ee('CP/URL')->make('settings/menu-manager/edit-set/'.$set->getId());

			$main_link = array(
				'content' => $set->name,
				'href' => $edit_url
			);

			$toolbar = array(
				'edit' => array(
					'href' => $edit_url,
					'title' => lang('edit')
				)
			);

			$checkbox = array(
				'name' => 'menu_sets[]',
				'value' => $set->getId(),
				'data'	=> array(
					'confirm' => lang('menu_set') . ': <b>' . htmlentities($set->name, ENT_QUOTES, 'UTF-8') . '</b>'
				)
			);

			if ($set->getId() == 1)
			{
				$checkbox['disabled'] = "disabled";
			}

			$assigned = $set->MemberGroups->filter('can_access_cp', TRUE)->pluck('group_title');

			$columns = array(
				$main_link,
				implode(', ', $assigned),
				array('toolbar_items' => $toolbar),
				$checkbox
			);

			$data[] = array(
				'attrs' => array(),
				'columns' => $columns
			);
		}

		$table->setData($data);

		return $table;
	}

	public function createSet()
	{
		return $this->form();
	}

	public function editSet($set_id)
	{
		return $this->form($set_id);
	}

	public function removeSet()
	{
		$set_ids = ee('Request')->post('menu_sets');

		ee('Model')->get('MenuSet', $set_ids)->delete();

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('menu_sets_removed'))
			->addToBody(sprintf(lang('menu_sets_removed_desc'), count($set_ids)))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make('settings/menu-manager'));
	}

	/**
	 * Show the create/edit form for a menu set.
	 */
	private function form($set_id = NULL)
	{
		if (is_null($set_id))
		{
			$alert_key = 'created';
			ee()->view->cp_page_title = lang('create_new_menu_set');
			ee()->view->base_url = ee('CP/URL')->make('settings/menu-manager/create-set/');
			$set = ee('Model')->make('MenuSet');
		}
		else
		{
			$set = ee('Model')->get('MenuSet')->filter('set_id', (int) $set_id)->first();

			if ( ! $set)
			{
				show_error(lang('unauthorized_access'), 403);
			}

			$alert_key = 'updated';
			ee()->view->cp_page_title = lang('edit_menu_set');
			ee()->view->base_url = ee('CP/URL')->make('settings/menu-manager/edit-set/'.$set_id);
		}

		if ( ! empty($_POST))
		{
			$set->set($_POST);

			$assigned = ee('Request')->post('member_groups');
			$set->MemberGroups = ee('Model')
				->get('MemberGroup', (array) $assigned)
				->filter('can_access_cp', 'y')
				->all();

			$sort = (array) ee('Request')->post('sort', array());
			$kids = ee('Model')->get('MenuItem', $sort)->all();

			if (count($sort))
			{
				$sort = array_flip($sort);

				foreach ($kids as $kid)
				{
					$kid->sort = $sort[$kid->getId()];
				}
			}

			$result = $set->validate();

			if ($response = $this->ajaxValidation($result))
			{
				return $response;
			}

			if ($result->isValid())
			{
				$set->save();
				$kids->save();

				ee('CP/Alert')->makeInline('shared-form')
					->asSuccess()
					->withTitle(lang('menu_set_'.$alert_key))
					->addToBody(sprintf(lang('menu_set_'.$alert_key.'_desc'), htmlentities($set->name)))
					->defer();
			}

			if (ee('Request')->post('submit') == 'save_and_new')
			{
				ee()->functions->redirect(ee('CP/URL')->make('settings/menu-manager/create-set'));
			}
			elseif (ee()->input->post('submit') == 'save_and_close')
			{
				ee()->functions->redirect(ee('CP/URL')->make('settings/menu-manager'));
			}
			else
			{
				ee()->functions->redirect(ee('CP/URL')->make('settings/menu-manager/edit-set/' . $set->getId()));
			}
		}

		$vars['sections'][] = $this->mainForm($set);

		if (isset($set_id))
		{
			$vars['sections']['menu_options'] = array(
				array(
					'title' => 'menu_items',
					'desc' => 'menu_items_desc',
					'button' => array(
						'text' => 'add_menu_item',
						'rel' => 'add_new'
					),
					'fields' => array(
						'menu_items' => array(
							'type' => 'html',
							'content' => $this->reorderList($set)
						)
					)
				)
			);;
		}

		$grid = ee('CP/GridInput', array(
			'field_name' => 'submenu',
			'reorder'    => TRUE
		));

		$grid->loadAssets();

		ee()->javascript->set_global([
			'menuSetsItem.createUrl' =>
			ee('CP/URL')->make('settings/menu-manager/create-item/'.$set_id)->compile(),
			'menuSetsItem.editUrl' =>
			ee('CP/URL')->make('settings/menu-manager/edit-item/'.$set_id.'/###/')->compile(), // ### is replaced in JS. Can't append to S= urls
			'menuSetsItem.removeUrl' =>
			ee('CP/URL')->make('settings/menu-manager/remove-item')->compile(),
		]);

		ee()->cp->add_js_script('file', 'cp/settings/menu-manager/edit');

		ee()->view->cp_page_title = is_null($set_id) ? lang('create_menu_set') : lang('edit_menu_set');
		ee()->view->ajax_validate = TRUE;

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

		ee()->cp->set_breadcrumb(ee('CP/URL')->make('settings/menu-manager'), lang('menu_sets'));

		ee()->cp->render('settings/form', $vars);
	}

	/**
	 * AJAX endpoint to reorder a menu set's items
	 */
	public function itemReorder()
	{
		$order = 1;
		$flattened = [];
		foreach (ee('Request')->post('order') as $item)
		{
			$flattened += $this->flattenItemsTree($item, 0, $order);
			$order++;
		}

		$items = ee('Model')->get('MenuItem', array_keys($flattened))->all();
		foreach ($items as $item)
		{
			$item->sort = $flattened[$item->getId()];
		}

		$items->save();

		return ['success'];
	}

	/**
	 * Recursive function to flatten the item tree we get back from the SelectField
	 */
	private function flattenItemsTree($item, $parent_id, $order)
	{
		$flattened = [$item['id'] => $order];

		// Has children? Flatten them to same array
		if (isset($item['children']))
		{
			$order = 1;
			foreach ($item['children'] as $child)
			{
				$flattened += $this->flattenItemsTree($child, $item['id'], $order);
				$order++;
			}
		}

		return $flattened;
	}

	/**
	 * Show the upper half of the create/edit form for a menu set. This includes
	 * the name and selected member groups.
	 *
	 * @return array of shared form sections
	 */
	private function mainForm(MenuSet $set)
	{
		$disabled_choices = array();
		$member_groups = ee('Model')->get('MemberGroup')
			->filter('can_access_cp', 'y')
			->filter('site_id', 1) // this is on purpose, saving the member group apply the set to other sites
			->all()
			->getDictionary('group_id', 'group_title');

		$other_sets = ee('Model')->get('MenuSet')
			->with('MemberGroups')
			->filter('MemberGroups.can_access_cp', 'y');

		if ( ! $set->isNew())
		{
			// Exclude this set
			$other_sets->filter('set_id', '!=', $set->set_id);
		}

		foreach ($other_sets->all() as $other_set)
		{
			foreach ($other_set->MemberGroups as $group)
			{
				if ($group->can_access_cp)
				{
					$member_groups[$group->group_id] = [
						'label' => $group->group_title,
						'value' => $group->group_id,
						'instructions' => lang('assigned_to') . ' ' . $other_set->name
					];
					$disabled_choices[] = $group->group_id;
				}
			}
		}

		$selected_member_groups = ($set->MemberGroups) ? $set->MemberGroups->pluck('group_id') : array();

		$section = array(
			array(
				'title' => 'name',
				'fields' => array(
					'name' => array(
						'type' => 'text',
						'required' => TRUE,
						'value' => $set->name,
					)
				)
			),
			array(
				'title' => 'set_member_groups',
				'desc' => 'set_member_groups_desc',
				'fields' => array(
					'member_groups' => array(
						'type' => 'checkbox',
						'choices' => $member_groups,
						'disabled_choices' => $disabled_choices,
						'value' => $selected_member_groups,
						'no_results' => [
							'text' => sprintf(lang('no_found'), lang('member_groups'))
						]
					)
				)
			),
		);

		return $section;
	}

	/**
	 * Create the nested list of menu items for a given set
	 *
	 * @return array of form sections or the rendered html
	 */
	private function reorderList(MenuSet $set)
	{
		// annoying model issue where partial sets are not fully reloaded
		// which can happen with submenus. Need to fix that in the model code,
		// but for now ...
		$set = ee('Model')->get('MenuSet', $set->getId())->first();

		return ee('View')->make('ee:_shared/form/fields/select')->render([
			'field_name'  => 'menu_items',
			'choices'     => $set->buildItemsTree(),
			'value'       => NULL,
			'multi'       => FALSE,
			'nested'      => TRUE,
			'selectable'  => FALSE,
			'reorderable' => TRUE,
			'removable'   => TRUE,
			'editable'    => TRUE,
			'reorder_ajax_url'    => ee('CP/URL', 'settings/menu-manager/item-reorder')->compile(),
			'no_results' => [
				'text' => sprintf(lang('no_found'), lang('menu_items')),
				'link_text' => 'add_new',
				'link_href' => '#'
			]
		]);
	}

	public function createItem($set_id = NULL)
	{
		$set = NULL;

		if ($set_id)
		{
			$set = ee('Model')->get('MenuSet', $set_id)->first();
		}

		$vars = $this->itemForm($set);

		$vars['cp_page_title'] = lang('create_menu_item');
		$vars['base_url'] = ee('CP/URL')->make('settings/menu-manager/create-item/'.$set_id);

		return ee('View')->make('_shared/form')->render($vars);
	}

	public function editItem($set_id, $item_id)
	{
		$item = ee('Model')->get('MenuItem', $item_id)->first();
		$set = ee('Model')->get('MenuSet', $set_id)->first();

		$vars = $this->itemForm($set, $item);

		$vars['cp_page_title'] = lang('edit_menu_item');
		$vars['base_url']  = ee('CP/URL')->make('settings/menu-manager/edit-item/'.$set_id.'/'.$item_id);

		return ee('View')->make('_shared/form')->render($vars);
	}


	public function removeItem()
	{
		$item_id = ee('Request')->post('content_id');

		$item = ee('Model')->get('MenuItem', $item_id)->first();
		$set_id = $item->set_id;

		$item->delete();

		$set = ee('Model')->get('MenuSet', $set_id)->first();

		ee()->output->send_ajax_response(array(
			'selectList' => $this->reorderList($set, TRUE)
		));
	}

	/**
	 * Render the create/edit MenuItem form. Typically shown in a side modal.
	 */
	private function itemForm($set = NULL, $item = NULL)
	{
		if ( ! AJAX_REQUEST)
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$item = $item ?: ee('Model')->make('MenuItem', array('sort' => 1));

		$vars = array('sections' => array());

		if ( ! empty($_POST))
		{
			if (isset($set))
			{
				$items = $set->Items;
				$last = $items->sortBy('sort')->last();
				$item->sort = $last && $item->isNew() ? $last->sort + 1 : $item->sort;
				$set->Items[] = $item;
			}

			switch (ee('Request')->post('type'))
			{
				case 'addon':
					$item->type = 'addon';
					$this->processAddon($set, $item, ee('Request')->post('addon'));
					break;
				case 'link':
					$item->type = 'link';
					$item->name = ee('Request')->post('name');
					$item->data = $this->processURL(ee('Request')->post('data'));
					break;
				case 'submenu':
					$item->type = 'submenu';
					$item->name = ee('Request')->post('name');
					$this->processGrid($set, $item, ee('Request')->post('submenu', array()));
					break;
			}

			$result = $item->validate();

			if (isset($_POST['ee_fv_field']) && $response = $this->ajaxValidation($result))
			{
				return ee()->output->send_ajax_response($response);
			}

			if ($result->isValid() && isset($set))
			{
				$item->save();

				ee()->output->send_ajax_response(array(
					'selectList' => $this->reorderList($set, TRUE)
				));
			}
			elseif ($result->isNotValid())
			{
				$vars['errors'] = $result;
			}
		}

		$grid = $this->getSubmenuGrid($set, $item);

		$type_options = array(
			'link' => lang('menu_single')
		);

		if ((int) $item->parent_id == 0)
		{
			$type_options = array(
				'addon' => lang('menu_addon'),
				'link' => lang('menu_single'),
				'submenu' => lang('menu_dropdown')
			);
		}

		$vars['sections'][] = array(
			array(
				'title' => 'menu_type',
				'fields' => array(
					'type' => array(
						'type' => 'radio',
						'choices' => $type_options,
						'value' => $item->type,
						'group_toggle' => [
							'addon' => 'addon',
							'link' => 'link',
							'submenu' => 'submenu',
						]
					)
				)
			),
			array(
				'title' => 'menu_label',
				'desc' => 'menu_label_desc',
				'group' => ['link', 'submenu'],
				'fields' => array(
					'name' => array(
						'type' => 'text',
						'value' => $item->name
					)
				)
			),
			array(
				'title' => 'menu_url',
				'desc' => 'menu_url_desc',
				'group' => 'link',
				'fields' => array(
					'data' => array(
						'type' => 'text',
						'value' => ($item->type == 'link') ? $item->data : NULL
					)
				)
			),
			array(
				'title' => 'menu_addon',
				'desc' => 'menu_addon_desc',
				'group' => 'addon',
				'fields' => array(
					'addon' => array(
						'type' => 'radio',
						'choices' => $this->getAvailableAddons($set),
						'value' => ($item->type == 'addon') ? $item->data : NULL,
						'no_results' => array(
							'text' => lang('menu_no_addons')
						)
					)
				)
			),
			array(
				'title' => 'submenu',
				'desc' => 'submenu_desc',
				'wide' => TRUE,
				'grid' => TRUE,
				'group' => 'submenu',
				'fields' => array(
					'submenu_items' => array(
						'type' => 'html',
						'content' => ee()->load->view('_shared/table', $grid->viewData(), TRUE)
					)
				)
			)
		);

		$vars['save_btn_text'] = lang('save');
		$vars['save_btn_text_working'] = 'btn_saving';
		$vars['ajax_validate'] = TRUE;

		return $vars;
	}

	/**
	 * Handle data for an add-on menu item
	 */
	private function processAddon($set, $item, $class)
	{
		$addons = $this->getAvailableAddons($set);

		if (isset($addons[$class]))
		{
			$item->name = $addons[$class];
			$item->data = $class;
		}
	}

	/**
	 * Preprocess urls to deal with copy-pasted data. We don't want to store
	 * full cp urls if it can be avoided.
	 *
	 * @param String $url The pasted url
	 */
	private function processURL($url)
	{
		$cp_url = ee()->config->item('cp_url');
		$base_url = ee()->config->item('base_url');

		if (strpos($url, $cp_url) === 0)
		{
			$url = str_replace($cp_url, '', $url);
		}

		// not a cp url - treat as external
		if (strpos($url, '://') !== FALSE)
		{
			if (strpos($url, $base_url) === 0)
			{
				return $url;
			}

			return ee()->cp->masked_url($url);
		}

		$url = trim($url, ' ?/');
		parse_str($url, $qs);

		$out = '';

		// ditch session ids
		unset($qs['S']);

		// first key will be cp/whatever => ""
		// we'll remove that so that http_build_query doesn't encode the slash
		if (current($qs) == '')
		{
			$out = key($qs).'&';
			array_shift($qs);
		}

		$out .= http_build_query($qs);
		return trim($out, '&');
	}

	/**
	 * Handle data for a dropdown menu item
	 */
	private function processGrid($set, $item, $post)
	{
		$children = $item->Children->indexBy('item_id');

		if ( ! isset($post['rows']) || empty($post['rows']))
		{
			return;
		}

		$i = 1;

		foreach ($post['rows'] as $row_id => $columns)
		{
			if (strpos($row_id, 'row_id_') !== FALSE)
			{
				$sub = $children[str_replace('row_id_', '', $row_id)];
				$sub->type = 'link';
				$sub->name = $columns['name'];
				$sub->data = $this->processURL($columns['data']);
				$sub->sort = $i++;
			}
			else
			{
				$sub = ee('Model')->make('MenuItem');
				$sub->type = 'link';
				$sub->name = $columns['name'];
				$sub->data = $this->processURL($columns['data']);
				$sub->sort = $i++;
				$item->Children[] = $sub;
			}
		}
	}

	/**
	 * Fetch valid add-ons for the custom menu hook
	 */
	private function getAvailableAddons($set)
	{
		$addons = ee('Addon')->installed();
		$result = array();

		$extensions = ee('Model')
			->get('Extension')
			->filter('hook', 'cp_custom_menu')
			->filter('enabled', 'y')
			->all()
			->pluck('class');

		if (empty($extensions))
		{
			return $result;
		}

		foreach ($addons as $prefix => $addon)
		{
			if ($addon->hasExtension())
			{
				$class = ucfirst($addon->getPrefix()).'_ext';

				if (in_array($class, $extensions))
				{
					$result[$class] = $addon->getName();
				}
			}
		}

		return $result;
	}

	/**
	 * Prepare the grid for the submenu form
	 */
	private function getSubmenuGrid($set, $item)
	{
		$grid = ee('CP/GridInput', array(
			'field_name' => 'submenu',
			'reorder'    => TRUE
		));

		$grid->setColumns(
			array(
				'name' => array(
					'label' => 'menu_label',
					'desc'  => 'menu_label_desc'
				),
				'data' => array(
					'label' => 'menu_url',
					'desc'  => 'menu_url_desc'
				)
			)
		);

		$grid->setNoResultsText(lang('no_menu_items'), lang('add_menu_item'));
		$grid->setBlankRow($this->getGridRow(ee('Model')->make('MenuItem')));

		$data = array();

		if (count($item->Children))
		{
			foreach ($item->Children as $item)
			{
				$data[] = array(
					'attrs' => array('row_id' => $item->getId()),
					'columns' => $this->getGridRow($item),
				);
			}
		}
		// Auto-populate the Grid with the Single Link info
		elseif ($item->type == 'link')
		{
			$data[] = array(
				'attrs' => array(),
				'columns' => $this->getGridRow($item),
			);
		}

		$grid->setData($data);

		return $grid;
	}

	/**
	 * Create a single grid row
	 */
	private function getGridRow($item)
	{
		return array(
			array(
				'html' => form_input('name', $item->name),
				'error' => ''
			),
			array(
				'html' => form_input('data', $item->data),
				'error' => ''
			)
		);
	}
}
