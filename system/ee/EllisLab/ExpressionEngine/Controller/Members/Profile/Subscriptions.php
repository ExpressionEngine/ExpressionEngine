<?php

namespace EllisLab\ExpressionEngine\Controller\Members\Profile;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use CP_Controller;

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Member Profile Subscriptions Settings Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */
class Subscriptions extends Settings {

	private $base_url = 'members/profile/subscriptions';

	public function __construct()
	{
		parent::__construct();
		ee()->load->library('members');
		$this->index_url = $this->base_url;
		$this->base_url = ee('CP/URL')->make($this->base_url, $this->query_string);
	}

	/**
	 * Subscriptions index
	 */
	public function index()
	{
		if (ee()->input->post('bulk_action') == 'unsubscribe')
		{
			$selection = ee()->input->post('selection');
			$this->unsubscribe($selection);
		}

		$links = array();
		$perpage = 50;
		$sort_col = 'title';
		$sort_dir = 'asc';
		$page = ee()->input->get('page') > 0 ? ee()->input->get('page') : 1;
		$search = ee()->input->post('search');
		$current = ($page - 1) * $perpage;
		$subscriptions = ee()->members->get_member_subscriptions($this->member->member_id, $current, $perpage);

		foreach ($subscriptions['result_array'] as $hash => $subscription)
		{
			if (empty($search) || stristr($subscription['title'], $search) !== FALSE)
			{
				$links[] = array(
					'title' => $subscription['title'],
					'type' => $subscription['type'],
					array(
						'name' => 'selection[]',
						'value' => $subscription['id'],
						'data'	=> array(
							'confirm' => lang('subscription') . ': <b>' . htmlentities($subscription['title'], ENT_QUOTES, 'UTF-8') . '</b>'
						)
					)
				);
			}
		}

		$table = ee('CP/Table');
		$table->setColumns(
			array(
				'title',
				'type',
				array(
					'type'	=> Table::COL_CHECKBOX
				)
			)
		);

		$table->setNoResultsText('no_subscriptions_found');
		$table->setData($links);

		$data['table'] = $table->viewData($this->base_url);

		ee()->javascript->set_global('lang.remove_confirm', lang('subscriptions') . ': <b>### ' . lang('subscriptions') . '</b>');
		ee()->cp->add_js_script(array(
			'file' => array('cp/confirm_remove'),
		));

		ee()->view->base_url = $this->base_url;
		ee()->view->ajax_validate = TRUE;
		ee()->view->cp_page_title = lang('subscriptions');
		ee()->cp->render('account/subscriptions', $data);
	}

	/**
	 * Delete Subscriptions
	 *
	 * @access public
	 * @return void
	 */
	public function unsubscribe($selection)
	{
		$type = array(
			'b' => 'comment',
			'f' => 'forum'
		);

		$column = array(
			'comment' => 'entry_id',
			'forum' => 'topic_id'
		);

		$delete = array();

		foreach ($selection as $id)
		{
			$char = $id[0];
			$id = substr($id, 1);
			$delete[$type[$char]][] = $id;
		}


		foreach ($delete as $type => $ids)
		{
			if (ee()->db->table_exists("exp_{$type}_subscriptions"))
			{
				ee()->db->where('member_id', $this->member->member_id);
				ee()->db->where_in($column[$type], $ids);
				ee()->db->delete("exp_{$type}_subscriptions");
			}
		}

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('unsubscribe_success'))
			->addToBody($cp_message)
			->defer();
		ee()->functions->redirect(ee('CP/URL')->make($this->index_url, $this->query_string));
	}

}

// END CLASS

// EOF
