<?php $this->extend('_templates/out'); ?>

<div class="panel redirect">
  <div class="panel-heading">
    <h3>Redirect Warning</h3>
  </div>
	<div class="panel-body">
		<p>You’re opening a new web page going to host <b><?=$host?></b> that is not part of <?=config_item('site_label')?>.</p>
		<p>Please double check that the address is correct.</p>
		<div class="alert-notice">
			<code><?=$url?></code>
		</div>

	</div>
  <div class="panel-footer">
    <?=$link?> <a href="<?=config_item('site_url')?>" class="button button--default">Cancel</a>
  </div>
</div>
