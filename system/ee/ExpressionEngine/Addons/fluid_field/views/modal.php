<div class="modal-wrap modal-remove-field hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"><span class="sr-only"><?=lang('close_modal')?></span></a>
				<div class="form-standard">
					<?=form_open('', 'class="settings"', (isset($hidden)) ? $hidden : array())?>
						<div class="form-btns form-btns-top">
							<h2><?=lang('confirm_removal')?></h2>
						</div>
						<?=ee('CP/Alert')
						    ->makeInline()
						    ->asIssue()
						    ->addToBody('Removing fields from this Fluid Field will result in data loss.')
						    ->render()?>
						<div class="form-btns">
							<?=cp_form_submit('btn_confirm_and_remove', 'btn_confirm_and_remove_working')?>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
