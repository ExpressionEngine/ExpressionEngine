<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<fieldset class="tbl-search right">
			<?=$themes?>
		</fieldset>
		<h1><?=$cp_heading?></h1>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		<?php $this->embed('_shared/table', $table); ?>
		<?php if (isset($pagination)) echo $pagination; ?>
	<?=form_close()?>
</div>
