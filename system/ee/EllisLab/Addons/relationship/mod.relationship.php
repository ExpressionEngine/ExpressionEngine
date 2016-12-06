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

	/**
	 * AJAX endpoint for filtering a Relationship field on the publish form
	 */
	public function entryList()
	{
		ee()->load->library('encrypt');

		$settings = ee()->encrypt->decode(
			ee('Request')->post('settings'),
			ee()->db->username.ee()->db->password
		);
		$settings = json_decode($settings, TRUE);

		if (empty($settings))
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$settings['search'] = ee('Request')->post('search');
		$settings['channel_id'] = ee('Request')->post('channel_id');

		if ( ! AJAX_REQUEST OR ! ee()->session->userdata('member_id'))
		{
			show_error(lang('unauthorized_access'), 403);
		}

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
