<div class="modal-wrap modal-confirm-new-version hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="form-standard">
				<form>
					<div class="form-btns form-btns-top">
						<h1><?=lang('important')?></h1>
					</div>
					<?=ee('CP/Alert')
						->makeInline()
						->asIssue()
						->addToBody(lang('new_consent_version_notice'))
						->render()?>
					<div class="txt-wrap">
					</div>
					<div class="form-btns">
						<?=cp_form_submit('btn_confirm_and_save', 'btn_confirm_and_save_working')?>
					</div>
				</form>
			</div>
			</div>
		</div>
	</div>
</div>
