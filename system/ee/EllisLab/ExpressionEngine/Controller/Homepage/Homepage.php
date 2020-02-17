<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\ExpressionEngine\Controller\Homepage;

use CP_Controller;

/**
 * Homepage Controller
 */
class Homepage extends CP_Controller {

	public function index()
	{
		$this->redirectIfNoSegments();

		ee('CP/Alert')->makeDeprecationNotice()->now();

		// First login, this is 0 on the first page load
		$vars['last_visit'] = (empty(ee()->session->userdata['last_visit'])) ? ee()->localize->human_time() : ee()->localize->human_time(ee()->session->userdata['last_visit']);

		if (ee()->config->item('enable_comments') == 'y')
		{
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
				->count();
		}

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

		$vars['spam_module_installed'] = (bool) ee('Model')->get('Module')->filter('module_name', 'Spam')->count();

		if ($vars['spam_module_installed'])
		{
			$vars['number_of_new_spam'] = ee('Model')->get('spam:SpamTrap')
				->filter('site_id', ee()->config->item('site_id'))
				->filter('trap_date', '>', ee()->session->userdata['last_visit'])
				->count();

			$vars['number_of_spam'] = ee('Model')->get('spam:SpamTrap')
				->filter('site_id', ee()->config->item('site_id'))
				->count();

			// db query to aggregate
			$vars['trapped_spam'] = ee()->db->select('content_type, COUNT(trap_id) as total_trapped')
				->group_by('content_type')
				->get('spam_trap')
				->result();

			foreach ($vars['trapped_spam'] as $trapped)
			{
				ee()->lang->load($trapped->content_type);
			}

			$vars['can_moderate_spam'] = ee('Permission')->can('moderate_spam');
		}

		$vars['can_view_homepage_news'] = bool_config_item('show_ee_news')
			&& ee('Permission')->can('view_homepage_news');

		if ($vars['can_view_homepage_news'])
		{
			// Gather the news
			ee()->load->library(array('rss_parser', 'typography'));
			$url_rss = 'https://expressionengine.com/blog/rss-feed/cpnews/';
			$vars['url_rss'] = ee()->cp->masked_url($url_rss);
			$news = array();

			try
			{
				$feed = ee()->rss_parser->create(
					$url_rss,
					60 * 6, // 6 hour cache
					'cpnews_feed'
				);

				foreach ($feed->get_items(0, 10) as $item)
				{
					$news[] = array(
						'title'   => strip_tags($item->get_title()),
						'date'    => ee()->localize->format_date(
							"%j%S %M, %Y",
							$item->get_date('U')
						),
						'content' => ee('Security/XSS')->clean(
							ee()->typography->parse_type(
								$item->get_content(),
								array(
									'text_format'   => 'xhtml',
									'html_format'   => 'all',
									'auto_links'    => 'y',
									'allow_img_url' => 'n'
								)
							)
						),
						'link'    => ee()->cp->masked_url($item->get_permalink())
					);
				}

				$vars['news'] = $news;
			}
			catch (\Exception $e)
			{
				// Nothing to see here, the view will take care of it
			}
		}

		if (bool_config_item('share_analytics'))
		{
			require_once(APPPATH.'libraries/El_pings.php');
			$pings = new \El_pings();
			$pings->shareAnalytics();
		}


		/**
 * Tom testing tempalte parsing as a whole.
 */
		//----------------------------------------
        // Allows template parsing!
        //----------------------------------------
//         ee()->load->library('template', null, 'TMPL');

//         $out = '{exp:channel:form channel="blog" return="channel_name/edit/ENTRY_ID" entry_id="1"}

//     <label for="title">Title</label>
//     <input type="text" name="title" id="title" value="{title}" size="50" maxlength="100" onkeyup="liveUrlTitle(event);">

//     <label for="url_title">URL Title</label>
//     <input type="text" name="url_title" id="url_title" value="{url_title}" maxlength="75" size="50">

//     {custom_fields}
//      <br><br><br>
//         <label for="{field_name}">{if required}* {/if}{field_label}</label>

//         {field_instructions}
//         {formatting_buttons}

//         {if error}
//           <p class="error">{error}</p>
//         {/if}


//         {if text}
//           <input type="text" dir="{text_direction}" id="{field_name}" name="{field_name}" value="{field_data}" maxlength="{maxlength}" size="50">
//         {/if}

//         {if grid}
//           {display_field}
//           <br>
//           <br>
//         {/if}

// 	    {if textarea}
//           {display_field}
//           <br>
//           <br>
//         {/if}

//         {if multiselect}
//           <select id="{field_name}" name="{field_name}[]" multiple="multiple">
//             {options}
//               <option value="{option_value}"{selected}>{option_name}</option>
//             {/options}
//           </select>
//       {/if}

//     {/custom_fields}


//     <input type="submit" name="submit" value="Submit">
// {/exp:channel:form}';

//         ee()->TMPL->parse($out, false, ee()->config->item('site_id'));
//         $out = ee()->TMPL->final_template;

//         // remove EE comments to fix bug in EE 2.9.0
//         if (method_exists(ee()->TMPL, 'remove_ee_comments')) {
//             $out = ee()->TMPL->remove_ee_comments($out);
//         }
//         $vars['out'] = $out;
        // return $out;
// var_dump($out);
// exit;
/**
 * END Tom testing tempalte parsing as a whole.
 */

		$vars['can_moderate_comments'] = ee('Permission')->can('moderate_comments');
		$vars['can_edit_comments'] = ee('Permission')->can('edit_all_comments');
		$vars['can_access_members'] = ee('Permission')->can('access_members');
		$vars['can_create_members'] = ee('Permission')->can('create_members');
		$vars['can_access_channels'] = ee('Permission')->can('admin_channels');
		$vars['can_create_channels'] = ee('Permission')->can('create_channels');
		$vars['can_access_fields'] = ee('Permission')->hasAll('can_create_channel_fields', 'can_edit_channel_fields', 'can_delete_channel_fields');
		$vars['can_access_member_settings'] = ee('Permission')->hasAll('can_access_sys_prefs', 'can_access_members');
		$vars['can_create_entries'] = ee('Permission')->can('can_create_entries');

		$vars['header'] = array(
			'title' => ee()->config->item('site_name'),
		);

		ee()->view->cp_page_title = ee()->config->item('site_name') . ' ' . lang('overview');
		ee()->cp->render('homepage', $vars);
	}

