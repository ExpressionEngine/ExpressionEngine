<div class="js-grid-images">
	<?=$grid_markup?>

	<?php
	$component = [
		'lang' => $lang
	];
	?>

	<div data-grid-images-react="<?=base64_encode(json_encode($component))?>"></div>
</div>
