<?php $this->extend('_templates/default-nav') ?>
<div class="panel">
<div class="tbl-ctrls">
<?=form_open($form_url)?>
<div class="panel-heading">
	<div class="title-bar">
		<h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>
		<?php if (isset($filters)) {
    echo $filters;
} ?>
	</div>
</div>
<div class="panel-body">
<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
  <section>
		<?php if ($disabled): ?>
			<p class="no-results"><?=lang('throttling_disabled')?> <a href="<?=ee('CP/URL')->make('settings/throttling')?>"><?=lang('enable_throttling')?></a></p>
		<?php else: ?>
			<?php if (count($logs) == 0): ?>
				<p class="no-results"><?=lang('no_throttling_logs_found')?></p>
			<?php else: ?>
				<div class="list-group">
				<?php foreach ($logs as $log): ?>

				<div class="list-item">
					<div class="list-item__content">
						<a href="" class="m-link float-right button button--default button--small" rel="modal-confirm-<?=$log->throttle_id?>" title="<?=lang('delete')?>"><i class="fal fa-trash-alt"><span class="hidden"><?=lang('delete')?></span></i></a>

						<div style="margin-bottom: 20px;"><b><?=lang('date_logged')?>:</b> <?=$localize->human_time($log->last_activity)?>, <b><abbr title="<?=lang('internet_protocol')?>"><?=lang('ip')?></abbr>:</b> <?=$log->ip_address?></div>

						<div class="list-item__body">
							<pre><code><?=lang('front_end_requests')?>: <?=$log->hits?></pre></code>
						</div>
					</div>
				</div>

				<?php endforeach; ?>
				</div>

				<?=$pagination?>
      </div>
      <div class="panel-footer">
				<div class="form-btns">
					<button class="button button--danger m-link" rel="modal-confirm-all"><?=lang('clear_throttle_logs')?></button>
				</div>
      </div>
			<?php endif; ?>
		<?php endif; ?>
	</section>
<?=form_close()?>
</div>
</div>
<?php
if (! $disabled) {
    // Individual confirm delete modals
    foreach ($logs as $log) {
        $modal_vars = array(
            'name' => 'modal-confirm-' . $log->throttle_id,
            'form_url' => $form_url,
            'hidden' => array(
                'delete' => $log->throttle_id
            ),
            'checklist' => array(
                array(
                    'kind' => lang('view_throttle_log'),
                    'desc' => $log->ip_address . ' ' . lang('hits') . ': ' . $log->hits
                )
            )
        );

        $modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
        ee('CP/Modal')->addModal($log->throttle_id, $modal);
    }

    // Confirm delete all modal
    $modal_vars = array(
        'name' => 'modal-confirm-all',
        'form_url' => $form_url,
        'hidden' => array(
            'delete' => 'all'
        ),
        'checklist' => array(
            array(
                'kind' => lang('view_throttle_log'),
                'desc' => lang('all')
            )
        )
    );

    $modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
    ee('CP/Modal')->addModal('all', $modal);
}
?>
