<?php $this->extend('_templates/out'); ?>

<div class="panel redirect">
  <div class="panel-heading">
    <h3><?=lang('redirect_warning_header')?></h3>
  </div>
	<div class="panel-body">
		<p>
			<?=sprintf(lang('redirect_description'), $host)?>  <?=config_item('site_label')?>.</p>
		<p><?=lang('redirect_check_address')?></p>
		<div class="alert-notice">
			<code><?=$url?></code>
		</div>
	</div>
  <div class="panel-footer">
    <?=$link?> <a href="<?=config_item('site_url')?>" class="button button--default"><?=lang('redirect_cancel')?></a>
  </div>
</div>
