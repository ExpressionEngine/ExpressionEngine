<?php

namespace EllisLab\ExpressionEngine\Controllers\Homepage;

use CP_Controller;

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
		$vars['number_of_new_comments'] = '?';

		$vars['number_of_pending_comments'] = ee('Model')->get('Comment')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('status', 'p')
			->count();

		$vars['number_of_spam_comments'] = '?';

		$vars['number_of_channels'] = ee('Model')->get('Channel')
			->filter('site_id', ee()->config->item('site_id'))
			->count();

		$vars['number_of_channel_fields'] = ee('Model')->get('ChannelFieldStructure')
			->filter('site_id', ee()->config->item('site_id'))
			->count();

		$vars['number_of_members'] = ee('Model')->get('Member')
			->count();

		$vars['number_of_banned_members'] = ee('Model')->get('MemberGroup', 2)
			->first()
			->getMembers()
			->count();

		$vars['number_of_entries'] = ee('Model')->get('ChannelEntry')
			->filter('site_id', ee()->config->item('site_id'))
			->count();

		$vars['number_of_comments'] = ee('Model')->get('Comment')
			->filter('site_id', ee()->config->item('site_id'))
			->count();

		$vars['number_of_closed_entries'] = ee('Model')->get('ChannelEntry')
			->filter('site_id', ee()->config->item('site_id'))
			->filter('status', 'closed')
			->count();

		$vars['number_of_comments_on_closed_entries'] = '?' ;/*ee('Model')->get('Comment')
			->with('Entry')
			->filter('Comment.site_id', ee()->config->item('site_id'))
			->filter('Entry.status', 'closed')
			->count();
		*/

		ee()->view->cp_page_title = ee()->config->item('site_name') . ' ' . lang('overview');
		ee()->cp->render('homepage', $vars);
	}

}
// EOF