<?php $this->extend('_templates/default-nav') ?>

<div class="tbl-ctrls">
<?=form_open($form_url)?>
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

	<div class="title-bar">
		<h2 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h2>
		<?php if (isset($filters)) echo $filters; ?>
	</div>

	<section>
		<?php if (empty($rows)): ?>
			<p class="no-results"><?=lang('no_developer_logs_found')?></p>
		<?php else: ?>
			<div class="list-group">
			<?php foreach($rows as $row): ?>
			<div class="list-item">
				<div class="list-item__content">
					<a href="" class="m-link button button--danger float-right" rel="modal-confirm-<?=$row['log_id']?>" title="<?=lang('delete')?>"><i class="fas fa-trash-alt"></i></a>

					<div><b><?=lang('date_logged')?>:</b> <?=$row['timestamp']?></div>
					<div class="list-item__body">
						<pre><code><?=$row['description']?></pre></code>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
			</div>

			<?=$pagination?>

			<fieldset class="bulk-action-bar">
				<button class="button button--danger m-link" rel="modal-confirm-all"><?=lang('clear_developer_logs')?></button>
			</fieldset>
		<?php endif; ?>
	</section>
<?=form_close()?>
</div>

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

	$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
	ee('CP/Modal')->addModal($row['log_id'], $modal);
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

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('all', $modal);
?>
