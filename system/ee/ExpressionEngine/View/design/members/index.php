<?php $this->extend('_templates/default-nav'); ?>
<div class="panel">
	<?=form_open($form_url)?>
  <div class="panel-heading">
    <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		<fieldset class="tbl-search right">
			<?=$themes?>
		</fieldset>



		<div class="title-bar">
			<h3 class="title-bar__title"><?=$cp_heading?></h3>
		</div>
  </div>

		<?php $this->embed('_shared/table', $table); ?>
		<?php if (isset($pagination)) {
    echo $pagination;
} ?>
	<?=form_close()?>
</div>