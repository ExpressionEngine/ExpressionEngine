<?php

namespace ExpressionEngine\Library\CP\EntryManager\Columns;

abstract class IconDateColumn extends Column
{
    protected function getIcon($entry, $column)
    {
        $icon = '';
        if($entry->status == 'closed') {
            $icon = 'closed';
        }

        if(!$icon) {
            if(ee()->localize->now >= $entry->entry_date) {
                $icon = 'past';
            } else if(ee()->localize->now <= $entry->entry_date) {
                $icon = 'future';
            }
        }
    }
}
