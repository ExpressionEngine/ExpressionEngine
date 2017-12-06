<?php $this->extend('_templates/out'); ?>

<div class="box updating<?php if ($warn_message !== NULL): ?> hidden<?php endif ?>">
	<div class="updater-working">
		<div class="updater-load"></div>
		<h1><?=sprintf(lang('updating_to_from'), $site_name, $current_version, $to_version)?></h1>
		<ul class="updater-steps">
			<li class="updater-step-work"><?=$first_step?><span>...</span></li>
		</ul>
	</div>
</div>

<div class="box updater-stopped<?php if ($warn_message === NULL): ?> hidden<?php endif ?>">
	<h1><?=lang('update_stopped')?> <span class="updater-fade hidden">(<a class="toggle" rel="updater-stack-trace" href=""><?=lang('view_stack_trace')?></a>)</span><span class="icon-issue"></span></h1>
	<div class="updater-stack-trace"></div>
	<div class="updater-msg">
		<p><?=lang('could_not_complete')?></p>
		<p class="stopped"><?=sprintf(lang('we_stopped_on'), lang('preflight_step'))?></p>
		<div class="alert-notice">
			<p><?=$warn_message?></p>
		</div>

		<p class="msg-choices warn-choices <?php if ($warn_message === NULL): ?> hidden<?php endif ?>"><?=sprintf(lang('troubleshoot'), ee('CP/URL')->make('updater'))?></p>
		<p class="warn-choices<?php if ($warn_message === NULL): ?> hidden<?php endif ?>"><?=sprintf(lang('or_return_to_cp'), ee('CP/URL')->make('homepage'))?></p>

		<p class="msg-choices issue-choices hidden"><a href="" rel="rollback"><?=sprintf(lang('rollback_to'), strip_tags($current_version))?></a></p>
		<p class="issue-choices hidden"><?=sprintf(lang('cannot_rollback'), DOC_URL.'installation/update.html')?></p>
	</div>
</div>

<?=ee()->javascript->get_global()
	. ee()->view->script_tag('jquery/jquery.js')
	. ee()->view->script_tag('cp/updater.js');
?>

<script type="text/javascript">
	Updater.init();
	<?php if ( ! $warn_message && $next_step): ?>
		Updater.runStep('<?=$next_step?>');
	<?php endif ?>
</script>
