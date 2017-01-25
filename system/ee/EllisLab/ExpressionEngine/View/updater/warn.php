<?php $this->extend('_templates/out'); ?>

<div class="box warn">
	<h1>Update Stopped<span class="icon-issue"></span></h1>
	<div class="updater-msg">
		<p>Oops, looks like the updater couldn't&nbsp;complete.</p>
		<p>We stopped on <b>step name/phrase</b>.</p>
		<div class="alert-notice">
			<p><?=$error_message?></p>
		</div>
		<p class="msg-choices">Troubleshoot, then <a href="">Continue</a></p>
	</div>
</div>
