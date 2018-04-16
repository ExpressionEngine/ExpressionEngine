<?php $this->extend('_templates/default-nav') ?>

<div class="tbl-ctrls">
<?=form_open($form_url)?>
	<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
	<?php if (isset($filters)) echo $filters; ?>
	<section class="item-wrap log">
		<?php if ($disabled): ?>
			<p class="no-results"><?=lang('throttling_disabled')?> <a href="<?=ee('CP/URL')->make('settings/throttling')?>"><?=lang('enable_throttling')?></a></p>
		<?php else: ?>
			<?php if (count($logs) == 0): ?>
				<p class="no-results"><?=lang('no_throttling_logs_found')?></p>
			<?php else: ?>
				<?php foreach($logs as $log): ?>

				<div class="item">
					<ul class="toolbar">
					<li class="remove"><a href="" class="m-link" rel="modal-confirm-<?=$log->throttle_id?>" title="remove"></a></li>
					</ul>
					<h3><b><?=lang('date_logged')?>:</b> <?=$localize->human_time($log->last_activity)?>, <b><abbr title="<?=lang('internet_protocol')?>"><?=lang('ip')?></abbr>:</b> <?=$log->ip_address?></h3>
					<div class="message">
						<p><?=lang('front_end_requests')?>: <?=$log->hits?></p>
					</div>
				</div>

				<?php endforeach; ?>

				<?=$pagination?>

				<fieldset class="tbl-bulk-act">
				<button class="btn action m-link" rel="modal-confirm-all"><?=lang('clear_throttle_logs')?></button>
				</fieldset>
			<?php endif; ?>
		<?php endif; ?>
	</section>
<?=form_close()?>
</div>

<?php
if ( ! $disabled)
{
	// Individual confirm delete modals
	foreach($logs as $log)
	{
		$modal_vars = array(
			'name'      => 'modal-confirm-' . $log->throttle_id,
			'form_url'	=> $form_url,
			'hidden'	=> array(
				'delete'	=> $log->throttle_id
			),
			'checklist'	=> array(
				array(
					'kind' => lang('view_throttle_log'),
					'desc' => $log->ip_address . ' ' . lang('hits') . ': ' . $log->hits
				)
			)
		);

		$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
		ee('CP/Modal')->addModal($log->throttle_id, $modal);
	}

	// Confirm delete all modal
	$modal_vars = array(
		'name'      => 'modal-confirm-all',
		'form_url'	=> $form_url,
		'hidden'	=> array(
			'delete'	=> 'all'
		),
		'checklist'	=> array(
			array(
				'kind' => lang('view_throttle_log'),
				'desc' => lang('all')
			)
		)
	);

	$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
	ee('CP/Modal')->addModal('all', $modal);
}
?>
