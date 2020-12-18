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
        $alerts[0] = ee('CP/Alert')
            ->makeInline()
            ->withTitle(lang('debug_tools_debug_tags'))
            ->addToBody(sprintf(lang('debug_tools_broken_tags_found'), $bad_tags_count) . '<br><a href="' . ee('CP/URL')->make('utilities/debug-tools/debug-tags') . '">' . lang('debug_tools_debug_tags') . '</a>');
        if ($bad_tags_count == 0) {
            $alerts[0]->asSuccess();
        } else {
            $alerts[0]->asImportant();
        }

        $alerts[1] = ee('CP/Alert')
            ->makeInline()
            ->withTitle(lang('debug_tools_fieldtypes'))
            ->addToBody(sprintf(lang('debug_tools_found_missing_fieldtypes'), $missing_fieldtype_count) . '<br><a href="' . ee('CP/URL')->make('utilities/debug-tools/debug-fieldtypes') . '">' . lang('debug_tools_show_missing_fieldtypes') . '</a>');
        if ($missing_fieldtype_count == 0) {
            $alerts[1]->asSuccess();
        } else {
            $alerts[1]->asImportant();
        }

        foreach ($alerts as $alert) {
            $alert->cannotClose();
            echo $alert->render();
        }
        ?>
    <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

  </div>

</div>
