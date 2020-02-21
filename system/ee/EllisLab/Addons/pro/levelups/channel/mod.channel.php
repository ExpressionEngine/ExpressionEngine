<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

use EllisLab\Addons\Pro\Components\LiteLoader;

LiteLoader::loadIntoNamespace('channel/mod.channel.php');
LiteLoader::loadIntoNamespace('channel/mod.channel_calendar.php'); 

/**
 * Channel Module
 */
class Channel extends Lite\Channel {


	/**
	 * popcorn creats the auto magical editing interface for a given field.
	 *
	 * @method popcorn
	 * @return Template string parsed already?
	 */
	public function single_field_editor()
	{



		ee()->load->library('channel_form/channel_form_lib');
		//ee()->load->library('view');
		ee()->lang->loadfile('channel');

		ee()->load->library('template', null, 'TMPL');
		ee()->TMPL->tagparams['channel_id'] = ee()->input->get_post('channel_id');
		ee()->TMPL->tagparams['entry_id'] = $entry_id = ee()->input->get_post('entry_id');
		ee()->TMPL->tagparams['require_entry'] = 'yes';
		ee()->TMPL->tagparams['return'] = @$_SERVER['HTTP_REFERER'];
		ee()->TMPL->tagparams['show_fields'] = ee()->input->get_post('short_name');

		$full_link = NULL;
		if (ee('Permission')->can('access_cp'))
		{
			$full_link = ee('CP/URL')->make('publish/edit/entry/' . $entry_id . AMP . 'preview=y' . AMP .'hide_closer=y' . AMP . 'return='.urlencode(ee()->TMPL->tagparams['return']), [], ee()->config->item('cp_url'));
		}		

		ee()->TMPL->tagdata = ee('View')->make('channel:single_field_editor')->render(['full_link'	=> $full_link]);

		$out = '';

		if ( ! empty(ee()->TMPL))
		{
			try
			{
				$template = ee()->channel_form_lib->entry_form();
				ee()->TMPL->parse($template, false, ee()->config->item('site_id'));
				$form = ee()->TMPL->parse_globals(ee()->TMPL->final_template);
				
				$out = ee('View')->make('channel:single_field_editor_wrapper')->render([
					'form' => $form
				]);
				ee()->TMPL->parse($out, false, ee()->config->item('site_id'));
				$out = ee()->TMPL->parse_globals(ee()->TMPL->final_template);

				if (method_exists(ee()->TMPL, 'remove_ee_comments')) 
				{
					$out = ee()->TMPL->remove_ee_comments($out);
				}

				echo $out;
			}
			catch (Channel_form_exception $e)
			{
				echo $e->show_user_error();
			}
		}

    exit();

	}


}
// END CLASS

// EOF
