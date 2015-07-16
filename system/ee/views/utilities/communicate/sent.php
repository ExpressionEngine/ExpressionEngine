<?php $this->extend('_templates/default-nav'); ?>

<div class="tbl-ctrls">
<?=form_open($table['base_url'])?>
	<fieldset class="tbl-search right">
		<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$table['search']?>">
		<input class="btn submit" type="submit" value="<?=lang('search_emails_button')?>">
	</fieldset>
	<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
	<?=ee('Alert')->getAllInlines()?>
	<?php $this->view('_shared/table', $table); ?>

	<?=$pagination?>

	<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
		<fieldset class="tbl-bulk-act">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<button class="btn submit" data-conditional-modal="confirm-trigger" ><?=lang('submit')?></button>
		</fieldset>
	<?php endif; ?>
<?=form_close()?>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php foreach($emails as $email): ?>
<div class="modal-wrap modal-email-<?=$email->cache_id?> hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="box">
					<h1><?=$email->subject?></h1>
					<div class="txt-wrap">
						<ul class="checklist mb">
							<li><b><?=lang('sent')?>:</b> <?=$localize->human_time($email->cache_date)?> <?=lang('to')?> <?=$email->total_sent?> <?=lang('recipients')?></li>
						</ul>
						<?=$email->message?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php endforeach; ?>

<?php
$modal_vars = array(
	'name'      => 'modal-confirm-remove',
	'form_url'	=> $table['base_url'],
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>