<div class="panel" <?php if (ee()->uri->segment(3) == 'prolet') : ?> style="overflow: hidden; margin-bottom: 0px; margin-top: -16px; margin-left: -16px; margin-right: -16px; border-radius: 0;"<?php endif; ?>>
    <div class="tbl-ctrls">
        <?=form_open($form_url)?>

        <div class="panel-heading"<?php if (ee()->uri->segment(3) == 'prolet') : ?> style="display: none;"<?php endif; ?>>
          <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
          <?php if (isset($cp_heading)) : ?>
          <div class="title-bar">
              <h3 class="title-bar__title"><?=$cp_heading?></h3>
          </div>
          <?php endif; ?>
        </div>

        <div class="filter-search-bar" <?php if (ee()->uri->segment(3) == 'prolet') : ?> style="padding: 10px 10px;"<?php endif; ?>>

            <!-- All filters (not including search input) are contained within 'filter-search-bar__filter-row' -->
            <div class="filter-search-bar__filter-row">
                <?php if (isset($filters)) echo $filters; ?>
            </div>

            <!-- The search input and non-filter controls are contained within 'filter-search-bar__search-row' -->
            <div class="filter-search-bar__search-row">
                <?php if (isset($filters_search)) echo $filters_search; ?>
            </div>
        </div>

        <?php $this->embed('_shared/table', $table); ?>

        <?php if (! empty($table['columns']) && ! empty($table['data'])): ?>
            <?php if ($can_edit || $can_delete) {
    $options = [
        [
            'value' => "",
            'text' => '-- ' . lang('with_selected') . ' --'
        ]
    ];
    if ($can_delete) {
        $options[] = [
            'value' => "remove",
            'text' => lang('delete'),
            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete-entry"'
        ];
    }
    if ($can_edit) {
        $options[] = [
            'value' => "edit",
            'text' => lang('edit'),
            'attrs' => ' data-confirm-trigger="selected" rel="modal-edit"'
        ];
        $options[] = [
            'value' => "bulk-edit",
            'text' => lang('bulk_edit'),
            'attrs' => ' data-confirm-trigger="selected" rel="modal-bulk-edit"'
        ];
        $options[] = [
            'value' => "add-categories",
            'text' => lang('add_categories'),
            'attrs' => ' data-confirm-trigger="selected" rel="modal-bulk-edit"'
        ];
        $options[] = [
            'value' => "remove-categories",
            'text' => lang('remove_categories'),
            'attrs' => ' data-confirm-trigger="selected" rel="modal-bulk-edit"'
        ];
    }
    $this->embed('ee:_shared/form/bulk-action-bar', [
        'options' => $options,
        'modal' => true
    ]);
}
            ?>
        <?php endif; ?>

        <?=$pagination?>

      <?=form_close()?>
  </div>
</div>
