<?php

$this->extend('_templates/default-nav', [], 'outer_box');
?>
<div class="panel">
    <div class="panel-heading">
        <div class="title-bar title-bar--large">
            <h3 class="title-bar__title"><?=ee('Format')->make('Text', (isset($cp_page_title_alt)) ? $cp_page_title_alt : $cp_page_title)->attributeSafe()->compile()?></h3>
        </div>
    </div>
    <div class="panel-body">
        <?php
        if (isset($extra_alerts)) {
            foreach ($extra_alerts as $alert) {
                echo ee('CP/Alert')->get($alert);
            }
        }
        ?>
    </div>
</div>
