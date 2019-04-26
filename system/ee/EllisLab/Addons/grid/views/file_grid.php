<div class="js-file-grid">
	<?=$grid_markup?>

	<?php
	$component = [
		'allowedDirectory' => $allowed_directory,
		'contentType' => $content_type,
		'maxRows' => $grid_max_rows,
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
