<?php $this->extend('_templates/default-nav'); ?>
<div class="panel">
	<?=form_open($form_url)?>
  <div class="panel-heading">
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h3 class="title-bar__title"><?=$cp_heading?><br><i><?=$cp_sub_heading?></i></h3>
		</div>
  </div>

  <div class="panel-body">
		<?php $this->embed('_shared/table', $table); ?>
		<?php $this->embed('_shared/pagination'); ?>
  </div>
    <?php if (! empty($table['data'])): ?>
      <div class="panel-footer">
  			<div class="form-btns">
  				<input class="button button--primary" type="submit" value="<?=lang('update')?>">
  			</div>
      </div>
		<?php endif ?>
	<?=form_close()?>
</div>