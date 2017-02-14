<?php $this->extend('_templates/out'); ?>

<div class="box<?php if ($warn_message !== NULL): ?> hidden<?php endif ?>">
	<div class="updater-working">
		<div class="updater-load"></div>
		<h1>Updating <b><?=$site_name?></b> from <?=$current_version?> to <?=$to_version?></h1>
		<ul class="updater-steps">
			<li class="updater-step-work">Downloading Update<span>...</span></li>
		</ul>
	</div>
</div>

<div class="box warn<?php if ($warn_message === NULL): ?> hidden<?php endif ?>">
	<h1>Update Stopped<span class="icon-issue"></span></h1>
	<div class="updater-msg">
		<p>Oops, looks like the updater couldn't&nbsp;complete.</p>
		<p>We stopped on <b>step name/phrase</b>.</p>
		<div class="alert-notice">
			<p><?=$warn_message?></p>
		</div>
		<p class="msg-choices">Troubleshoot, then <a href="">Continue</a></p>
	</div>
</div>

<div class="box issue hidden">
	<!-- stack trace link only shows if we have that data -->
	<h1>Update Failed <span class="updater-fade">(<a class="toggle" rel="updater-stack-trace" href="">view stack trace</a>)</span><span class="icon-issue"></span></h1>
	<div class="updater-stack-trace">	</ul>
	</div>
	<div class="updater-msg">
		<p>Oops, looks like the updater couldn't&nbsp;complete.</p>
		<p>We stopped on <b>step name/phrase</b>.</p>
		<div class="alert-notice">
			<p>Here is some more information on how to troubleshoot this issue.</p>
			<ul>
				<li><a href="">link to appropriate docs</a></li>
				<li><a href="">link to bug report</a></li>
			</ul>
			<p>You may also want to open a <a href="">support&nbsp;ticket</a>.</p>
		</div>
		<p class="msg-choices"><a href="">Rollback to 3.5.0</a></p>
	</div>
</div>

<style type="text/css"> .hidden { display: none} </style>

<?php if ($next_step):
	echo ee()->javascript->get_global()
		. ee()->view->script_tag('jquery/jquery.js')
		. ee()->view->script_tag('cp/updater.js');
	?>
	<script type="text/javascript">
		Updater.runStep('<?=$next_step?>');
	</script>
<?php endif ?>
