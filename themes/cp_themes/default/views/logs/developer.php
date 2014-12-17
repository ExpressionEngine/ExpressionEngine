<?php extend_template('default-nav') ?>

<div class="tbl-ctrls">
<?=form_open($form_url)?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$search_value?>">
		<input class="btn submit" type="submit" value="<?=lang('search_logs_button')?>">
	</fieldset>
	<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
	<?=ee('Alert')->getAllInlines()?>
	<?php if (isset($filters)) echo $filters; ?>
	<section class="item-wrap log">
		<?php if (empty($rows)): ?>
			<p class="no-results"><?=lang('no_developer_logs_found')?></p>
		<?php else: ?>
			<?php foreach($rows as $row): ?>
			<div class="item">
				<ul class="toolbar">
					<li class="remove"><a href="" class="m-link" rel="modal-confirm-<?=$row['log_id']?>" title="remove"></a></li>
				</ul>
				<h3><b><?=lang('date_logged')?>:</b> <?=$row['timestamp']?></h3>
				<div class="message">
					<?=$row['description']?>
				</div>
			</div>
			<?php endforeach; ?>

			<?php $this->view('_shared/pagination'); ?>

			<fieldset class="tbl-bulk-act">
				<button class="btn remove m-link" rel="modal-confirm-all"><?=lang('clear_developer_logs')?></button>
			</fieldset>
		<?php endif; ?>
	</section>
<?=form_close()?>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php
// Individual confirm delete modals
foreach($rows as $row)
{
	$modal_vars = array(
		'name'      => 'modal-confirm-' . $row['log_id'],
		'form_url'	=> $form_url,
		'hidden'	=> array(
			'delete'	=> $row['log_id']
		),
		'checklist'	=> array(
			array(
				'kind' => lang('view_developer_log'),
				'desc' => $row['description']
			)
		)
	);

	$this->view('_shared/modal_confirm_remove', $modal_vars);
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
			'kind' => lang('view_developer_log'),
			'desc' => lang('all')
		)
	)
);

$this->view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>