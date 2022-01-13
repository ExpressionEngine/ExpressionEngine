<?php

namespace ExpressionEngine\Library\CP\EntryManager;

use ExpressionEngine\Library\CP\EntryManager\Columns\Column;

abstract class ColumnIconDate extends Column
{
    /**
     * Should return the Icon
     * @param $entry
     * @param $column
     * @return string
     */
    protected function getIcon($entry, $column)
    {
        $icon = '';
        if ($entry->status == 'closed') {
            $icon = 'closed';
        }

        if (!$icon && !empty($entry->$column)) {
            if (ee()->localize->now >= $entry->$column) {
                $icon = 'already-published';
            } else if (ee()->localize->now <= $entry->$column) {
                $icon = 'future';
            }
        }

        return '<span="col-date-'.$icon.'">-'.$icon.'-</span>';
    }

    /**
     * @return bool[]
     */
    public function getTableColumnConfig()
    {
        $encode = $this->shouldDisplayIcon() === false;
        return [
            'encode' => $encode
        ];
    }

    /**
     * @return bool
     */
    protected function shouldDisplayIcon()
    {
        return ee()->config->item('entry_icon_dates') == 'y';
    }
}
