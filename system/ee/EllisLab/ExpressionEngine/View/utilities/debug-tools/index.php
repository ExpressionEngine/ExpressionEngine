<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="table-list-wrap">
    <h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
</div>

<div class="box mb">
    <h1><?=lang('debug_tools_debug_tags')?></h1>
    <div class="md-wrap">
        <ul class="checklist">
            <?=sprintf(lang('debug_tools_broken_tags_found'), $bad_tags_count)?>
            <br><br><a href='<?= ee('CP/URL')->make('utilities/debug-tools/debug-tags') ?>'><?=lang('debug_tools_debug_tags')?></a>
        </ul>
    </div>
</div>

<div class="box mb">
    <h1><?=lang('debug_tools_fieldtypes')?></h1>
    <div class="md-wrap">
        <ul class="checklist">
        <?=sprintf(lang('debug_tools_found_missing_fieldtypes'), $missing_fieldtype_count)?>
            <?php if ($missing_fieldtype_count): ?>
                <br><br><a href='<?= ee('CP/URL')->make('utilities/debug-tools/debug-fieldtypes') ?>'><?=lang('debug_tools_show_missing_fieldtypes')?></a>
            <?php endif; ?>
        </ul>
    </div>
</div>


<br>



