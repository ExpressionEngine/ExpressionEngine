<?php extend_template('wrapper'); ?>

<div class="col-group">
	<div class="col w-16 last">
		<div class="box full mb">
			<div class="tbl-ctrls">
				<?=form_open($form_url)?>
					<fieldset class="tbl-search right">
						<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$table['search']?>">
						<input class="btn submit" type="submit" value="<?=lang('search_addons_button')?>">
					</fieldset>
					<h1><?=$cp_page_title?></h1>
				<?=form_close()?>
			</div>
		</div>
	</div>
</div>

<div class="col-group">
	<div class="col w-16 last">
		<?php if (count($cp_breadcrumbs)): ?>
			<ul class="breadcrumb">
				<?php foreach ($cp_breadcrumbs as $link => $title): ?>
					<li><a href="<?=$link?>"><?=$title?></a></li>
				<?php endforeach ?>
				<li class="last"><?=$cp_page_title?></li>
			</ul>
		<?php endif ?>
			<div class="box snap">
				<div class="tbl-ctrls">
					<?=form_open($form_url)?>
						<h1><?=$cp_heading?></h1>
						<?=ee('Alert')->getAllInlines()?>
						<?php if (isset($filters)) echo $filters; ?>
						<?php $this->view('_shared/table', $table); ?>
						<?php $this->view('_shared/pagination'); ?>
						<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
						<fieldset class="tbl-bulk-act">
							<select name="bulk_action">
								<option value="">-- <?=lang('with_selected')?> --</option>
								<option value="install"><?=lang('install')?></option>
								<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
								<option value="update"><?=lang('update')?></option>
							</select>
							<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
						</fieldset>
						<?php endif; ?>
					<?=form_close()?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>
<?php
$modal_vars = array(
	'name'      => 'modal-confirm-remove',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>
