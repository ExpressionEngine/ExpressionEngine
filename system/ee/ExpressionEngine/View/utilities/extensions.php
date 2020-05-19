<?php $this->extend('_templates/default-nav'); ?>

	<?=form_open($form_url)?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?=$cp_heading?></h2>
		</div>

		<?php $this->embed('_shared/table', $table); ?>
		<? if (isset($pagination)) echo $pagination; ?>
		<?php if ($table['total_rows'] > 0): ?>
		<?php $this->embed('ee:_shared/form/bulk-action-bar', [
			'options' => [
				[
					'value' => "",
					'text' => '-- ' . lang('with_selected') . ' --'
				],
				[
					'value' => "disable",
					'text' => lang('disable')
				],
				[
					'value' => "enable",
					'text' => lang('enable')
				]
			]
		]); ?>
		<?php endif; ?>
	<?=form_close()?>
