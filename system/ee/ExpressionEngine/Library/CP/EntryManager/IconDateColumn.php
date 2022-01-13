<?php

namespace ExpressionEngine\Library\CP\EntryManager\Columns;

abstract class IconDateColumn extends Column
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
        if($entry->status == 'closed') {
            $icon = 'closed';
        }

        if(!$icon && !empty($entry->$column)) {
            if(ee()->localize->now >= $entry->$column) {
                $icon = 'already-published';
            } else if(ee()->localize->now <= $entry->$column) {
                $icon = 'future';
            }
        }

        return '<span="col-date-'.$icon.'">-'.$icon.'-</span>';
    }

    /**
     * We gotta render some HTML
     * @return false[]
     */
    public function getTableColumnConfig()
    {
        return [
            'encode' => false
        ];
    }
}
