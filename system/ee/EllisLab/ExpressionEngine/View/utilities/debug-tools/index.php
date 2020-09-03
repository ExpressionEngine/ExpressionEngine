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
    <h1><?=lang('debug_tools_corrupt_categories')?></h1>
    <div class="md-wrap">
        <ul class="checklist">
            <?=sprintf(lang('debug_tools_corrupt_categories_found'), $broken_category_count)?>
            <?php if ($broken_category_count): ?>
                <br><br><a href='<?= ee('CP/URL')->make('utilities/debug-tools/category-fix') ?>'><?=lang('debug_tools_fix_corrupt_categories')?></a>
            <?php endif; ?>
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

<div class="box">
    <h1><?=lang('debug_tools_duplicate_layout_tabs')?></h1>
    <div class="md-wrap">
        <ul class="checklist">
            <?=sprintf(lang('debug_tools_duplicate_layout_tabs_found'), $duplicate_tabs_count)?>
            <?php if ($duplicate_tabs_count): ?>
                <br><br><a href='<?= ee('CP/URL')->make('utilities/debug-tools/showDuplicateChannelLayoutTabs') ?>'>Manage Channel Layout Tabs</a>
            <?php endif; ?>
        </ul>
    </div>
</div>

<br>



