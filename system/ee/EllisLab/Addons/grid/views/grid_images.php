<div class="js-grid-images">
	<?=$grid_markup?>

	<?php
	$component = [
		'lang'               => $lang,
		'allowedDirectory'   => $allowed_directory,
		'uploadDestinations' => ee('View/Helpers')->normalizedChoices($upload_destinations)
	];
	?>

	<div data-grid-images-react="<?=base64_encode(json_encode($component))?>"></div>
</div>
