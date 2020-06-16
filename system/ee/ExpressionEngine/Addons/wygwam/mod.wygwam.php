<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2020, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Wygwam Module
 */
class Wygwam {


	public function pages_autocomplete()
	{

		$search = ee()->input->get('search');
		$pages = [];

		$modified = ee()->input->get('t');
		if ($modified == 0) {
			$modified = ee()->localize->now;
		}

		ee()->output->set_status_header(200);
		@header("Cache-Control: max-age=172800, must-revalidate");
		@header('Vary: Accept-Encoding');
		@header('Last-Modified: '.ee()->localize->format_date('%D, %d %M %Y %H:%i:%s', $modified, false).' GMT');
		@header('Expires: '.ee()->localize->format_date('%D, %d %M %Y %H:%i:%s', ee()->localize->now + 172800, false).' GMT');

		$cache_key = '/site_pages/wygwam';
		if (!empty($search)) {
			$cache_key .= '_' . urlencode($search);
		}
		$pages = ee()->cache->get($cache_key);

		if ($pages === FALSE) {

			// `wygwam_autocomplete_pages` extension hook
			if (ee()->extensions->active_hook('wygwam_autocomplete_pages') === true) {
				$pages = ee()->extensions->call('wygwam_autocomplete_pages', $this, $pages, $search);
				if (ee()->extensions->end_script === true) {
					ee()->cache->save($cache_key, $pages, 0);
					ee()->output->send_ajax_response($pages);
				}
			}

			$site_pages = ee()->config->item('site_pages');
			$site_id = ee()->config->item('site_id');
			$entry_ids = array_keys($site_pages[$site_id]['uris']);
			$channels = ee('Model')->get('Channel')
				->fields('channel_id', 'channel_title')
				->all()
				->getDictionary('channel_id', 'channel_title');
			$entries = ee('Model')->get('ChannelEntry', $entry_ids)
				->fields('entry_id', 'title', 'url_title', 'channel_id')
				->all();
			$titles = $entries->getDictionary('entry_id', 'title');
			$channel_ids = $entries->getDictionary('entry_id', 'channel_id');
			foreach($site_pages[$site_id]['uris'] as $entry_id => $url) {
				if (isset($titles[$entry_id])) {
					$pages[] = (object) [
						'id' => '@' . $entry_id,
						'text' => $titles[$entry_id],
						'extra' => $channels[$channel_ids[$entry_id]],
						'href' => '{page_' . $entry_id . '}'//$url
					];
				}
			}
			ee()->cache->save($cache_key, $pages, 0);
		}

		ee()->output->send_ajax_response($pages);

	}


}
// END CLASS

// EOF
