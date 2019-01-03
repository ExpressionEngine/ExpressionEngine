<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Channels;

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder;
use EllisLab\ExpressionEngine\Service\CP\Filter\Filter;
use EllisLab\ExpressionEngine\Service\Filter\FilterFactory;

/**
 * Abstract Channels
 */
abstract class AbstractChannels extends CP_Controller {

	protected $perpage = 25;
	protected $page = 1;
	protected $offset = 0;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		ee('CP/Alert')->makeDeprecationNotice()->now();

		// Allow AJAX requests for category editing
		if (AJAX_REQUEST && in_array(ee()->router->method, array('createCat', 'editCat')))
		{
			if ( ! ee()->cp->allowed_group_any(
				'can_create_categories',
				'can_edit_categories'
			))
			{
				show_error(lang('unauthorized_access'), 403);
			}
		}
		else
		{
			if ( ! ee()->cp->allowed_group('can_admin_channels'))
			{
				show_error(lang('unauthorized_access'), 403);
			}
			elseif ( ! ee()->cp->allowed_group_any(
				'can_create_channels',
				'can_edit_channels',
				'can_delete_channels',
				'can_create_channel_fields',
				'can_edit_channel_fields',
				'can_delete_channel_fields',
				'can_create_statuses',
				'can_delete_statuses',
				'can_edit_statuses'
				))
			{
				show_error(lang('unauthorized_access'), 403);
			}
		}

		ee()->lang->loadfile('content');
		ee()->lang->loadfile('admin_content');
		ee()->lang->loadfile('channel');
		ee()->load->library('form_validation');

		// This header is section-wide
		ee()->view->header = array(
			'title' => lang('channel_manager'),
			'toolbar_items' => array(
				'settings' => array(
					'href' => ee('CP/URL')->make('settings/content-design'),
					'title' => lang('settings')
				)
			)
		);

		ee()->javascript->set_global(
			'sets.importUrl',
			ee('CP/URL', 'channels/sets')->compile()
		);

		ee()->cp->add_js_script(array(
			'file' => array('cp/channel/menu'),
		));
	}

	/**
	 * Display filters
	 *
	 * @param filter object
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
