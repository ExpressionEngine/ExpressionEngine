<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box mb">
    <h1><?=lang('debug_tools_missing_fieldtypes')?></h1>
    <div class="md-wrap">

        <?php if ($missing_fieldtype_count) : ?>
            <p><?=lang('debug_tools_missing_fieldtypes_desc')?></p>
            <ul class="">
                <?php foreach ($missing_fieldtypes as $fieldtype => $tables) : ?>
                    <li class="last"><code><?= $fieldtype ?> (<?=implode(", ", $tables)?>)</code></li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p><?=lang('debug_tools_no_missing_fieldtypes_desc')?></p>
        <?php endif; ?>
    </div>
</div>

<div class="box mb">
    <h1><?=lang('debug_tools_installed_unused_fieldtypes')?></h1>
    <div class="md-wrap">
        <p><?=lang('debug_tools_installed_unused_fieldtypes_desc')?></p>

        <ul class="">
            <?php foreach ($unused_fieldtypes as $fieldtype) : ?>
                <li class="last"><code><?= $fieldtype ?></code></li>
            <?php endforeach; ?>
        </ul>

    </div>
</div>

<div class="box">
    <h1><?=lang('debug_tools_all_used_fieldtypes')?></h1>
    <div class="md-wrap">

        <ul class="">
            <?php foreach ($used_fieldtypes as $fieldtype) : ?>
                <li class="last"><code><?= $fieldtype ?></code></li>
            <?php endforeach; ?>
        </ul>

    </div>
</div>


