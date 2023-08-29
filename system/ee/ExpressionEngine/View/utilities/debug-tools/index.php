<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="panel">

  <div class="panel-heading">
    <div class="title-bar">
        <h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>
    </div>
  </div>

  <div class="panel-body">

    <?php
        $alerts = [];

        $alert = ee('CP/Alert')
            ->makeInline()
            ->withTitle(lang('debug_tools_debug_duplicate_template_groups'));
        if ($duplicate_template_groups_count > 0) {
            $alert->addToBody(sprintf(lang('duplicate_template_groups_found'), $duplicate_template_groups_count) . '<br><a href="' . ee('CP/URL')->make('utilities/debug-tools/duplicate-template-groups') . '">' . lang('review_duplicate_template_groups') . '</a>');
            $alert->asImportant();
        } else {
            $alert->addToBody(lang('no_duplicate_template_groups_found'));
            $alert->asSuccess();
        }
        $alerts[] = $alert;

        $alert = ee('CP/Alert')
            ->makeInline()
            ->withTitle(lang('debug_tools_debug_tags'))
            ->addToBody(sprintf(lang('debug_tools_broken_tags_found'), $bad_tags_count) . '<br><a href="' . ee('CP/URL')->make('utilities/debug-tools/debug-tags') . '">' . lang('debug_tools_debug_tags') . '</a>');
        if ($bad_tags_count == 0) {
            $alert->asSuccess();
        } else {
            $alert->asImportant();
        }
        $alerts[] = $alert;

        $alert = ee('CP/Alert')
            ->makeInline()
            ->withTitle(lang('debug_tools_fieldtypes'))
            ->addToBody(sprintf(lang('debug_tools_found_missing_fieldtypes'), $missing_fieldtype_count) . '<br><a href="' . ee('CP/URL')->make('utilities/debug-tools/debug-fieldtypes') . '">' . lang('debug_tools_show_missing_fieldtypes') . '</a>');
        if ($missing_fieldtype_count == 0) {
            $alert->asSuccess();
        } else {
            $alert->asImportant();
        }
        $alerts[] = $alert;

        foreach ($alerts as $alert) {
            $alert->cannotClose();
            echo $alert->render();
        }
        ?>
    <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

  </div>

</div>
