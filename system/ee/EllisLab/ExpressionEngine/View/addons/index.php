<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<?php if (isset($tables['third'])): ?>
<div class="box mb table-list-wrap">
<?php else: ?>
<div class="box table-list-wrap">
<?php endif; ?>
	<div class="tbl-ctrls">
		<?=form_open($form_url)?>
			<h1><?=$cp_heading['first']?></h1>
			<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
			<?php if (isset($filters['first'])) echo $filters['first']; ?>
			<?php $this->embed('_shared/table', $tables['first']); ?>
			<?php if ( ! empty($tables['first']['columns']) && ! empty($tables['first']['data'])): ?>
			<fieldset class="tbl-bulk-act hidden">
				<select name="bulk_action">
					<option value="">-- <?=lang('with_selected')?> --</option>
					<option value="install"><?=lang('install')?></option>
					<option value="remove" data-confirm-trigger-first="selected" rel="modal-confirm-remove"><?=lang('uninstall')?></option>
					<option value="update"><?=lang('update')?></option>
				</select>
				<button class="btn submit" data-conditional-modal="confirm-trigger-first"><?=lang('submit')?></button>
			</fieldset>
			<?php endif; ?>
		<?=form_close()?>
	</div>
</div>
<?php if (isset($tables['third'])): ?>
<div class="box table-list-wrap">
	<div class="tbl-ctrls">
		<?=form_open($form_url)?>
			<h1><?=$cp_heading['third']?></h1>
			<?php if (isset($filters['third'])) echo $filters['third']; ?>
			<?php $this->embed('_shared/table', $tables['third']); ?>
			<?php if ( ! empty($tables['third']['columns']) && ! empty($tables['third']['data'])): ?>
			<fieldset class="tbl-bulk-act hidden">
				<select name="bulk_action">
					<option value="">-- <?=lang('with_selected')?> --</option>
					<option value="install"><?=lang('install')?></option>
					<option value="remove" data-confirm-trigger-third="selected" rel="modal-confirm-remove"><?=lang('uninstall')?></option>
					<option value="update"><?=lang('update')?></option>
				</select>
				<button class="btn submit" data-conditional-modal="confirm-trigger-third"><?=lang('submit')?></button>
			</fieldset>
			<?php endif; ?>
		<?=form_close()?>
	</div>
</div>
<?php endif; ?>

<?php ee('CP/Modal')->startModal('modal-confirm-remove'); ?>
<div class="modal-wrap modal-confirm-remove hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="form-standard">
					<?=form_open($form_url, '', array('bulk_action' => 'remove'))?>
						<div class="form-btns form-btns-top">
							<h1><?=lang('confirm_uninstall')?></h1>
						</div>
						<?=ee('CP/Alert')
							->makeInline()
							->asIssue()
							->addToBody(lang('confirm_uninstall_desc'))
							->render()?>
						<div class="txt-wrap">
							<ul class="checklist">
								<?php if (isset($checklist)):
									$end = end($checklist); ?>
									<?php foreach ($checklist as $item): ?>
									<li<?php if ($item == $end) echo ' class="last"'; ?>><?=$item['kind']?>: <b><?=$item['desc']?></b></li>
									<?php endforeach;
								endif ?>
							</ul>
							<div class="ajax"></div>
						</div>
						<div class="form-btns">
							<?=cp_form_submit('btn_confirm_and_uninstall', 'btn_confirm_and_uninstall_working')?>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php ee('CP/Modal')->endModal(); ?>
