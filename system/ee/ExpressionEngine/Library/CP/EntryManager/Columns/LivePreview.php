<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\CP\EntryManager\Columns;

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;

/**
 * Live Preview Column
 */
class LivePreview extends Column
{
    public function getTableColumnLabel()
    {
        return 'preview';
    }

    public function getTableColumnConfig()
    {
        return [
            'encode' => false
        ];
    }

    public function renderTableCell($data, $field_id, $entry)
    {
        $previewUrl = ee('LivePreview')->createLivePreviewModal($entry, 'url');
        if (empty($previewUrl)) {
            return '';
        }
        return '<button class="button button--default button--xsmall" rel="entry-preview" data-url="' . $previewUrl . '" data-title="' . ee('Format')->make('Text', $entry->title)->convertToEntities() . '"><i class="fal fa-eye"></i></button>';
    }
}
