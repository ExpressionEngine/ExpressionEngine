<?php
/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Library\CP\EntryManager\Columns;

use ExpressionEngine\Library\CP\EntryManager as Core;
use ExpressionEngine\Library\CP\EntryManager\Columns\Column;

/**
 * Title Column
 */
class Title extends Core\Columns\Title
{
    public function renderTableCell($data, $field_id, $entry, $viewtype = 'list', $pickerMode = false, $addQueryString = [])
    {
        $title = ee('Format')->make('Text', $entry->title)->convertToEntities();

        if ($this->canEdit($entry)) {
            $edit_link = ee('CP/URL')->make(
                'publish/edit/entry/' . $entry->entry_id,
                [
                    'site_id' => $entry->site_id,
                    'hide_closer' => 'y',
                    'preview' => 'y',
                    'prefer_system_preview' => 'y',
                    'return' => ee('Request')->get('current_uri')
                ],
                ee()->config->item('cp_url')
            );
            $title = '<a href="' . $edit_link . '" target="_top">' . $title . '</a>';
        }

        if ($entry->Autosaves->count()) {
            $title .= ' <span class="auto-save" title="' . lang('auto_saved') . '">&#10033;</span>';
        }

        return $title;
    }
}