	/**
	 * If we arrive to this controller's index as a result of being the default
	 * controller, check to see if there is a default homepage we should be
	 * redirecting to instead
	 */
	private function redirectIfNoSegments()
	{
		if (empty(ee()->uri->segments))
		{
			$member_home_url = ee('Model')->get('Member', ee()->session->userdata('member_id'))
				->first()
				->getCPHomepageURL();

			if ($member_home_url->path != 'homepage')
			{
				// Preserve updater result status messages
				if (ee('Request')->get('update'))
				{
					$member_home_url->setQueryStringVariable(
						'update',
						ee('Request')->get('update')
					);
				}

				ee()->session->benjaminButtonFlashdata();

				$this->functions->redirect($member_home_url);
			}
		}
	}

	public function acceptChecksums()
	{
		if ( ! ee('Permission')->isSuperAdmin())
		{
			show_error(lang('unauthorized_access'), 403);
		}

		$return = ee('CP/URL')->make('homepage');

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

			$return = ee('CP/URL')->decodeUrl(ee()->input->post('return'));
		}

		ee()->functions->redirect($return);
	}

	/**
	 * Records that the changelog for this version of EE has been viewed by
	 * this member, and then redirects to the changelog.
	 */
	public function showChangelog()
	{
		$news_view = ee('Model')->get('MemberNewsView')
			->filter('member_id', ee()->session->userdata('member_id'))
			->first();

		if ( ! $news_view)
		{
			$news_view = ee('Model')->make(
				'MemberNewsView',
				['member_id' => ee()->session->userdata('member_id')]
			);
		}

		$news_view->version = APP_VER;
		$news_view->save();

		ee()->functions->redirect(
			ee()->cp->makeChangelogLinkForVersion(APP_VER)
		);
	}

}

// EOF
