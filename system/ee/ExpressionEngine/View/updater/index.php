<?php $this->extend('_templates/out'); ?>

<div class="login__logo">
	<?php $this->embed('ee:_shared/ee-logo')?>
</div>

<div class="panel updating<?php if ($warn_message !== null): ?> hidden<?php endif ?>">
  <div class="panel-heading" style="text-align: center;">
    <h3><?=sprintf(lang('updating_to_from'), $site_name, $current_version, $to_version)?></h3>
  </div>
  <div class="panel-body">
    <div class="progress-bar" style="margin-bottom: 20px;">
    <div class="progress"></div>
    </div>
    <div class="updater-working">
  		<div class="updater-load"></div>

  		<ul class="updater-steps">
  			<li class="updater-step-work"><?=$first_step?><span>...</span></li>
  		</ul>
  	</div>
  </div>
</div>


<div class="panel updater-stopped<?php if ($warn_message === null): ?> hidden<?php endif ?>"">
  <div class="panel-heading" style="text-align: center;">
    <h3><?=lang('update_stopped')?> <span class="updater-fade hidden">(<a class="toggle" rel="updater-stack-trace" href=""><?=lang('view_stack_trace')?></a>)</span></h3>
  </div>
  <div class="panel-body">
  	<div class="updater-stack-trace"></div>
	<div class="updater-msg">
		<p style="margin-bottom: 20px;"><?=lang('could_not_complete')?></p>

		<div class="alert alert--attention app-notice---error">
			<div class="alert__icon">
				<i class="fas fa-info-circle fa-fw"></i>
			</div>
			<div class="alert__content">
				<div class="alert__title">
					<p><?=sprintf(lang('we_stopped_on'), lang('preflight_step'))?></p>
				</div>

                <div class="alert-notice">
                    <p><?=$warn_message?></p>
                </div>
			</div>
		</div>

		<p class="msg-choices warn-choices <?php if ($warn_message === null): ?> hidden<?php endif ?>"><?=sprintf(lang('troubleshoot'), ee('CP/URL')->make('updater'))?></p>
		<p class="warn-choices<?php if ($warn_message === null): ?> hidden<?php endif ?>"><?=sprintf(lang('or_return_to_cp'), ee('CP/URL')->make('homepage'))?></p>

		<p class="msg-choices issue-choices hidden"><a href="" rel="rollback"><?=sprintf(lang('rollback_to'), strip_tags($current_version))?></a></p>
		<p class="issue-choices hidden"><?=sprintf(lang('cannot_rollback'), DOC_URL . 'installation/update.html')?></p>
	</div>
  </div>
</div>

<div class="panel updater-subscribe hidden">
  <div class="panel-heading" style="text-align: center;">
    <h3><?=lang('updater_subscribe_title')?></h3>
  </div>
  <div class="panel-body">
    <p><?=lang('updater_subscribe_desc')?></p>

    <form id="updater-subscribe-form"
      data-error-invalid-email="<?=lang('updater_subscribe_invalid_email')?>"
      data-error-submit="<?=lang('updater_subscribe_submit_error')?>">
      <fieldset class="fieldset">
        <div class="field-instruct">
          <label for="updater-subscribe-email"><?=lang('updater_subscribe_email_label')?></label>
        </div>
        <div class="field-control">
          <input type="email" name="email" id="updater-subscribe-email" autocomplete="email" required>
        </div>
      </fieldset>

      <fieldset class="fieldset">
        <label class="checkbox-label" for="updater-subscribe-marketing">
          <input type="checkbox" name="marketing_opt_in" id="updater-subscribe-marketing" value="y">
          <?=lang('updater_subscribe_marketing_label')?>
        </label>
      </fieldset>

      <div class="alert alert--attention app-notice---error hidden js-updater-subscribe-error">
        <div class="alert__icon">
          <i class="fas fa-info-circle fa-fw"></i>
        </div>
        <div class="alert__content">
          <div class="alert__title">
            <p><?=lang('updater_subscribe_error_title')?></p>
          </div>
          <div class="alert-notice">
            <p class="js-updater-subscribe-error-text"></p>
          </div>
        </div>
      </div>

      <div class="form-ctrls">
        <button class="button button--primary" type="submit">
          <?=lang('updater_subscribe_submit')?>
        </button>
        <button class="button button--default js-updater-subscribe-skip" type="button">
          <?=lang('updater_subscribe_skip')?>
        </button>
      </div>
    </form>
  </div>
</div>


<?=ee()->javascript->get_global()
    . ee()->view->script_tag('jquery/jquery.js')
    . ee()->view->script_tag('cp/updater.js');
?>

<script type="text/javascript">
	Updater.init();
	<?php if (! $warn_message && $next_step): ?>
		Updater.runStep('<?=$next_step?>');
	<?php endif ?>
</script>
