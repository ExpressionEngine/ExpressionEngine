<?php $this->extend('_templates/default-nav') ?>

<div class="tbl-ctrls">
<?=form_open($form_url)?>
	<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
	<?=ee('CP/Alert')->getAllInlines()?>
	<?php if (isset($filters)) echo $filters; ?>
	<section class="item-wrap log">
		<?php if (count($logs) == 0): ?>
			<p class="no-results"><?=lang('no_email_logs_found')?></p>
		<?php else: ?>
			<?php foreach($logs as $log): ?>

			<div class="item">
				<ul class="toolbar">
					<li class="remove"><a href="" class="m-link" rel="modal-confirm-<?=$log->cache_id?>" title="remove"></a></li>
				</ul>
				<h3>
					<b><?=lang('date_logged')?>:</b> <?=$localize->human_time($log->cache_date)?><br>
					<b><?=lang('username')?>:</b> <a href="<?=ee('CP/URL')->make('myaccount', array('id' => $log->member_id))?>"><?=$log->member_name?></a>,
					<b><abbr title="<?=lang('internet_protocol')?>"><?=lang('ip')?></abbr>:</b> <?=$log->ip_address?>
				</h3>
				<div class="message">
					<p><?=lang('sent_to')?> <b><?=$log->recipient_name?></b>, <?=lang('subject')?>: <a href="<?=ee('CP/URL')->make('logs/email/view/'.$log->cache_id)?>"><?=$log->subject?></a></p>
				</div>
			</div>
			<?php endforeach; ?>

			<?=$pagination?>

			<fieldset class="tbl-bulk-act">
				<button class="btn remove m-link" rel="modal-confirm-all"><?=lang('clear_email_logs')?></button>
			</fieldset>
		<?php endif; ?>
	</section>
<?=form_close()?>
</div>

<?php
// Individual confirm delete modals
foreach($logs as $log)
{
	$modal_vars = array(
		'name'      => 'modal-confirm-' . $log->cache_id,
		'form_url'	=> $form_url,
		'hidden'	=> array(
			'delete'	=> $log->cache_id
		),
		'checklist'	=> array(
			array(
				'kind' => lang('view_email_logs'),
				'desc' => lang('sent_to') . ' ' . $log->recipient_name . ', ' . lang('subject') . ': ' . $log->subject
			)
		)
	);

	$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
	ee('CP/Modal')->addModal($log->cache_id, $modal);
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
			'kind' => lang('view_email_logs'),
			'desc' => lang('all')
		)
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('all', $modal);
?>
