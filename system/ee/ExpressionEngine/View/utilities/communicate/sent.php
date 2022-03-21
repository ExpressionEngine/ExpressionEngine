<?php $this->extend('_templates/default-nav'); ?>

<div class="panel">
<?=form_open($table['base_url'])?>
	<div class="panel-heading">

	<div class="title-bar">
		<h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>
		<?php if (isset($filters)) {
    echo $filters;
} ?>
	</div>

</div>

  <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

	<?php $this->embed('_shared/table', $table); ?>

	<?=$pagination?>

	<?php if (! empty($table['columns']) && ! empty($table['data'])): ?>
		<?php $this->embed('ee:_shared/form/bulk-action-bar', [
		    'options' => [
		        [
		            'value' => "",
		            'text' => '-- ' . lang('with_selected') . ' --'
		        ],
		        [
		            'value' => "remove",
		            'text' => lang('delete'),
		            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete"'
		        ]
		    ],
		    'modal' => true
		]); ?>
	<?php endif; ?>
<?=form_close()?>

<?php foreach ($emails as $email): ?>
	<?php ee('CP/Modal')->startModal('email-' . $email->cache_id); ?>
	<div class="modal-wrap modal-email-<?=$email->cache_id?> hidden">
		<div class="modal">
			<div class="col-group">
				<div class="col w-16">
					<a class="m-close" href="#"><span class="sr-only"><?=lang('close_modal')?></span></a>
					<div class="box">
						<h1><?=$email->subject?></h1>
						<div class="txt-wrap">
							<ul class="checklist mb">
								<li><b><?=lang('sent')?>:</b> <?=$localize->human_time($email->cache_date->format('U'))?> <?=lang('to')?> <?=$email->total_sent?> <?=lang('recipients')?></li>
							</ul>
							<?=ee('Security/XSS')->clean($email->message)?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php ee('CP/Modal')->endModal(); ?>
<?php endforeach; ?>
</div>

<?php
$modal_vars = array(
    'name' => 'modal-confirm-delete',
    'form_url' => $table['base_url'],
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
