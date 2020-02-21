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

	public function hasFrontEditPermission($channel_id, $entry_id)
	{
		$has_permission = ee('Permission')->can('edit_other_entries_channel_id_'.$channel_id);
		if (!$has_permission)
		{
			$author_id = ee()->db->select('author_id')
				->where('entry_id', $entry_id)
				->where('channel_id', $channel_id)
				->get('channel_titles');
			if ($author_id->num_rows()==1 && $author_id->row('author_id')==ee()->session->userdata('member_id'))
			{
				$has_permission = ee('Permission')->can('edit_self_entries_channel_id_'.$channel_id);
			}
		}

		return $has_permission;
	}

	/**
	 * Get edit link for entry field
	 *  
   * @param int $channel_id Channel id
   * @param int $entry_id Entry id
	 * @param string $field_short_name Field short name
	 */
	public function entryFieldEditLink($channel_id, $entry_id, $field_short_name)
	{
		$has_permission = $this->hasFrontEditPermission($channel_id, $entry_id);
		if (! AJAX_REQUEST && !ee('LivePreview')->hasEntryData() && $has_permission)
		{
			$action_id = ee()->db->select('action_id')
				->where('class', 'Channel')
				->where('method', 'single_field_editor')
				->get('actions');
			if ($action_id->num_rows()!=1) return '';

			$edit_link = "<a href=\"".ee()->functions->fetch_site_index().QUERY_MARKER.'ACT='.$action_id->row('action_id').AMP.'channel_id='.$channel_id.AMP.'entry_id='.$entry_id.AMP.'short_name='.$field_short_name."\" class=\"eeFrontEdit\">".lang('edit_this')."</a>";
			
			return $edit_link;
		}
	}

}

// EOF
