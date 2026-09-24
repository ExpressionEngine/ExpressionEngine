<div class="js-file-grid">
	<div class="file-grid-view-controls">
		<div class="button-group button-group-small file-grid-view-controls__toggle" role="group" aria-label="File Grid View">
			<button type="button" class="button button--small button--secondary is-active js-file-grid-view-toggle" data-file-grid-view="table">Table</button>
			<button type="button" class="button button--small button--default js-file-grid-view-toggle" data-file-grid-view="gallery">Gallery</button>
		</div>
	</div>

	<?=$grid_markup?>

	<div class="file-grid-gallery hidden js-file-grid-gallery">
		<div class="file-grid-gallery__empty hidden js-file-grid-gallery-empty"></div>
		<div class="file-grid-gallery__grid js-file-grid-gallery-grid"></div>
	</div>

	<?php
    $component = [
        'allowedDirectory' => $allowed_directory,
        'contentType' => $content_type,
        'maxRows' => $grid_max_rows,
        'allowMultipleFiles' => true,
    ];
    ?>

	<div data-file-grid-react="<?=base64_encode(json_encode($component))?>">
		<div class="fields-select">
			<div class="field-inputs">
				<label class="field-loading">
					<?=lang('loading')?><span></span>
				</label>
			</div>
		</div>
	</div>
</div>
