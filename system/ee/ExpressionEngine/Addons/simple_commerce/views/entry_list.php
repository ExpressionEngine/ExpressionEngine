	<?=form_open($form_url, 'class="tbl-ctrls"')?>
		<h1><?=sprintf(lang('create_new_item_step'), 1)?><br><i><?=lang('create_new_item_step_desc')?></i></h1>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		<?php if (isset($filters)) echo $filters; ?>
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
