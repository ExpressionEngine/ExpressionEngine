<style type="text/css">
/* Hack for capybara-webkit, leave in place for now */
.fields-grid-tools a {
	display: inline-block;
	min-width: 1px;
}
</style>

<div class="fields-grid-setup" data-group="<?=$group?>">
	<?=$this->embed('ee:_shared/form/no_results', [
		'text' => sprintf(lang('no_found'), lang('columns')),
		'link_href' => '#',
		'link_text' => lang('add_new')
	])?>
	<?php foreach ($columns as $column): ?>
		<?=$column?>
	<?php endforeach ?>
</div>

<div class="<?=$group?>-col-settings-elements" data-group="always-hidden" class="hidden">
	<?=$blank_col?>

	<?php foreach ($settings_forms as $form): ?>
		<?=$form?>
	<?php endforeach ?>
</div>
