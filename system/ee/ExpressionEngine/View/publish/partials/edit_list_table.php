<div class="panel" <?php if (ee()->uri->segment(3) == 'prolet') : ?> style="overflow: hidden; margin-bottom: 0px; margin-top: -16px; margin-left: -16px; margin-right: -16px; border-radius: 0;"<?php endif; ?>>
    <div class="tbl-ctrls">
        <?=form_open($form_url)?>

        <div class="panel-heading entry-pannel-heading"<?php if (ee()->uri->segment(3) == 'prolet') : ?> style="display: none;"<?php endif; ?>>
          <div class="title-bar">
                <h3 class="title-bar__title"><?=$head['title']?></h3>
          </div>

        <?php if (isset($head['action_button'])): ?>
            <div class="button-wrap">
            <?php if (isset($head['action_button']['choices'])): ?>
                <button type="button" class="button button--primary js-dropdown-toggle has-sub" data-dropdown-pos="bottom-end"><?=$head['action_button']['text']?></button>
                <div class="dropdown">
                    <?php if (count($head['action_button']['choices']) > 8): ?>
                        <div class="dropdown__search">
                            <div class="search-input">
                                <input type="text" value="" class="search-input__input input--small" data-fuzzy-filter="true" placeholder="<?=$head['action_button']['filter_placeholder']?>">
                            </div>
                        </div>
                    <?php endif ?>

                    <div class="dropdown__scroll">
                    <?php foreach ($head['action_button']['choices'] as $link => $text): ?>
                        <a href="<?=$link?>" class="dropdown__link"><?=$text?></a>
                    <?php endforeach ?>
                    </div>
                </div>
            <?php else: ?>
                <a class="button button--primary" href="<?=$head['action_button']['href']?>"><?=$head['action_button']['text']?></a>
            <?php endif ?>
            </div>
        <?php endif ?>
        </div>

        <div class="entry-pannel-notice-wrap">
            <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
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
