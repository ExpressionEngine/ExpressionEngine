<?php $this->extend('_templates/out', [], 'outer_box'); ?>
<div class="panel">
    <div class="panel-heading">
        <div class="app-notice-wrap">
            <?=ee('CP/Alert')->getAllInlines()?>
        </div>
    </div>
</div>