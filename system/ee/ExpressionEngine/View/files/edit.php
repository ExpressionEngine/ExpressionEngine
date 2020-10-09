
<div class="file-preview-modal">
	<div class="file-preview-modal__preview">
		<div class="title-bar">

			<div class="title-bar__extra-tools">
				<div class="button-group">
					<a class="button button--large filter-bar__button" href="<?=$download_url?>" title="<?=lang('download')?>"><i class="fas fa-download"></i></a>
					<a class="button button--large filter-bar__button" href="<?=$file->getAbsoluteURL()?>" rel="external"  title="<?=lang('open')?>"><i class="fas fa-link"></i></a>
				</div>
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
					echo "{$image_info['width']} x {$image_info['height']} " . lang('pixels') . ' - ';
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
