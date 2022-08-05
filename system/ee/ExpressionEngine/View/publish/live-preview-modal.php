<div class="live-preview-container">
	<div class="live-preview live-preview--closed">
		<div class="live-preview__form">
			<div class="live-preview__form-header">
				<?php if (!isset($hide_closer) || !$hide_closer): ?>
				<h1><a href class="js-close-live-preview"><i class="fal fa-times"></i> <?=lang('close_preview')?></a></h1>
				<?php elseif (ee()->input->get('hide_closer') === 'y' && ee()->input->get('return', true) != '') : ?>
				<h1><a href="<?=urldecode(ee()->input->get('return', true))?>"><i class="fal fa-times"></i> <?=lang('close')?></a></h1>
				<?php endif; ?>
				<div class="button-group">
					<button href="" class="button button--primary js-live-preview-save-button"><?=lang('save')?></button>
				</div>
			</div>

			<div class="live-preview__form-content">

			</div>
		</div>
		<div class="live-preview__divider"></div>
		<div class="live-preview__preview">
			<div class="live-preview__preview-loader">
				<!-- <span><?=lang('refreshing')?></span> -->
				<span class="pulse-loader"></span>
			</div>

			<iframe src="" data-url="<?=$preview_url?>" class="live-preview__frame"></iframe>
		</div>
	</div>
</div>
