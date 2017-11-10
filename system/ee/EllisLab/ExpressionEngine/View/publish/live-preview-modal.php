<!-- Live preview setup, should only be available on Publish and Edit forms. -->
<div class="app-modal app-modal--side app-modal--live-preview" rev="live-preview">
	<div class="app-modal__content">
		<div class="app-modal__dismiss">
			<a class="js-modal-close" rel="modal-side" href="#"><?=lang('cancel_preview')?></a> <span class="txt-fade">[<?=lang('esc')?>]</span>
		</div>

		<div class="form-standard form-standard--stacked">
		</div>
	</div>
</div>
<!-- Website iframe -->
<div class="live-preview">
	<iframe src="" data-url="<?=$preview_url?>" class="live-preview__frame"></iframe>
</div>
