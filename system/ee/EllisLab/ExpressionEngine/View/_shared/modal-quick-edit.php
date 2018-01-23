<div class="app-modal app-modal--center<?php if (isset($destructive) && $destructive): ?> js-modal--destruct<?php endif ?>" rel="modal-quick-edit">
	<div class="app-modal__content">
		<div class="app-modal__dismiss">
			<a class="js-modal-close" rel="modal-quick-edit" href="#"><?=lang('close_modal')?></a> <span class="txt-fade">[esc]</span>
		</div>
		<div class="col-group align-right">
			<div class="col w-12 remove-pad--right">
				Form
			</div>
			<div class="col w-4 remove-pad--left" data-quick-edit-entries-react>
				<!-- filter -->
			</div>
		</div>
	</div>
</div>
