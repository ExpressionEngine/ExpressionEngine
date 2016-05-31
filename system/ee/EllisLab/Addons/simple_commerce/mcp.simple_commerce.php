<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine Simple Commerce Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Simple_commerce_mcp {

	var $export_type		= 'tab';
	var $perform_redirects	= TRUE;
	var $menu_email			= array();
	var $menu_groups		= array();
	var $nest_categories	= 'y';
	var $perpage			= 50;
	var $pipe_length 		= 5;
	var $base_url			= '';

	/**
	 * Constructor
	 *
	 * @access	public
	 */

	function __construct($switch = TRUE)
	{
		$this->base_url = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=simple_commerce';

		$this->sidebar = ee('CP/Sidebar')->make();

		$this->items_nav = $this->sidebar->addHeader(lang('items'), ee('CP/URL')->make('addons/settings/simple_commerce'))
			->withButton(lang('new'), ee('CP/URL')->make('addons/settings/simple_commerce/create-item'));

		$this->purchases_nav = $this->sidebar->addHeader(lang('purchases'), ee('CP/URL')->make('addons/settings/simple_commerce/purchases'))
			->withButton(lang('new'), ee('CP/URL')->make('addons/settings/simple_commerce/create-purchase'));

		$this->email_templates_nav = $this->sidebar->addHeader(lang('email_templates'), ee('CP/URL')->make('addons/settings/simple_commerce/email-templates'))
			->withButton(lang('new'), ee('CP/URL')->make('addons/settings/simple_commerce/create-email-template'));

		ee()->view->header = array(
			'toolbar_items' => array(
				'settings' => array(
					'href' => ee('CP/URL')->make('addons/settings/simple_commerce/settings'),
					'title' => lang('settings')
				)
			)
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Control Panel Index
	 *
	 * @access	public
	 */

	function index($message = '')
	{
		$table = ee('CP/Table');
		$table->setColumns(array(
			'name',
			'price_sale' => array(
				'encode' => FALSE
			),
			'frequency',
			'subscribers',
			'purchases',
			'manage' => array(
				'type'	=> Table::COL_TOOLBAR
			),
			array(
				'type'	=> Table::COL_CHECKBOX
			)
		));

		$table->setNoResultsText(sprintf(lang('no_found'), lang('items')), 'create_new_item', ee('CP/URL')->make('addons/settings/simple_commerce/create-item'));

		$sort_map = array(
			'name'        => 'ChannelEntry.title',
			'price_sale'  => 'item_regular_price',
			'frequency'   => 'subscription_frequency',
			'subscribers' => 'current_subscriptions',
			'purchases'   => 'item_purchases'
		);

		$items = ee('Model')->get('simple_commerce:Item')->with('ChannelEntry');
		$total_rows = $items->count();

		$items = $items->order($sort_map[$table->sort_col], $table->sort_dir)
			->limit($table->config['limit'])
			->offset(($table->config['page'] - 1) * $table->config['limit'])
			->all();

		$data = array();
		foreach ($items as $item)
		{
			if ($item->item_use_sale)
			{
				$price_col = '<span class="faded">$'.$item->item_regular_price.' / </span><span class="yes">$'.$item->item_sale_price.'</span>';
			}
			else
			{
				$price_col = '<span class="yes">$'.$item->item_regular_price.'</span><span class="faded"> / $'.$item->item_sale_price.'</span>';
			}

			$edit_url = ee('CP/URL')->make('addons/settings/simple_commerce/edit-item/'.$item->getId());

			$columns = array(
				array(
					'content' => $item->ChannelEntry->title,
					'href' => $edit_url
				),
				$price_col,
				$item->subscription_frequency ?: '--',
				$item->current_subscriptions,
				$item->item_purchases,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'items[]',
					'value' => $item->getId(),
					'data'	=> array(
						'confirm' => lang('item') . ': <b>' . htmlentities($item->ChannelEntry->title, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			$attrs = array();
			$highlight_ids = ee()->session->flashdata('highlight_id') ?: array();
			if (in_array($item->getId(), $highlight_ids))
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => $columns
			);
		}

		$table->setData($data);

		$vars['base_url'] = ee('CP/URL')->make('addons/settings/simple_commerce');
		$vars['table'] = $table->viewData($vars['base_url']);

		$vars['pagination'] = ee('CP/Pagination', $total_rows)
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('items') . ': <b>### ' . lang('items') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		return array(
			'heading' => lang('commerce_items'),
			'body' => ee('View')->make('simple_commerce:items')->render($vars),
			'sidebar' => $this->sidebar
		);
	}

	/**
	 * Remove purchases handler
	 */
	public function removeItem()
	{
		$item_ids = ee()->input->post('items');

		if ( ! empty($item_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			$item_ids = array_filter($item_ids, 'is_numeric');

			if ( ! empty($item_ids))
			{
				ee('Model')->get('simple_commerce:Item', $item_ids)->delete();

				ee('CP/Alert')->makeInline('items-table')
					->asSuccess()
					->withTitle(lang('items_removed'))
					->addToBody(sprintf(lang('items_removed_desc'), count($item_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/simple_commerce', ee()->cp->get_url_state()));
	}

	/**
	 * First step of item creation
	 */
	public function createItem()
	{
		ee()->lang->load('content');

		$base_url = ee('CP/URL')->make('addons/settings/simple_commerce/create-item');
		$entry_listing = ee('CP/EntryListing', ee()->input->get_post('search'));
		$entries = $entry_listing->getEntries();
		$filters = $entry_listing->getFilters();
		$count = $entries->count();

		$vars['filters'] = $filters->render($base_url);
		$filter_values = $filters->values();
		$base_url->addQueryStringVariables($filter_values);

		$table = ee('CP/Table', array(
			'sort_dir' => 'desc',
			'sort_col' => 'column_entry_date',
		));

		$table->setColumns(
			array(
				'column_entry_id',
				'column_title' => array(
					'encode' => FALSE
				),
				'column_entry_date',
				'column_status' => array(
					'type'	=> Table::COL_STATUS
				),
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);
		$table->setNoResultsText(sprintf(lang('no_found'), lang('no_entries')));

		$channels = ee('Model')->get('Channel')
			->fields('channel_id', 'channel_name')
			->filter('site_id', ee()->config->item('site_id'))
			->all();

		$page = ((int) ee()->input->get('page')) ?: 1;
		$offset = ($page - 1) * $filter_values['perpage']; // Offset is 0 indexed

		$entries->order(str_replace('column_', '', $table->sort_col), $table->sort_dir)
			->limit($filter_values['perpage'])
			->offset($offset);

		$data = array();

		$entry_id = ee()->session->flashdata('entry_id');

		foreach ($entries->all() as $entry)
		{
			$title = htmlentities($entry->title, ENT_QUOTES, 'UTF-8');
			$title .= '<br><span class="meta-info">&mdash; ' . lang('by') . ': ' . htmlentities($entry->getAuthorName(), ENT_QUOTES, 'UTF-8') . ', ' . lang('in') . ': ' . htmlentities($entry->Channel->channel_title, ENT_QUOTES, 'UTF-8') . '</span>';

			$data[] = array(
				$entry->entry_id,
				$title,
				ee()->localize->human_time($entry->entry_date),
				$entry->status,
				array(
					'name' => 'entries[]',
					'value' => $entry->entry_id,
					'data' => array(
						'confirm' => lang('entry') . ': <b>' . htmlentities($entry->title, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);
		}

		$table->setData($data);

		$vars['table'] = $table->viewData($base_url);
		$vars['form_url'] = ee('CP/URL')->make('addons/settings/simple_commerce/add-items');

		$vars['pagination'] = ee('CP/Pagination', $count)
			->perPage($filter_values['perpage'])
			->currentPage($page)
			->render($base_url);

		$this->items_nav->isActive();

		return array(
			'heading' => sprintf(lang('create_new_item_step'), 1),
			'breadcrumb' => array(
				ee('CP/URL')->make('addons/settings/simple_commerce')->compile() => lang('commerce_items')
			),
			'body' => ee('View')->make('simple_commerce:entry_list')->render($vars),
			'sidebar' => $this->sidebar
		);
	}

	/**
	 * Step 2 in item creation
	 */
	public function addItems()
	{
		$entry_ids = ee()->input->post('entries');

		if ( ! ee()->input->post('items') && (empty($entry_ids) OR ee()->input->post('bulk_action') != 'add_item'))
		{
			ee()->functions->redirect(ee('CP/URL')->make('addons/settings/simple_commerce/create-item', ee()->cp->get_url_state()));
		}

		$forms = array();
		if ( ! empty($_POST) && isset($_POST['items']))
		{
			// Validate all items before saving
			$valid = TRUE;
			foreach ($_POST['items'] as $entry_id => $item_data)
			{
				$item = ee('Model')->make('simple_commerce:Item');
				$item->set($item_data);
				$item->ChannelEntry = ee('Model')->get('ChannelEntry', $entry_id)->first();
				$item->subscription_frequency = empty($item->subscription_frequency) ? NULL : $item->subscription_frequency;
				$result = $item->validate();

				$forms[] = array(
					'form_title' => lang('create_new').': '.$item->ChannelEntry->title,
					'sections' => $this->itemForm($item, 'items['.$entry_id.']'),
					'errors' => $result,
					'item' => $item,
					'entry_id' => $entry_id
				);

				if ($result->isNotValid())
				{
					ee('CP/Alert')->makeInline('item-form-'.$entry_id)
						->asIssue()
						->withTitle(lang('item_not_created'))
						->addToBody(lang('item_not_created_desc'))
						->now();

					// Hack because we have prefixed fields that don't match the fields in the model
					ee()->load->library('form_validation');
					foreach ($result->renderErrors() as $field_name => $error)
					{
						ee()->form_validation->_error_array['items['.$entry_id.']['.$field_name.']'] = $error;
					}

					if ($valid)
					{
						$valid = FALSE;
					}
				}
			}

			if ($valid)
			{
				$item_ids = array();
				foreach ($forms as $form)
				{
					$item = $form['item'];
					$item->save();
					$item_ids[] = $item->getId();
				}

				ee()->session->set_flashdata('highlight_id', $item_ids);

				ee('CP/Alert')->makeInline('items-table')
					->asSuccess()
					->withTitle(lang('item_created'))
					->addToBody(lang('item_created_desc'))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('addons/settings/simple_commerce'));
			}
		}

		if (empty($forms))
		{
			$existing_items = ee('Model')->get('simple_commerce:Item')->all()->pluck('entry_id');

			foreach ($entry_ids as $entry_id)
			{
				// Skip over entries we already have an item for
				if (in_array($entry_id, $existing_items))
				{
					continue;
				}

				$item = ee('Model')->make('simple_commerce:Item');
				$item->ChannelEntry = ee('Model')->get('ChannelEntry', $entry_id)->first();
				$forms[] = array(
					'form_title' => lang('create_new').': '.$item->ChannelEntry->title,
					'sections' => $this->itemForm($item, 'items['.$entry_id.']'),
					'errors' => NULL,
					'entry_id' => 0
				);
			}
		}

		$vars = array(
			'forms' => $forms,
			'form_url' => ee('CP/URL')->make('addons/settings/simple_commerce/add-items'),
			'save_btn_text' => sprintf(lang('btn_save'), (count($forms) > 1) ? lang('items') : lang('item')),
			'save_btn_text_working' => 'btn_saving'
		);

		$this->items_nav->isActive();

		return array(
			'heading' => sprintf(lang('create_new_item_step'), 2),
			'breadcrumb' => array(
				ee('CP/URL')->make('addons/settings/simple_commerce')->compile() => lang('commerce_items')
			),
			'body' => ee('View')->make('simple_commerce:add_items')->render($vars),
			'sidebar' => $this->sidebar
		);
	}

	/**
	 * Edit item
	 */
	public function editItem($item_id)
	{
		$item = ee('Model')->get('simple_commerce:Item', $item_id)->first();

		if ( ! $item)
		{
			show_error(lang('unauthorized_access'));
		}

		if ( ! empty($_POST))
		{
			$item->set($_POST['item']);
			$item->subscription_frequency = empty($item->subscription_frequency) ? NULL : $item->subscription_frequency;
			$result = $item->validate();

			if ($result->isValid())
			{
				$item = $item->save();

				ee('CP/Alert')->makeInline('items-table')
					->asSuccess()
					->withTitle(lang('item_updated'))
					->addToBody(sprintf(lang('item_updated_desc'), $item->ChannelEntry->title))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('addons/settings/simple_commerce'));
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('item_not_updated'))
					->addToBody(lang('item_not_updated_desc'))
					->now();
			}
		}

		$vars['sections'] = $this->itemForm($item);
		$vars['cp_page_title'] = lang('edit_item').': '.$item->ChannelEntry->title;
		$vars['base_url'] = ee('CP/URL')->make('addons/settings/simple_commerce/edit-item/'.$item_id);
		$vars['save_btn_text'] = sprintf(lang('btn_save'), lang('item'));
		$vars['save_btn_text_working'] = 'btn_saving';

		$this->items_nav->isActive();

		return array(
			'heading' => lang('edit_item'),
			'breadcrumb' => array(
				ee('CP/URL')->make('addons/settings/simple_commerce')->compile() => lang('commerce_items')
			),
			'body' => ee('View')->make('simple_commerce:form')->render($vars),
			'sidebar' => $this->sidebar
		);
	}

	/**
	 * Generates the item form for creating or editing
	 *
	 * @param Item		$item	Item model object
	 * @param string	$prefix	Optional alternate prefix for input names, default is "item", leading to "item[input_name]"
	 */
	private function itemForm($item, $prefix = 'item')
	{
		static $email_templates;
		static $member_groups;

		if (empty($email_templates))
		{
			// Email template choices
			$email_templates = array(0 => lang('send_no_email'));
			$email_templates += ee('Model')->get('simple_commerce:EmailTemplate')->all()->getDictionary('email_id', 'email_name');
		}

		if (empty($member_groups))
		{
			// Member group choices
			$member_groups = array(0 => lang('no_change'));
			$member_groups += ee('Model')->get('MemberGroup')
				->filter('site_id', ee()->config->item('site_id'))
				->order('group_title')
				->all()
				->getDictionary('group_id', 'group_title');
		}

		return array(
			array(
				array(
					'title' => 'enable_item',
					'desc' => 'enable_item_desc',
					'fields' => array(
						$prefix.'[item_enabled]' => array(
							'type' => 'yes_no',
							'value' => $item->item_enabled
						)
					)
				),
				array(
					'title' => 'regular_price',
					'fields' => array(
						$prefix.'[item_regular_price]' => array(
							'type' => 'text',
							'value' => $item->item_regular_price ?: '0.00'
						)
					)
				),
				array(
					'title' => 'sale_price',
					'fields' => array(
						$prefix.'[item_sale_price]' => array(
							'type' => 'text',
							'value' => $item->item_sale_price ?: '0.00'
						)
					)
				),
				array(
					'title' => 'use_sale_price',
					'fields' => array(
						$prefix.'[item_use_sale]' => array(
							'type' => 'yes_no',
							'value' => $item->item_use_sale
						)
					)
				)
			),
			'email_options' => array(
				array(
					'title' => 'admin_email_address',
					'desc' => 'admin_email_address_desc',
					'fields' => array(
						$prefix.'[admin_email_address]' => array(
							'type' => 'text',
							'value' => $item->admin_email_address
						)
					)
				),
				array(
					'title' => 'admin_email_template',
					'desc' => 'admin_email_template_desc',
					'fields' => array(
						$prefix.'[admin_email_template]' => array(
							'type' => 'select',
							'choices' => $email_templates,
							'value' => $item->admin_email_template
						)
					)
				),
				array(
					'title' => 'customer_email_template',
					'desc' => 'customer_email_template_desc',
					'fields' => array(
						$prefix.'[customer_email_template]' => array(
							'type' => 'select',
							'choices' => $email_templates,
							'value' => $item->customer_email_template
						)
					)
				),
				array(
					'title' => 'new_member_group',
					'desc' => 'new_member_group_desc',
					'fields' => array(
						$prefix.'[new_member_group]' => array(
							'type' => 'select',
							'choices' => $member_groups,
							'value' => $item->new_member_group
						)
					)
				),
				array(
					'title' => 'admin_email_template_unsubscribe',
					'desc' => 'admin_email_template_unsubscribe_desc',
					'fields' => array(
						$prefix.'[admin_email_template_unsubscribe]' => array(
							'type' => 'select',
							'choices' => $email_templates,
							'value' => $item->admin_email_template_unsubscribe
						)
					)
				),
				array(
					'title' => 'customer_email_unsubscribe',
					'desc' => 'customer_email_unsubscribe_desc',
					'fields' => array(
						$prefix.'[customer_email_template_unsubscribe]' => array(
							'type' => 'select',
							'choices' => $email_templates,
							'value' => $item->customer_email_template_unsubscribe
						)
					)
				),
				array(
					'title' => 'new_member_group',
					'desc' => 'member_group_unsubscribe_desc',
					'fields' => array(
						$prefix.'[member_group_unsubscribe]' => array(
							'type' => 'select',
							'choices' => $member_groups,
							'value' => $item->member_group_unsubscribe
						)
					)
				),
			),
			'subscription_options' => array(
				array(
					'title' => 'recurring',
					'desc' => 'recurring_desc',
					'fields' => array(
						$prefix.'[recurring]' => array(
							'type' => 'yes_no',
							'value' => $item->recurring
						)
					)
				),
				array(
					'title' => 'subscription_frequency',
					'desc' => 'subscription_frequency_desc',
					'fields' => array(
						$prefix.'[subscription_frequency]' => array(
							'type' => 'text',
							'value' => $item->subscription_frequency
						),
						$prefix.'[subscription_frequency_unit]' => array(
							'type' => 'select',
							'choices' => array(
								'day' => lang('days'),
								'week' => lang('weeks'),
								'month' => lang('months'),
								'year' => lang('years')
							),
							'value' => $item->subscription_frequency_unit
						)
					)
				)
			)
		);
	}

	/**
	 * Purchases listing
	 */
	public function purchases()
	{
		$table = ee('CP/Table');
		$table->setColumns(array(
			'item',
			'purchaser',
			'date_of_purchase',
			'sub_end_date',
			'cost',
			'manage' => array(
				'type'	=> Table::COL_TOOLBAR
			),
			array(
				'type'	=> Table::COL_CHECKBOX
			)
		));

		$table->setNoResultsText(sprintf(lang('no_found'), lang('purchases')), 'create_purchase', ee('CP/URL')->make('addons/settings/simple_commerce/create-purchase'));

		$sort_map = array(
			// Change when relationships work
			'item'             => 'ChannelEntry.title',
			'purchaser'        => 'Member.screen_name',
			'date_of_purchase' => 'purchase_date',
			'sub_end_date'     => 'subscription_end_date',
			'cost'             => 'item_cost'
		);

		$purchases = ee('Model')->get('simple_commerce:Purchase')->with(array('Item' => 'ChannelEntry'));
		$total_rows = $purchases->count();

		$purchases = $purchases->order($sort_map[$table->sort_col], $table->sort_dir)
			->limit($table->config['limit'])
			->offset(($table->config['page'] - 1) * $table->config['limit'])
			->all();

		$data = array();
		foreach ($purchases as $purchase)
		{
			$edit_url = ee('CP/URL')->make('addons/settings/simple_commerce/edit-purchase/'.$purchase->getId());

			$columns = array(
				array(
					'content' => $purchase->Item->ChannelEntry->title,
					'href' => $edit_url
				),
				$purchase->Member->screen_name,
				ee()->localize->human_time($purchase->purchase_date),
				$purchase->subscription_end_date ?: '--',
				'$'.$purchase->item_cost,
				array('toolbar_items' => array(
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'purchases[]',
					'value' => $purchase->getId(),
					'data'	=> array(
						'confirm' => lang('purchase') . ': <b>' . htmlentities($purchase->Item->ChannelEntry->title, ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			$attrs = array();
			if (ee()->session->flashdata('highlight_id') == $purchase->getId())
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => $columns
			);
		}

		$table->setData($data);

		$vars['base_url'] = ee('CP/URL')->make('addons/settings/simple_commerce/purchases');
		$vars['table'] = $table->viewData($vars['base_url']);

		$vars['pagination'] = ee('CP/Pagination', $total_rows)
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('purchases') . ': <b>### ' . lang('purchases') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		return array(
			'heading' => lang('commerce_purchases'),
			'body' => ee('View')->make('simple_commerce:purchases')->render($vars),
			'sidebar' => $this->sidebar
		);
	}

	/**
	 * Remove purchases handler
	 */
	public function removePurchase()
	{
		$purchase_ids = ee()->input->post('purchases');

		if ( ! empty($purchase_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			$purchase_ids = array_filter($purchase_ids, 'is_numeric');

			if ( ! empty($purchase_ids))
			{
				ee('Model')->get('simple_commerce:Purchase', $purchase_ids)->delete();

				ee('CP/Alert')->makeInline('purchases-table')
					->asSuccess()
					->withTitle(lang('purchases_removed'))
					->addToBody(sprintf(lang('purchases_removed_desc'), count($purchase_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/simple_commerce/purchases', ee()->cp->get_url_state()));
	}

	/**
	 * Create purchase URL endpoint
	 */
	public function createPurchase()
	{
		return $this->purchaseForm();
	}

	/**
	 * Edit purchase URL endpoint
	 */
	public function editPurchase($purchase_id)
	{
		return $this->purchaseForm($purchase_id);
	}

	/**
	 * Purchase create/edit form
	 */
	public function purchaseForm($purchase_id = NULL)
	{
		if (is_null($purchase_id))
		{
			$alert_key = 'created';
			$vars['cp_page_title'] = lang('create_purchase');
			$vars['base_url'] = ee('CP/URL')->make('addons/settings/simple_commerce/create-purchase');

			$purchase = ee('Model')->make('simple_commerce:Purchase');
		}
		else
		{
			$purchase = ee('Model')->get('simple_commerce:Purchase', $purchase_id)->first();

			if ( ! $purchase)
			{
				show_error(lang('unauthorized_access'));
			}

			$alert_key = 'updated';
			$vars['cp_page_title'] = lang('edit_purchase');
			$vars['base_url'] = ee('CP/URL')->make('addons/settings/simple_commerce/edit-purchase/'.$purchase_id);
		}

		if ( ! empty($_POST))
		{
			$purchase->set($_POST);
			$result = $purchase->validate();

			if ($result->isValid())
			{
				$purchase = $purchase->save();

				if (is_null($purchase_id))
				{
					ee()->session->set_flashdata('highlight_id', $purchase->getId());
				}

				ee('CP/Alert')->makeInline('purchases-table')
					->asSuccess()
					->withTitle(lang('purchase_'.$alert_key))
					->addToBody(sprintf(lang('purchase_'.$alert_key.'_desc'), $purchase->Item->ChannelEntry->title))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('addons/settings/simple_commerce/purchases'));
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('purchase_not_'.$alert_key))
					->addToBody(lang('purchase_not_'.$alert_key.'_desc'))
					->now();
			}
		}

		$item_choices = array();
		foreach (ee('Model')->get('simple_commerce:Item')->with('ChannelEntry')->order('ChannelEntry.title')->all() as $item)
		{
			$item_choices[$item->getId()] = $item->ChannelEntry->title;
		}

		$vars['sections'] = array(
			array(
				ee('CP/Alert')->makeInline()
					->asWarning()
					->addToBody(lang('purchase_create_warn'))
					->cannotClose()
					->render(),
				array(
					'title' => 'txn_id',
					'desc' => 'txn_id_desc',
					'fields' => array(
						'txn_id' => array(
							'type' => 'text',
							'value' => $purchase->txn_id,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'screen_name',
					'desc' => 'screen_name_desc',
					'fields' => array(
						'member_id' => array(
							'type' => 'text',
							'value' => $purchase->Member ? $purchase->Member->getMemberName() : '',
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'item',
					'desc' => 'item_purchased',
					'fields' => array(
						'item_id' => array(
							'type' => 'select',
							'choices' => $item_choices,
							'value' => $purchase->item_id,
							'required' => TRUE,
							'no_results' => array(
								'text' => sprintf(lang('no_found'), lang('items')),
								'link_text' => 'create_new_item',
								'link_href' => ee('CP/URL')->make('addons/settings/simple_commerce/create-item')
							)
						)
					)
				),
				array(
					'title' => 'price',
					'desc' => 'price_desc',
					'fields' => array(
						'item_cost' => array(
							'type' => 'text',
							'value' => $purchase->item_cost,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'purchase_date',
					'fields' => array(
						'purchase_date' => array(
							'type' => 'text',
							'value' => ee()->localize->human_time($purchase->purchase_date),
							'required' => TRUE,
							'attrs' => 'rel="date-picker" data-timestamp="'.$purchase->purchase_date.'"'
						)
					)
				)
			)
		);

		$vars['save_btn_text'] = sprintf(lang('btn_save'), lang('purchase'));
		$vars['save_btn_text_working'] = 'btn_saving';

		ee()->javascript->set_global('date.date_format', ee()->localize->get_date_format());
		ee()->javascript->set_global('lang.date.months.full', array(
			lang('january'),
			lang('february'),
			lang('march'),
			lang('april'),
			lang('may'),
			lang('june'),
			lang('july'),
			lang('august'),
			lang('september'),
			lang('october'),
			lang('november'),
			lang('december')
		));
		ee()->javascript->set_global('lang.date.months.abbreviated', array(
			lang('jan'),
			lang('feb'),
			lang('mar'),
			lang('apr'),
			lang('may'),
			lang('june'),
			lang('july'),
			lang('aug'),
			lang('sept'),
			lang('oct'),
			lang('nov'),
			lang('dec')
		));
		ee()->javascript->set_global('lang.date.days', array(
			lang('su'),
			lang('mo'),
			lang('tu'),
			lang('we'),
			lang('th'),
			lang('fr'),
			lang('sa'),
		));
		ee()->cp->add_js_script(array(
			'file' => array('cp/date_picker'),
		));

		$this->purchases_nav->isActive();

		return array(
			'heading' => $vars['cp_page_title'],
			'breadcrumb' => array(
				ee('CP/URL')->make('addons/settings/simple_commerce/purchases')->compile() => lang('commerce_purchases')
			),
			'body' => ee('View')->make('simple_commerce:form')->render($vars),
			'sidebar' => $this->sidebar
		);
	}

	/**
	 * Email templates listing
	 */
	public function emailTemplates()
	{
		$table = ee('CP/Table', array('autosort' => TRUE));
		$table->setColumns(array(
			'name',
			'manage' => array(
				'type'	=> Table::COL_TOOLBAR
			),
			array(
				'type'	=> Table::COL_CHECKBOX
			)
		));

		$table->setNoResultsText(sprintf(lang('no_found'), lang('no_email_templates')), 'create_email_template', ee('CP/URL')->make('addons/settings/simple_commerce/create-email-template'));

		$sort_map = array(
			'name' => 'email_name',
		);

		$email_templates = ee('Model')->get('simple_commerce:EmailTemplate');
		$total_rows = $email_templates->count();

		$email_templates = $email_templates->order($sort_map[$table->sort_col], $table->sort_dir)
			->limit($table->config['limit'])
			->offset(($table->config['page'] - 1) * $table->config['limit'])
			->all();

		$data = array();
		foreach ($email_templates as $template)
		{
			$edit_url = ee('CP/URL')->make('addons/settings/simple_commerce/edit-email-template/'.$template->getId());

			$columns = array(
				array(
					'content' => $template->email_name,
					'href' => $edit_url
				),
				array('toolbar_items' => array(
					'edit' => array(
						'href' => $edit_url,
						'title' => lang('edit')
					)
				)),
				array(
					'name' => 'templates[]',
					'value' => $template->getId(),
					'data'	=> array(
						'confirm' => lang('template') . ': <b>' . htmlentities($template->getId(), ENT_QUOTES, 'UTF-8') . '</b>'
					)
				)
			);

			$attrs = array();
			if (ee()->session->flashdata('highlight_id') == $template->getId())
			{
				$attrs = array('class' => 'selected');
			}

			$data[] = array(
				'attrs' => $attrs,
				'columns' => $columns
			);
		}

		$table->setData($data);

		$vars['base_url'] = ee('CP/URL')->make('addons/settings/simple_commerce/email-templates');
		$vars['table'] = $table->viewData($vars['base_url']);

		$vars['pagination'] = ee('CP/Pagination', $total_rows)
			->perPage($vars['table']['limit'])
			->currentPage($vars['table']['page'])
			->render($vars['table']['base_url']);

		ee()->javascript->set_global('lang.remove_confirm', lang('email_templates') . ': <b>### ' . lang('email_templates') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		return array(
			'heading' => lang('email_templates'),
			'body' => ee('View')->make('simple_commerce:email_templates')->render($vars),
			'sidebar' => $this->sidebar
		);
	}

	/**
	 * Remove email templates handler
	 */
	public function removeTemplate()
	{
		$template_ids = ee()->input->post('templates');

		if ( ! empty($template_ids) && ee()->input->post('bulk_action') == 'remove')
		{
			$template_ids = array_filter($template_ids, 'is_numeric');

			if ( ! empty($template_ids))
			{
				ee('Model')->get('simple_commerce:EmailTemplate', $template_ids)->delete();

				ee('CP/Alert')->makeInline('email-templates-table')
					->asSuccess()
					->withTitle(lang('email_templates_removed'))
					->addToBody(sprintf(lang('email_templates_removed_desc'), count($template_ids)))
					->defer();
			}
		}
		else
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/simple_commerce/email-templates', ee()->cp->get_url_state()));
	}

	/**
	 * Create email template URL endpoint
	 */
	public function createEmailTemplate()
	{
		return $this->emailTemplateForm();
	}

	/**
	 * Edit email template URL endpoint
	 */
	public function editEmailTemplate($template_id)
	{
		return $this->emailTemplateForm($template_id);
	}

	/**
	 * Email template create/edit form
	 */
	public function emailTemplateForm($template_id = NULL)
	{
		if (is_null($template_id))
		{
			$alert_key = 'created';
			$vars['cp_page_title'] = lang('create_email_template');
			$vars['base_url'] = ee('CP/URL')->make('addons/settings/simple_commerce/create-email-template');

			$email_template = ee('Model')->make('simple_commerce:EmailTemplate');
		}
		else
		{
			$email_template = ee('Model')->get('simple_commerce:EmailTemplate', $template_id)->first();

			if ( ! $email_template)
			{
				show_error(lang('unauthorized_access'));
			}

			$alert_key = 'updated';
			$vars['cp_page_title'] = lang('edit_email_template');
			$vars['base_url'] = ee('CP/URL')->make('addons/settings/simple_commerce/edit-email-template/'.$template_id);
		}

		if ( ! empty($_POST))
		{
			$email_template->set($_POST);
			$result = $email_template->validate();

			if ($result->isValid())
			{
				$email_template = $email_template->save();

				if (is_null($template_id))
				{
					ee()->session->set_flashdata('highlight_id', $email_template->getId());
				}

				ee('CP/Alert')->makeInline('email-templates-table')
					->asSuccess()
					->withTitle(lang('email_template_'.$alert_key))
					->addToBody(sprintf(lang('email_template_'.$alert_key.'_desc'), $email_template->email_name))
					->defer();

				ee()->functions->redirect(ee('CP/URL')->make('addons/settings/simple_commerce/email-templates'));
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('email_template_not_'.$alert_key))
					->addToBody(lang('email_template_not_'.$alert_key.'_desc'))
					->now();
			}
		}

		$vars['sections'] = array(
			array(
				array(
					'title' => 'name',
					'desc' => 'email_template_name_desc',
					'fields' => array(
						'email_name' => array(
							'type' => 'text',
							'value' => $email_template->email_name,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'email_subject',
					'wide' => TRUE,
					'fields' => array(
						'email_subject' => array(
							'type' => 'text',
							'value' => $email_template->email_subject,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'email_body',
					'wide' => TRUE,
					'fields' => array(
						'email_body' => array(
							'type' => 'textarea',
							'value' => $email_template->email_body,
							'required' => TRUE
						)
					)
				),
				array(
					'title' => 'paypal_variables',
					'desc' => 'paypal_variables_desc',
					'wide' => TRUE,
					'fields' => array(
						'email_vars' => array(
							'type' => 'html',
							'content' => ee('View')->make('simple_commerce:_email_body_vars')->render()
						)
					)
				)
			)
		);

		ee()->javascript->output('

			$(document).ready(function () {

				$(".glossary-wrap a").click(function(){
					$("textarea[name=email_body]").insertAtCursor("{"+$(this).text()+"}");
					return false;
				});
			});

		');

		$vars['save_btn_text'] = sprintf(lang('btn_save'), lang('email_template'));
		$vars['save_btn_text_working'] = 'btn_saving';

		$this->email_templates_nav->isActive();

		return array(
			'heading' => $vars['cp_page_title'],
			'breadcrumb' => array(
				ee('CP/URL')->make('addons/settings/simple_commerce/email-templates')->compile() => lang('email_templates')
			),
			'body' => ee('View')->make('simple_commerce:form')->render($vars),
			'sidebar' => $this->sidebar
		);
	}

	/**
	 * Settings
	 */
	public function settings()
	{
		$base_url = ee('CP/URL')->make('addons/settings/simple_commerce/settings');

		$vars = array(
			'heading' => lang('commerce_settings'),
			'cp_page_title' => lang('commerce_settings'),
			'base_url' => $base_url,
			'ajax_validate' => TRUE,
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving'
		);

		$base = reduce_double_slashes(str_replace('/public_html', '', SYSPATH).'/user/encryption/');

		$vars['sections'] = array(
			array(
				ee('CP/Alert')->makeInline('ipn-notice')
					->asWarning()
					->cannotClose()
					->addToBody(sprintf(lang('commerce_ipn_notice'), 'https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNIntro/'))
					->render(),
				array(
					'title' => 'commerce_ipn_url',
					'desc' => 'commerce_ipn_url_desc',
					'fields' => array(
						'sc_api_url' => array(
							'type' => 'text',
							'value' => ee()->functions->fetch_site_index(0,0).QUERY_MARKER.'ACT='.ee()->cp->fetch_action_id('Simple_commerce', 'incoming_ipn'),
							'disabled' => TRUE
						)
					)
				),
				array(
					'title' => 'commerce_paypal_email',
					'desc' => 'commerce_paypal_email_desc',
					'fields' => array(
						'sc_paypal_account' => array('type' => 'text')
					)
				),
				array(
					'title' => 'commerce_encrypt_paypal',
					'desc' => 'commerce_encrypt_paypal_desc',
					'fields' => array(
						'sc_encrypt_buttons' => array('type' => 'yes_no')
					)
				),
				array(
					'title' => 'commerce_paypal_cert_id',
					'desc' => 'commerce_paypal_cert_id_desc',
					'fields' => array(
						'sc_certificate_id' => array(
							'type' => 'text',
							'value' => (ee()->config->item('sc_certificate_id') === FALSE) ? '' : ee()->config->item('sc_certificate_id')
						)
					)
				),
				array(
					'title' => 'commerce_cert_path',
					'desc' => 'commerce_cert_path_desc',
					'fields' => array(
						'sc_public_certificate' => array(
							'type' => 'text',
							'value' => (ee()->config->item('sc_public_certificate') === FALSE OR ee()->config->item('sc_public_certificate') == '') ? $base.'public_certificate.pem' : ee()->config->item('sc_public_certificate', '', TRUE)
						)
					)
				),
				array(
					'title' => 'commerce_key_path',
					'desc' => 'commerce_key_path_desc',
					'fields' => array(
						'sc_private_key' => array(
							'type' => 'text',
							'value' => (ee()->config->item('sc_private_key') === FALSE OR ee()->config->item('sc_private_key') == '') ? $base.'private_key.pem' : ee()->config->item('sc_private_key', '', TRUE)
						)
					)
				),
				array(
					'title' => 'commerce_paypal_cert_path',
					'desc' => 'commerce_paypal_cert_path_desc',
					'fields' => array(
						'sc_paypal_certificate' => array(
							'type' => 'text',
							'value' => (ee()->config->item('sc_paypal_certificate') === FALSE OR ee()->config->item('sc_paypal_certificate') == '') ? $base.'paypal_certificate.pem' : ee()->config->item('sc_paypal_certificate', '', TRUE)
						)
					)
				),
				array(
					'title' => 'commerce_temp_path',
					'desc' => 'commerce_temp_path_desc',
					'fields' => array(
						'sc_temp_path' => array('type' => 'text')
					)
				)
			)
		);

		if ( ! empty($_POST))
		{
			$result = ee('Validation')->make(array(
				'sc_paypal_account'     => 'email',
				'sc_encrypt_buttons'    => 'enum[y,n]',
				'sc_public_certificate' => 'fileExists',
				'sc_private_key'        => 'fileExists',
				'sc_paypal_certificate' => 'fileExists',
				'sc_temp_path'          => 'fileExists'
			))->validate($_POST);

			if (ee()->input->is_ajax_request())
			{
				$field = ee()->input->post('ee_fv_field');

				if ($result->hasErrors($field))
				{
					return array(
						'ajax' => TRUE,
						'body' => array('error' => $result->renderError($field))
					);
				}
				else
				{
					return array(
						'ajax' => TRUE,
						'body' => array('success')
					);
				}
			}

			if ($result->isValid())
			{
				// Unset API URL
				unset($vars['sections'][0][1]);

				$fields = array();

				// Make sure we're getting only the fields we asked for
				foreach ($vars['sections'] as $settings)
				{
					foreach ($settings as $setting)
					{
						foreach ($setting['fields'] as $field_name => $field)
						{
							$fields[$field_name] = ee()->input->post($field_name);
						}
					}
				}

				ee()->config->update_site_prefs($fields);

				ee('CP/Alert')->makeInline('shared-form')
						->asSuccess()
						->withTitle(lang('preferences_updated'))
						->addToBody(lang('preferences_updated_desc'))
						->defer();

				ee()->functions->redirect($base_url);
			}
			else
			{
				$vars['errors'] = $result;
				ee('CP/Alert')->makeInline('shared-form')
					->asIssue()
					->withTitle(lang('settings_save_error'))
					->addToBody(lang('settings_save_error_desc'))
					->now();
			}
		}

		return array(
			'heading' => $vars['cp_page_title'],
			'body' => ee('View')->make('simple_commerce:form')->render($vars),
			'sidebar' => $this->sidebar
		);
	}

	/** -------------------------------------------
	/**  Export Functions
	/** -------------------------------------------*/

	function export_purchases() { $this->export('purchases'); }
	function export_items() 	{ $this->export('items'); }

	function export($which='purchases')
	{

		ee()->load->helper('download');

		$tab  = ($this->export_type == 'csv') ? ',' : "\t";
		$cr	  = "\n";
		$data = '';

		$filename = $which.'_'.ee()->localize->format_date('%y%m%d').'.txt';

		if ($which == 'items')
		{
			$query = ee()->db->query("SELECT wt.title as item_name, sc.* FROM exp_simple_commerce_items sc, exp_channel_titles wt
								 WHERE sc.entry_id = wt.entry_id
								 ORDER BY item_name");
		}
		else
		{
			$query = ee()->db->query("SELECT wt.title AS item_purchased, m.screen_name AS purchaser, scp.*
								 FROM exp_simple_commerce_purchases scp, exp_simple_commerce_items sci, exp_members m, exp_channel_titles wt
								 WHERE scp.item_id = sci.item_id
								 AND sci.entry_id = wt.entry_id
								 AND scp.member_id = m.member_id
								 ORDER BY scp.purchase_date desc");
		}

		if ($query->num_rows() > 0)
		{
			foreach($query->row_array() as $key => $value)
			{
				if ($key == 'paypal_details') continue;

				$data .= $key.$tab;
			}

			$data = trim($data).$cr; // Remove end tab and add carriage

			foreach($query->result_array() as $row)
			{
				$datum = '';

				foreach($row as $key => $value)
				{
					$datum .= $value.$tab;
				}

				$data .= trim($datum).$cr;
			}
		}
		else
		{
			$data = 'No data';
		}

     force_download($filename, $data);


	}

	/*
================
	 NOTES
================

REQUIREMENTS
PayPal Premeire or Business Account
The accounts are free to sign up for and are needed to use the IPN (below). Click here to sign up:
https://www.paypal.com/cgi-bin/webscr?cmd=_registration-run

PayPal IPN (Instant Payment Notification) Activiation
This is needed for the user's account to be upgraded automatically. To activate your IPN:
- Log into your PayPal account
- Click on the "Profile" tab
- Then click "Selling Preferences"
- Instant Payment Notification Preferences'
- From there you have to enter a URL for the IPN to talk to.
This URL must be on your web site (i.e.-http://www.yoursite.com/ipn.asp).
*/

}

// EOF
