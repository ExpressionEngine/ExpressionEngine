<div class="js-grid-images">
	<?=$grid_markup?>

	<?php
	$component = [
		'allowedDirectory' => $allowed_directory
	];
	?>

	<div data-grid-images-react="<?=base64_encode(json_encode($component))?>">
		<div class="fields-select">
			<div class="field-inputs">
				<label class="field-loading">
					<?=lang('loading')?><span></span>
				</label>
			</div>
		</div>
	</div>
</div>
