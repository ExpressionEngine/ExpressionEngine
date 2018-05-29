<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="box snap mb table-list-wrap">
	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<h1>
			<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
		</h1>

		<div class="app-notice-wrap">
			<?=ee('CP/Alert')->get('view-members')?>
		</div>

		<?php if (isset($filters)) echo $filters; ?>

		<?php $this->embed('_shared/table', $table); ?>

		<?php if ( ! empty($pagination)) echo $pagination; ?>

		<?php if ( ! empty($table['data']) && ($can_edit || $can_delete)): ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<?php if ($can_edit): ?>
				<option value="approve"><?=lang('approve')?></option>
				<?php if ($resend_available): ?>
				<option value="resend"><?=lang('resend')?></option>
				<?php endif; ?>
				<?php endif; ?>
				<?php if ($can_delete): ?>
				<option value="decline" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('decline')?></option>
				<?php endif; ?>
			</select>
			<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
	</div>
</div>

<?php ee('CP/Modal')->startModal('modal-confirm-remove'); ?>
<div class="modal-wrap modal-confirm-remove hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="form-standard">
					<?=form_open($form_url, '', array('bulk_action' => 'decline'))?>
						<div class="form-btns form-btns-top">
							<h1><?=lang('confirm_decline')?></h1>
						</div>
						<?=ee('CP/Alert')
							->makeInline()
							->asIssue()
							->addToBody(lang('confirm_decline_desc'))
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
							<?=cp_form_submit('btn_confirm_and_decline', 'btn_confirm_and_decline_working')?>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php ee('CP/Modal')->endModal(); ?>
