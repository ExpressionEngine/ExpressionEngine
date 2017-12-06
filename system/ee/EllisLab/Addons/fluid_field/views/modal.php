<div class="modal-wrap modal-remove-field hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="box">
					<h1><?=lang('confirm_removal')?></h1>
					<?=form_open('', 'class="settings"', (isset($hidden)) ? $hidden : array())?>
						<div class="alert inline issue">
							<p>Removing fields from this Fluid Field will result in data loss.</p>
						</div>
						<fieldset class="form-ctrls">
							<?=cp_form_submit('btn_confirm_and_remove', 'btn_confirm_and_remove_working')?>
						</fieldset>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
