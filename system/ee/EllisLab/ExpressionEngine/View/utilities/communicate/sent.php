<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
<?=form_open($table['base_url'])?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
		<input class="btn submit" type="submit" value="<?=lang('search_emails_button')?>">
	</fieldset>
	<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
	<?php $this->embed('_shared/table', $table); ?>

	<?=$pagination?>

	<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<button class="btn submit" data-conditional-modal="confirm-trigger" ><?=lang('submit')?></button>
		</fieldset>
	<?php endif; ?>
<?=form_close()?>
</div>

<?php foreach($emails as $email): ?>
	<?php ee('CP/Modal')->startModal('email-' . $email->cache_id); ?>
	<div class="modal-wrap modal-email-<?=$email->cache_id?> hidden">
		<div class="modal">
			<div class="col-group">
				<div class="col w-16">
					<a class="m-close" href="#"></a>
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

<?php
$modal_vars = array(
	'name'      => 'modal-confirm-remove',
	'form_url'	=> $table['base_url'],
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
