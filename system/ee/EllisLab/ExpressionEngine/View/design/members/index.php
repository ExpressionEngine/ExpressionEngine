<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<fieldset class="tbl-search right">
			<?=$themes?>
		</fieldset>

		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?=$cp_heading?></h2>
		</div>

		<?php $this->embed('_shared/table', $table); ?>
		<?php if (isset($pagination)) echo $pagination; ?>
	<?=form_close()?>
</div>
