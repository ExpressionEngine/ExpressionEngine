
<div class="file-preview-modal">
	<div class="file-preview-modal__preview">
		<div class="title-bar">
			<h3 class="title-bar__title"><?= lang('preview'); ?></h3>

			<div class="title-bar__extra-tools">
				<a href="<?=$download_url?>" class="button button--clear" title="<?=lang('download')?>"><i class="fas fa-lg fa-download"></i></a>
				<a href="<?=$file->getAbsoluteURL()?>" rel="external" class="button button--clear" title="<?=lang('open')?>"><i class="fas fa-lg fa-external-link-alt"></i></a>
			</div>
		</div>

		<div class="file-preview-modal__preview-file">
			<?php if ($is_image) {
				echo "<img src=\"{$file->getAbsoluteURL()}\">";
				} else {
					echo "<div class=\"file-preview-modal__preview-file-name\">{$file->file_name}</div>";
				}
			?>

			<div class="file-preview-modal__preview-file-meta">
				<?php
				if ($is_image) {
					echo "{$image_info['width']} x {$image_info['height']} " . lang('pixels') . '.';
				}
				?><i><?= $size ?></i>
			</div>
		</div>
	</div>
	<div class="file-preview-modal__form">

	<?php
	$this->embed('_shared/form');

	$modal = ee('View')->make('ee:_shared/modal-form')->render([
		'name' => 'modal-form',
		'contents' => ''
	]);
	ee('CP/Modal')->addModal('modal-form', $modal);
?>


	</div>
</div>
