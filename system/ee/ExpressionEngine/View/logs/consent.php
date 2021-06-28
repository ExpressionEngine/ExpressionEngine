<?php $this->extend('_templates/default-nav') ?>
<div class="panel">
<div class="tbl-ctrls">
  <div class="panel-heading">
  <h3><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>


	<?=form_open($form_url)?>
		<?php if (isset($filters)) {
    echo $filters;
} ?>
	</form>
</div>
<div class="panel-body">
  <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
	<section>
		<?php if (count($logs) == 0): ?>
			<p class="no-results"><?=lang('no_consent_logs_found')?></p>
		<?php else: ?>
			<div class="list-group">
			<?php foreach ($logs as $log): ?>

			<div class="list-item">
				<div class="list-item__content">
					<div style="margin-bottom: 20px;">
						<b><?=lang('date_logged')?>:</b> <?=$localize->human_time($log->log_date->getTimestamp())?>,
						<b><?=lang('username')?>:</b> <?php if (!empty($log->member_id)) : ?><a href="<?=ee('CP/URL')->make('myaccount', array('id' => $log->member_id))?>"><?=$log->Member->username?></a><?php else: echo lang('anonymous'); endif; ?><?php if ($log->ip_address) : ?>,
						<b><?=lang('ip_address')?>:</b> <?=$log->ip_address?>
						<br />
						<b><?=lang('user_agent')?>:</b> <?=$log->user_agent?><?php endif; ?>
					</div>
					<div class="list-item__body">
						<pre><code class="hljs"><a href="<?=ee('CP/URL')->make('settings/consents/versions/' . $log->ConsentRequest->consent_request_id)?>#modal-consent-request-<?=$log->consent_request_version_id?>"><?=$log->ConsentRequest->title?></a>: <?=$log->action?>
						</pre></code>
					</div>
				</div>
			</div>

			<?php endforeach; ?>
			</div>

			<?=$pagination?>
		<?php endif; ?>
	</section>
</div>
</div>
</div>
