<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2020, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace EllisLab\Addons\Pro\Service\FrontEdit;

/**
 * Frontend edit service
 */
class FrontEdit {

	/**
	 * Get edit link for entry field
	 *  
   * @param int $channel_id Channel id
   * @param int $entry_id Entry id
	 * @param string $field_short_name Field short name
	 */
	public function entryFieldEditLink($channel_id, $entry_id, $field_short_name)
	{
		$action_id = ee()->db->select('action_id')
			->where('class', 'Channel')
			->where('method', 'single_field_editor')
			->get('actions');
		if ($action_id->num_rows()!=1) return '';

		$edit_link = "<a href=\"".ee()->functions->fetch_site_index().QUERY_MARKER.'ACT='.$action_id->row('action_id').AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id.AMP.'short_name='.$field_short_name."\" class=\"ee_popcorn\">".lang('edit_this')." (entry_id=".$entry_id.")</a>";
		
		return $edit_link;
	}

}

// EOF
