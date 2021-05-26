<div class="panel">
	<div class="tbl-ctrls">
	<?=form_open($form_url, ['data-search-url' => $base_url])?>
    <div class="panel-heading">
      <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
  		<h3 class="title-bar__title"><?=sprintf(lang('create_new_item_step'), 1)?><br><i><?=lang('create_new_item_step_desc')?></i></h3>

		<div class="filter-search-bar" style="background: none; padding: 10px 0 0 0; border: none;">

			<!-- All filters (not including search input) are contained within 'filter-search-bar__filter-row' -->
			<div class="filter-search-bar__filter-row">
				<?php if (isset($filters)) {
    echo $filters;
} ?>
			</div>

			<!-- The search input and non-filter controls are contained within 'filter-search-bar__search-row' -->
			<div class="filter-search-bar__search-row">
				<?php if (isset($filters_search)) {
    echo $filters_search;
} ?>
			</div>
		</div>
    </div>

		<?php $this->embed('ee:_shared/table', $table); ?>
		<?=$pagination?>
		<?php if (! empty($table['columns']) && ! empty($table['data'])): ?>
		<?php $this->embed('ee:_shared/form/bulk-action-bar', [
		    'options' => [
		        [
		            'value' => "",
		            'text' => '-- ' . lang('with_selected') . ' --'
		        ],
		        [
		            'value' => "add_item",
		            'text' => lang('add_item')
		        ]
		    ]
		]); ?>
		<?php endif; ?>
	<?=form_close()?>
	</div>
</div>
