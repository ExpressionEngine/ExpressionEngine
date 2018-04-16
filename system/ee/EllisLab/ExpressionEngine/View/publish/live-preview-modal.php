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
	<div class="app-notice app-notice--banner app-notice---attention">
		<div class="app-notice__tag">
			<span class="app-notice__icon"></span>
		</div>
		<div class="app-notice__content">
			<p><b><?=lang('preview')?></b> <span data-unpublished>(<?=lang('unpublished')?>)</span><span class="hidden" data-loading>(<?=lang('loading')?>)</span></p>
		</div>
		<div class="app-notice__controls">
			<a href="#" class="txt-rsp-lrg js-preview-wide align-block-right" data-close="<?=lang('continue_editing')?>" data-open="<?=lang('view_wider')?>"><?=lang('view_wider')?></a>
		</div>
	</div>
	<iframe src="" data-url="<?=$preview_url?>" class="live-preview__frame"></iframe>
</div>
