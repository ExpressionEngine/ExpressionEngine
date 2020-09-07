<div class="panel">
	<?=form_open($form_url, 'class="tbl-ctrls"')?>
    <div class="panel-heading">
      <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
  		<h3 class="title-bar__title"><?=sprintf(lang('create_new_item_step'), 1)?><br><i><?=lang('create_new_item_step_desc')?></i></h3>

  		<?php if (isset($filters)) echo $filters; ?>
    </div>

		<?php $this->embed('ee:_shared/table', $table); ?>
		<?=$pagination?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
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