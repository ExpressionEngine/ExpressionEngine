<?php

namespace EllisLab\ExpressionEngine\Controller\Homepage;

use CP_Controller;

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2015, EllisLab, Inc.
 * @license		https://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 3.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * ExpressionEngine CP Homepage Class
 *
 * @package		ExpressionEngine
 * @subpackage	Control Panel
 * @category	Control Panel
 * @author		EllisLab Dev Team
 * @link		http://ellislab.com
 */
class Homepage extends CP_Controller {

	public function index()
	{
		$stats = ee('Model')->get('Stats')
			->filter('site_id', ee()->config->item('site_id'))
			->first();

		$vars['number_of_members'] = $stats->total_members;
		$vars['number_of_entries'] = $stats->total_entries;
		$vars['number_of_comments'] = $stats->total_comments;

		$vars['last_visit'] = ee()->localize->human_time(ee()->session->userdata['last_visit']);

		$vars['number_of_new_comments'] = ee('Model')->get('Comment')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('comment_date', '>', ee()->session->userdata['last_visit'])
			->count();

		$vars['number_of_pending_comments'] = ee('Model')->get('Comment')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('status', 'p')
			->count();

		$vars['number_of_spam_comments'] = ee('Model')->get('Comment')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('status', 's')
			->count();;

		$vars['number_of_channels'] = ee('Model')->get('Channel')
			->filter('site_id', ee()->config->item('site_id'))
			->count();

		if ($vars['number_of_channels'] == 1)
		{
			$vars['channel_id'] = ee('Model')->get('Channel')
				->filter('site_id', ee()->config->item('site_id'))
				->first()
				->channel_id;
		}

		$vars['number_of_channel_fields'] = ee('Model')->get('ChannelField')
			->filter('site_id', ee()->config->item('site_id'))
			->count();

		$vars['number_of_banned_members'] = ee('Model')->get('MemberGroup', 2)
			->first()
			->getMembers()
			->count();

		$vars['number_of_closed_entries'] = ee('Model')->get('ChannelEntry')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('status', 'closed')
			->count();

		$vars['number_of_comments_on_closed_entries'] = ee('Model')->get('Comment')
			->with('Entry')
			->filter('Comment.site_id', ee()->config->item('site_id'))
			->filter('Entry.status', 'closed')
			->count();

		ee()->view->cp_page_title = ee()->config->item('site_name') . ' ' . lang('overview');
		ee()->cp->render('homepage', $vars);
	}

	public function acceptChecksums()
	{
		if (ee()->session->userdata('group_id') != 1)
		{
			show_error(lang('unauthorized_access'));
		}

		$return = ee('CP/URL', 'homepage');

		if (ee()->input->post('return'))
		{
			ee()->load->library('file_integrity');
			$changed = ee()->file_integrity->check_bootstrap_files(TRUE);

			if ($changed)
			{
				foreach($changed as $site_id => $paths)
				{
					foreach($paths as $path)
					{
						ee()->file_integrity->create_bootstrap_checksum($path, $site_id);
					}
				}
			}

			$return = base64_decode(ee()->input->post('return'));
			$uri_elements = json_decode($return, TRUE);
			$return = ee('CP/URL', $uri_elements['path'], $uri_elements['arguments']);
		}

		ee()->functions->redirect($return);
	}

}
// EOF