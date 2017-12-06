<style type="text/css">
/* Hack for capybara-webkit, leave in place for now */
.fields-grid-tools a {
	display: inline-block;
	min-width: 1px;
}
</style>

<div class="fields-grid-setup" data-group="grid">
	<?php foreach ($columns as $column): ?>
		<?=$column?>
	<?php endforeach ?>
</div>

<div id="grid_col_settings_elements" data-group="always-hidden" class="hidden">
	<?=$blank_col?>

	<?php foreach ($settings_forms as $form): ?>
		<?=$form?>
	<?php endforeach ?>
</div>
