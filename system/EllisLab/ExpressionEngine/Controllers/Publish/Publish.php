<?php

namespace EllisLab\ExpressionEngine\Controllers\Publish;

use CP_Controller;
use EllisLab\ExpressionEngine\Library\CP\Pagination;
use EllisLab\ExpressionEngine\Library\CP\Table;
use EllisLab\ExpressionEngine\Library\CP\URL;
use EllisLab\ExpressionEngine\Module\Channel\Model\ChannelEntry;
/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Publish Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Publish extends CP_Controller {

	protected $is_admin = FALSE;
	protected $assigned_channel_ids = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		if ( ! ee()->cp->allowed_group('can_access_content'))
		{
			show_error(lang('unauthorized_access'));
		}

		ee()->lang->loadfile('content');

		$this->is_admin = (ee()->session->userdata['group_id'] == 1);
		$this->assigned_channel_ids = array_keys(ee()->session->userdata['assigned_channels']);
	}

	protected function createChannelFilter()
	{
		$allowed_channel_ids = ($this->is_admin) ? NULL : $this->assigned_channel_ids;
		$channels = ee('Model')->get('Channel', $allowed_channel_ids)
			->filter('site_id', ee()->config->item('site_id'))
			->order('channel_title', 'asc')
			->all();

		$channel_filter_options = array();
		foreach ($channels as $channel)
		{
			$channel_filter_options[$channel->channel_id] = $channel->channel_title;
		}
		$channel_filter = ee('Filter')->make('filter_by_channel', 'filter_by_channel', $channel_filter_options);
		$channel_filter->disableCustomValue(); // This may have to go
		return $channel_filter;
	}

	protected function getLayout(ChannelEntry $entry)
	{
		$layout = array();

		// Default Layout
		$layout[] = array(
			'name' => 'publish',
			'fields' => array(
				array('title', TRUE),
				array('url_title', TRUE)
			)
		);

		foreach ($entry->getChannel()->getCustomFields() as $info)
		{
			$layout[0]['fields'][] = array('field_id_' . $info->field_id, TRUE);
		}

		$layout[] = array(
			'name' => 'date',
			'fields' => array(
				array('entry_date', TRUE),
				array('expiration_date', TRUE),
				array('comment_expiration_date', TRUE)
			)
		);

		$layout[] = array(
			'name' => 'categories',
			'fields' => array(
				array('categories', TRUE)
			)
		);

		$layout[] = array(
			'name' => 'options',
			'fields' => array(
				array('channel_id', TRUE),
				array('status', TRUE),
				array('author_id', TRUE),
				array('sticky', TRUE),
				array('allow_comments', TRUE)
			)
		);

		foreach ($layout as &$section)
		{
			$fields = array();
			foreach ($section['fields'] as list($field_name, $visible))
			{
				if ($visible)
				{
					$fields[] = $entry->getForm($field_name);
				}
			}
			$section['fields'] = $fields;
		}

		return $layout;
	}

}
// EOF