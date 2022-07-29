<div class="panel panel__with-border">
    <div class="f_entries-table">
        <h2><?=lang('entries')?></h2>

        <?php $this->embed('_shared/table', $entries); ?>

    </div>
</div>

<div class="panel panel__with-border">
    <div class="f_category-table">
        <h2><?=lang('categories')?></h2>

        <?php $this->embed('_shared/table', $categories); ?>

    </div>
</div>
