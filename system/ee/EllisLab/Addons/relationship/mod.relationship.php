<?php

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2016, EllisLab, Inc.
 * @license		https://expressionengine.com/license
 * @link		https://ellislab.com
 * @since		Version 3.4.5
 * @filesource
 */

// --------------------------------------------------------------------

/**
 * ExpressionEngine Relationship Module
 *
 * @package		ExpressionEngine
 * @subpackage	Modules
 * @category	Modules
 * @author		EllisLab Dev Team
 * @link		https://ellislab.com
 */

class Relationship {

	public function entryList()
	{
		if ( ! AJAX_REQUEST OR ! ee()->session->userdata('member_id'))
		{
			show_error(lang('unauthorized_access'));
		}

		$settings = array(
			'channels'    => ee('Request')->post('channels'),
			'categories'  => ee('Request')->post('categories'),
			'statuses'    => ee('Request')->post('statuses'),
			'authors'     => ee('Request')->post('authors'),
			'limit'       => ee('Request')->post('limit'),
			'expired'     => ee('Request')->post('expired'),
			'future'      => ee('Request')->post('future'),
			'order_field' => ee('Request')->post('order_field'),
			'order_dir'   => ee('Request')->post('order_dir'),
			'entry_id'    => ee('Request')->post('entry_id'),
			'search'      => ee('Request')->post('search'),
			'channel_id'  => ee('Request')->post('channel_id')
		);

		// Filter based on assigned channels, too, to prevent any monkey business

		ee()->load->library('EntryList');
		$entries = ee()->entrylist->query($settings);

		$response = array();
		foreach ($entries as $entry)
		{
			$response[] = array(
				'entry_id'     => $entry->getId(),
				'title'        => htmlentities($entry->title, ENT_QUOTES, 'UTF-8'),
				'channel_id'   => $entry->Channel->getId(),
				'channel_name' => htmlentities($entry->Channel->channel_title, ENT_QUOTES, 'UTF-8')
			);
		}

		ee()->output->send_ajax_response($response);
	}
}

// EOF
