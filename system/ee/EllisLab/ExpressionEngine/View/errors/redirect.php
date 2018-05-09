<?php $this->extend('_templates/out'); ?>

<div class="box warn">
	<h1>Redirect Warning <span class="icon-issue"></span></h1>
	<div class="updater-msg">
		<p>You’re opening a new web page going to host <b><?=$host?></b> that is not part of <?=config_item('site_label')?>.</p>
		<p>Please double check that the address is correct.</p>
		<div class="alert-notice">
			<p><?=$url?></p>
		</div>
		<p class="msg-choices"><?=$link?> or <a href="<?=config_item('site_url')?>">Cancel</a></p>
	</div>
</div>
