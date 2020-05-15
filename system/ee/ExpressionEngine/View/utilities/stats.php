<?php $this->extend('_templates/default-nav'); ?>

	<?=form_open(ee('CP/URL')->make('utilities/stats/sync'))?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h2>
		</div>

		<?php $this->embed('ee:_shared/table', $table); ?>

		<?php $this->embed('ee:_shared/form/bulk-action-bar', [
			'options' => [
				[
					'value' => "",
					'text' => '-- ' . lang('with_selected') . ' --'
				],
				[
					'value' => "sync",
					'text' => lang('sync')
				]
			]
		]); ?>

	<?=form_close()?>
