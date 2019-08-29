<?php $this->extend('_templates/default-nav'); ?>

	<?=form_open($form_url)?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?=$cp_heading?><br><i><?=$cp_sub_heading?></i></h2>
		</div>

		<?php $this->embed('_shared/table', $table); ?>
		<?php $this->embed('_shared/pagination'); ?>
		<?php if ( ! empty($table['data'])): ?>
			<fieldset class="bulk-action-bar">
				<input class="button button--primary" type="submit" value="<?=lang('update')?>">
			</fieldset>
		<?php endif ?>
	<?=form_close()?>
