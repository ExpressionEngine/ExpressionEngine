<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="panel">

    <div class="panel-heading">
        <div class="title-bar">
            <h2 class="title-bar__title"><?=lang('debug_tools_channel_entries')?></h2>
        </div>
    </div>

    <div class="panel-body">
        <?php
        foreach ($entries_missing_data as $type => $data) {
            if (count($data) > 0) {
                $info = '';
                foreach ($data as $entry_id => $title) {
                    $info .= '<li class="last">ID# ' . $entry_id . ': ' . $title . '</li>';
                }
                echo ee('CP/Alert')
                    ->makeInline()
                    ->withTitle(sprintf(lang('debug_tools_entries_missing_data_desc'), lang($type)))
                    ->addToBody('<ul>' . $info . '</ul>')
                    ->asImportant()
                    ->render();
            } else {
                echo ee('CP/Alert')
                    ->makeInline()
                    ->withTitle(sprintf(lang('debug_tools_no_entries_missing_data_desc'), lang($type)))
                    ->asSuccess()
                    ->cannotClose()
                    ->render();
            }
        }

        ?>

    </div>

</div>